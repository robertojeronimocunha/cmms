from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import require_com_catalogo, require_roles
from app.core.database import get_db
from app.models.lubricant import Lubricant
from app.models.user import User
from app.schemas.lubricant import LubricantCreate, LubricantResponse, LubricantUpdate

router = APIRouter(prefix="/lubrificantes", tags=["lubrificantes"])


@router.get("", response_model=list[LubricantResponse])
def list_lubricants(
    limit: int = Query(default=100, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    q = select(Lubricant).order_by(Lubricant.nome.asc()).limit(limit).offset(offset)
    return list(db.execute(q).scalars().all())


@router.post("", response_model=LubricantResponse, status_code=status.HTTP_201_CREATED)
def create_lubricant(
    payload: LubricantCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    codigo = payload.codigo_erp.strip().upper()
    nome = payload.nome.strip()
    if not codigo:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="COD. ERP é obrigatório.")
    if not nome:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Nome é obrigatório.")
    exists_code = db.scalar(select(Lubricant.id).where(Lubricant.codigo_erp == codigo))
    if exists_code:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="COD. ERP já cadastrado.")
    exists_name = db.scalar(select(Lubricant.id).where(Lubricant.nome == nome))
    if exists_name:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Nome já cadastrado.")
    row = Lubricant(
        codigo_erp=codigo,
        nome=nome,
        fabricante=(payload.fabricante.strip() if payload.fabricante else None),
        especificacao=(payload.especificacao.strip() if payload.especificacao else None),
    )
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.patch("/{lubricant_id}", response_model=LubricantResponse)
def update_lubricant(
    lubricant_id: str,
    payload: LubricantUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(Lubricant, lubricant_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Lubrificante nao encontrado")
    data = payload.model_dump(exclude_none=True)
    if "codigo_erp" in data:
        codigo = str(data["codigo_erp"]).strip().upper()
        if not codigo:
            raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="COD. ERP inválido.")
        exists_code = db.scalar(select(Lubricant.id).where(Lubricant.codigo_erp == codigo, Lubricant.id != row.id))
        if exists_code:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="COD. ERP já em uso.")
        row.codigo_erp = codigo
    if "nome" in data:
        nome = str(data["nome"]).strip()
        if not nome:
            raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Nome inválido.")
        exists_name = db.scalar(select(Lubricant.id).where(Lubricant.nome == nome, Lubricant.id != row.id))
        if exists_name:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Nome já em uso.")
        row.nome = nome
    if "fabricante" in data:
        row.fabricante = (str(data["fabricante"]).strip() if data["fabricante"] else None)
    if "especificacao" in data:
        row.especificacao = (str(data["especificacao"]).strip() if data["especificacao"] else None)
    if "ativo" in data:
        row.ativo = bool(data["ativo"])
    db.add(row)
    db.commit()
    db.refresh(row)
    return row
