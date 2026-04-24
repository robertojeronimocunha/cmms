from datetime import datetime
from decimal import Decimal
from typing import Optional
from uuid import UUID

from pydantic import BaseModel, Field


class EmulsionInspectionCreate(BaseModel):
    ativo_id: UUID
    valor_brix: Optional[Decimal] = None
    valor_ph: Optional[Decimal] = None
    volume_tanque_litros: Optional[Decimal] = None
    observacoes: Optional[str] = None


class EmulsionInspectionResponse(BaseModel):
    id: UUID
    status_inspecao: str
    precisa_correcao: bool
    volume_agua_sugerido: Optional[Decimal] = None
    volume_oleo_sugerido: Optional[Decimal] = None

    class Config:
        from_attributes = True


class EmulsionAssetItem(BaseModel):
    id: UUID
    tag_ativo: str
    descricao: str
    perfil_usinagem: str
    tanque_oleo_soluvel: int | None = None


class EmulsionInspectionListItem(BaseModel):
    id: UUID
    ativo_id: UUID
    tag_ativo: str
    perfil_usinagem: str
    data_inspecao: datetime
    valor_brix: Optional[Decimal] = None
    valor_ph: Optional[Decimal] = None
    status_inspecao: str
    precisa_correcao: bool
    volume_agua_sugerido: Optional[Decimal] = None
    volume_oleo_sugerido: Optional[Decimal] = None
    data_correcao: datetime | None = None


class EmulsionTaskItem(BaseModel):
    inspecao_id: UUID
    ativo_id: UUID
    tag_ativo: str
    perfil_usinagem: str
    data_inspecao: datetime
    volume_agua_sugerido: Optional[Decimal] = None
    volume_oleo_sugerido: Optional[Decimal] = None
    volume_agua_real: Optional[Decimal] = None
    volume_oleo_real: Optional[Decimal] = None
    status: str


class MedicaoEmulsaoResumo(BaseModel):
    """Uma medição (concentração Brix ou pH) com a data do registro."""

    valor: Decimal
    data_inspecao: datetime


class EmulsionUltimasMedicoesItem(BaseModel):
    """Última concentração e último pH por ativo (podem ser inspeções diferentes)."""

    ativo_id: UUID
    ultima_concentracao: MedicaoEmulsaoResumo | None = None
    ultima_ph: MedicaoEmulsaoResumo | None = None


class EmulsionCorrectionCreate(BaseModel):
    volume_agua_real: Decimal = Field(default=Decimal("0"), ge=0)
    volume_oleo_real: Decimal = Field(default=Decimal("0"), ge=0)
    observacoes: Optional[str] = None
