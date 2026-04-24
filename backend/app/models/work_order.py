import uuid

from sqlalchemy import Boolean, Column, DateTime, Enum, ForeignKey, Numeric, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class WorkOrder(Base):
    __tablename__ = "ordens_servico"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    codigo_os = Column(String(40), nullable=False, unique=True)
    ativo_id = Column(UUID(as_uuid=True), ForeignKey("ativos.id"), nullable=False)
    solicitante_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=False)
    tecnico_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=True)
    tipo_manutencao = Column(
        Enum("CORRETIVA", "PREVENTIVA", "PREDITIVA", "MELHORIA", "INSPECAO", name="tipo_manutencao_enum"),
        nullable=False,
    )
    prioridade = Column(Enum("BAIXA", "MEDIA", "ALTA", "URGENTE", name="prioridade_os_enum"), nullable=False)
    status = Column(
        Enum(
            "ABERTA",
            "AGENDADA",
            "EM_EXECUCAO",
            "AGUARDANDO_PECA",
            "AGUARDANDO_TERCEIRO",
            "AGUARDANDO_APROVACAO",
            "FINALIZADA",
            "CANCELADA",
            name="os_status_enum",
        ),
        nullable=False,
        default="ABERTA",
    )
    data_abertura = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    data_agendamento = Column(DateTime(timezone=True), nullable=True)
    data_inicio_real = Column(DateTime(timezone=True), nullable=True)
    data_conclusao_real = Column(DateTime(timezone=True), nullable=True)
    falha_sintoma = Column(Text, nullable=True)
    causa_raiz = Column(Text, nullable=True)
    solucao = Column(Text, nullable=True)
    observacoes = Column(Text, nullable=True)
    consolidada = Column(Boolean, nullable=False, default=False, server_default="false")
    consolidada_em = Column(DateTime(timezone=True), nullable=True)
    consolidada_por_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=True)
    tag_defeito = Column(String(120), nullable=True)
    custo_internos = Column(Numeric(12, 2), nullable=False, default=0, server_default="0")
    custo_terceiros = Column(Numeric(12, 2), nullable=False, default=0, server_default="0")
    custo_pecas = Column(Numeric(12, 2), nullable=False, default=0, server_default="0")
    custo_total = Column(Numeric(12, 2), nullable=False, default=0, server_default="0")
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
