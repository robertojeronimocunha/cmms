"""Operações de backup/restauração restritas a ADMIN (chamadas via rotas)."""

from __future__ import annotations

import os
import re
import shutil
import subprocess
from datetime import datetime, timezone
from pathlib import Path

from fastapi import HTTPException

from app.core.config import Settings

DB_BACKUP_NAME_RE = re.compile(r"^cmms_\d{8}_\d{6}\.sql\.gz$")
# Script usa %H-%M-%S; cópias antigas podem ter só %H-%M.
SYSTEM_BACKUP_NAME_RE = re.compile(
    r"^CMMS_BACKUP_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}(?:-\d{2})?\.tar$"
)

SYSTEM_RESTORE_CONFIRM = "RESTAURAR_SISTEMA"


def _libpq_url(database_url: str) -> str:
    return database_url.replace("postgresql+psycopg2://", "postgresql://", 1)


def _safe_resolved_file(parent: Path, name: str, pattern: re.Pattern[str]) -> Path:
    if not pattern.match(name):
        raise HTTPException(status_code=400, detail="Nome de arquivo inválido")
    base = parent.resolve()
    try:
        target = (base / name).resolve()
    except OSError as exc:
        raise HTTPException(status_code=400, detail="Caminho inválido") from exc
    try:
        target.relative_to(base)
    except ValueError as exc:
        raise HTTPException(status_code=400, detail="Caminho fora do diretório de backup") from exc
    return target


def list_db_backups(settings: Settings) -> list[dict]:
    d = Path(settings.backup_db_dir)
    if not d.is_dir():
        return []
    out: list[dict] = []
    for p in sorted(d.iterdir(), key=lambda x: x.stat().st_mtime, reverse=True):
        if p.is_file() and DB_BACKUP_NAME_RE.match(p.name):
            st = p.stat()
            out.append(
                {
                    "name": p.name,
                    "size_bytes": st.st_size,
                    "modified_at": datetime.fromtimestamp(st.st_mtime, tz=timezone.utc).isoformat(),
                }
            )
    return out


def list_system_backups(settings: Settings) -> list[dict]:
    d = Path(settings.backup_system_dir)
    if not d.is_dir():
        return []
    out: list[dict] = []
    for p in sorted(d.iterdir(), key=lambda x: x.stat().st_mtime, reverse=True):
        if p.is_file() and SYSTEM_BACKUP_NAME_RE.match(p.name):
            st = p.stat()
            out.append(
                {
                    "name": p.name,
                    "size_bytes": st.st_size,
                    "modified_at": datetime.fromtimestamp(st.st_mtime, tz=timezone.utc).isoformat(),
                }
            )
    return out


def run_db_backup(settings: Settings) -> dict:
    pg_dump = shutil.which("pg_dump")
    gzip_bin = shutil.which("gzip")
    if not pg_dump or not gzip_bin:
        raise HTTPException(
            status_code=503,
            detail="pg_dump ou gzip não encontrados no PATH do servidor.",
        )
    backup_dir = Path(settings.backup_db_dir)
    try:
        backup_dir.mkdir(parents=True, exist_ok=True)
    except OSError as exc:
        raise HTTPException(
            status_code=500,
            detail=f"Não foi possível criar o diretório de backup: {exc}",
        ) from exc

    stamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    out_path = backup_dir / f"cmms_{stamp}.sql.gz"
    dump_url = _libpq_url(settings.database_url)

    p_dump: subprocess.Popen | None = None
    p_gz: subprocess.Popen | None = None
    try:
        with open(out_path, "wb") as out_f:
            p_dump = subprocess.Popen(
                [pg_dump, dump_url],
                stdout=subprocess.PIPE,
                stderr=subprocess.PIPE,
            )
            p_gz = subprocess.Popen(
                [gzip_bin, "-9"],
                stdin=p_dump.stdout,
                stdout=out_f,
                stderr=subprocess.PIPE,
            )
            if p_dump.stdout:
                p_dump.stdout.close()
            try:
                _, gz_err = p_gz.communicate(timeout=7200)
            except subprocess.TimeoutExpired:
                if p_gz:
                    p_gz.kill()
                if p_dump:
                    p_dump.kill()
                raise
            dump_err = b""
            if p_dump.stderr:
                dump_err = p_dump.stderr.read() or b""
            rc_dump = p_dump.wait(timeout=120)
            rc_gz = p_gz.returncode if p_gz.returncode is not None else -1
            if rc_dump != 0 or rc_gz != 0:
                try:
                    out_path.unlink(missing_ok=True)
                except OSError:
                    pass
                msg = (dump_err.decode("utf-8", errors="replace") + (gz_err or b"").decode(
                    "utf-8", errors="replace"
                )).strip()[-4000:]
                raise HTTPException(
                    status_code=502,
                    detail=f"Falha no pg_dump/gzip: {msg or 'sem detalhe'}",
                )
    except subprocess.TimeoutExpired:
        try:
            out_path.unlink(missing_ok=True)
        except OSError:
            pass
        raise HTTPException(status_code=504, detail="Tempo limite excedido ao gerar backup do banco.") from None
    except HTTPException:
        raise
    except OSError as exc:
        try:
            out_path.unlink(missing_ok=True)
        except OSError:
            pass
        raise HTTPException(status_code=500, detail=f"Erro ao gravar backup: {exc}") from exc

    return {"ok": True, "message": "Backup do banco criado.", "filename": out_path.name}


