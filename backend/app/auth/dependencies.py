from uuid import UUID

from fastapi import Depends, HTTPException, status
from fastapi.security import OAuth2PasswordBearer
from sqlalchemy.orm import Session

from app.auth.security import decode_access_token
from app.core.database import get_db
from app.models.user import User

oauth2_scheme = OAuth2PasswordBearer(tokenUrl="/api/v1/auth/login")

# Perfis que podem abrir nova OS (solicitar serviço)
PERFIS_ABRIR_OS = frozenset({"ADMIN", "TECNICO", "LUBRIFICADOR", "DIRETORIA", "USUARIO", "LIDER"})

# Perfis que executam manutenção na OS (checklists LOTO/demais no cadastro de tarefas; não inclui LIDER).
PERFIS_EXECUTAR_OS = frozenset({"ADMIN", "TECNICO", "LUBRIFICADOR"})

# Cadastros e módulos além de dashboard + ordens de serviço (perfil USUARIO não acessa)
PERFIS_COM_CATALOGO = frozenset({"ADMIN", "TECNICO", "LUBRIFICADOR", "DIRETORIA"})

# GET /pecas (lista/busca): catálogo + LIDER + USUARIO (sugestão ao solicitar peça na OS; tela ?page=pecas segue o menu)
PERFIS_LEITURA_PECAS = PERFIS_COM_CATALOGO | frozenset({"LIDER", "USUARIO"})

# Leitura de setores (dashboard LIDER filtra OS por responsabilidade; sem gravação de cadastro)
PERFIS_LEITURA_SETORES = PERFIS_COM_CATALOGO | frozenset({"LIDER"})


def get_current_user(token: str = Depends(oauth2_scheme), db: Session = Depends(get_db)) -> User:
    try:
        payload = decode_access_token(token)
        user_id = payload.get("sub")
        user_uuid = UUID(user_id)
    except Exception as exc:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Nao autenticado") from exc

    user = db.get(User, user_uuid)
    if not user or not user.ativo:
        raise HTTPException(status_code=status.HTTP_401_UNAUTHORIZED, detail="Usuario invalido")
    return user


def require_roles(*roles: str):
    role_set = set(roles)

    def dependency(user: User = Depends(get_current_user)) -> User:
        if user.perfil_acesso not in role_set:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao")
        return user

    return dependency


def require_abrir_os():
    return require_roles(*sorted(PERFIS_ABRIR_OS))


def require_executar_os():
    return require_roles(*sorted(PERFIS_EXECUTAR_OS))


def require_com_catalogo():
    """Bloqueia perfil USUARIO (apenas dashboard + OS no frontend)."""
    return require_roles(*sorted(PERFIS_COM_CATALOGO))


def require_leitura_setores():
    """GET /setores: catálogo + LIDER (painel por setor). Gravação continua só ADMIN."""
    return require_roles(*sorted(PERFIS_LEITURA_SETORES))
