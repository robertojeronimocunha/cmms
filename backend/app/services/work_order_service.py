from datetime import datetime, timezone

from fastapi import HTTPException, status
from sqlalchemy.orm import Session

from app.models.work_order import WorkOrder

TERMINAL_STATUSES = frozenset({"FINALIZADA", "CANCELADA"})


def horas_intervalo_apontamento(data_inicio: datetime | None, data_fim: datetime | None) -> float:
    """Horas entre início e fim do apontamento (mão de obra); 0 se ausente ou inválido."""
    if data_inicio is None or data_fim is None:
        return 0.0
    secs = max((data_fim - data_inicio).total_seconds(), 0.0)
    return secs / 3600.0


def _norm_status(v: object) -> str:
    return str(v.value if hasattr(v, "value") else v)


def can_transition(current_status: object, next_status: str) -> bool:
    """Permite qualquer mudança a partir de estados não terminais; bloqueia saída de FINALIZADA/CANCELADA."""
    c = _norm_status(current_status)
    n = (next_status or "").strip().upper()
    if c == n:
        return True
    if c in TERMINAL_STATUSES:
        return False
    return True


def apply_status_transition(db: Session, work_order: WorkOrder, next_status: str) -> WorkOrder:
    if not can_transition(work_order.status, next_status):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=f"Transicao invalida: {_norm_status(work_order.status)} -> {next_status}",
        )

    work_order.status = next_status
    now = datetime.now(timezone.utc)
    if next_status == "EM_EXECUCAO" and not work_order.data_inicio_real:
        work_order.data_inicio_real = now
    if next_status in ("FINALIZADA", "CANCELADA"):
        work_order.data_conclusao_real = now

    db.add(work_order)
    db.commit()
    db.refresh(work_order)
    return work_order
