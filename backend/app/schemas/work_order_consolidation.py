from datetime import datetime
from uuid import UUID

from pydantic import BaseModel, Field

from app.schemas.work_order_part_request import WorkOrderPartRequestUpdateAdmin


class WorkOrderConsolidationPartUpdate(BaseModel):
    request_id: UUID
    dados: WorkOrderPartRequestUpdateAdmin


class WorkOrderConsolidationUpsert(BaseModel):
    tag_defeito: str | None = Field(default=None, max_length=120)
    causa_raiz: str | None = None
    solucao: str | None = None
    observacoes: str | None = None
    custo_internos: float = Field(default=0, ge=0)
    custo_terceiros: float = Field(default=0, ge=0)
    custo_pecas: float = Field(default=0, ge=0)
    custo_total: float | None = Field(default=None, ge=0)
    ajustes_pecas: list[WorkOrderConsolidationPartUpdate] = Field(default_factory=list)


class WorkOrderConsolidationSalvarPecasPayload(BaseModel):
    """Salva ajustes nas solicitações de peças, recalcula custo de peças (Σ qtde × preço) e custo total na OS, sem consolidar."""

    custo_internos: float | None = Field(default=None, ge=0, description="Se omitido, mantém o valor gravado na OS.")
    custo_terceiros: float | None = Field(default=None, ge=0, description="Se omitido, mantém o valor gravado na OS.")
    ajustes_pecas: list[WorkOrderConsolidationPartUpdate] = Field(default_factory=list)


class WorkOrderConsolidationResumo(BaseModel):
    horas_aberta: float
    horas_agendada: float
    horas_em_execucao: float
    horas_aguardando_peca: float
    horas_aguardando_terceiro: float
    horas_aguardando_aprovacao: float


class WorkOrderConsolidationOsResumo(BaseModel):
    """Ficha da OS para análise antes de consolidar."""

    id: UUID
    codigo_os: str
    ativo_id: UUID
    tag_ativo: str | None = None
    ativo_descricao: str | None = None
    setor_nome: str | None = None
    tipo_manutencao: str
    prioridade: str
    status: str
    falha_sintoma: str | None = None
    observacoes: str | None = None
    data_abertura: datetime
    data_agendamento: datetime | None = None
    data_inicio_real: datetime | None = None
    data_conclusao_real: datetime | None = None
    solicitante_id: UUID
    solicitante_nome: str | None = None
    tecnico_id: UUID | None = None
    tecnico_nome: str | None = None


class WorkOrderConsolidationApontamento(BaseModel):
    """Tempo de mão de obra: sempre a partir de data_inicio/data_fim, independente da transição de status."""

    id: UUID
    created_at: datetime
    usuario_id: UUID
    usuario_nome: str | None = None
    status_anterior: str
    status_novo: str
    descricao: str
    data_inicio: datetime | None = None
    data_fim: datetime | None = None
    horas_trabalhadas: float = Field(description="Horas entre início e fim quando ambos existem; senão 0.")
    custo_hora_usuario: float = Field(description="R$/h cadastrado no usuário do apontamento.")
    custo_mao_obra_linha: float = Field(description="horas_trabalhadas × custo_hora_usuario.")


class WorkOrderConsolidationResponse(BaseModel):
    work_order_id: UUID
    codigo_os: str
    status: str
    consolidada: bool
    consolidada_em: datetime | None
    consolidada_por_id: UUID | None
    tag_defeito: str | None
    causa_raiz: str | None = None
    solucao: str | None = None
    observacoes: str | None = None
    custo_internos: float
    custo_terceiros: float
    custo_pecas: float
    custo_total: float
    resumo_horas: WorkOrderConsolidationResumo
    os_resumo: WorkOrderConsolidationOsResumo
    apontamentos: list[WorkOrderConsolidationApontamento] = Field(default_factory=list)
    total_horas_mao_obra_apontamentos: float = 0
    total_custo_mao_obra_sugerido: float = 0
