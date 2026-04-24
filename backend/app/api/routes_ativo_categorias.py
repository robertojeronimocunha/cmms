from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user, require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.ativo_categoria import AtivoCategoria
from app.models.user import User
from app.schemas.ativo_categoria import AtivoCategoriaCreate, AtivoCategoriaResponse, AtivoCategoriaUpdate

router = APIRouter(prefix="/ativo-categorias", tags=["ativo-categorias"])


@router.get("", response_model=list[AtivoCategoriaResponse])
def list_ativo_categorias(
    limit: int = Query(default=500, ge=1, le=1000),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    q = (
        select(AtivoCategoria)
        .order_by(AtivoCategoria.ordem.asc(), AtivoCategoria.nome.asc())
        .limit(limit)
        .offset(offset)
    )
    return list(db.execute(q).scalars().all())


@router.post("", response_model=AtivoCategoriaResponse, status_code=status.HTTP_201_CREATED)
def create_ativo_categoria(
    payload: AtivoCategoriaCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    nome = payload.nome.strip()
    if not nome:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Nome obrigatorio")
    exists = db.scalar(
        select(AtivoCategoria.id).where(func.lower(AtivoCategoria.nome) == nome.lower()).limit(1)
    )
    if exists:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Ja existe categoria com este nome")
    row = AtivoCategoria(nome=nome, ordem=payload.ordem)
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.patch("/{categoria_id}", response_model=AtivoCategoriaResponse)
def update_ativo_categoria(
    categoria_id: UUID,
    payload: AtivoCategoriaUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(AtivoCategoria, categoria_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Categoria nao encontrada")
    data = payload.model_dump(exclude_none=True)
    if not data:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Nenhum campo para atualizar",
        )
    if "nome" in data:
        nome = data["nome"].strip()
        if not nome:
            raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Nome obrigatorio")
        conflict = db.scalar(
            select(AtivoCategoria.id).where(
                func.lower(AtivoCategoria.nome) == nome.lower(),
                AtivoCategoria.id != categoria_id,
            ).limit(1)
        )
        if conflict:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Ja existe categoria com este nome")
        row.nome = nome
    if "ordem" in data:
        row.ordem = int(data["ordem"])
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.delete("/{categoria_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_ativo_categoria(
    categoria_id: UUID,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(AtivoCategoria, categoria_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Categoria nao encontrada")
    em_uso = db.scalar(select(Asset.id).where(Asset.categoria_id == categoria_id).limit(1))
    if em_uso:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Categoria em uso por ativos; reatribua ou remova antes.",
        )
    db.delete(row)
    db.commit()
    return None
