import os
import uuid
from io import BytesIO
from pathlib import Path

from fastapi import HTTPException, UploadFile, status

from app.core.config import settings
from PIL import Image, ImageOps

try:
    from pillow_heif import register_heif_opener

    register_heif_opener()
except ImportError:
    pass

# Extensões comuns; outros formatos podem ser aceitos se o Pillow identificar como imagem.
IMAGE_EXTENSIONS = {
    "jpg",
    "jpeg",
    "jfif",
    "png",
    "webp",
    "gif",
    "bmp",
    "tif",
    "tiff",
    "ico",
    "heic",
    "heif",
    "avif",
}


def _get_extension(filename: str) -> str:
    _, ext = os.path.splitext(filename or "")
    return ext.lower().lstrip(".")


def _replace_extension(filename: str, new_ext: str) -> str:
    base, _ = os.path.splitext(filename or "")
    return (base or filename or "anexo") + "." + (new_ext or "jpg")


def _is_pdf_bytes(content: bytes) -> bool:
    return len(content) >= 4 and content[:4] == b"%PDF"


def _try_identify_image(content: bytes) -> bool:
    """Se o Pillow abre como imagem (e não é PDF), aceita como upload de imagem."""
    if not content or _is_pdf_bytes(content):
        return False
    try:
        with Image.open(BytesIO(content)) as im:
            im.load()
        return True
    except Exception:
        return False


def _classify_upload(file: UploadFile, content: bytes) -> str:
    """Retorna 'pdf', 'image' ou 'unsupported'."""
    ext = _get_extension(file.filename or "")
    mime = (file.content_type or "").strip().lower()

    if ext == "pdf" or mime == "application/pdf":
        return "pdf"
    if _is_pdf_bytes(content):
        return "pdf"

    if mime.startswith("image/"):
        return "image"
    if ext in IMAGE_EXTENSIONS:
        return "image"
    if mime == "application/octet-stream" and _try_identify_image(content):
        return "image"
    if ext in settings.allowed_upload_extensions and ext != "pdf" and _try_identify_image(content):
        return "image"
    if _try_identify_image(content):
        return "image"

    return "unsupported"


def validate_upload(file: UploadFile, content: bytes) -> None:
    """Valida tamanho antes do processamento: PDF limitado ao tamanho final; imagem ao ingresso."""
    size_bytes = len(content)
    kind = _classify_upload(file, content)

    if kind == "unsupported":
        ext = _get_extension(file.filename or "")
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Formato nao suportado. Envie PDF ou imagem (JPEG, PNG, WEBP, GIF, HEIC, etc.).",
        )

    if kind == "pdf":
        if not _is_pdf_bytes(content):
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Arquivo PDF invalido.",
            )
        if size_bytes > settings.max_upload_size_bytes:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"PDF excede limite de {settings.max_upload_size_bytes} bytes",
            )
        mime = (file.content_type or "").strip().lower()
        if mime and mime != "application/octet-stream" and not mime.startswith("application/pdf"):
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail=f"MIME type nao permitido para PDF: {mime}",
            )
        return

    # imagem
    if size_bytes > settings.max_ingress_image_bytes:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail=(
                f"Imagem muito grande ({size_bytes} bytes). "
                f"Limite de envio {settings.max_ingress_image_bytes} bytes antes da compressao."
            ),
        )


def _process_image_to_jpeg(content: bytes) -> tuple[bytes, str, str]:
    """
    Converte imagem para JPEG, corrigindo EXIF e redimensionando para reduzir tamanho no servidor.
    """
    img = Image.open(BytesIO(content))
    # GIF animado / multipágina TIFF: primeiro quadro
    if getattr(img, "n_frames", 1) > 1:
        img.seek(0)
    img = ImageOps.exif_transpose(img)

    # Converte para RGB. Se houver alpha, coloca fundo branco.
    if img.mode not in ("RGB", "L"):
        img = img.convert("RGBA")
        bg = Image.new("RGBA", img.size, (255, 255, 255, 255))
        bg.paste(img, (0, 0), img.split()[3])
        img = bg.convert("RGB")
    elif img.mode == "L":
        img = img.convert("RGB")
    else:
        img = img.convert("RGB")

    max_dim = max(1, int(settings.image_max_dimension_px))
    if img.width > max_dim or img.height > max_dim:
        img.thumbnail((max_dim, max_dim), Image.Resampling.LANCZOS)

    quality = max(1, int(settings.image_jpeg_quality))
    quality_min = max(1, int(settings.image_jpeg_quality_min))
    step = max(1, int(settings.image_jpeg_quality_step))

    while True:
        buf = BytesIO()
        img.save(
            buf,
            format="JPEG",
            quality=quality,
            optimize=True,
            progressive=True,
        )
        out = buf.getvalue()
        if len(out) <= settings.max_upload_size_bytes or quality <= quality_min:
            return out, "image/jpeg", "jpg"
        quality -= step


def persist_upload(file: UploadFile, content: bytes, work_order_id: str) -> tuple[str, str, int, str]:
    original_filename = file.filename or "anexo"
    original_ext = _get_extension(original_filename)
    original_mime = (file.content_type or "").strip()

    is_pdf = _is_pdf_bytes(content)

    should_process_as_image = False
    if not is_pdf:
        should_process_as_image = (
            original_mime.startswith("image/")
            or original_ext in IMAGE_EXTENSIONS
            or original_mime == "application/octet-stream"
            or _try_identify_image(content)
        )

    stored_content = content
    stored_mime = original_mime or "application/octet-stream"
    stored_ext = original_ext or "bin"
    stored_filename = original_filename

    if is_pdf:
        stored_mime = "application/pdf"
        stored_ext = "pdf"
        if len(stored_content) > settings.max_upload_size_bytes:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="PDF excede limite apos validacao.",
            )
    elif should_process_as_image:
        try:
            stored_content, stored_mime, stored_ext = _process_image_to_jpeg(content)
            stored_filename = _replace_extension(original_filename, stored_ext)
        except Exception:
            if len(content) > settings.max_upload_size_bytes:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail="Nao foi possivel processar a imagem. Tente outro formato ou arquivo menor.",
                ) from None
            stored_content = content
            stored_mime = original_mime or "image/jpeg"
            stored_ext = original_ext or "jpg"
            stored_filename = original_filename
    else:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Tipo de arquivo nao suportado.",
        )

    safe_name = f"{uuid.uuid4()}_{work_order_id}.{stored_ext}"
    base_dir = Path(settings.upload_dir) / "os_anexos" / work_order_id
    base_dir.mkdir(parents=True, exist_ok=True)
    file_path = base_dir / safe_name
    file_path.write_bytes(stored_content)
    return str(file_path), stored_mime, len(stored_content), stored_filename
