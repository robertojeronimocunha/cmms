from datetime import datetime, timezone
from decimal import Decimal
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import and_, case, nulls_last, select
from sqlalchemy.orm import Session

from app.auth.dependencies import require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.setor import Setor
from app.models.user import User
from app.models.work_order import WorkOrder
from app.models.work_order_log import WorkOrderLog
from app.models.work_order_part_request import WorkOrderPartRequest
from app.schemas.work_order import WorkOrderResponse
from app.services.work_order_service import horas_intervalo_apontamento
from app.schemas.work_order_consolidation import (
    WorkOrderConsolidationApontamento,
    WorkOrderConsolidationOsResumo,
    WorkOrderConsolidationResponse,
    WorkOrderConsolidationResumo,
    WorkOrderConsolidationSalvarPecasPayload,
    WorkOrderConsolidationPartUpdate,
    WorkOrderConsolidationUpsert,
)

router = APIRouter(prefix="/ordens-servico", tags=["os-consolidacao"])


def _apply_consolidation_part_adjustments(
    db: Session,
    work_order: WorkOrder,
    items: list[WorkOrderConsolidationPartUpdate],
) -> None:
    for item in items:
        req = db.get(WorkOrderPartRequest, item.request_id)
        if not req or req.ordem_servico_id != work_order.id:
            raise HTTPException(
                status_code=status.HTTP_404_NOT_FOUND,
                detail=f"Solicitação de peça não encontrada para esta OS: {item.request_id}",
            )
        data = item.dados.model_dump(exclude_unset=True)
        if "codigo_peca" in data:
            req.codigo_peca = (data.get("codigo_peca") or None)
        if "descricao" in data and data.get("descricao") is not None:
            req.descricao = str(data["descricao"]).strip()
        if "quantidade" in data and data.get("quantidade") is not None:
            req.quantidade = data["quantidade"]
        if "numero_solicitacao_erp" in data:
            req.numero_solicitacao_erp = (data.get("numero_solicitacao_erp") or None)
        if "preco_unitario" in data:
            req.preco_unitario = data.get("preco_unitario")
        db.add(req)


def _sum_custo_pecas_from_requests(db: Session, work_order_id: UUID) -> float:
    rows = (
        db.execute(select(WorkOrderPartRequest).where(WorkOrderPartRequest.ordem_servico_id == work_order_id))
        .scalars()
        .all()
    )
    total = Decimal("0")
    for r in rows:
        if r.preco_unitario is None:
            continue
        total += Decimal(str(r.quantidade)) * Decimal(str(r.preco_unitario))
    return float(total.quantize(Decimal("0.01")))


def _normalize_status(v: object) -> str:
    return str(v.value if hasattr(v, "value") else v)


def _setor_display(setor: Setor | None) -> str | None:
    if setor is None:
        return None
    return f"{setor.tag_setor} — {setor.descricao}"


def _status_para_bucket(st: str) -> str:
    """Normaliza rótulos legados de apontamentos."""
    if st == "EM_TESTE":
        return "AGUARDANDO_APROVACAO"
    return st


