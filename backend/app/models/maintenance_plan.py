import uuid

from sqlalchemy import Boolean, Column, DateTime, ForeignKey, Integer, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class MaintenancePlan(Base):
    __tablename__ = "planos_manutencao"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ativo_id = Column(UUID(as_uuid=True), ForeignKey("ativos.id"), nullable=False)
    titulo = Column(String(160), nullable=False)
    descricao = Column(Text, nullable=True)
    periodicidade_dias = Column(Integer, nullable=False)
    ultima_execucao = Column(DateTime(timezone=True), nullable=True)
    proxima_execucao = Column(DateTime(timezone=True), nullable=True)
    ativo = Column(Boolean, nullable=False, default=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
