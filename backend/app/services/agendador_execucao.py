"""Execução de tarefas do agendador (backup completo, preventivas, vendor frontend)."""

from __future__ import annotations

import os
import shutil
import subprocess
from datetime import datetime, timedelta, timezone
from pathlib import Path

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.core.config import settings
from app.models.agendador_tarefa import AgendadorTarefa
from app.models.user import User
from app.services.preventiva_execucao import executar_preventivas_vencidas_para_usuario

CHAVE_BACKUP_COMPLETO = "backup_completo"
CHAVE_PREVENTIVAS_VENCIDAS = "preventivas_vencidas"
CHAVE_VENDOR_FRONTEND = "vendor_frontend"
CHAVES_VALIDAS = frozenset(
    {CHAVE_BACKUP_COMPLETO, CHAVE_PREVENTIVAS_VENCIDAS, CHAVE_VENDOR_FRONTEND}
)

# Perfis que podem ser solicitante nas OS criadas pelo agendador (alinhado a quem executa preventiva na API).
PERFIS_SOLICITANTE_PREVENTIVA_AGENDA = frozenset({"ADMIN", "TECNICO", "LUBRIFICADOR", "DIRETORIA"})


def _run_backup_completo() -> tuple[bool, str]:
    script = Path(settings.repo_root) / "scripts" / "cmms_backup_scheduled.sh"
    if not script.is_file():
        return False, "Script cmms_backup_scheduled.sh não encontrado."
    try:
        if os.geteuid() == 0:
            r = subprocess.run(
                [str(script)],
                capture_output=True,
                text=True,
                timeout=7200,
            )
        else:
            sudo = shutil.which("sudo")
            if not sudo:
                return False, "É necessário root ou sudo para o backup completo."
            r = subprocess.run(
                [sudo, "-n", str(script)],
                capture_output=True,
                text=True,
                timeout=7200,
            )
        tail = (r.stdout + "\n" + r.stderr).strip()[-6000:]
        if r.returncode != 0:
            return False, tail or "backup agendado retornou código de erro"
        return True, (tail[-2500:] if tail else "Concluído.")
    except subprocess.TimeoutExpired:
        return False, "Tempo limite excedido no backup completo."
    except OSError as exc:
        return False, str(exc)[:4000]


def _run_update_frontend_vendor() -> tuple[bool, str]:
    script = Path(settings.repo_root) / "scripts" / "update-frontend-vendor.sh"
    if not script.is_file():
        return False, "Script update-frontend-vendor.sh não encontrado."
    try:
        base_kw: dict = {
            "capture_output": True,
            "text": True,
            "cwd": str(settings.repo_root),
            "timeout": 3600,
        }
        r = subprocess.run(["bash", str(script)], **base_kw)
        tail = (r.stdout + "\n" + r.stderr).strip()[-6000:]
        if r.returncode != 0 and os.geteuid() != 0:
            sudo = shutil.which("sudo")
            err_blob = tail.lower()
            perm_fail = (
                "curl: (23)" in tail
                or "failure writing output" in err_blob
                or "permission denied" in err_blob
            )
            if sudo and perm_fail:
                r = subprocess.run([sudo, "-n", "bash", str(script)], **base_kw)
                tail = (r.stdout + "\n" + r.stderr).strip()[-6000:]
        if r.returncode != 0:
            hint = ""
            if "curl: (23)" in tail or "Failure writing output" in tail:
                hint = (
                    " Dica: ficheiros em assets/vendor podem pertencer a root; "
                    "adicione NOPASSWD para update-frontend-vendor.sh (ver deploy/sudoers-cmms-backup-ui.example) ou: "
                    f"sudo chown -R www-data:www-data {settings.repo_root}/frontend/public/assets/vendor"
                )
            return False, (tail or "update-frontend-vendor.sh retornou código de erro") + hint
        return True, (tail[-2500:] if tail else "Concluído.")
    except subprocess.TimeoutExpired:
        return False, "Tempo limite excedido ao atualizar dependências do frontend."
    except OSError as exc:
        return False, str(exc)[:4000]