def _compute_status_hours(work_order: WorkOrder, logs: list[WorkOrderLog]) -> WorkOrderConsolidationResumo:
    now = datetime.now(timezone.utc)
    timeline: list[tuple[datetime, str]] = [(work_order.data_abertura, "ABERTA")]
    for log in sorted(logs, key=lambda x: x.created_at):
        timeline.append((log.created_at, _status_para_bucket(log.status_novo)))
    timeline.append((work_order.data_conclusao_real or now, _status_para_bucket(_normalize_status(work_order.status))))

    bucket = {
        "ABERTA": 0.0,
        "AGENDADA": 0.0,
        "EM_EXECUCAO": 0.0,
        "AGUARDANDO_PECA": 0.0,
        "AGUARDANDO_TERCEIRO": 0.0,
        "AGUARDANDO_APROVACAO": 0.0,
    }

    for idx in range(len(timeline) - 1):
        ts, st = timeline[idx]
        ts_next, _ = timeline[idx + 1]
        st = _status_para_bucket(st)
        if st not in bucket:
            continue
        delta_h = max((ts_next - ts).total_seconds(), 0.0) / 3600.0
        bucket[st] += delta_h

    # Mão de obra declarada nos apontamentos (início/fim) soma em EM_EXECUCAO (custo usa o mesmo intervalo).
    mao_obra_h = sum(horas_intervalo_apontamento(lg.data_inicio, lg.data_fim) for lg in logs)
    bucket["EM_EXECUCAO"] += mao_obra_h

    return WorkOrderConsolidationResumo(
        horas_aberta=round(bucket["ABERTA"], 2),
        horas_agendada=round(bucket["AGENDADA"], 2),
        horas_em_execucao=round(bucket["EM_EXECUCAO"], 2),
        horas_aguardando_peca=round(bucket["AGUARDANDO_PECA"], 2),
        horas_aguardando_terceiro=round(bucket["AGUARDANDO_TERCEIRO"], 2),
        horas_aguardando_aprovacao=round(bucket["AGUARDANDO_APROVACAO"], 2),
    )


def _build_response(db: Session, work_order: WorkOrder) -> WorkOrderConsolidationResponse:
    logs = list(
        db.execute(
            select(WorkOrderLog).where(WorkOrderLog.ordem_servico_id == work_order.id).order_by(WorkOrderLog.created_at.asc())
        )
        .scalars()
        .all()
    )
    asset = db.get(Asset, work_order.ativo_id)
    setor: Setor | None = None
    if asset and asset.setor_id:
        setor = db.get(Setor, asset.setor_id)

    user_ids: set[UUID] = {work_order.solicitante_id}
    if work_order.tecnico_id:
        user_ids.add(work_order.tecnico_id)
    for lg in logs:
        user_ids.add(lg.usuario_id)

    users_map: dict[UUID, User] = {}
    if user_ids:
        rows_u = db.execute(select(User).where(User.id.in_(user_ids))).scalars().all()
        users_map = {u.id: u for u in rows_u}

    sol = users_map.get(work_order.solicitante_id)
    tec = users_map.get(work_order.tecnico_id) if work_order.tecnico_id else None

    os_resumo = WorkOrderConsolidationOsResumo(
        id=work_order.id,
        codigo_os=work_order.codigo_os,
        ativo_id=work_order.ativo_id,
        tag_ativo=asset.tag_ativo if asset else None,
        ativo_descricao=asset.descricao if asset else None,
        setor_nome=_setor_display(setor),
        tipo_manutencao=_normalize_status(work_order.tipo_manutencao),
        prioridade=_normalize_status(work_order.prioridade),
        status=_normalize_status(work_order.status),
        falha_sintoma=work_order.falha_sintoma,
        observacoes=work_order.observacoes,
        data_abertura=work_order.data_abertura,
        data_agendamento=work_order.data_agendamento,
        data_inicio_real=work_order.data_inicio_real,
        data_conclusao_real=work_order.data_conclusao_real,
        solicitante_id=work_order.solicitante_id,
        solicitante_nome=sol.nome_completo if sol else None,
        tecnico_id=work_order.tecnico_id,
        tecnico_nome=tec.nome_completo if tec else None,
    )

    apontamentos: list[WorkOrderConsolidationApontamento] = []
    total_h = 0.0
    total_custo = 0.0
    for lg in logs:
        u = users_map.get(lg.usuario_id)
        custo_h = float(u.custo_hora_interno or 0) if u else 0.0
        h = round(horas_intervalo_apontamento(lg.data_inicio, lg.data_fim), 2)
        linha = round(h * custo_h, 2)
        total_h += h
        total_custo += linha
        apontamentos.append(
            WorkOrderConsolidationApontamento(
                id=lg.id,
                created_at=lg.created_at,
                usuario_id=lg.usuario_id,
                usuario_nome=u.nome_completo if u else None,
                status_anterior=lg.status_anterior,
                status_novo=lg.status_novo,
                descricao=lg.descricao,
                data_inicio=lg.data_inicio,
                data_fim=lg.data_fim,
                horas_trabalhadas=h,
                custo_hora_usuario=custo_h,
                custo_mao_obra_linha=linha,
            )
        )

    return WorkOrderConsolidationResponse(
        work_order_id=work_order.id,
        codigo_os=work_order.codigo_os,
        status=_normalize_status(work_order.status),
        consolidada=bool(work_order.consolidada),
        consolidada_em=work_order.consolidada_em,
        consolidada_por_id=work_order.consolidada_por_id,
        tag_defeito=work_order.tag_defeito,
        causa_raiz=work_order.causa_raiz,
        solucao=work_order.solucao,
        observacoes=work_order.observacoes,
        custo_internos=float(work_order.custo_internos or 0),
        custo_terceiros=float(work_order.custo_terceiros or 0),
        custo_pecas=float(work_order.custo_pecas or 0),
        custo_total=float(work_order.custo_total or 0),
        resumo_horas=_compute_status_hours(work_order, logs),
        os_resumo=os_resumo,
        apontamentos=apontamentos,
        total_horas_mao_obra_apontamentos=round(total_h, 2),
        total_custo_mao_obra_sugerido=round(total_custo, 2),
    )


