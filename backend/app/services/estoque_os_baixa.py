"""Baixa de estoque ao finalizar OS: solicitações com código igual ao catálogo; só itens com controla_estoque."""

from __future__ import annotations

from decimal import Decimal
from uuid import UUID

from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.models.part import Part
from app.models.work_order_part_request import WorkOrderPartRequest


def baixa_estoque_ao_finalizar_os(db: Session, ordem_servico_id: UUID) -> None:
    rows = (
        db.execute(
            select(WorkOrderPartRequest).where(WorkOrderPartRequest.ordem_servico_id == ordem_servico_id)
        )
        .scalars()
        .all()
    )
    for req in rows:
        cod = (req.codigo_peca or "").strip()
        if not cod:
            continue
        part = db.scalar(
            select(Part).where(func.lower(Part.codigo_interno) == func.lower(cod))
        )
        if part is None or not part.controla_estoque:
            continue
        qtd = req.quantidade
        if qtd is None:
            continue
        qd = Decimal(str(qtd))
        if qd <= 0:
            continue
        cur = part.estoque_atual if part.estoque_atual is not None else Decimal("0")
        part.estoque_atual = cur - qd
        db.add(part)
