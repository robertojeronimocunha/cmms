from fastapi import APIRouter, Depends, HTTPException

from app.auth.dependencies import require_roles
from app.core.config import settings
from app.models.user import User
from app.schemas.backup_admin import (
    BackupFileOut,
    BackupRunOut,
    DbRestoreBody,
    SystemRestoreBody,
)
from app.services.backup_admin import (
    delete_db_backup,
    delete_system_backup,
    list_db_backups,
    list_system_backups,
    restore_db_backup,
    restore_system_backup,
    run_db_backup,
    run_system_backup,
)

router = APIRouter(prefix="/admin/backup", tags=["admin-backup"])
_admin = require_roles("ADMIN")


@router.get("/db/files", response_model=list[BackupFileOut])
def list_db_files(_: User = Depends(_admin)):
    return list_db_backups(settings)


@router.post("/db/run", response_model=BackupRunOut)
def run_db(_: User = Depends(_admin)):
    return run_db_backup(settings)


@router.delete("/db/file/{filename}", status_code=204)
def delete_db_file(filename: str, _: User = Depends(_admin)):
    delete_db_backup(settings, filename)


@router.post("/db/restore", response_model=BackupRunOut)
def restore_db(body: DbRestoreBody, _: User = Depends(_admin)):
    if not body.confirm:
        raise HTTPException(
            status_code=400,
            detail="Envie confirm: true para restaurar o banco a partir do arquivo indicado.",
        )
    return restore_db_backup(settings, body.filename)


@router.get("/system/files", response_model=list[BackupFileOut])
def list_system_files(_: User = Depends(_admin)):
    return list_system_backups(settings)


@router.post("/system/run", response_model=BackupRunOut)
def run_system(_: User = Depends(_admin)):
    return run_system_backup(settings)


@router.delete("/system/file/{filename}", status_code=204)
def delete_system_file(filename: str, _: User = Depends(_admin)):
    delete_system_backup(settings, filename)


@router.post("/system/restore", response_model=BackupRunOut)
def restore_system(body: SystemRestoreBody, _: User = Depends(_admin)):
    return restore_system_backup(settings, body.filename, body.confirm_phrase)
