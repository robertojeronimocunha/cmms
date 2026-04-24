import uuid

from sqlalchemy import Boolean, Column, DateTime, ForeignKey, String, func
from sqlalchemy.dialects.postgresql import UUID
from sqlalchemy.orm import relationship

from app.core.database import Base


class Setor(Base):
    __tablename__ = "setores"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    tag_setor = Column(String(32), nullable=False, index=True)
    descricao = Column(String(200), nullable=False)
    responsavel1_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=True)
    responsavel2_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=True)
    ativo = Column(Boolean, nullable=False, default=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())

    ativos = relationship("Asset", back_populates="setor_rel")
    responsaveis_assoc = relationship(
        "SetorResponsavel",
        back_populates="setor",
        cascade="all, delete-orphan",
        order_by="SetorResponsavel.ordem",
    )
