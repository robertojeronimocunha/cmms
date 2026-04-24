import uuid

from sqlalchemy import Column, DateTime, ForeignKey, Integer, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class LubricationPoint(Base):
    __tablename__ = "pontos_lubrificacao"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ativo_id = Column(UUID(as_uuid=True), ForeignKey("ativos.id"), nullable=False)
    lubrificante_id = Column(UUID(as_uuid=True), ForeignKey("lubrificantes.id"), nullable=True)
    descricao_ponto = Column(String(180), nullable=False)
    periodicidade_dias = Column(Integer, nullable=False)
    ultima_execucao = Column(DateTime(timezone=True), nullable=True)
    proxima_execucao = Column(DateTime(timezone=True), nullable=True)
    observacoes = Column(Text, nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
