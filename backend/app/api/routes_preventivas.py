from datetime import datetime, timedelta, timezone

from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.api.routes_work_orders import _work_order_to_response
from app.auth.dependencies import require_com_catalogo, require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.maintenance_plan import MaintenancePlan
from app.models.setor import Setor
from app.models.user import User
from app.models.work_order import WorkOrder
from app.schemas.maintenance_plan import (
    ExecutarPreventivaResponse,
    MaintenancePlanCreate,
    MaintenancePlanResponse,
    MaintenancePlanUpdate,
)
from app.services.preventiva_execucao import executar_plano_criar_os

router = APIRouter(prefix="/preventivas", tags=["preventivas"])


def _plan_to_response(plan: MaintenancePlan, tag: str | None) -> MaintenancePlanResponse:
    return MaintenancePlanResponse(
        id=plan.id,
        ativo_id=plan.ativo_id,
        titulo=plan.titulo,
        descricao=plan.descricao,
        periodicidade_dias=plan.periodicidade_dias,
        ultima_execucao=plan.ultima_execucao,
        proxima_execucao=plan.proxima_execucao,
        ativo=plan.ativo,
        tag_ativo=tag,
    )


@router.get("", response_model=list[MaintenancePlanResponse])
def list_plans(
    vencidas: bool = Query(default=False, description="Somente com proxima_execucao no passado"),
    somente_ativos: bool = Query(
        default=False,
        description="Se true, retorna apenas planos com flag ativo (nao desativados administrativamente).",
    ),
    limit: int = Query(default=50, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    query = (
        select(MaintenancePlan, Asset.tag_ativo)
        .join(Asset, MaintenancePlan.ativo_id == Asset.id)
        .order_by(MaintenancePlan.proxima_execucao.asc().nulls_last())
        .limit(limit)
        .offset(offset)
    )
    if somente_ativos:
        query = query.where(MaintenancePlan.ativo.is_(True))
    if vencidas:
        query = query.where(
            MaintenancePlan.ativo.is_(True),
            MaintenancePlan.proxima_execucao.isnot(None),
            MaintenancePlan.proxima_execucao < datetime.now(timezone.utc),
        )
    rows = db.execute(query).all()
    return [_plan_to_response(p, tag) for p, tag in rows]


@router.post("", response_model=MaintenancePlanResponse, status_code=status.HTTP_201_CREATED)
def create_plan(
    payload: MaintenancePlanCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    now = datetime.now(timezone.utc)
    proxima = payload.proxima_execucao
    if proxima is None:
        proxima = now + timedelta(days=payload.periodicidade_dias)
    plan = MaintenancePlan(
        ativo_id=payload.ativo_id,
        titulo=payload.titulo,
        descricao=payload.descricao,
        periodicidade_dias=payload.periodicidade_dias,
        ultima_execucao=None,
        proxima_execucao=proxima,
        ativo=True,
    )
    db.add(plan)
    db.commit()
    db.refresh(plan)
    tag = db.scalar(select(Asset.tag_ativo).where(Asset.id == plan.ativo_id))
    return _plan_to_response(plan, tag)


@router.patch("/{plan_id}", response_model=MaintenancePlanResponse)
def update_plan(
    plan_id: str,
    payload: MaintenancePlanUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    try:
        pl_uuid = UUID(plan_id)
    except ValueError:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="ID de plano invalido")
    plan = db.get(MaintenancePlan, pl_uuid)
    if not plan:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Plano nao encontrado")
    data = payload.model_dump(exclude_unset=True, exclude_none=False)
    if "ativo_id" in data and data["ativo_id"] is not None:
        alvo = db.get(Asset, data["ativo_id"])
        if not alvo:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ativo nao encontrado")
    for key, value in data.items():
        setattr(plan, key, value)
    db.add(plan)
    db.commit()
    db.refresh(plan)
    tag = db.scalar(select(Asset.tag_ativo).where(Asset.id == plan.ativo_id))
    return _plan_to_response(plan, tag)


@router.delete("/{plan_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_plan(
    plan_id: str,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    try:
        pl_uuid = UUID(plan_id)
    except ValueError:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="ID de plano invalido")
    plan = db.get(MaintenancePlan, pl_uuid)
    if not plan:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Plano nao encontrado")
    db.delete(plan)
    db.commit()
    return None


@router.post("/{plan_id}/executar", response_model=ExecutarPreventivaResponse)
def executar_plano(
    plan_id: str,
    user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR")),
    db: Session = Depends(get_db),
):
    try:
        pl_uuid = UUID(plan_id)
    except ValueError:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="ID de plano invalido")
    plan = db.get(MaintenancePlan, pl_uuid)
    if not plan:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Plano nao encontrado")
    work_order = executar_plano_criar_os(db, plan, user)
    # Recarregar plano apos service (commit)
    plan = db.get(MaintenancePlan, pl_uuid)
    if not plan:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Plano nao recarregado apos executar",
        )
    tag = db.scalar(select(Asset.tag_ativo).where(Asset.id == plan.ativo_id))
    nome_sol = User.nome_completo.label("nome_solicitante")
    row_wo = (
        db.execute(
            select(WorkOrder, Asset, Setor, nome_sol)
            .join(Asset, WorkOrder.ativo_id == Asset.id)
            .outerjoin(Setor, Asset.setor_id == Setor.id)
            .join(User, WorkOrder.solicitante_id == User.id)
            .where(WorkOrder.id == work_order.id)
        )
        .mappings()
        .first()
    )
    if not row_wo:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="OS preventiva nao recarregada apos execucao",
        )
    return ExecutarPreventivaResponse(
        plano=_plan_to_response(plan, tag),
        ordem_servico=_work_order_to_response(
            row_wo[WorkOrder], row_wo[Asset], row_wo[Setor], row_wo["nome_solicitante"]
        ),
    )
