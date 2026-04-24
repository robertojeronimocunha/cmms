from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, Field


class WorkOrderPartRequestCreate(BaseModel):
    """Cópia dos dados no momento do pedido (`codigo_peca` / `descricao`); não há vínculo ao catálogo na OS."""

    codigo_peca: str | None = Field(default=None, max_length=80)
    descricao: str = Field(min_length=3, max_length=4000)
    quantidade: float = Field(gt=0)


class WorkOrderPartRequestUpdateAdmin(BaseModel):
    """Campos opcionais: apenas os enviados são atualizados. Perfil ADMIN."""

    codigo_peca: str | None = Field(default=None, max_length=80)
    descricao: str | None = Field(default=None, min_length=3, max_length=4000)
    quantidade: float | None = Field(default=None, gt=0)
    numero_solicitacao_erp: str | None = Field(default=None, max_length=80)
    preco_unitario: float | None = Field(default=None, ge=0)


class WorkOrderPartRequestResponse(BaseModel):
    id: UUID
    ordem_servico_id: UUID
    solicitante_id: UUID
    solicitante_nome: str | None = None
    codigo_peca: str | None = None
    descricao: str
    quantidade: float
    numero_solicitacao_erp: str | None = None
    preco_unitario: float | None = None
    created_at: datetime

    class Config:
        from_attributes = True
