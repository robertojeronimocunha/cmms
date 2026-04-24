from datetime import datetime
from uuid import UUID

from pydantic import BaseModel


class WorkOrderReportItem(BaseModel):
    id: UUID
    codigo_os: str
    ativo_id: UUID
    solicitante_nome: str | None = None
    tag_ativo: str | None
    status: str
    prioridade: str
    tipo_manutencao: str
    data_abertura: datetime
    data_conclusao_real: datetime | None
    falha_sintoma: str | None
    solucao: str | None
