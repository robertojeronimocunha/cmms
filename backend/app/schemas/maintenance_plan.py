from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, Field

from app.schemas.work_order import WorkOrderResponse


class MaintenancePlanCreate(BaseModel):
    ativo_id: UUID
    titulo: str = Field(max_length=160)
    descricao: str | None = None
    periodicidade_dias: int = Field(ge=1, le=3650)
    proxima_execucao: datetime | None = None


class MaintenancePlanUpdate(BaseModel):
    ativo_id: UUID | None = None
    titulo: str | None = Field(default=None, max_length=160)
    descricao: str | None = None
    periodicidade_dias: int | None = Field(default=None, ge=1, le=3650)
    proxima_execucao: datetime | None = None
    ativo: bool | None = None


class MaintenancePlanResponse(BaseModel):
    id: UUID
    ativo_id: UUID
    titulo: str
    descricao: str | None
    periodicidade_dias: int
    ultima_execucao: datetime | None
    proxima_execucao: datetime | None
    ativo: bool
    tag_ativo: str | None = None

    class Config:
        from_attributes = True


class ExecutarPreventivaResponse(BaseModel):
    """Resposta de POST /preventivas/{id}/executar: plano atualizado + OS preventiva gerada com checklists."""

    plano: MaintenancePlanResponse
    ordem_servico: WorkOrderResponse
