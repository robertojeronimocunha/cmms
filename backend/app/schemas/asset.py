from datetime import date, datetime
from decimal import Decimal
from uuid import UUID

from pydantic import BaseModel, Field, field_validator, model_validator


class AssetBase(BaseModel):
    tag_ativo: str = Field(..., max_length=80)
    descricao: str = Field(..., max_length=200)
    fabricante: str | None = Field(default=None, max_length=120)
    modelo: str | None = Field(default=None, max_length=120)
    numero_serie: str = Field(..., max_length=120)
    categoria_id: UUID | None = None
    setor_id: UUID | None = None
    criticidade: str = Field(default="MEDIA", pattern="^(BAIXA|MEDIA|ALTA|CRITICA)$")
    status: str = Field(
        default="OPERANDO",
        pattern="^(OPERANDO|PARADO|MANUTENCAO|INATIVO)$",
    )
    horimetro_acumulado: int = Field(default=0, ge=0)
    controle_lubrificacao: bool = False
    controle_emulsao: bool = False
    tanque_oleo_soluvel: int | None = Field(default=None, ge=0)
    data_instalacao: date | None = None
    data_garantia: date | None = None
    turnos: int | None = Field(default=None, ge=1, le=3)
    participa_metricas: bool = False
    perfil_usinagem: str = Field(default="LEVE", pattern="^(LEVE|PESADO)$")
    cnc_tipo_maquina: str | None = Field(default=None, max_length=40)
    cnc_cursos_xyz_mm: str | None = Field(default=None, max_length=80)
    cnc_aceleracao_ms2: Decimal | None = Field(default=None, ge=0)
    cnc_eixo_4: str | None = Field(default=None, max_length=500)
    cnc_eixo_5: str | None = Field(default=None, max_length=500)
    cnc_rpm_maximo: int | None = Field(default=None, ge=0)
    cnc_cone: str | None = Field(default=None, max_length=120)
    cnc_pino_fixacao: str | None = Field(default=None, max_length=120)
    cnc_tempo_troca_ferramenta_s: Decimal | None = Field(default=None, ge=0)
    cnc_unifilar: str | None = Field(default=None, max_length=255)

    @field_validator(
        "cnc_tipo_maquina",
        "cnc_cursos_xyz_mm",
        "cnc_eixo_4",
        "cnc_eixo_5",
        "cnc_cone",
        "cnc_pino_fixacao",
        "cnc_unifilar",
        mode="before",
    )
    @classmethod
    def _strip_opcionais_str(cls, v):
        if v is None or v == "":
            return None
        if isinstance(v, str):
            s = v.strip()
            return s if s else None
        return v

    @model_validator(mode="after")
    def _tanque_emulsao(self):
        if self.controle_emulsao:
            if self.tanque_oleo_soluvel is None or self.tanque_oleo_soluvel <= 0:
                raise ValueError("Com controle de emulsao ativo, informe tanque_oleo_soluvel (inteiro > 0)")
        return self


class AssetCreate(AssetBase):
    pass


class AssetUpdate(BaseModel):
    tag_ativo: str | None = Field(default=None, max_length=80)
    descricao: str | None = Field(default=None, max_length=200)
    fabricante: str | None = Field(default=None, max_length=120)
    modelo: str | None = Field(default=None, max_length=120)
    numero_serie: str | None = Field(default=None, max_length=120)
    categoria_id: UUID | None = None
    setor_id: UUID | None = None
    criticidade: str | None = Field(default=None, pattern="^(BAIXA|MEDIA|ALTA|CRITICA)$")
    status: str | None = Field(
        default=None,
        pattern="^(OPERANDO|PARADO|MANUTENCAO|INATIVO)$",
    )
    horimetro_acumulado: int | None = Field(default=None, ge=0)
    controle_lubrificacao: bool | None = None
    controle_emulsao: bool | None = None
    tanque_oleo_soluvel: int | None = Field(default=None, ge=0)
    data_instalacao: date | None = None
    data_garantia: date | None = None
    turnos: int | None = Field(default=None, ge=1, le=3)
    participa_metricas: bool | None = None
    perfil_usinagem: str | None = Field(default=None, pattern="^(LEVE|PESADO)$")
    cnc_tipo_maquina: str | None = Field(default=None, max_length=40)
    cnc_cursos_xyz_mm: str | None = Field(default=None, max_length=80)
    cnc_aceleracao_ms2: Decimal | None = Field(default=None, ge=0)
    cnc_eixo_4: str | None = Field(default=None, max_length=500)
    cnc_eixo_5: str | None = Field(default=None, max_length=500)
    cnc_rpm_maximo: int | None = Field(default=None, ge=0)
    cnc_cone: str | None = Field(default=None, max_length=120)
    cnc_pino_fixacao: str | None = Field(default=None, max_length=120)
    cnc_tempo_troca_ferramenta_s: Decimal | None = Field(default=None, ge=0)
    cnc_unifilar: str | None = Field(default=None, max_length=255)

    @field_validator(
        "cnc_tipo_maquina",
        "cnc_cursos_xyz_mm",
        "cnc_eixo_4",
        "cnc_eixo_5",
        "cnc_cone",
        "cnc_pino_fixacao",
        "cnc_unifilar",
        mode="before",
    )
    @classmethod
    def _strip_opcionais_str_update(cls, v):
        if v is None or v == "":
            return None
        if isinstance(v, str):
            s = v.strip()
            return s if s else None
        return v


class AssetResponse(AssetBase):
    id: UUID
    emulsao_ultima_concentracao: Decimal | None = None
    emulsao_ultima_concentracao_em: datetime | None = None
    emulsao_ultimo_ph: Decimal | None = None
    emulsao_ultimo_ph_em: datetime | None = None
    categoria_nome: str | None = None
    setor_nome: str | None = None
    setor_tag: str | None = None
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True
