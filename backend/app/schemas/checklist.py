from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, Field


class ChecklistPadraoCreate(BaseModel):
    codigo_checklist: str = Field(min_length=2, max_length=40)
    nome: str = Field(min_length=3, max_length=160)
    descricao: str | None = None
    ativo: bool = True


class ChecklistPadraoUpdate(BaseModel):
    codigo_checklist: str | None = Field(default=None, min_length=2, max_length=40)
    nome: str | None = Field(default=None, min_length=3, max_length=160)
    descricao: str | None = None
    ativo: bool | None = None


class ChecklistPadraoResponse(BaseModel):
    id: UUID
    codigo_checklist: str
    nome: str
    descricao: str | None = None
    ativo: bool
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ChecklistTarefaPadraoCreate(BaseModel):
    ordem: int = Field(default=1, ge=1, le=999)
    tarefa: str = Field(min_length=3, max_length=4000)
    obrigatoria: bool = True


class ChecklistTarefaPadraoUpdate(BaseModel):
    ordem: int | None = Field(default=None, ge=1, le=999)
    tarefa: str | None = Field(default=None, min_length=3, max_length=4000)
    obrigatoria: bool | None = None


class ChecklistTarefaPadraoResponse(BaseModel):
    id: UUID
    checklist_padrao_id: UUID
    ordem: int
    tarefa: str
    obrigatoria: bool
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ChecklistExecutadaCreate(BaseModel):
    checklist_padrao_id: UUID


class ChecklistExecutadaResponse(BaseModel):
    id: UUID
    ordem_servico_id: UUID
    os_apontamento_id: UUID | None = None
    checklist_padrao_id: UUID
    usuario_id: UUID
    nome: str
    descricao: str | None = None
    codigo_checklist: str | None = None
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ChecklistTarefaExecutadaUpdate(BaseModel):
    executada: bool | None = None
    observacao: str | None = Field(default=None, max_length=2000)


class ChecklistTarefaExecutadaResponse(BaseModel):
    id: UUID
    checklist_executada_id: UUID
    ordem: int
    tarefa: str
    obrigatoria: bool
    executada: bool
    observacao: str | None = None
    ultimo_preenchimento_por_id: UUID | None = None
    ultimo_preenchimento_em: datetime | None = None
    ultimo_preenchimento_por_nome: str | None = None
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


class ChecklistObrigatorioStatusResponse(BaseModel):
    codigo_checklist: str
    concluido: bool
    checklist_padrao_ativo: bool
    pendencias_obrigatorias: int = 0
    # Só preenchido para FINALIZACAO_OS: checklist concluído numa execução copiada por usuário LIDER.
    concluido_copia_lider: bool | None = None


class ChecklistHistoricoItem(BaseModel):
    id: UUID
    ordem_servico_id: UUID
    os_apontamento_id: UUID | None = None
    checklist_padrao_id: UUID
    codigo_checklist: str
    usuario_id: UUID
    usuario_nome: str | None = None
    nome: str
    descricao: str | None = None
    concluido: bool = False
    pendencias_obrigatorias: int = 0
    created_at: datetime
    # Preenchido quando GET .../historico?incluir_tarefas=true (itens da execução para leitura).
    tarefas: list[ChecklistTarefaExecutadaResponse] = Field(default_factory=list)

    class Config:
        from_attributes = True


class GarantirChecklistsPadroesResponse(BaseModel):
    criadas: int
    checklist_executada_ids: list[UUID]
