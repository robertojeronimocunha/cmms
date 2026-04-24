import uuid

from sqlalchemy import Boolean, Column, DateTime, String, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class Lubricant(Base):
    __tablename__ = "lubrificantes"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    codigo_erp = Column(String(40), nullable=False, unique=True, index=True)
    nome = Column(String(120), nullable=False, unique=True)
    fabricante = Column(String(120), nullable=True)
    especificacao = Column(String(120), nullable=True)
    ativo = Column(Boolean, nullable=False, default=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
