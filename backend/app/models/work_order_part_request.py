import uuid

from sqlalchemy import Column, DateTime, ForeignKey, Numeric, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class WorkOrderPartRequest(Base):
    __tablename__ = "os_solicitacoes_pecas"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ordem_servico_id = Column(UUID(as_uuid=True), ForeignKey("ordens_servico.id"), nullable=False, index=True)
    solicitante_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=False, index=True)
    codigo_peca = Column(String(80), nullable=True)
    descricao = Column(Text, nullable=False)
    quantidade = Column(Numeric(14, 3), nullable=False)
    numero_solicitacao_erp = Column(String(80), nullable=True)
    preco_unitario = Column(Numeric(14, 2), nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
