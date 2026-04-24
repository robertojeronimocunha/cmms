from sqlalchemy import Column, ForeignKey, Integer
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship

from app.core.database import Base


class SetorResponsavel(Base):
    """Vínculo setor ↔ usuário responsável (ordem preserva a sequência no cadastro)."""

    __tablename__ = "setor_responsaveis"

    setor_id = Column(UUID(as_uuid=True), ForeignKey("setores.id", ondelete="CASCADE"), primary_key=True)
    usuario_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id", ondelete="CASCADE"), primary_key=True)
    ordem = Column(Integer, nullable=False, default=0, server_default="0")

    setor = relationship("Setor", back_populates="responsaveis_assoc")
