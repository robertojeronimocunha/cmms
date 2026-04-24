import uuid

from sqlalchemy import Boolean, Column, DateTime, Enum, Numeric, String, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class User(Base):
    __tablename__ = "usuarios"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    nome_completo = Column(String(160), nullable=False)
    email = Column(String(180), nullable=False, unique=True, index=True)
    senha_hash = Column(String, nullable=False)
    perfil_acesso = Column(
        Enum(
            "ADMIN",
            "TECNICO",
            "LUBRIFICADOR",
            "DIRETORIA",
            "LIDER",
            "USUARIO",
            name="perfil_acesso_enum",
        ),
        nullable=False,
    )
    ativo = Column(Boolean, nullable=False, default=True)
    permite_trocar_senha = Column(Boolean, nullable=False, default=True, server_default="true")
    custo_hora_interno = Column(Numeric(12, 2), nullable=False, default=0, server_default="0")
    ultimo_login = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
