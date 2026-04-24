from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user
from app.auth.security import create_access_token, get_password_hash, verify_password
from app.core.database import get_db
from app.models.user import User
from app.schemas.auth import LoginRequest, TokenResponse, TrocarSenhaRequest
from app.schemas.user import UserResponse

router = APIRouter(prefix="/auth", tags=["auth"])


@router.post("/login", response_model=TokenResponse)
def login(payload: LoginRequest, db: Session = Depends(get_db)):
    user = db.execute(select(User).where(User.email == payload.email)).scalar_one_or_none()
    if not user or not verify_password(payload.senha, user.senha_hash):
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Credenciais invalidas")
    if not user.ativo:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Usuario inativo")

    user.ultimo_login = datetime.now(timezone.utc)
    db.add(user)
    db.commit()

    token = create_access_token(str(user.id))
    return TokenResponse(access_token=token)


@router.get("/me", response_model=UserResponse)
def auth_me(user: User = Depends(get_current_user)):
    """Perfil do usuario autenticado (para o frontend exibir menus e permissoes)."""
    return user


@router.post("/trocar-senha", status_code=status.HTTP_204_NO_CONTENT)
def auth_trocar_senha(
    payload: TrocarSenhaRequest,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    if not user.permite_trocar_senha:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Troca de senha desativada para este usuario. Contacte o administrador.",
        )
    if not verify_password(payload.senha_atual, user.senha_hash):
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Senha atual incorreta")
    user.senha_hash = get_password_hash(payload.senha_nova)
    db.add(user)
    db.commit()
    return None
