import uuid

from sqlalchemy import Column, DateTime, ForeignKey, Numeric, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class LubricationExecution(Base):
    """Registo de cada execução de lubrificação (óleo aplicado + observação da ronda)."""

    __tablename__ = "lubrificacao_execucoes"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ponto_lubrificacao_id = Column(
        UUID(as_uuid=True),
        ForeignKey("pontos_lubrificacao.id", ondelete="CASCADE"),
        nullable=False,
    )
    usuario_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id", ondelete="SET NULL"), nullable=True)
    executado_em = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    quantidade_oleo_litros = Column(Numeric(12, 3), nullable=False)
    observacao = Column(Text, nullable=True)
