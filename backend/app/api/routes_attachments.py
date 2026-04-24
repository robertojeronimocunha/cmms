from datetime import datetime, timezone
from uuid import UUID

from fastapi import APIRouter, Depends, File, HTTPException, Query, Request, UploadFile, status
from fastapi.responses import FileResponse
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user, require_abrir_os
from app.core.database import get_db
from app.models.attachment import WorkOrderAttachment
from app.models.user import User
from app.models.work_order_log import WorkOrderLog
from app.models.work_order import WorkOrder
from app.schemas.attachment import AttachmentResponse
from app.services.attachment_service import persist_upload, validate_upload

router = APIRouter(prefix="/ordens-servico", tags=["os-anexos"])


@router.post("/{work_order_id}/anexos", response_model=AttachmentResponse, status_code=status.HTTP_201_CREATED)
async def upload_attachment(
    work_order_id: UUID,
    file: UploadFile = File(...),
    os_apontamento_id: UUID | None = Query(default=None),
    user: User = Depends(require_abrir_os()),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")

    if os_apontamento_id is not None:
        log = db.get(WorkOrderLog, os_apontamento_id)
        if not log or log.ordem_servico_id != work_order_id:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Apontamento nao encontrado para esta OS")

    content = await file.read()
    validate_upload(file, content)
    path, stored_mime, stored_size_bytes, stored_filename = persist_upload(file, content, str(work_order_id))

    attachment = WorkOrderAttachment(
        ordem_servico_id=work_order_id,
        os_apontamento_id=os_apontamento_id,
        usuario_id=user.id,
        nome_arquivo=stored_filename,
        caminho_arquivo=path,
        mime_type=stored_mime,
        tamanho_bytes=stored_size_bytes,
    )
    db.add(attachment)
    db.commit()
    db.refresh(attachment)
    return attachment


@router.get("/{work_order_id}/anexos", response_model=list[AttachmentResponse])
def list_attachments(
    work_order_id: UUID,
    incluir_removidos: bool = Query(default=False, description="Somente gestao pode listar removidos"),
    os_apontamento_id: UUID | None = Query(default=None),
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")

    if incluir_removidos and user.perfil_acesso != "ADMIN":
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Somente perfis de gestao podem incluir anexos removidos",
        )

    conditions = [WorkOrderAttachment.ordem_servico_id == work_order_id]
    if os_apontamento_id is not None:
        conditions.append(WorkOrderAttachment.os_apontamento_id == os_apontamento_id)
    if not incluir_removidos:
        conditions.append(WorkOrderAttachment.deleted_at.is_(None))

    query = select(WorkOrderAttachment).where(*conditions).order_by(WorkOrderAttachment.created_at.desc())
    return list(db.execute(query).scalars().all())


@router.get("/anexos/{attachment_id}/download")
def download_attachment(
    attachment_id: UUID,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    attachment = db.get(WorkOrderAttachment, attachment_id)
    if not attachment or attachment.deleted_at is not None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Anexo nao encontrado")

    work_order = db.get(WorkOrder, attachment.ordem_servico_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS do anexo nao encontrada")

    file_path = attachment.caminho_arquivo
    try:
        with open(file_path, "rb"):
            pass
    except FileNotFoundError as exc:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Arquivo fisico nao encontrado") from exc

    return FileResponse(
        path=file_path,
        media_type=attachment.mime_type,
        filename=attachment.nome_arquivo,
    )


@router.delete("/anexos/{attachment_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_attachment(
    attachment_id: UUID,
    _user: User = Depends(require_abrir_os()),
    db: Session = Depends(get_db),
):
    attachment = db.get(WorkOrderAttachment, attachment_id)
    if not attachment or attachment.deleted_at is not None:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Anexo nao encontrado")

    work_order = db.get(WorkOrder, attachment.ordem_servico_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS do anexo nao encontrada")

    attachment.deleted_at = datetime.now(timezone.utc)
    db.add(attachment)
    db.commit()
    return None