def delete_db_backup(settings: Settings, filename: str) -> None:
    path = _safe_resolved_file(Path(settings.backup_db_dir), filename, DB_BACKUP_NAME_RE)
    if not path.is_file():
        raise HTTPException(status_code=404, detail="Arquivo não encontrado")
    try:
        path.unlink()
        return
    except PermissionError:
        pass
    except OSError as exc:
        raise HTTPException(status_code=500, detail=f"Não foi possível apagar: {exc}") from exc

    helper = Path(settings.repo_root) / "scripts" / "cmms_delete_db_backup.sh"
    if not helper.is_file():
        raise HTTPException(
            status_code=503,
            detail="Sem permissão para apagar (dono root?). Instale scripts/cmms_delete_db_backup.sh e o sudoers.",
        )
    sudo = shutil.which("sudo")
    if not sudo:
        raise HTTPException(
            status_code=403,
            detail="Sem permissão para apagar este backup de banco no servidor.",
        )
    bd = str(Path(settings.backup_db_dir).resolve())
    run_env = os.environ.copy()
    run_env["CMMS_BACKUP_DB_DELETE_DIR"] = bd
    r = subprocess.run(
        [sudo, "-n", str(helper), filename],
        env=run_env,
        capture_output=True,
        text=True,
    )
    if r.returncode != 0:
        msg = (r.stderr or r.stdout or "").strip()[-2000:]
        raise HTTPException(
            status_code=502,
            detail=msg or "Falha ao apagar (sudo). Adicione cmms_delete_db_backup.sh ao sudoers.",
        )


def restore_db_backup(settings: Settings, filename: str) -> dict:
    path = _safe_resolved_file(Path(settings.backup_db_dir), filename, DB_BACKUP_NAME_RE)
    if not path.is_file():
        raise HTTPException(status_code=404, detail="Arquivo não encontrado")
    psql = shutil.which("psql")
    gunzip = shutil.which("gunzip")
    if not psql or not gunzip:
        raise HTTPException(status_code=503, detail="psql ou gunzip não encontrados no PATH.")
    dump_url = _libpq_url(settings.database_url)
    try:
        gz = subprocess.Popen(
            [gunzip, "-c", str(path)],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
        )
        if not gz.stdout:
            gz.kill()
            raise HTTPException(status_code=500, detail="Falha ao iniciar descompactação")
        try:
            r = subprocess.run(
                [psql, dump_url, "-v", "ON_ERROR_STOP=1"],
                stdin=gz.stdout,
                capture_output=True,
                text=True,
                timeout=7200,
            )
        finally:
            if gz.stdout:
                gz.stdout.close()
            gz.wait(timeout=120)
        if r.returncode != 0:
            err = (r.stderr or r.stdout or "").strip()[-4000:]
            raise HTTPException(status_code=502, detail=f"Falha na restauração: {err or 'sem detalhe'}")
    except subprocess.TimeoutExpired:
        raise HTTPException(status_code=504, detail="Tempo limite excedido na restauração do banco.") from None

    return {
        "ok": True,
        "message": "Restauração do banco concluída. Reinicie cmms-api e serviços web se necessário.",
        "filename": filename,
    }


