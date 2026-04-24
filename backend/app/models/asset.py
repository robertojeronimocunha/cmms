import uuid

from sqlalchemy import Boolean, Column, Date, DateTime, Enum, ForeignKey, Integer, Numeric, SmallInteger, String, func
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship

from app.core.database import Base


class Asset(Base):
    __tablename__ = "ativos"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tag_ativo = Column(String(80), nullable=False, unique=True, index=True)
    descricao = Column(String(200), nullable=False)
    fabricante = Column(String(120), nullable=True)
    modelo = Column(String(120), nullable=True)
    numero_serie = Column(String(120), nullable=False)
    categoria_id = Column(UUID(as_uuid=True), ForeignKey("ativo_categorias.id"), nullable=True, index=True)
    categoria_rel = relationship("AtivoCategoria", lazy="joined")
    setor_id = Column(UUID(as_uuid=True), ForeignKey("setores.id"), nullable=True, index=True)
    setor_rel = relationship("Setor", back_populates="ativos", lazy="joined")
    criticidade = Column(Enum("BAIXA", "MEDIA", "ALTA", "CRITICA", name="criticidade_enum"), nullable=False, default="MEDIA")
    status = Column(Enum("OPERANDO", "PARADO", "MANUTENCAO", "INATIVO", name="ativo_status_enum"), nullable=False, default="OPERANDO")
    horimetro_acumulado = Column(Integer, nullable=False, default=0, server_default="0")
    controle_lubrificacao = Column(Boolean, nullable=False, default=False)
    controle_emulsao = Column(Boolean, nullable=False, default=False)
    tanque_oleo_soluvel = Column(Integer, nullable=True)
    data_instalacao = Column(Date, nullable=True)
    data_garantia = Column(Date, nullable=True)
    turnos = Column(SmallInteger, nullable=True)
    participa_metricas = Column(Boolean, nullable=False, default=False, server_default="false")
    perfil_usinagem = Column(
        Enum("LEVE", "PESADO", name="perfil_usinagem_enum", native_enum=False),
        nullable=False,
        default="LEVE",
        server_default="LEVE",
    )
    emulsao_ultima_concentracao = Column(Numeric(8, 3), nullable=True)
    emulsao_ultima_concentracao_em = Column(DateTime(timezone=True), nullable=True)
    emulsao_ultimo_ph = Column(Numeric(8, 3), nullable=True)
    emulsao_ultimo_ph_em = Column(DateTime(timezone=True), nullable=True)
    cnc_tipo_maquina = Column(String(40), nullable=True)
    cnc_cursos_xyz_mm = Column(String(80), nullable=True)
    cnc_aceleracao_ms2 = Column(Numeric(10, 2), nullable=True)
    cnc_eixo_4 = Column(String(500), nullable=True)
    cnc_eixo_5 = Column(String(500), nullable=True)
    cnc_rpm_maximo = Column(Integer, nullable=True)
    cnc_cone = Column(String(120), nullable=True)
    cnc_pino_fixacao = Column(String(120), nullable=True)
    cnc_tempo_troca_ferramenta_s = Column(Numeric(8, 2), nullable=True)
    cnc_unifilar = Column(String(255), nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())

    @property
    def categoria_nome(self) -> str | None:
        if self.categoria_rel is None:
            return None
        return self.categoria_rel.nome

    @property
    def setor_nome(self) -> str | None:
        """Texto para exibição na lista de ativos (tag + descrição)."""
        if self.setor_rel is None:
            return None
        return f"{self.setor_rel.tag_setor} — {self.setor_rel.descricao}"

    @property
    def setor_tag(self) -> str | None:
        if self.setor_rel is None:
            return None
        return self.setor_rel.tag_setor
