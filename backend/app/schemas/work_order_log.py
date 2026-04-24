from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, ConfigDict, Field, computed_field, field_validator, model_validator


class WorkOrderLogCreate(BaseModel):
    descricao: str = Field(min_length=3, max_length=8000)
    proximo_status: str | None = None
    data_inicio: datetime | None = None
    data_fim: datetime | None = None
    status_ativo: str | None = Field(
        default=None,
        description="Atualiza o status do ativo no cadastro: OPERANDO ou PARADO.",
    )

    @field_validator("status_ativo", mode="before")
    @classmethod
    def normalize_status_ativo(cls, v: object) -> str | None:
        if v is None or v == "":
            return None
        if not isinstance(v, str):
            return None
        s = v.strip().upper()
        return s if s else None

    @field_validator("status_ativo")
    @classmethod
    def validate_status_ativo(cls, v: str | None) -> str | None:
        if v is None:
            return None
        if v not in frozenset({"OPERANDO", "PARADO"}):
            raise ValueError("status_ativo deve ser OPERANDO ou PARADO")
        return v

    @model_validator(mode="after")
    def validate_period(self):
        if self.data_inicio and self.data_fim and self.data_fim < self.data_inicio:
            raise ValueError("data_fim nao pode ser menor que data_inicio")
        return self


class WorkOrderLogResponse(BaseModel):
    model_config = ConfigDict(from_attributes=True)

    id: UUID
    ordem_servico_id: UUID
    usuario_id: UUID
    usuario_nome: str | None = None
    status_anterior: str
    status_novo: str
    descricao: str
    data_inicio: datetime | None = None
    data_fim: datetime | None = None
    created_at: datetime

    @computed_field
    @property
    def horas_trabalhadas(self) -> float:
        di, df = self.data_inicio, self.data_fim
        if di is None or df is None:
            return 0.0
        secs = max((df - di).total_seconds(), 0.0)
        return round(secs / 3600.0, 4)
