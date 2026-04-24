from collections import defaultdict
from datetime import datetime, timezone
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session, aliased

from app.auth.dependencies import (
    PERFIS_COM_CATALOGO,
    PERFIS_EXECUTAR_OS,
    get_current_user,
    require_roles,
)
from app.core.database import get_db
from app.models.checklist import (
    ChecklistExecutada,
    ChecklistPadrao,
    ChecklistTarefaExecutada,
    ChecklistTarefaPadrao,
)
from app.models.user import User
from app.models.work_order import WorkOrder
from app.schemas.checklist import (
    ChecklistObrigatorioStatusResponse,
    ChecklistHistoricoItem,
    ChecklistExecutadaCreate,
    ChecklistExecutadaResponse,
    ChecklistPadraoCreate,
    ChecklistPadraoResponse,
    ChecklistPadraoUpdate,
    ChecklistTarefaExecutadaResponse,
    ChecklistTarefaExecutadaUpdate,
    ChecklistTarefaPadraoCreate,
    ChecklistTarefaPadraoResponse,
    ChecklistTarefaPadraoUpdate,
    GarantirChecklistsPadroesResponse,
)
from app.services.checklist_execucao_os import (
    copiar_checklist_padrao_para_os,
    ensure_padroes_obrigatorios_na_os,
    wo_em_fase_finalizacao,
)
from app.services.os_checklist_obrigatorio import has_finalizacao_concluida_execucao_criada_por_lider

router = APIRouter(prefix="/checklists", tags=["checklists"])
_CHECKLIST_COD_LOTO = "LOTO"
_CHECKLIST_COD_FINALIZACAO = "FINALIZACAO_OS"


def _norm_checklist_cod(value: str | None) -> str:
    return (value or "").strip().upper()


def _wo_status_str(wo: WorkOrder) -> str:
    return str(wo.status.value if hasattr(wo.status, "value") else wo.status)


def _required_checklist_status(
    db: Session,
    work_order_id: UUID,
    codigo_checklist: str,
) -> ChecklistObrigatorioStatusResponse:
    checklist_id = db.scalar(
        select(ChecklistPadrao.id).where(
            ChecklistPadrao.codigo_checklist == codigo_checklist,
            ChecklistPadrao.ativo.is_(True),
        )
    )
    if not checklist_id:
        return ChecklistObrigatorioStatusResponse(
            codigo_checklist=codigo_checklist,
            concluido=False,
            checklist_padrao_ativo=False,
            pendencias_obrigatorias=0,
        )

    exec_ids = list(
        db.execute(
            select(ChecklistExecutada.id).where(
                ChecklistExecutada.ordem_servico_id == work_order_id,
                ChecklistExecutada.checklist_padrao_id == checklist_id,
            )
        )
        .scalars()
        .all()
    )
    if not exec_ids:
        return ChecklistObrigatorioStatusResponse(
            codigo_checklist=codigo_checklist,
            concluido=False,
            checklist_padrao_ativo=True,
            pendencias_obrigatorias=0,
        )

    min_pend = None
    for exec_id in exec_ids:
        pend = db.scalar(
            select(func.count())
            .select_from(ChecklistTarefaExecutada)
            .where(
                ChecklistTarefaExecutada.checklist_executada_id == exec_id,
                ChecklistTarefaExecutada.obrigatoria.is_(True),
                ChecklistTarefaExecutada.executada.is_(False),
            )
        ) or 0
        if pend == 0:
            return ChecklistObrigatorioStatusResponse(
                codigo_checklist=codigo_checklist,
                concluido=True,
                checklist_padrao_ativo=True,
                pendencias_obrigatorias=0,
            )
        min_pend = pend if min_pend is None else min(min_pend, pend)

    return ChecklistObrigatorioStatusResponse(
        codigo_checklist=codigo_checklist,
        concluido=False,
        checklist_padrao_ativo=True,
        pendencias_obrigatorias=min_pend or 0,
    )