def _primeiro_admin_ativo(db: Session) -> User | None:
    uid = db.scalar(
        select(User.id)
        .where(User.ativo.is_(True), User.perfil_acesso == "ADMIN")
        .order_by(User.created_at.asc())
        .limit(1)
    )
    if uid is None:
        return None
    return db.get(User, uid)


def _scheduler_preventivas_user(db: Session, job: AgendadorTarefa) -> User | None:
    if job.solicitante_usuario_id:
        u = db.get(User, job.solicitante_usuario_id)
        if (
            u
            and u.ativo
            and u.perfil_acesso in PERFIS_SOLICITANTE_PREVENTIVA_AGENDA
        ):
            return u
    return _primeiro_admin_ativo(db)


def _run_preventivas_vencidas(db: Session, job: AgendadorTarefa) -> tuple[bool, str]:
    user = _scheduler_preventivas_user(db, job)
    if not user:
        return (
            False,
            "Nenhum solicitante válido: escolha um utilizador no cartão Preventivas ou mantenha um ADMIN ativo.",
        )
    r = executar_preventivas_vencidas_para_usuario(db, user)
    partes = [f"OS criadas: {r['criadas']}"]
    if r["erros"]:
        errs = r["erros"][:25]
        partes.append("Erros: " + "; ".join(errs))
        if len(r["erros"]) > 25:
            partes.append(f"(+{len(r['erros']) - 25} erros)")
    ok = len(r["erros"]) == 0
    return ok, " | ".join(partes)


def _payload_execucao(db: Session, job: AgendadorTarefa) -> tuple[bool, str]:
    if job.chave == CHAVE_BACKUP_COMPLETO:
        return _run_backup_completo()
    if job.chave == CHAVE_PREVENTIVAS_VENCIDAS:
        return _run_preventivas_vencidas(db, job)
    if job.chave == CHAVE_VENDOR_FRONTEND:
        return _run_update_frontend_vendor()
    return False, f"Tarefa desconhecida: {job.chave}"


def _finalizar_tarefa(db: Session, job_id, ok: bool, msg: str) -> None:
    finished = datetime.now(timezone.utc)
    j = db.get(AgendadorTarefa, job_id)
    if not j:
        return
    j.ultima_execucao_em = finished
    j.proxima_execucao_em = finished + timedelta(minutes=j.intervalo_minutos)
    j.ultimo_ok = ok
    j.ultimo_mensagem = msg[:4000] if msg else None
    db.add(j)
    db.commit()


def process_due_jobs(db: Session) -> list[str]:
    """Processa todas as tarefas ativas com proxima_execucao_em <= agora."""
    now = datetime.now(timezone.utc)
    jobs = db.scalars(
        select(AgendadorTarefa)
        .where(AgendadorTarefa.ativo.is_(True))
        .where(
            (AgendadorTarefa.proxima_execucao_em.is_(None)) | (AgendadorTarefa.proxima_execucao_em <= now)
        )
        .order_by(AgendadorTarefa.chave.asc())
    ).all()
    log_lines: list[str] = []
    for job in jobs:
        try:
            ok, msg = _payload_execucao(db, job)
        except Exception as exc:  # noqa: BLE001
            ok, msg = False, str(exc)[:4000]
        _finalizar_tarefa(db, job.id, ok, msg)
        log_lines.append(f"{job.chave}: {'OK' if ok else 'ERRO'} — {msg[:180]}")
    return log_lines


def forcar_execucao(db: Session, chave: str) -> tuple[bool, str]:
    if chave not in CHAVES_VALIDAS:
        raise ValueError("chave_invalida")
    job = db.scalars(select(AgendadorTarefa).where(AgendadorTarefa.chave == chave)).one_or_none()
    if not job:
        raise ValueError("nao_encontrado")
    if not job.ativo:
        raise ValueError("inativo")
    try:
        ok, msg = _payload_execucao(db, job)
    except Exception as exc:  # noqa: BLE001
        ok, msg = False, str(exc)[:4000]
    _finalizar_tarefa(db, job.id, ok, msg)
    return ok, msg