def run_system_backup(settings: Settings) -> dict:
    script = Path(settings.backup_system_script)
    if not script.is_file():
        raise HTTPException(status_code=500, detail="Script backup_sistema.sh não encontrado")
    backup_dir = Path(settings.backup_system_dir)
    try:
        backup_dir.mkdir(parents=True, exist_ok=True)
    except OSError:
        # Ex.: /backup só root cria; o script com sudo faz mkdir -p no destino.
        pass

    bd = str(backup_dir.resolve())
    wd = str(Path(settings.repo_root).resolve())
    run_env = os.environ.copy()
    run_env["RAIZ_BACKUP"] = bd
    run_env["WEB_DIR"] = wd

    sudo = shutil.which("sudo")
    cmd: list[str] = [str(script)]
    if sudo:
        cmd = [sudo, "-n", str(script)]

    try:
        r = subprocess.run(
            cmd,
            env=run_env,
            capture_output=True,
            text=True,
            timeout=7200,
        )
    except subprocess.TimeoutExpired:
        raise HTTPException(status_code=504, detail="Tempo limite excedido no backup de sistema.") from None
    except FileNotFoundError as exc:
        raise HTTPException(status_code=503, detail="Não foi possível executar o script de backup.") from exc

    if r.returncode != 0:
        tail = (r.stderr or r.stdout or "").strip()
        tail = tail[-4500:] if tail else ""
        hint = (
            " Backup de sistema exige execução como root. "
            "No serviço cmms-api, pode ser necessário NoNewPrivileges=false e sudoers sem senha para o script; "
            "veja deploy/sudoers-cmms-backup-ui.example."
        )
        raise HTTPException(status_code=502, detail=(tail or "Falha no script de backup.") + hint)

    # Descobrir o .tar mais recente que casa com o padrão
    names = list_system_backups(settings)
    latest = names[0]["name"] if names else None
    return {
        "ok": True,
        "message": "Backup de sistema concluído.",
        "filename": latest,
    }


def delete_system_backup(settings: Settings, filename: str) -> None:
    path = _safe_resolved_file(Path(settings.backup_system_dir), filename, SYSTEM_BACKUP_NAME_RE)
    if not path.is_file():
        raise HTTPException(status_code=404, detail="Arquivo não encontrado")
    try:
        path.unlink()
        return
    except PermissionError:
        pass
    except OSError as exc:
        raise HTTPException(status_code=500, detail=f"Não foi possível apagar: {exc}") from exc

    helper = Path(settings.repo_root) / "scripts" / "cmms_delete_system_backup.sh"
    if not helper.is_file():
        raise HTTPException(
            status_code=503,
            detail="Arquivo pertence a outro usuário (ex.: root). Instale scripts/cmms_delete_system_backup.sh e o sudoers indicado em deploy/.",
        )
    sudo = shutil.which("sudo")
    if not sudo:
        raise HTTPException(
            status_code=403,
            detail="Sem permissão para apagar este backup. No servidor, use sudo ou chown para www-data.",
        )
    bd = str(Path(settings.backup_system_dir).resolve())
    run_env = os.environ.copy()
    run_env["RAIZ_BACKUP"] = bd
    r = subprocess.run(
        [sudo, "-n", str(helper), filename],
        env=run_env,
        capture_output=True,
        text=True,
    )
    if r.returncode != 0:
        msg = (r.stderr or r.stdout or "").strip()[-2000:]
        raise HTTPException(
            status_code=502,
            detail=msg or "Falha ao apagar (sudo). Adicione cmms_delete_system_backup.sh ao sudoers — ver deploy/sudoers-cmms-backup-ui.example.",
        )


def restore_system_backup(settings: Settings, filename: str, confirm_phrase: str) -> dict:
    if confirm_phrase.strip() != SYSTEM_RESTORE_CONFIRM:
        raise HTTPException(
            status_code=400,
            detail=f'Informe confirm_phrase exatamente "{SYSTEM_RESTORE_CONFIRM}" para restaurar o sistema.',
        )
    script = Path(settings.restore_system_script)
    if not script.is_file():
        raise HTTPException(status_code=500, detail="Script restore.sh não encontrado")
    path = _safe_resolved_file(Path(settings.backup_system_dir), filename, SYSTEM_BACKUP_NAME_RE)
    if not path.is_file():
        raise HTTPException(status_code=404, detail="Arquivo não encontrado")

    sudo = shutil.which("sudo")
    cmd: list[str]
    if sudo:
        cmd = [sudo, "-n", str(script), str(path)]
    else:
        cmd = [str(script), str(path)]

    try:
        r = subprocess.run(
            cmd,
            capture_output=True,
            text=True,
            timeout=7200,
        )
    except subprocess.TimeoutExpired:
        raise HTTPException(status_code=504, detail="Tempo limite excedido na restauração do sistema.") from None
    except FileNotFoundError as exc:
        raise HTTPException(status_code=503, detail="Não foi possível executar o script de restauração.") from exc

    if r.returncode != 0:
        tail = (r.stderr or r.stdout or "").strip()[-4500:]
        hint = (
            " Restauração de sistema exige root (sudo). "
            "Ajuste sudoers e, se aplicável, NoNewPrivileges no cmms-api — ver deploy/sudoers-cmms-backup-ui.example."
        )
        raise HTTPException(status_code=502, detail=(tail or "Falha na restauração.") + hint)

    return {
        "ok": True,
        "message": "Restauração do sistema concluída. Reinicie cmms-api, nginx e php-fpm.",
        "filename": filename,
    }
