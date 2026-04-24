"""Execução de plano de preventiva: cria OS PREVENTIVA em AGENDADA, inclui LOTO, LOTO_LIDER, FINALIZACAO_OS e vincula checklist da TAG."""

from __future__ import annotations

import uuid
from datetime import datetime, timedelta, timezone

from fastapi import HTTPException, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session
from sqlalchemy.exc import IntegrityError

from app.models.asset import Asset
from app.models.checklist import ChecklistPadrao
from app.models.maintenance_plan import MaintenancePlan
from app.models.user import User
from app.models.work_order import WorkOrder
from app.services.checklist_execucao_os import (
    ensure_padroes_obrigatorios_na_os,
    vincular_checklist_por_codigo_tag_ativo,
)


def _gerar_codigo_os_preventiva_unico(db: Session) -> str:
    for _ in range(40):
        cod = f"OS-PRV-{uuid.uuid4().hex[:10].upper()}"
        if len(cod) > 40:
            cod = cod[:40]
        if not db.scalar(select(WorkOrder.id).where(WorkOrder.codigo_os == cod).limit(1)):
            return cod
    raise HTTPException(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        detail="Nao foi possivel gerar codigo unico de OS preventiva",
    )


def executar_plano_criar_os(
    db: Session,
    plan: MaintenancePlan,
    user: User,
) -> WorkOrder:
    """
    Valida TAG + checklist, cria OS PREVENTIVA em AGENDADA, garante LOTO, LOTO_LIDER e FINALIZACAO_OS na OS,
    vincula checklist da tag. A cadeia LOTO não precisa estar concluída para o vínculo pelo TAG.
    atualiza datas do plano. Commits no fluxo.
    """
    if not plan.ativo:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Plano de preventiva inativo",
        )
    asset = db.get(Asset, plan.ativo_id)
    if not asset:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ativo do plano nao encontrado")

    tag = (asset.tag_ativo or "").strip()
    if not tag:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="O ativo precisa ter TAG para executar a preventiva (checklist vinculado por codigo = TAG).",
        )
    existente_tag = db.scalar(
        select(ChecklistPadrao.id).where(
            func.lower(ChecklistPadrao.codigo_checklist) == func.lower(tag),
            ChecklistPadrao.ativo.is_(True),
        )
    )
    if not existente_tag:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=(
                f'Cadastre um checklist padrão ativo com codigo_checklist exatamente igual à TAG do ativo ("{tag}").'
            ),
        )

    now = datetime.now(timezone.utc)
    desc_curta = (plan.descricao or "").strip()
    falha = f"Preventiva: {plan.titulo}"[:4000]
    obs = "OS gerada na execução do plano de preventiva." + (f" {desc_curta}" if desc_curta else "")
    obs = obs[:4000]

    last_exc: Exception | None = None
    for _ in range(12):
        codigo = _gerar_codigo_os_preventiva_unico(db)
        work_order = WorkOrder(
            codigo_os=codigo,
            ativo_id=plan.ativo_id,
            solicitante_id=user.id,
            tecnico_id=None,
            tipo_manutencao="PREVENTIVA",
            prioridade="MEDIA",
            status="AGENDADA",
            data_agendamento=now,
            falha_sintoma=falha,
            observacoes=obs,
        )
        db.add(work_order)
        try:
            db.commit()
        except IntegrityError as exc:
            db.rollback()
            last_exc = exc
            continue
        db.refresh(work_order)
        ensure_padroes_obrigatorios_na_os(db, work_order, user)
        work_order = db.get(WorkOrder, work_order.id)
        if not work_order:
            raise HTTPException(
                status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
                detail="Falha ao recarregar OS apos vincular checklists padrao",
            )
        vincular_checklist_por_codigo_tag_ativo(db, work_order, tag, user)

        plan.ultima_execucao = now
        plan.proxima_execucao = now + timedelta(days=max(1, plan.periodicidade_dias))
        db.add(plan)
        db.commit()
        db.refresh(plan)
        return work_order

    raise HTTPException(
        status_code=status.HTTP_409_CONFLICT,
        detail="Nao foi possivel gravar a OS preventiva. Tente novamente.",
    ) from last_exc


def executar_preventivas_vencidas_para_usuario(db: Session, user: User) -> dict:
    """
    Cria OS para cada plano ativo com proxima_execucao no passado.
    Ignora falhas por plano e devolve contagem e lista de erros.
    """
    now = datetime.now(timezone.utc)
    plan_ids = db.scalars(
        select(MaintenancePlan.id)
        .where(MaintenancePlan.ativo.is_(True))
        .where(MaintenancePlan.proxima_execucao.isnot(None))
        .where(MaintenancePlan.proxima_execucao < now)
        .order_by(MaintenancePlan.proxima_execucao.asc())
    ).all()
    criadas = 0
    erros: list[str] = []
    for pid in plan_ids:
        plan = db.get(MaintenancePlan, pid)
        if not plan or not plan.ativo:
            continue
        if plan.proxima_execucao is None or plan.proxima_execucao >= now:
            continue
        try:
            executar_plano_criar_os(db, plan, user)
            criadas += 1
        except HTTPException as exc:
            det = exc.detail
            if not isinstance(det, str):
                det = str(det)
            erros.append(f"{pid}: {det}")
        except Exception as exc:  # noqa: BLE001
            erros.append(f"{pid}: {exc!s}")
    return {"criadas": criadas, "erros": erros}
