from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user, require_roles
from app.auth.security import get_password_hash
from app.core.database import get_db
from app.models.user import User
from app.schemas.user import UserCreate, UserResponse, UserUpdate

router = APIRouter(prefix="/usuarios", tags=["usuarios"])


@router.get("", response_model=list[UserResponse])
def list_users(
    ativo: bool | None = Query(default=None),
    limit: int = Query(default=50, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_roles("ADMIN", "DIRETORIA")),
    db: Session = Depends(get_db),
):
    query = select(User)
    if ativo is not None:
        query = query.where(User.ativo == ativo)
    query = query.order_by(User.nome_completo.asc()).limit(limit).offset(offset)
    return list(db.execute(query).scalars().all())


@router.get("/{user_id}", response_model=UserResponse)
def get_user(
    user_id: UUID,
    _user: User = Depends(require_roles("ADMIN", "DIRETORIA")),
    db: Session = Depends(get_db),
):
    user = db.get(User, user_id)
    if not user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario nao encontrado")
    return user


@router.post("", response_model=UserResponse, status_code=status.HTTP_201_CREATED)
def create_user(
    payload: UserCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    exists = db.execute(select(User).where(User.email == str(payload.email))).scalar_one_or_none()
    if exists:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Login ja cadastrado")

    user = User(
        nome_completo=payload.nome_completo,
        email=str(payload.email),
        senha_hash=get_password_hash(payload.senha),
        perfil_acesso=payload.perfil_acesso,
        ativo=payload.ativo,
        permite_trocar_senha=payload.permite_trocar_senha,
        custo_hora_interno=payload.custo_hora_interno,
    )
    db.add(user)
    db.commit()
    db.refresh(user)
    return user


@router.patch("/{user_id}", response_model=UserResponse)
def update_user(
    user_id: UUID,
    payload: UserUpdate,
    current_user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    user = db.get(User, user_id)
    if not user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario nao encontrado")

    data = payload.model_dump(exclude_none=True)

    if "senha" in data:
        pwd = data.pop("senha")
        if pwd:
            user.senha_hash = get_password_hash(pwd)

    if "email" in data:
        new_email = str(data.pop("email"))
        conflict = db.execute(
            select(User).where(User.email == new_email, User.id != user_id)
        ).scalar_one_or_none()
        if conflict:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Login ja em uso")
        user.email = new_email

    for key, value in data.items():
        setattr(user, key, value)

    db.add(user)
    db.commit()
    db.refresh(user)
    return user


@router.delete("/{user_id}", status_code=status.HTTP_204_NO_CONTENT)
def deactivate_user(
    user_id: UUID,
    current_user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    if user_id == current_user.id:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Nao e possivel desativar o proprio usuario",
        )
    user = db.get(User, user_id)
    if not user:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Usuario nao encontrado")
    user.ativo = False
    db.add(user)
    db.commit()
    return None
