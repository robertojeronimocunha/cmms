from datetime import date, datetime
from typing import Optional
from uuid import UUID

from pydantic import BaseModel, Field


class WorkOrderCreate(BaseModel):
    codigo_os: str
    ativo_id: UUID
    tipo_manutencao: str
    prioridade: str
    falha_sintoma: Optional[str] = None
    observacoes: Optional[str] = None
    marcar_ativo_parado: bool = Field(
        default=False,
        description="Se True, grava o ativo como PARADO no cadastro (máquina parada).",
    )


class WorkOrderStatusUpdate(BaseModel):
    status: str
    observacao: Optional[str] = None


class WorkOrderResponse(BaseModel):
    id: UUID
    codigo_os: str
    ativo_id: UUID
    solicitante_id: UUID
    solicitante_nome: str | None = None
    status: str
    prioridade: str
    tipo_manutencao: str | None = None
    falha_sintoma: str | None = None
    observacoes: str | None = None
    data_abertura: datetime
    tag_ativo: str | None = None
    ativo_descricao: str | None = None
    setor_nome: str | None = None
    ativo_fabricante: str | None = None
    ativo_modelo: str | None = None
    ativo_numero_serie: str | None = None
    ativo_data_garantia: date | None = None
    ativo_status: str | None = None
    ativo_criticidade: str | None = None
    consolidada: bool = False
    consolidada_em: datetime | None = None
    consolidada_por_id: UUID | None = None
    tag_defeito: str | None = None
    custo_internos: float = 0
    custo_terceiros: float = 0
    custo_pecas: float = 0
    custo_total: float = 0

    class Config:
        from_attributes = True
