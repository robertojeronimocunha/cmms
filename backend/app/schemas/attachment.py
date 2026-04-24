from datetime import datetime
from uuid import UUID

from pydantic import BaseModel


class AttachmentResponse(BaseModel):
    id: UUID
    ordem_servico_id: UUID
    os_apontamento_id: UUID | None = None
    usuario_id: UUID
    nome_arquivo: str
    caminho_arquivo: str
    mime_type: str
    tamanho_bytes: int
    created_at: datetime

    class Config:
        from_attributes = True
