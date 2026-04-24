import uuid

from sqlalchemy import Boolean, Column, DateTime, ForeignKey, Integer, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class AgendadorTarefa(Base):
    __tablename__ = "agendador_tarefas"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    chave = Column(String(64), nullable=False, unique=True, index=True)
    titulo = Column(String(240), nullable=False)
    ativo = Column(Boolean, nullable=False, default=True)
    intervalo_minutos = Column(Integer, nullable=False)
    solicitante_usuario_id = Column(
        UUID(as_uuid=True),
        ForeignKey("usuarios.id", ondelete="SET NULL"),
        nullable=True,
    )
    ultima_execucao_em = Column(DateTime(timezone=True), nullable=True)
    proxima_execucao_em = Column(DateTime(timezone=True), nullable=True)
    ultimo_ok = Column(Boolean, nullable=True)
    ultimo_mensagem = Column(Text, nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
