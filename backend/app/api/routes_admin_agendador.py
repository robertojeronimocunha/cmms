from datetime import datetime, timedelta, timezone

from fastapi import APIRouter, BackgroundTasks, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import require_roles
from app.core.config import settings
from app.core.database import SessionLocal, get_db
from app.models.agendador_tarefa import AgendadorTarefa
from app.models.user import User
from app.schemas.agendador import (
    AgendadorExecucaoResposta,
    AgendadorLogManutencaoIn,
    AgendadorLogManutencaoOut,
    AgendadorLogOut,
    AgendadorTarefaOut,
    AgendadorTarefaUpdate,
)
from app.services import agendador_execucao as ag_svc
from app.services import agendador_log as ag_log

router = APIRouter(prefix="/admin/agendador", tags=["admin-agendador"])
_admin = require_roles("ADMIN")


def _to_out(row: AgendadorTarefa, solicitante_nome: str | None = None) -> AgendadorTarefaOut:
    base = AgendadorTarefaOut.model_validate(row)
    return base.model_copy(update={"solicitante_nome": solicitante_nome})


@router.get("/tarefas", response_model=list[AgendadorTarefaOut])
def listar_tarefas(_: User = Depends(_admin), db: Session = Depends(get_db)):
    rows = db.scalars(select(AgendadorTarefa).order_by(AgendadorTarefa.chave.asc())).all()
    uids = [r.solicitante_usuario_id for r in rows if r.solicitante_usuario_id]
    nome_map: dict = {}
    if uids:
        for u in db.scalars(select(User).where(User.id.in_(uids))).all():
            nome_map[u.id] = u.nome_completo
    return [_to_out(r, nome_map.get(r.solicitante_usuario_id)) for r in rows]


@router.patch("/tarefas/{chave}", response_model=AgendadorTarefaOut)
def atualizar_tarefa(
    chave: str,
    payload: AgendadorTarefaUpdate,
    _: User = Depends(_admin),
    db: Session = Depends(get_db),
):
    if chave not in ag_svc.CHAVES_VALIDAS:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa desconhecida")
    job = db.scalars(select(AgendadorTarefa).where(AgendadorTarefa.chave == chave)).one_or_none()
    if not job:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa não cadastrada")
    data = payload.model_dump(exclude_unset=True)
    if "ativo" in data:
        job.ativo = bool(data["ativo"])
    if "intervalo_minutos" in data:
        job.intervalo_minutos = int(data["intervalo_minutos"])
    if "proxima_execucao_em" in data:
        job.proxima_execucao_em = data["proxima_execucao_em"]
    elif "intervalo_minutos" in data:
        job.proxima_execucao_em = datetime.now(timezone.utc) + timedelta(minutes=job.intervalo_minutos)
    if "solicitante_usuario_id" in data and chave == ag_svc.CHAVE_PREVENTIVAS_VENCIDAS:
        sid = data["solicitante_usuario_id"]
        if sid is None:
            job.solicitante_usuario_id = None
        else:
            u = db.get(User, sid)
            if not u or not u.ativo:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail="Utilizador não encontrado ou inativo",
                )
            if u.perfil_acesso not in ag_svc.PERFIS_SOLICITANTE_PREVENTIVA_AGENDA:
                raise HTTPException(
                    status_code=status.HTTP_400_BAD_REQUEST,
                    detail="Perfil não permitido (use ADMIN, TECNICO, LUBRIFICADOR ou DIRETORIA).",
                )
            job.solicitante_usuario_id = sid
    db.add(job)
    db.commit()
    db.refresh(job)
    snome = None
    if job.solicitante_usuario_id:
        su = db.get(User, job.solicitante_usuario_id)
        snome = su.nome_completo if su else None
    return _to_out(job, snome)


def _bg_forcar(chave: str) -> None:
    db = SessionLocal()
    try:
        ag_svc.forcar_execucao(db, chave)
    finally:
        db.close()


@router.get("/log", response_model=AgendadorLogOut)
def obter_log_cron(
    _: User = Depends(_admin),
    max_linhas: int = Query(default=settings.agendador_log_read_max_lines),
    max_bytes: int | None = Query(None),
):
    if max_linhas < 10 or max_linhas > 100_000:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="max_linhas entre 10 e 100000",
        )
    mb = max_bytes if max_bytes is not None else settings.agendador_log_read_max_bytes
    if mb < 4096 or mb > 15 * 1024 * 1024:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="max_bytes entre 4096 e 15728640",
        )
    try:
        caminho, existe, tamanho, cortado, linhas = ag_log.read_log_tail_newest_first(
            max_bytes=mb,
            max_lines=max_linhas,
        )
    except OSError as exc:
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail=f"Leitura do log falhou: {exc}",
        ) from exc
    return AgendadorLogOut(
        caminho=caminho,
        existe=existe,
        tamanho_bytes=tamanho,
        leitura_cortada=cortado,
        linhas=linhas,
    )


@router.post("/log/manutencao", response_model=AgendadorLogManutencaoOut)
def manutencao_log_cron(payload: AgendadorLogManutencaoIn, _: User = Depends(_admin)):
    if payload.acao == "reter_ultimas_linhas" and payload.linhas is None:
        raise HTTPException(
            status_code=status.HTTP_400_BAD_REQUEST,
            detail="Indique linhas para reter_ultimas_linhas",
        )
    if payload.acao == "esvaziar":
        ok, msg = ag_log.manter_log_esvaziar()
    else:
        ok, msg = ag_log.manter_log_reter_ultimas_linhas(int(payload.linhas))
    if not ok:
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail=msg[:4000])
    return AgendadorLogManutencaoOut(ok=True, mensagem=msg[:2000])


@router.post("/tarefas/{chave}/executar-agora", response_model=AgendadorExecucaoResposta)
def executar_agora(
    chave: str,
    background_tasks: BackgroundTasks,
    sync: bool = False,
    _: User = Depends(_admin),
    db: Session = Depends(get_db),
):
    if chave not in ag_svc.CHAVES_VALIDAS:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa desconhecida")
    job = db.scalars(select(AgendadorTarefa).where(AgendadorTarefa.chave == chave)).one_or_none()
    if not job:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa não cadastrada")
    if not job.ativo:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail="Ative a tarefa antes de executar")
    if sync:
        try:
            ok, msg = ag_svc.forcar_execucao(db, chave)
        except ValueError:
            raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa inválida") from None
        return AgendadorExecucaoResposta(ok=ok, mensagem=msg[:4000])
    background_tasks.add_task(_bg_forcar, chave)
    return AgendadorExecucaoResposta(
        ok=True,
        mensagem="Execução iniciada em segundo plano. Atualize a lista para ver o resultado.",
    )