# Teto de segurança para listar O.S. finalizadas e canceladas (modo revisão com consolidadas).
_CONSOLIDACAO_LISTA_MAX = 20_000

_STATUS_VISIVEL_CONSOLIDACAO = ("FINALIZADA", "CANCELADA")
_STATUS_PODE_CONSOLIDAR = frozenset(_STATUS_VISIVEL_CONSOLIDACAO)


@router.get("/consolidacao/pendentes", response_model=list[WorkOrderResponse])
def list_pending_consolidation(
    limit: int = Query(default=200, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
    incluir_consolidadas: bool = Query(
        default=False,
        description="Se true, devolve a lista completa (até o teto de segurança) de O.S. finalizadas ou canceladas, pendentes e consolidadas, para revisão. Ignora limit e offset.",
    ),
    _user=Depends(require_roles("ADMIN", "DIRETORIA")),
    db: Session = Depends(get_db),
):
    cond = [WorkOrder.status.in_(_STATUS_VISIVEL_CONSOLIDACAO)]
    if not incluir_consolidadas:
        cond.append(WorkOrder.consolidada.is_(False))
    stmt = select(WorkOrder).where(and_(*cond))
    if incluir_consolidadas:
        stmt = stmt.order_by(
            WorkOrder.consolidada.asc(),
            case((WorkOrder.status == "CANCELADA", 1), else_=0).asc(),
            nulls_last(WorkOrder.data_conclusao_real.desc()),
            WorkOrder.data_abertura.desc(),
        )
    else:
        stmt = stmt.order_by(
            case((WorkOrder.status == "CANCELADA", 1), else_=0).asc(),
            WorkOrder.data_abertura.asc(),
        )
    if incluir_consolidadas:
        rows = db.execute(stmt.limit(_CONSOLIDACAO_LISTA_MAX)).scalars().all()
    else:
        rows = db.execute(stmt.limit(limit).offset(offset)).scalars().all()
    out: list[WorkOrderResponse] = []
    for wo in rows:
        out.append(
            WorkOrderResponse(
                id=wo.id,
                codigo_os=wo.codigo_os,
                ativo_id=wo.ativo_id,
                solicitante_id=wo.solicitante_id,
                status=_normalize_status(wo.status),
                prioridade=_normalize_status(wo.prioridade),
                tipo_manutencao=_normalize_status(wo.tipo_manutencao),
                falha_sintoma=wo.falha_sintoma,
                observacoes=wo.observacoes,
                data_abertura=wo.data_abertura,
                consolidada=bool(wo.consolidada),
                consolidada_em=wo.consolidada_em,
                consolidada_por_id=wo.consolidada_por_id,
                tag_defeito=wo.tag_defeito,
                custo_internos=float(wo.custo_internos or 0),
                custo_terceiros=float(wo.custo_terceiros or 0),
                custo_pecas=float(wo.custo_pecas or 0),
                custo_total=float(wo.custo_total or 0),
            )
        )
    return out


@router.get("/{work_order_id}/consolidacao", response_model=WorkOrderConsolidationResponse)
def get_work_order_consolidation(
    work_order_id: UUID,
    _user=Depends(require_roles("ADMIN", "DIRETORIA", "TECNICO", "LUBRIFICADOR")),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    return _build_response(db, work_order)


@router.post("/{work_order_id}/consolidacao-salvar-pecas", response_model=WorkOrderConsolidationResponse)
def salvar_pecas_e_recalcular_consolidacao(
    work_order_id: UUID,
    payload: WorkOrderConsolidationSalvarPecasPayload,
    _user=Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    """Grava ajustes nas solicitações de peças, recalcula custo de peças (Σ qtde × preço) e custo total; não altera a flag consolidada."""
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    current_status = _normalize_status(work_order.status)
    if current_status not in _STATUS_PODE_CONSOLIDAR:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="OS precisa estar FINALIZADA ou CANCELADA.",
        )

    _apply_consolidation_part_adjustments(db, work_order, payload.ajustes_pecas)

    custo_pecas = _sum_custo_pecas_from_requests(db, work_order.id)
    work_order.custo_pecas = custo_pecas
    if payload.custo_internos is not None:
        work_order.custo_internos = payload.custo_internos
    if payload.custo_terceiros is not None:
        work_order.custo_terceiros = payload.custo_terceiros

    ci = float(work_order.custo_internos or 0)
    ct = float(work_order.custo_terceiros or 0)
    work_order.custo_total = round(ci + ct + custo_pecas, 2)

    db.add(work_order)
    db.commit()
    db.refresh(work_order)
    return _build_response(db, work_order)


@router.post("/{work_order_id}/consolidar", response_model=WorkOrderConsolidationResponse)
def consolidate_work_order(
    work_order_id: UUID,
    payload: WorkOrderConsolidationUpsert,
    user=Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    current_status = _normalize_status(work_order.status)
    if current_status not in _STATUS_PODE_CONSOLIDAR:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="OS precisa estar FINALIZADA ou CANCELADA.",
        )

    _apply_consolidation_part_adjustments(db, work_order, payload.ajustes_pecas)

    work_order.tag_defeito = payload.tag_defeito.strip() if payload.tag_defeito else None
    if payload.causa_raiz is not None:
        work_order.causa_raiz = payload.causa_raiz.strip() or None
    if payload.solucao is not None:
        work_order.solucao = payload.solucao.strip() or None
    if payload.observacoes is not None:
        work_order.observacoes = payload.observacoes.strip() or None

    work_order.custo_internos = payload.custo_internos
    work_order.custo_terceiros = payload.custo_terceiros
    work_order.custo_pecas = payload.custo_pecas
    total = payload.custo_total
    if total is None:
        total = payload.custo_internos + payload.custo_terceiros + payload.custo_pecas
    work_order.custo_total = total
    work_order.consolidada = True
    work_order.consolidada_em = datetime.now(timezone.utc)
    work_order.consolidada_por_id = user.id
    if current_status == "CANCELADA":
        work_order.status = "CANCELADA"
    else:
        work_order.status = "FINALIZADA"
    if not work_order.data_conclusao_real:
        work_order.data_conclusao_real = datetime.now(timezone.utc)
    db.add(work_order)
    db.commit()
    db.refresh(work_order)
    return _build_response(db, work_order)
