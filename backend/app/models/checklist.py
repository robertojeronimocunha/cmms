import uuid

from sqlalchemy import Boolean, Column, DateTime, ForeignKey, Integer, String, Text, func
from sqlalchemy.dialects.postgresql import UUID

from app.core.database import Base


class ChecklistPadrao(Base):
    __tablename__ = "checklist_padrao"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    codigo_checklist = Column(String(40), nullable=False, unique=True)
    nome = Column(String(160), nullable=False)
    descricao = Column(Text, nullable=True)
    ativo = Column(Boolean, nullable=False, default=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())


class ChecklistTarefaPadrao(Base):
    __tablename__ = "checklist_tarefas_padrao"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    checklist_padrao_id = Column(UUID(as_uuid=True), ForeignKey("checklist_padrao.id"), nullable=False, index=True)
    ordem = Column(Integer, nullable=False, default=1)
    tarefa = Column(Text, nullable=False)
    obrigatoria = Column(Boolean, nullable=False, default=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())


class ChecklistExecutada(Base):
    __tablename__ = "checklist_executada"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    ordem_servico_id = Column(UUID(as_uuid=True), ForeignKey("ordens_servico.id"), nullable=False, index=True)
    os_apontamento_id = Column(UUID(as_uuid=True), ForeignKey("os_apontamentos.id"), nullable=True, index=True)
    checklist_padrao_id = Column(UUID(as_uuid=True), ForeignKey("checklist_padrao.id"), nullable=False, index=True)
    usuario_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=False)
    nome = Column(String(160), nullable=False)
    descricao = Column(Text, nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())


class ChecklistTarefaExecutada(Base):
    __tablename__ = "checklist_tarefas_executada"

    id = Column(UUID(as_uuid=True), primary_key=True, default=uuid.uuid4)
    checklist_executada_id = Column(UUID(as_uuid=True), ForeignKey("checklist_executada.id"), nullable=False, index=True)
    ordem = Column(Integer, nullable=False, default=1)
    tarefa = Column(Text, nullable=False)
    obrigatoria = Column(Boolean, nullable=False, default=True)
    executada = Column(Boolean, nullable=False, default=False)
    observacao = Column(Text, nullable=True)
    ultimo_preenchimento_por_id = Column(UUID(as_uuid=True), ForeignKey("usuarios.id"), nullable=True, index=True)
    ultimo_preenchimento_em = Column(DateTime(timezone=True), nullable=True)
    created_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now())
    updated_at = Column(DateTime(timezone=True), nullable=False, server_default=func.now(), onupdate=func.now())
