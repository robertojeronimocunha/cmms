from uuid import UUID

from pydantic import BaseModel, Field, field_validator


class SetorResponsavelItem(BaseModel):
    usuario_id: UUID
    nome_completo: str | None = None


class SetorCreate(BaseModel):
    tag_setor: str = Field(..., min_length=1, max_length=32)
    descricao: str = Field(..., min_length=1, max_length=200)
    responsavel_ids: list[UUID] = Field(default_factory=list, max_length=30)
    ativo: bool = True

    @field_validator("responsavel_ids")
    @classmethod
    def _unique_responsaveis(cls, v: list[UUID]) -> list[UUID]:
        seen: set[UUID] = set()
        for x in v:
            if x in seen:
                raise ValueError("Lista de responsaveis nao pode ter duplicados")
            seen.add(x)
        return v


class SetorUpdate(BaseModel):
    tag_setor: str | None = Field(default=None, min_length=1, max_length=32)
    descricao: str | None = Field(default=None, min_length=1, max_length=200)
    responsavel_ids: list[UUID] | None = Field(default=None, max_length=30)
    ativo: bool | None = None

    @field_validator("responsavel_ids")
    @classmethod
    def _unique_responsaveis_upd(cls, v: list[UUID] | None) -> list[UUID] | None:
        if v is None:
            return v
        seen: set[UUID] = set()
        for x in v:
            if x in seen:
                raise ValueError("Lista de responsaveis nao pode ter duplicados")
            seen.add(x)
        return v


class SetorResponse(BaseModel):
    id: UUID
    tag_setor: str
    descricao: str
    responsaveis: list[SetorResponsavelItem] = Field(default_factory=list)
    responsavel1_id: UUID | None = None
    responsavel1_nome: str | None = None
    responsavel2_id: UUID | None = None
    responsavel2_nome: str | None = None
    ativo: bool

    class Config:
        from_attributes = True
