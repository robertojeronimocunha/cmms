from datetime import datetime, timezone
from uuid import UUID
from zoneinfo import ZoneInfo

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user
from app.core.database import get_db
from app.models.asset import Asset
from app.models.user import User
from app.models.work_order import WorkOrder
from app.models.work_order_log import WorkOrderLog
from app.models.work_order_part_request import WorkOrderPartRequest
from app.schemas.work_order_log import WorkOrderLogCreate, WorkOrderLogResponse
from app.services.estoque_os_baixa import baixa_estoque_ao_finalizar_os
from app.services.os_checklist_obrigatorio import (
    CHECKLIST_COD_FINALIZACAO,
    CHECKLIST_COD_LOTO,
    has_obrigatorio_concluido,
)
from app.services.work_order_service import can_transition

_BR_TZ = ZoneInfo("America/Sao_Paulo")

PERFIS_APONTAMENTO = frozenset({"ADMIN", "TECNICO", "LUBRIFICADOR", "LIDER"})
PERFIS_CANCELAR = frozenset({"ADMIN", "LIDER"})
PERFIS_FINALIZAR = frozenset({"ADMIN", "LIDER"})

router = APIRouter(prefix="/ordens-servico", tags=["os-apontamentos"])


def _norm_wo_status(v: object) -> str:
    return str(v.value if hasattr(v, "value") else v)


def _format_solicitado_suffix(db: Session, work_order_id: UUID) -> str:
    """Lista todas as solicitações da OS (quem e quando) para histórico no apontamento."""
    rows = (
        db.execute(
            select(WorkOrderPartRequest, User.nome_completo)
            .join(User, WorkOrderPartRequest.solicitante_id == User.id)
            .where(WorkOrderPartRequest.ordem_servico_id == work_order_id)
            .order_by(WorkOrderPartRequest.created_at.asc())
        )
        .all()
    )
    if not rows:
        return ""
    parts: list[str] = []
    for req, nome in rows:
        dt = req.created_at.astimezone(_BR_TZ).strftime("%d/%m/%Y %H:%M")
        cod = (req.codigo_peca or "").strip()
        cod_txt = f" [{cod}]" if cod else ""
        qf = float(req.quantidade)
        parts.append(f"{nome} ({dt}): {req.descricao}{cod_txt} x{qf}")
    return "\n\nSOLICITADO: " + " | ".join(parts)


@router.get("/{work_order_id}/apontamentos", response_model=list[WorkOrderLogResponse])
def list_work_order_logs(
    work_order_id: UUID,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")

    rows = (
        db.execute(
            select(WorkOrderLog, User.nome_completo)
            .join(User, WorkOrderLog.usuario_id == User.id)
            .where(WorkOrderLog.ordem_servico_id == work_order_id)
            .order_by(WorkOrderLog.created_at.desc())
        )
        .all()
    )
    out: list[WorkOrderLogResponse] = []
    for log, nome in rows:
        out.append(
            WorkOrderLogResponse.model_validate(log).model_copy(update={"usuario_nome": nome})
        )
    return out


@router.post("/{work_order_id}/apontamentos", response_model=WorkOrderLogResponse, status_code=status.HTTP_201_CREATED)
def create_work_order_log(
    work_order_id: UUID,
    payload: WorkOrderLogCreate,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")

    perfil = str(user.perfil_acesso)
    if perfil not in PERFIS_APONTAMENTO:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao para registrar apontamento")

    status_anterior = _norm_wo_status(work_order.status)
    raw_next = payload.proximo_status
    status_novo = (raw_next.strip().upper() if isinstance(raw_next, str) and raw_next.strip() else status_anterior)
    checklist_ok_note: str | None = None

    if status_novo != status_anterior and not can_transition(work_order.status, status_novo):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Transicao invalida: {status_anterior} -> {status_novo}",
        )

    if status_novo == "CANCELADA" and perfil not in PERFIS_CANCELAR:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Apenas ADMIN ou LIDER pode cancelar OS.",
        )

    if status_novo == "FINALIZADA" and perfil not in PERFIS_FINALIZAR:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Apenas ADMIN ou LIDER pode finalizar OS.",
        )

    if status_novo != "CANCELADA":
        checklist_parts: list[str] = []
        if status_anterior == "ABERTA" and status_novo not in (
            "ABERTA",
            "CANCELADA",
            "AGENDADA",  # pode definir AGENDADA sem LOTO concluído; demais saídas de ABERTA exigem LOTO
        ):
            if not has_obrigatorio_concluido(db, work_order.id, CHECKLIST_COD_LOTO):
                raise HTTPException(
                    status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                    detail=(
                        "Para sair de ABERTA, execute e conclua o checklist obrigatório "
                        f"'{CHECKLIST_COD_LOTO}' nesta OS."
                    ),
                )
            checklist_parts.append(f"CHECKLIST_OK: {CHECKLIST_COD_LOTO}")
        if status_novo == "FINALIZADA":
            if not has_obrigatorio_concluido(db, work_order.id, CHECKLIST_COD_FINALIZACAO):
                raise HTTPException(
                    status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                    detail=(
                        "Antes de finalizar a OS, execute e conclua o checklist obrigatório "
                        f"'{CHECKLIST_COD_FINALIZACAO}' nesta OS."
                    ),
                )
            checklist_parts.append(f"CHECKLIST_OK: {CHECKLIST_COD_FINALIZACAO}")
        if checklist_parts:
            checklist_ok_note = "\n\n".join(checklist_parts)

    work_order.status = status_novo
    now = datetime.now(timezone.utc)
    if status_novo == "EM_EXECUCAO" and not work_order.data_inicio_real:
        work_order.data_inicio_real = payload.data_inicio or now
    if status_novo in ("FINALIZADA", "CANCELADA"):
        work_order.data_conclusao_real = payload.data_fim or now
    if status_novo == "EM_EXECUCAO" and not work_order.tecnico_id and perfil in ("ADMIN", "TECNICO", "LUBRIFICADOR"):
        work_order.tecnico_id = user.id

    if payload.status_ativo:
        asset = db.get(Asset, work_order.ativo_id)
        if asset:
            asset.status = payload.status_ativo
    elif status_novo == "FINALIZADA":
        asset = db.get(Asset, work_order.ativo_id)
        if asset:
            asset.status = "OPERANDO"
    elif status_novo == "CANCELADA":
        asset = db.get(Asset, work_order.ativo_id)
        if asset:
            asset.status = "OPERANDO"

    base_desc = payload.descricao.strip()
    if checklist_ok_note:
        base_desc = base_desc + "\n\n" + checklist_ok_note
    extra = _format_solicitado_suffix(db, work_order.id)
    combined = base_desc + extra
    if len(combined) > 8000:
        combined = combined[:7997] + "..."

    log = WorkOrderLog(
        ordem_servico_id=work_order.id,
        usuario_id=user.id,
        status_anterior=status_anterior,
        status_novo=status_novo,
        descricao=combined,
        data_inicio=payload.data_inicio,
        data_fim=payload.data_fim,
    )
    if status_novo == "FINALIZADA" and status_anterior != "FINALIZADA":
        baixa_estoque_ao_finalizar_os(db, work_order.id)

    db.add(work_order)
    db.add(log)
    db.commit()
    db.refresh(log)
    return WorkOrderLogResponse.model_validate(log).model_copy(
        update={"usuario_nome": user.nome_completo}
    )
