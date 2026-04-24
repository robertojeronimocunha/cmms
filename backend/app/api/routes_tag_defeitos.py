from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user, require_roles
from app.core.database import get_db
from app.models.tag_defeito import TagDefeito
from app.models.user import User
from app.schemas.tag_defeito import TagDefeitoCreate, TagDefeitoResponse, TagDefeitoUpdate

router = APIRouter(prefix="/tags-defeito", tags=["tags-defeito"])


@router.get("", response_model=list[TagDefeitoResponse])
def list_tags_defeito(
    ativo: bool | None = Query(default=True),
    limit: int = Query(default=200, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    q = select(TagDefeito).order_by(TagDefeito.codigo.asc()).limit(limit).offset(offset)
    if ativo is not None:
        q = q.where(TagDefeito.ativo.is_(ativo))
    return list(db.execute(q).scalars().all())


@router.post("", response_model=TagDefeitoResponse, status_code=status.HTTP_201_CREATED)
def create_tag_defeito(
    payload: TagDefeitoCreate,
    _user=Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    code = payload.codigo.strip().upper()
    exists = db.scalar(select(TagDefeito.id).where(TagDefeito.codigo == code))
    if exists:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Código de TAG_Defeito já cadastrado.")
    row = TagDefeito(codigo=code, descricao=payload.descricao.strip(), ativo=payload.ativo)
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.patch("/{tag_id}", response_model=TagDefeitoResponse)
def update_tag_defeito(
    tag_id: UUID,
    payload: TagDefeitoUpdate,
    _user=Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(TagDefeito, tag_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="TAG_Defeito não encontrada.")
    data = payload.model_dump(exclude_unset=True)
    if "codigo" in data and data["codigo"] is not None:
        code = str(data["codigo"]).strip().upper()
        other = db.scalar(select(TagDefeito.id).where(TagDefeito.codigo == code, TagDefeito.id != row.id))
        if other:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Código de TAG_Defeito já em uso.")
        row.codigo = code
    if "descricao" in data and data["descricao"] is not None:
        row.descricao = str(data["descricao"]).strip()
    if "ativo" in data and data["ativo"] is not None:
        row.ativo = bool(data["ativo"])
    db.add(row)
    db.commit()
    db.refresh(row)
    return row
