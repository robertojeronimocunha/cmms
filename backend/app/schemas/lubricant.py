from uuid import UUID

from pydantic import BaseModel, Field


class LubricantCreate(BaseModel):
    codigo_erp: str = Field(max_length=40)
    nome: str = Field(max_length=120)
    fabricante: str | None = Field(default=None, max_length=120)
    especificacao: str | None = Field(default=None, max_length=120)


class LubricantUpdate(BaseModel):
    codigo_erp: str | None = Field(default=None, max_length=40)
    nome: str | None = Field(default=None, max_length=120)
    fabricante: str | None = Field(default=None, max_length=120)
    especificacao: str | None = Field(default=None, max_length=120)
    ativo: bool | None = None


class LubricantResponse(BaseModel):
    id: UUID
    codigo_erp: str
    nome: str
    fabricante: str | None
    especificacao: str | None
    ativo: bool

    class Config:
        from_attributes = True
