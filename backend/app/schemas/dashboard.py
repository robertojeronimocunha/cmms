from pydantic import BaseModel


class DashboardResumo(BaseModel):
    """Contagens agregadas para o painel operacional."""

    os_abertas: int
    maquinas_paradas: int
    os_aguardando_peca: int
    os_aguardando_terceiro: int
    pecas_abaixo_minimo: int
    preventivas_vencidas: int
    lubrificacoes_hoje: int  # pontos com proxima_execucao ate a data de hoje (inclui atrasadas)
