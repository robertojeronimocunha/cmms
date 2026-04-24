from datetime import datetime, timedelta, timezone
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import require_com_catalogo, require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.lubricant import Lubricant
from app.models.lubrication_execution import LubricationExecution
from app.models.lubrication_point import LubricationPoint
from app.models.user import User
from app.schemas.lubrication_point import (
    ExecutarLubricacaoRequest,
    LubricationExecutionListResponse,
    LubricationPointCreate,
    LubricationPointResponse,
    LubricationPointUpdate,
)

router = APIRouter(prefix="/pontos-lubrificacao", tags=["pontos-lubrificacao"])


def _point_to_response(
    point: LubricationPoint, tag: str | None, lub_nome: str | None
) -> LubricationPointResponse:
    return LubricationPointResponse(
        id=point.id,
        ativo_id=point.ativo_id,
        lubrificante_id=point.lubrificante_id,
        descricao_ponto=point.descricao_ponto,
        periodicidade_dias=point.periodicidade_dias,
        ultima_execucao=point.ultima_execucao,
        proxima_execucao=point.proxima_execucao,
        observacoes=point.observacoes,
        tag_ativo=tag,
        lubrificante_nome=lub_nome,
    )


@router.get("", response_model=list[LubricationPointResponse])
def list_points(
    ativo_id: UUID | None = Query(default=None),
    limit: int = Query(default=100, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    query = (
        select(LubricationPoint, Asset.tag_ativo, Lubricant.nome)
        .join(Asset, LubricationPoint.ativo_id == Asset.id)
        .outerjoin(Lubricant, LubricationPoint.lubrificante_id == Lubricant.id)
        .order_by(LubricationPoint.proxima_execucao.asc().nulls_last())
        .limit(limit)
        .offset(offset)
    )
    if ativo_id is not None:
        query = query.where(LubricationPoint.ativo_id == ativo_id)
    rows = db.execute(query).all()
    return [_point_to_response(p, tag, ln) for p, tag, ln in rows]


@router.get("/execucoes", response_model=list[LubricationExecutionListResponse])
def list_execucoes(
    ativo_id: UUID | None = Query(default=None),
    ponto_id: UUID | None = Query(default=None),
    limit: int = Query(default=100, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    q = (
        select(
            LubricationExecution,
            Asset.tag_ativo,
            LubricationPoint.descricao_ponto,
            Lubricant.nome,
            User.nome_completo,
        )
        .join(LubricationPoint, LubricationExecution.ponto_lubrificacao_id == LubricationPoint.id)
        .join(Asset, LubricationPoint.ativo_id == Asset.id)
        .outerjoin(Lubricant, LubricationPoint.lubrificante_id == Lubricant.id)
        .outerjoin(User, LubricationExecution.usuario_id == User.id)
        .order_by(LubricationExecution.executado_em.desc())
        .limit(limit)
        .offset(offset)
    )
    if ativo_id is not None:
        q = q.where(LubricationPoint.ativo_id == ativo_id)
    if ponto_id is not None:
        q = q.where(LubricationExecution.ponto_lubrificacao_id == ponto_id)
    rows = db.execute(q).all()
    return [
        LubricationExecutionListResponse(
            id=ex.id,
            ponto_lubrificacao_id=ex.ponto_lubrificacao_id,
            tag_ativo=tag,
            descricao_ponto=desc_ponto,
            lubrificante_nome=ln,
            executado_em=ex.executado_em,
            quantidade_oleo_litros=ex.quantidade_oleo_litros,
            observacao=ex.observacao,
            usuario_nome=nome_user,
        )
        for ex, tag, desc_ponto, ln, nome_user in rows
    ]


@router.post("", response_model=LubricationPointResponse, status_code=status.HTTP_201_CREATED)
def create_point(
    payload: LubricationPointCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    now = datetime.now(timezone.utc)
    proxima = payload.proxima_execucao
    if proxima is None:
        proxima = now + timedelta(days=payload.periodicidade_dias)
    point = LubricationPoint(
        ativo_id=payload.ativo_id,
        lubrificante_id=payload.lubrificante_id,
        descricao_ponto=payload.descricao_ponto,
        periodicidade_dias=payload.periodicidade_dias,
        ultima_execucao=None,
        proxima_execucao=proxima,
        observacoes=payload.observacoes,
    )
    db.add(point)
    db.commit()
    db.refresh(point)
    tag = db.scalar(select(Asset.tag_ativo).where(Asset.id == point.ativo_id))
    ln = None
    if point.lubrificante_id:
        ln = db.scalar(select(Lubricant.nome).where(Lubricant.id == point.lubrificante_id))
    return _point_to_response(point, tag, ln)


@router.patch("/{point_id}", response_model=LubricationPointResponse)
def update_point(
    point_id: str,
    payload: LubricationPointUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    point = db.get(LubricationPoint, point_id)
    if not point:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ponto nao encontrado")
    for key, value in payload.model_dump(exclude_none=True).items():
        setattr(point, key, value)
    db.add(point)
    db.commit()
    db.refresh(point)
    tag = db.scalar(select(Asset.tag_ativo).where(Asset.id == point.ativo_id))
    ln = None
    if point.lubrificante_id:
        ln = db.scalar(select(Lubricant.nome).where(Lubricant.id == point.lubrificante_id))
    return _point_to_response(point, tag, ln)


@router.post("/{point_id}/executar", response_model=LubricationPointResponse)
def executar_ponto(
    point_id: UUID,
    payload: ExecutarLubricacaoRequest,
    user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR")),
    db: Session = Depends(get_db),
):
    point = db.get(LubricationPoint, point_id)
    if not point:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ponto nao encontrado")
    now = datetime.now(timezone.utc)
    exec_row = LubricationExecution(
        ponto_lubrificacao_id=point.id,
        usuario_id=user.id,
        quantidade_oleo_litros=payload.quantidade_oleo_litros,
        observacao=payload.observacao,
    )
    db.add(exec_row)
    point.ultima_execucao = now
    point.proxima_execucao = now + timedelta(days=max(1, point.periodicidade_dias))
    db.add(point)
    db.commit()
    db.refresh(point)
    tag = db.scalar(select(Asset.tag_ativo).where(Asset.id == point.ativo_id))
    ln = None
    if point.lubrificante_id:
        ln = db.scalar(select(Lubricant.nome).where(Lubricant.id == point.lubrificante_id))
    return _point_to_response(point, tag, ln)
