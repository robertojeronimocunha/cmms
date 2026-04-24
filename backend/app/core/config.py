import os
from pathlib import Path

# Carrega backend/.env no processo (pytest, scripts, uvicorn sem systemd).
# override=True: o arquivo .env do projeto prevalece sobre DATABASE_URL etc.
# exportados no shell (evita ficar preso a um valor antigo tipo Cmms123/postgres).
_backend_dir = Path(__file__).resolve().parent.parent.parent
_env_file = _backend_dir / ".env"
try:
    from dotenv import load_dotenv

    if _env_file.is_file():
        load_dotenv(_env_file, override=True)
except ImportError:
    pass


class Settings:
    app_name: str = os.getenv("APP_NAME", "CMMS API")
    app_env: str = os.getenv("APP_ENV", "development")
    debug: bool = os.getenv("APP_DEBUG", "false").lower() == "true"
    secret_key: str = os.getenv("SECRET_KEY", "change-me")
    jwt_algorithm: str = os.getenv("JWT_ALGORITHM", "HS256")
    jwt_expire_minutes: int = int(os.getenv("JWT_EXPIRE_MINUTES", "480"))
    database_url: str = os.getenv(
        "DATABASE_URL",
        "postgresql+psycopg2://cmms_app:Cmms123@127.0.0.1:5432/cmms",
    )
    # Sempre use caminho absoluto por padrão (evita depender do WorkingDirectory do systemd).
    upload_dir: str = os.getenv("UPLOAD_DIR", str(_backend_dir / "uploads"))
    max_upload_size_bytes: int = int(os.getenv("MAX_UPLOAD_SIZE_BYTES", "10485760"))
    # Imagens brutas (ex.: foto do celular) podem ser grandes; após JPEG+thumbnail ficam abaixo de max_upload_size_bytes.
    max_ingress_image_bytes: int = int(os.getenv("MAX_INGRESS_IMAGE_BYTES", "52428800"))
    allowed_upload_extensions: set[str] = set(
        ext.strip().lower()
        for ext in os.getenv(
            "ALLOWED_UPLOAD_EXTENSIONS",
            "jpg,jpeg,jfif,png,pdf,webp,gif,bmp,tif,tiff,ico,heic,heif,avif",
        ).split(",")
        if ext.strip()
    )

    # Upload de imagens: redimensiona e comprime para reduzir tamanho no servidor.
    image_max_dimension_px: int = int(os.getenv("IMAGE_MAX_DIMENSION_PX", "1600"))
    image_jpeg_quality: int = int(os.getenv("IMAGE_JPEG_QUALITY", "82"))
    image_jpeg_quality_min: int = int(os.getenv("IMAGE_JPEG_QUALITY_MIN", "55"))
    image_jpeg_quality_step: int = int(os.getenv("IMAGE_JPEG_QUALITY_STEP", "5"))

    # Backups (UI admin): diretórios sob a raiz do projeto por padrão (gravável por www-data).
    _html_root = _backend_dir.parent
    repo_root: str = str(_html_root)
    backup_db_dir: str = os.getenv(
        "CMMS_BACKUP_DB_DIR",
        str(_html_root / "var" / "backups" / "db"),
    )
    # Alinhado ao padrão de scripts/backup_sistema.sh (RAIZ_BACKUP=/backup). A UI lista o mesmo sítio.
    backup_system_dir: str = os.getenv("CMMS_BACKUP_SYSTEM_DIR", "/backup")
    backup_system_script: str = os.getenv(
        "CMMS_BACKUP_SYSTEM_SCRIPT",
        str(_html_root / "scripts" / "backup_sistema.sh"),
    )
    restore_system_script: str = os.getenv(
        "CMMS_RESTORE_SYSTEM_SCRIPT",
        str(_html_root / "scripts" / "restore.sh"),
    )
    # Log do cron do agendador (UI admin): leitura/manutenção.
    agendador_log_path: str = os.getenv("CMMS_AGENDADOR_LOG", "/var/log/cmms-agendador.log")
    agendador_log_read_max_bytes: int = int(
        os.getenv("CMMS_AGENDADOR_LOG_READ_MAX_BYTES", str(768 * 1024)),
    )
    agendador_log_read_max_lines: int = int(
        os.getenv("CMMS_AGENDADOR_LOG_READ_MAX_LINES", "4000"),
    )
    agendador_log_inline_max_bytes: int = int(
        os.getenv("CMMS_AGENDADOR_LOG_INLINE_MAX_BYTES", str(25 * 1024 * 1024)),
    )


settings = Settings()
