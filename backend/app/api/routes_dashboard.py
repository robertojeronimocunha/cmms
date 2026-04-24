from datetime import date

from sqlalchemy import Date, cast, func, select

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user
from app.core.database import get_db
from app.models.asset import Asset
from app.models.lubrication_point import LubricationPoint
from app.models.maintenance_plan import MaintenancePlan
from app.models.part import Part
from app.models.user import User
from app.models.work_order import WorkOrder
from app.schemas.dashboard import DashboardResumo

router = APIRouter(prefix="/dashboard", tags=["dashboard"])


@router.get("/resumo", response_model=DashboardResumo)
def dashboard_resumo(
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    os_abertas = db.scalar(
        select(func.count()).select_from(WorkOrder).where(WorkOrder.status == "ABERTA")
    ) or 0
    maquinas_paradas = db.scalar(
        select(func.count()).select_from(Asset).where(Asset.status == "PARADO")
    ) or 0
    os_aguardando_peca = db.scalar(
        select(func.count()).select_from(WorkOrder).where(WorkOrder.status == "AGUARDANDO_PECA")
    ) or 0
    os_aguardando_terceiro = db.scalar(
        select(func.count()).select_from(WorkOrder).where(WorkOrder.status == "AGUARDANDO_TERCEIRO")
    ) or 0
    pecas_abaixo = db.scalar(
        select(func.count())
        .select_from(Part)
        .where(
            Part.controla_estoque.is_(True),
            Part.estoque_atual <= Part.estoque_minimo,
        )
    ) or 0

    preventivas_vencidas = db.scalar(
        select(func.count())
        .select_from(MaintenancePlan)
        .where(
            MaintenancePlan.ativo.is_(True),
            MaintenancePlan.proxima_execucao.isnot(None),
            MaintenancePlan.proxima_execucao < func.now(),
        )
    ) or 0

    lubrificacoes_hoje = db.scalar(
        select(func.count())
        .select_from(LubricationPoint)
        .where(
            LubricationPoint.proxima_execucao.isnot(None),
            cast(LubricationPoint.proxima_execucao, Date) <= date.today(),
        )
    ) or 0

    return DashboardResumo(
        os_abertas=os_abertas,
        maquinas_paradas=maquinas_paradas,
        os_aguardando_peca=os_aguardando_peca,
        os_aguardando_terceiro=os_aguardando_terceiro,
        pecas_abaixo_minimo=pecas_abaixo,
        preventivas_vencidas=preventivas_vencidas,
        lubrificacoes_hoje=lubrificacoes_hoje,
    )
