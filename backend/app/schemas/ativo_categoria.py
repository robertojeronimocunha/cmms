from uuid import UUID

from pydantic import BaseModel, Field, field_validator


class AtivoCategoriaCreate(BaseModel):
    nome: str = Field(..., min_length=1, max_length=120)
    ordem: int = Field(default=0, ge=0, le=9999)

    @field_validator("nome")
    @classmethod
    def _strip_nome(cls, v: str) -> str:
        return v.strip()


class AtivoCategoriaUpdate(BaseModel):
    nome: str | None = Field(default=None, min_length=1, max_length=120)
    ordem: int | None = Field(default=None, ge=0, le=9999)

    @field_validator("nome")
    @classmethod
    def _strip_nome_opt(cls, v: str | None) -> str | None:
        if v is None:
            return v
        s = v.strip()
        return s if s else None


class AtivoCategoriaResponse(BaseModel):
    id: UUID
    nome: str
    ordem: int

    class Config:
        from_attributes = True
