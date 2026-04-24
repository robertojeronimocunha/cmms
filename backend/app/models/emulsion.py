import uuid

from sqlalchemy import Boolean, Column, DateTime, ForeignKey, Numeric, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class EmulsionInspection(Base):
    __tablename__ = "inspecoes_emulsao"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ativo_id = Column(UUID(as_uuid=True), ForeignKey("ativos.id"), nullable=False)
    tecnico_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=False)
    data_inspecao = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    valor_brix = Column(Numeric(8, 3), nullable=False)
    valor_ph = Column(Numeric(8, 3), nullable=False)
    temperatura_emulsao = Column(Numeric(8, 3), nullable=True)
    volume_tanque_litros = Column(Numeric(12, 2), nullable=True)
    status_inspecao = Column(String(40), nullable=False)
    observacoes = Column(Text, nullable=True)
    precisa_correcao = Column(Boolean, nullable=False, default=False)
    volume_agua_sugerido = Column(Numeric(12, 3), nullable=True)
    volume_oleo_sugerido = Column(Numeric(12, 3), nullable=True)
    volume_agua_real = Column(Numeric(12, 3), nullable=True)
    volume_oleo_real = Column(Numeric(12, 3), nullable=True)
    data_correcao = Column(DateTime(timezone=True), nullable=True)
    foto_teste = Column(Text, nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
