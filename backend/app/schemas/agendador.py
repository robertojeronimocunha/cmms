from datetime import datetime
from typing import Literal
from uuid import UUID

from pydantic import BaseModel, Field


class AgendadorTarefaOut(BaseModel):
    chave: str
    titulo: str
    ativo: bool
    intervalo_minutos: int
    ultima_execucao_em: datetime | None
    proxima_execucao_em: datetime | None
    ultimo_ok: bool | None
    ultimo_mensagem: str | None
    solicitante_usuario_id: UUID | None = None
    solicitante_nome: str | None = None

    class Config:
        from_attributes = True


class AgendadorTarefaUpdate(BaseModel):
    ativo: bool | None = None
    intervalo_minutos: int | None = Field(default=None, ge=5, le=525600)
    proxima_execucao_em: datetime | None = Field(
        default=None,
        description="Próxima execução (timezone-aware). Null = vencida na próxima passagem do tick.",
    )
    solicitante_usuario_id: UUID | None = Field(
        default=None,
        description="Só para preventivas_vencidas: utilizador solicitante das OS. Null = primeiro ADMIN ativo.",
    )


class AgendadorExecucaoResposta(BaseModel):
    ok: bool
    mensagem: str


class AgendadorLogOut(BaseModel):
    """Linhas em ordem decrescente (entrada mais recente do ficheiro primeiro)."""

    caminho: str
    existe: bool
    tamanho_bytes: int
    leitura_cortada: bool
    linhas: list[str] = Field(default_factory=list)


class AgendadorLogManutencaoIn(BaseModel):
    acao: Literal["esvaziar", "reter_ultimas_linhas"]
    linhas: int | None = Field(
        default=None,
        ge=1,
        le=9_000_000,
        description="Obrigatório se acao=reter_ultimas_linhas",
    )


class AgendadorLogManutencaoOut(BaseModel):
    ok: bool
    mensagem: str