@router.get("", response_model=list[ChecklistPadraoResponse])
def list_checklists(
    ativo: bool | None = Query(default=None),
    limit: int = Query(default=100, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    # USUARIO: leitura da lista para o detalhe da OS (copiar ainda bloqueada em POST …/executar).
    q = select(ChecklistPadrao).order_by(ChecklistPadrao.nome.asc()).limit(limit).offset(offset)
    if user.perfil_acesso == "LIDER":
        q = q.where(ChecklistPadrao.codigo_checklist == _CHECKLIST_COD_FINALIZACAO)
    elif user.perfil_acesso not in PERFIS_COM_CATALOGO:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao")
    if ativo is not None:
        q = q.where(ChecklistPadrao.ativo == ativo)
    return list(db.execute(q).scalars().all())


@router.post("", response_model=ChecklistPadraoResponse, status_code=status.HTTP_201_CREATED)
def create_checklist(
    payload: ChecklistPadraoCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    code = payload.codigo_checklist.strip().upper()
    if db.scalar(select(ChecklistPadrao.id).where(func.lower(ChecklistPadrao.codigo_checklist) == code.lower())):
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Codigo de checklist ja existe")
    row = ChecklistPadrao(
        codigo_checklist=code,
        nome=payload.nome.strip(),
        descricao=(payload.descricao or "").strip() or None,
        ativo=payload.ativo,
    )
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.patch("/{checklist_id}", response_model=ChecklistPadraoResponse)
def update_checklist(
    checklist_id: UUID,
    payload: ChecklistPadraoUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(ChecklistPadrao, checklist_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Checklist nao encontrado")
    data = payload.model_dump(exclude_none=True)
    if "codigo_checklist" in data:
        code = data["codigo_checklist"].strip().upper()
        conflict = db.scalar(
            select(ChecklistPadrao.id).where(
                func.lower(ChecklistPadrao.codigo_checklist) == code.lower(),
                ChecklistPadrao.id != checklist_id,
            )
        )
        if conflict:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Codigo de checklist ja existe")
        row.codigo_checklist = code
    if "nome" in data:
        row.nome = data["nome"].strip()
    if "descricao" in data:
        row.descricao = (data["descricao"] or "").strip() or None
    if "ativo" in data:
        row.ativo = data["ativo"]
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.delete("/{checklist_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_checklist(
    checklist_id: UUID,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(ChecklistPadrao, checklist_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Checklist nao encontrado")
    if db.scalar(select(ChecklistExecutada.id).where(ChecklistExecutada.checklist_padrao_id == checklist_id).limit(1)):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Checklist ja foi usado em execucao; desative em vez de excluir",
        )
    db.query(ChecklistTarefaPadrao).filter(ChecklistTarefaPadrao.checklist_padrao_id == checklist_id).delete()
    db.delete(row)
    db.commit()
    return None


@router.get("/{checklist_id}/tarefas", response_model=list[ChecklistTarefaPadraoResponse])
def list_checklist_tasks(
    checklist_id: UUID,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    checklist = db.get(ChecklistPadrao, checklist_id)
    if not checklist:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Checklist nao encontrado")
    if user.perfil_acesso == "LIDER":
        if _norm_checklist_cod(checklist.codigo_checklist) != _CHECKLIST_COD_FINALIZACAO:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="LIDER so acessa checklist de finalizacao")
    elif user.perfil_acesso not in PERFIS_COM_CATALOGO:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao")
    q = (
        select(ChecklistTarefaPadrao)
        .where(ChecklistTarefaPadrao.checklist_padrao_id == checklist_id)
        .order_by(ChecklistTarefaPadrao.ordem.asc(), ChecklistTarefaPadrao.created_at.asc())
    )
    return list(db.execute(q).scalars().all())


@router.post("/{checklist_id}/tarefas", response_model=ChecklistTarefaPadraoResponse, status_code=status.HTTP_201_CREATED)
def create_checklist_task(
    checklist_id: UUID,
    payload: ChecklistTarefaPadraoCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    checklist = db.get(ChecklistPadrao, checklist_id)
    if not checklist:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Checklist nao encontrado")
    row = ChecklistTarefaPadrao(
        checklist_padrao_id=checklist_id,
        ordem=payload.ordem,
        tarefa=payload.tarefa.strip(),
        obrigatoria=payload.obrigatoria,
    )
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.patch("/tarefas/{task_id}", response_model=ChecklistTarefaPadraoResponse)
def update_checklist_task(
    task_id: UUID,
    payload: ChecklistTarefaPadraoUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(ChecklistTarefaPadrao, task_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa nao encontrada")
    data = payload.model_dump(exclude_none=True)
    if "ordem" in data:
        row.ordem = data["ordem"]
    if "tarefa" in data:
        row.tarefa = data["tarefa"].strip()
    if "obrigatoria" in data:
        row.obrigatoria = data["obrigatoria"]
    db.add(row)
    db.commit()
    db.refresh(row)
    return row


@router.delete("/tarefas/{task_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_checklist_task(
    task_id: UUID,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(ChecklistTarefaPadrao, task_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa nao encontrada")
    db.delete(row)
    db.commit()
    return None


@router.post(
    "/ordens-servico/{work_order_id}/garantir-padroes-obrigatorios",
    response_model=GarantirChecklistsPadroesResponse,
    tags=["checklists-execucao"],
)
def garantir_checklists_padroes_obrigatorios(
    work_order_id: UUID,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    """Cria LOTO e FINALIZACAO_OS na OS se ainda não existirem (abertura do detalhe). Inclui LOTO em AGENDADA; conclusão só exige-se ao sair de ABERTA."""
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    ids = ensure_padroes_obrigatorios_na_os(db, work_order, user)
    return GarantirChecklistsPadroesResponse(criadas=len(ids), checklist_executada_ids=ids)


@router.post(
    "/ordens-servico/{work_order_id}/executar",
    response_model=ChecklistExecutadaResponse,
    status_code=status.HTTP_201_CREATED,
    tags=["checklists-execucao"],
)
def create_checklist_execution(
    work_order_id: UUID,
    payload: ChecklistExecutadaCreate,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    checklist = db.get(ChecklistPadrao, payload.checklist_padrao_id)
    if not checklist:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Checklist padrao nao encontrado")
    ex = copiar_checklist_padrao_para_os(db, work_order, checklist, user)
    return ChecklistExecutadaResponse.model_validate(ex).model_copy(update={"codigo_checklist": checklist.codigo_checklist})


@router.get(
    "/ordens-servico/{work_order_id}/executar",
    response_model=list[ChecklistExecutadaResponse],
    tags=["checklists-execucao"],
)
def list_checklist_executions_for_work_order(
    work_order_id: UUID,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    rows = db.execute(
        select(ChecklistExecutada, ChecklistPadrao.codigo_checklist)
        .join(ChecklistPadrao, ChecklistExecutada.checklist_padrao_id == ChecklistPadrao.id)
        .where(ChecklistExecutada.ordem_servico_id == work_order_id)
        .order_by(ChecklistExecutada.created_at.asc())
    ).all()
    return [
        ChecklistExecutadaResponse.model_validate(ex).model_copy(update={"codigo_checklist": cod})
        for ex, cod in rows
    ]


@router.get(
    "/ordens-servico/{work_order_id}/historico",
    response_model=list[ChecklistHistoricoItem],
    tags=["checklists-execucao"],
)
def list_work_order_history(
    work_order_id: UUID,
    incluir_tarefas: bool = Query(default=False),
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    rows = (
        db.execute(
            select(ChecklistExecutada, User.nome_completo, ChecklistPadrao.codigo_checklist)
            .join(User, ChecklistExecutada.usuario_id == User.id)
            .join(ChecklistPadrao, ChecklistExecutada.checklist_padrao_id == ChecklistPadrao.id)
            .where(ChecklistExecutada.ordem_servico_id == work_order_id)
            .order_by(ChecklistExecutada.created_at.desc())
        )
        .all()
    )
    tasks_by_exec: dict[UUID, list[ChecklistTarefaExecutadaResponse]] = defaultdict(list)
    if incluir_tarefas and rows:
        UltUser = aliased(User)
        exec_ids = [c.id for c, _, _ in rows]
        task_rows = db.execute(
            select(ChecklistTarefaExecutada, UltUser.nome_completo)
            .outerjoin(UltUser, ChecklistTarefaExecutada.ultimo_preenchimento_por_id == UltUser.id)
            .where(ChecklistTarefaExecutada.checklist_executada_id.in_(exec_ids))
            .order_by(
                ChecklistTarefaExecutada.checklist_executada_id.asc(),
                ChecklistTarefaExecutada.ordem.asc(),
                ChecklistTarefaExecutada.created_at.asc(),
            )
        ).all()
        for t, nome_u in task_rows:
            tasks_by_exec[t.checklist_executada_id].append(
                ChecklistTarefaExecutadaResponse.model_validate(t).model_copy(
                    update={"ultimo_preenchimento_por_nome": nome_u}
                )
            )

    return [
        ChecklistHistoricoItem(
            id=c.id,
            ordem_servico_id=c.ordem_servico_id,
            os_apontamento_id=c.os_apontamento_id,
            checklist_padrao_id=c.checklist_padrao_id,
            codigo_checklist=codigo,
            usuario_id=c.usuario_id,
            usuario_nome=nome,
            nome=c.nome,
            descricao=c.descricao,
            concluido=(pend == 0),
            pendencias_obrigatorias=pend,
            created_at=c.created_at,
            tarefas=list(tasks_by_exec[c.id]) if incluir_tarefas else [],
        )
        for c, nome, codigo, pend in [
            (
                c,
                nome,
                codigo,
                db.scalar(
                    select(func.count())
                    .select_from(ChecklistTarefaExecutada)
                    .where(
                        ChecklistTarefaExecutada.checklist_executada_id == c.id,
                        ChecklistTarefaExecutada.obrigatoria.is_(True),
                        ChecklistTarefaExecutada.executada.is_(False),
                    )
                )
                or 0,
            )
            for c, nome, codigo in rows
        ]
    ]


@router.get(
    "/execucoes/{execution_id}/tarefas",
    response_model=list[ChecklistTarefaExecutadaResponse],
    tags=["checklists-execucao"],
)
def list_execution_tasks(
    execution_id: UUID,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    exec_row = db.get(ChecklistExecutada, execution_id)
    if not exec_row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Checklist executada nao encontrada")
    UltUser = aliased(User)
    rows = db.execute(
        select(ChecklistTarefaExecutada, UltUser.nome_completo)
        .outerjoin(UltUser, ChecklistTarefaExecutada.ultimo_preenchimento_por_id == UltUser.id)
        .where(ChecklistTarefaExecutada.checklist_executada_id == execution_id)
        .order_by(ChecklistTarefaExecutada.ordem.asc(), ChecklistTarefaExecutada.created_at.asc())
    ).all()
    return [
        ChecklistTarefaExecutadaResponse.model_validate(t).model_copy(update={"ultimo_preenchimento_por_nome": nome})
        for t, nome in rows
    ]


@router.patch(
    "/execucoes/tarefas/{task_id}",
    response_model=ChecklistTarefaExecutadaResponse,
    tags=["checklists-execucao"],
)
def update_execution_task(
    task_id: UUID,
    payload: ChecklistTarefaExecutadaUpdate,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    row = db.get(ChecklistTarefaExecutada, task_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Tarefa executada nao encontrada")
    exec_row = db.get(ChecklistExecutada, row.checklist_executada_id)
    if not exec_row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Execucao de checklist nao encontrada")
    padrao = db.get(ChecklistPadrao, exec_row.checklist_padrao_id)
    if not padrao:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Checklist padrao nao encontrado")
    wo = db.get(WorkOrder, exec_row.ordem_servico_id)
    if not wo:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    wo_st = _wo_status_str(wo)
    if _norm_checklist_cod(padrao.codigo_checklist) == _CHECKLIST_COD_FINALIZACAO:
        if user.perfil_acesso == "LIDER":
            if not wo_em_fase_finalizacao(wo_st):
                raise HTTPException(
                    status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                    detail="Checklist de finalizacao so pode ser editada com a OS em AGUARDANDO_APROVACAO.",
                )
        elif user.perfil_acesso != "ADMIN":
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Apenas LIDER altera o checklist de finalizacao (ADMIN para suporte).",
            )
    else:
        if user.perfil_acesso not in PERFIS_EXECUTAR_OS:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao para editar esta tarefa")
        if wo_em_fase_finalizacao(wo_st):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Com a OS em AGUARDANDO_APROVACAO, apenas o checklist de finalizacao pode ser editado.",
            )
    data = payload.model_dump(exclude_none=True)
    mudou = False
    if "executada" in data and row.executada != data["executada"]:
        row.executada = data["executada"]
        mudou = True
    if "observacao" in data:
        novo = (data["observacao"] or "").strip() or None
        if row.observacao != novo:
            row.observacao = novo
            mudou = True
    if mudou:
        row.ultimo_preenchimento_por_id = user.id
        row.ultimo_preenchimento_em = datetime.now(timezone.utc)
    db.add(row)
    db.commit()
    db.refresh(row)
    nome_ult = db.scalar(select(User.nome_completo).where(User.id == row.ultimo_preenchimento_por_id)) if row.ultimo_preenchimento_por_id else None
    return ChecklistTarefaExecutadaResponse.model_validate(row).model_copy(
        update={"ultimo_preenchimento_por_nome": nome_ult}
    )


@router.get(
    "/ordens-servico/{work_order_id}/obrigatorios-status",
    response_model=list[ChecklistObrigatorioStatusResponse],
    tags=["checklists-execucao"],
)
def get_required_checklists_status(
    work_order_id: UUID,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    loto = _required_checklist_status(db, work_order_id, _CHECKLIST_COD_LOTO)
    fin = _required_checklist_status(db, work_order_id, _CHECKLIST_COD_FINALIZACAO)
    lider_ok = has_finalizacao_concluida_execucao_criada_por_lider(db, work_order_id)
    fin_out = fin.model_copy(update={"concluido_copia_lider": lider_ok})
    return [loto, fin_out]
