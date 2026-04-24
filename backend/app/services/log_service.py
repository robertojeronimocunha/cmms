from app.models.system_log import SystemLog


def register_log(
    db,
    *,
    usuario_id,
    acao: str,
    entidade: str,
    entidade_id,
    detalhes: dict | None = None,
    ip_origem: str | None = None,
    user_agent: str | None = None,
):
    log = SystemLog(
        usuario_id=usuario_id,
        acao=acao,
        entidade=entidade,
        entidade_id=entidade_id,
        nivel="INFO",
        detalhes=detalhes or {},
        ip_origem=ip_origem,
        user_agent=user_agent,
    )
    db.add(log)
