import uuid

from sqlalchemy import Column, DateTime, ForeignKey, String, Text, func
from sqlalchemy.dialects.postgresql import JSONB, UUID

from app.core.database import Base


class SystemLog(Base):
    __tablename__ = "logs_sistema"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    usuario_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=True)
    acao = Column(String(120), nullable=False)
    entidade = Column(String(120), nullable=True)
    entidade_id = Column(UUID(as_uuid=True), nullable=True)
    nivel = Column(String(20), nullable=False, default="INFO")
    detalhes = Column(JSONB, nullable=False, default=dict)
    ip_origem = Column(String(64), nullable=True)
    user_agent = Column(Text, nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
