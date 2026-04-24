from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user, require_executar_os
from app.core.database import get_db
from app.models.asset import Asset
from app.models.setor import Setor
from app.models.user import User
from app.models.work_order import WorkOrder
from app.schemas.work_order import WorkOrderCreate, WorkOrderResponse, WorkOrderStatusUpdate

router = APIRouter(prefix="/ordens-servico", tags=["ordens-servico"])


def _setor_display(setor: Setor | None) -> str | None:
    if setor is None:
        return None
    return f"{setor.tag_setor} — {setor.descricao}"


def _work_order_to_response(
    wo: WorkOrder,
    asset: Asset,
    setor: Setor | None,
    solicitante_nome: str | None,
) -> WorkOrderResponse:
    return WorkOrderResponse(
        id=wo.id,
        codigo_os=wo.codigo_os,
        ativo_id=wo.ativo_id,
        solicitante_id=wo.solicitante_id,
        solicitante_nome=solicitante_nome,
        status=str(wo.status.value if hasattr(wo.status, "value") else wo.status),
        prioridade=str(wo.prioridade.value if hasattr(wo.prioridade, "value") else wo.prioridade),
        tipo_manutencao=str(wo.tipo_manutencao.value if hasattr(wo.tipo_manutencao, "value") else wo.tipo_manutencao),
        falha_sintoma=wo.falha_sintoma,
        observacoes=wo.observacoes,
        data_abertura=wo.data_abertura,
        tag_ativo=asset.tag_ativo,
        ativo_descricao=asset.descricao,
        setor_nome=_setor_display(setor),
        ativo_fabricante=asset.fabricante,
        ativo_modelo=asset.modelo,
        ativo_numero_serie=asset.numero_serie,
        ativo_data_garantia=asset.data_garantia,
        ativo_status=str(asset.status.value if hasattr(asset.status, "value") else asset.status),
        ativo_criticidade=str(
            asset.criticidade.value if hasattr(asset.criticidade, "value") else asset.criticidade
        ),
        consolidada=bool(wo.consolidada),
        consolidada_em=wo.consolidada_em,
        consolidada_por_id=wo.consolidada_por_id,
        tag_defeito=wo.tag_defeito,
        custo_internos=float(wo.custo_internos or 0),
        custo_terceiros=float(wo.custo_terceiros or 0),
        custo_pecas=float(wo.custo_pecas or 0),
        custo_total=float(wo.custo_total or 0),
    )


@router.get("", response_model=list[WorkOrderResponse])
def list_work_orders(
    status_filter: str | None = Query(default=None, alias="status"),
    excluir_fechadas: bool = Query(
        default=False,
        description="Se true, lista só OS em andamento (exclui FINALIZADA e CANCELADA).",
    ),
    minhas: bool = Query(
        default=False,
        description="Se true, lista apenas OS em que o solicitante é o usuário autenticado.",
    ),
    limit: int = Query(default=50, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    nome_sol = User.nome_completo.label("nome_solicitante")
    query = (
        select(WorkOrder, Asset, Setor, nome_sol)
        .join(Asset, WorkOrder.ativo_id == Asset.id)
        .outerjoin(Setor, Asset.setor_id == Setor.id)
        .join(User, WorkOrder.solicitante_id == User.id)
        .order_by(WorkOrder.data_abertura.desc())
        .limit(limit)
        .offset(offset)
    )
    if minhas:
        query = query.where(WorkOrder.solicitante_id == _user.id)
    if excluir_fechadas:
        query = query.where(WorkOrder.status.notin_(("FINALIZADA", "CANCELADA")))
    elif status_filter:
        query = query.where(WorkOrder.status == status_filter)
    rows = db.execute(query).mappings().all()
    return [
        _work_order_to_response(r[WorkOrder], r[Asset], r[Setor], r["nome_solicitante"])
        for r in rows
    ]


@router.post("", response_model=WorkOrderResponse, status_code=status.HTTP_201_CREATED)
def create_work_order(
    payload: WorkOrderCreate,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    def increment_codigo_os(code: str) -> str:
        # Incrementa o sufixo numérico final, preservando largura com zeros.
        s = (code or "").strip()
        i = len(s)
        while i > 0 and s[i - 1].isdigit():
            i -= 1
        prefix = s[:i]
        suffix = s[i:]
        if suffix == "":
            return s + "1"
        width = len(suffix)
        new_num = int(suffix) + 1
        return prefix + str(new_num).zfill(width)

    codigo = payload.codigo_os.strip()
    max_attempts = 10
    last_exc: Exception | None = None

    for _ in range(max_attempts):
        work_order = WorkOrder(
            codigo_os=codigo,
            ativo_id=payload.ativo_id,
            solicitante_id=user.id,
            tecnico_id=None,
            tipo_manutencao=payload.tipo_manutencao,
            prioridade=payload.prioridade,
            status="ABERTA",
            falha_sintoma=payload.falha_sintoma,
            observacoes=payload.observacoes,
        )
        db.add(work_order)
        if payload.marcar_ativo_parado:
            asset_upd = db.get(Asset, payload.ativo_id)
            if asset_upd:
                asset_upd.status = "PARADO"
        try:
            db.commit()
            db.refresh(work_order)
            nome_sol = User.nome_completo.label("nome_solicitante")
            row = (
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
            if not row:
                raise HTTPException(
                    status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
                    detail="OS criada mas nao foi possivel recarregar dados do ativo.",
                )
            return _work_order_to_response(
                row[WorkOrder], row[Asset], row[Setor], row["nome_solicitante"]
            )
        except IntegrityError as exc:
            db.rollback()
            last_exc = exc
            codigo = increment_codigo_os(codigo)

    raise HTTPException(
        status_code=status.HTTP_409_CONFLICT,
        detail="Não foi possível gerar um código de OS único. Tente novamente.",
    ) from last_exc


@router.get("/{work_order_id}", response_model=WorkOrderResponse)
def get_work_order(
    work_order_id: str,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    try:
        wo_uuid = UUID(work_order_id)
    except ValueError:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="ID de OS invalido")
    nome_sol = User.nome_completo.label("nome_solicitante")
    row = (
        db.execute(
            select(WorkOrder, Asset, Setor, nome_sol)
            .join(Asset, WorkOrder.ativo_id == Asset.id)
            .outerjoin(Setor, Asset.setor_id == Setor.id)
            .join(User, WorkOrder.solicitante_id == User.id)
            .where(WorkOrder.id == wo_uuid)
        )
        .mappings()
        .first()
    )
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    return _work_order_to_response(
        row[WorkOrder], row[Asset], row[Setor], row["nome_solicitante"]
    )


@router.patch("/{work_order_id}/status", response_model=WorkOrderResponse)
def update_status(
    work_order_id: str,
    payload: WorkOrderStatusUpdate,
    user: User = Depends(require_executar_os()),
    db: Session = Depends(get_db),
):
    raise HTTPException(
        status_code=status.HTTP_409_CONFLICT,
        detail="Mudanca de status deve ser feita por apontamento da OS.",
    )
