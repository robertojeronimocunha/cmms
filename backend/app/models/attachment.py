import uuid

from sqlalchemy import BigInteger, Column, DateTime, ForeignKey, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class WorkOrderAttachment(Base):
    __tablename__ = "os_anexos"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ordem_servico_id = Column(UUID(as_uuid=True), ForeignKey("ordens_servico.id"), nullable=False, index=True)
    os_apontamento_id = Column(UUID(as_uuid=True), ForeignKey("os_apontamentos.id"), nullable=True, index=True)
    usuario_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=False)
    nome_arquivo = Column(String(255), nullable=False)
    caminho_arquivo = Column(Text, nullable=False)
    mime_type = Column(String(120), nullable=False)
    tamanho_bytes = Column(BigInteger, nullable=False)
    deleted_at = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
