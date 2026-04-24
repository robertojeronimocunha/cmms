import uuid

from sqlalchemy import Boolean, Column, DateTime, Numeric, String, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class Part(Base):
    __tablename__ = "pecas"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    codigo_interno = Column(String(80), nullable=False, unique=True, index=True)
    descricao = Column(String(200), nullable=False)
    fabricante = Column(String(120), nullable=True)
    estoque_atual = Column(Numeric(14, 3), nullable=False, default=0)
    estoque_minimo = Column(Numeric(14, 3), nullable=False, default=0)
    controla_estoque = Column(Boolean, nullable=False, default=False, server_default="false")
    localizacao_almoxarifado = Column(String(120), nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
