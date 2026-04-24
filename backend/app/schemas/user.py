from uuid import UUID

from pydantic import BaseModel, Field, field_validator

from app.schemas.email_field import EmailCmms

PERFIS_VALIDOS = frozenset(
    {"ADMIN", "TECNICO", "LUBRIFICADOR", "DIRETORIA", "LIDER", "USUARIO"}
)


class UserCreate(BaseModel):
    nome_completo: str = Field(..., min_length=1, max_length=160)
    email: EmailCmms
    senha: str = Field(..., min_length=6, max_length=128)
    perfil_acesso: str
    ativo: bool = True
    permite_trocar_senha: bool = True
    custo_hora_interno: float = Field(default=0, ge=0, description="R$/h para custeio interno nos apontamentos.")

    @field_validator("perfil_acesso")
    @classmethod
    def perfil_ok(cls, v: str) -> str:
        if v not in PERFIS_VALIDOS:
            raise ValueError("Perfil invalido")
        return v


class UserUpdate(BaseModel):
    nome_completo: str | None = Field(default=None, min_length=1, max_length=160)
    email: EmailCmms | None = None
    senha: str | None = Field(default=None, min_length=6, max_length=128)
    perfil_acesso: str | None = None
    ativo: bool | None = None
    permite_trocar_senha: bool | None = None
    custo_hora_interno: float | None = Field(default=None, ge=0)

    @field_validator("perfil_acesso")
    @classmethod
    def perfil_ok(cls, v: str | None) -> str | None:
        if v is None:
            return None
        if v not in PERFIS_VALIDOS:
            raise ValueError("Perfil invalido")
        return v


class UserResponse(BaseModel):
    id: UUID
    nome_completo: str
    email: EmailCmms
    perfil_acesso: str
    ativo: bool
    permite_trocar_senha: bool = True
    custo_hora_interno: float = 0

    class Config:
        from_attributes = True
