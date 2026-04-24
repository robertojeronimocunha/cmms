from decimal import Decimal
from uuid import UUID

from pydantic import BaseModel


class PartBase(BaseModel):
    codigo_interno: str
    descricao: str
    fabricante: str | None = None
    estoque_atual: Decimal = Decimal("0")
    estoque_minimo: Decimal = Decimal("0")
    controla_estoque: bool = False
    localizacao_almoxarifado: str | None = None


class PartCreate(PartBase):
    pass


class PartUpdate(BaseModel):
    descricao: str | None = None
    estoque_atual: Decimal | None = None
    estoque_minimo: Decimal | None = None
    controla_estoque: bool | None = None
    localizacao_almoxarifado: str | None = None


class PartResponse(PartBase):
    id: UUID

    class Config:
        from_attributes = True


class PartCatalogImportRowError(BaseModel):
    linha: int
    detalhe: str


class PartCatalogImportResult(BaseModel):
    """Resultado de importação em massa (upsert por `codigo_interno`; UUIDs preservados)."""

    inseridos: int
    atualizados: int
    linhas_ignoradas: int
    erros: list[PartCatalogImportRowError]
