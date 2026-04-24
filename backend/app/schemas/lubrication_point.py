from datetime import datetime
from decimal import Decimal
from uuid import UUID

from pydantic import BaseModel, Field, field_validator


class LubricationPointCreate(BaseModel):
    ativo_id: UUID
    lubrificante_id: UUID | None = None
    descricao_ponto: str = Field(max_length=180)
    periodicidade_dias: int = Field(ge=1, le=3650)
    proxima_execucao: datetime | None = None
    observacoes: str | None = None


class LubricationPointUpdate(BaseModel):
    lubrificante_id: UUID | None = None
    descricao_ponto: str | None = Field(default=None, max_length=180)
    periodicidade_dias: int | None = Field(default=None, ge=1, le=3650)
    proxima_execucao: datetime | None = None
    observacoes: str | None = None


class ExecutarLubricacaoRequest(BaseModel):
    """Corpo de POST /pontos-lubrificacao/{id}/executar."""

    quantidade_oleo_litros: Decimal = Field(..., gt=Decimal("0"), le=Decimal("999999.999"))
    observacao: str | None = Field(default=None, max_length=2000)

    @field_validator("observacao")
    @classmethod
    def _strip_obs(cls, v: str | None) -> str | None:
        if v is None:
            return None
        s = v.strip()
        return s if s else None


class LubricationPointResponse(BaseModel):
    id: UUID
    ativo_id: UUID
    lubrificante_id: UUID | None
    descricao_ponto: str
    periodicidade_dias: int
    ultima_execucao: datetime | None
    proxima_execucao: datetime | None
    observacoes: str | None
    tag_ativo: str | None = None
    lubrificante_nome: str | None = None

    class Config:
        from_attributes = True


class LubricationExecutionListResponse(BaseModel):
    """Item de GET /pontos-lubrificacao/execucoes (histórico de lubrificações)."""

    id: UUID
    ponto_lubrificacao_id: UUID
    tag_ativo: str | None = None
    descricao_ponto: str
    lubrificante_nome: str | None = None
    executado_em: datetime
    quantidade_oleo_litros: Decimal
    observacao: str | None = None
    usuario_nome: str | None = None

    class Config:
        from_attributes = True
