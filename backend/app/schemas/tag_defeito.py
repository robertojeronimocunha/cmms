from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, Field


class TagDefeitoCreate(BaseModel):
    codigo: str = Field(min_length=2, max_length=80)
    descricao: str = Field(min_length=3, max_length=500)
    ativo: bool = True


class TagDefeitoUpdate(BaseModel):
    codigo: str | None = Field(default=None, min_length=2, max_length=80)
    descricao: str | None = Field(default=None, min_length=3, max_length=500)
    ativo: bool | None = None


class TagDefeitoResponse(BaseModel):
    id: UUID
    codigo: str
    descricao: str
    ativo: bool
    created_at: datetime

    class Config:
        from_attributes = True
