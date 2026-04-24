"""Cópia de checklists padrão para a OS (vínculo na ordem de serviço, sem apontamento obrigatório)."""

from uuid import UUID

from fastapi import HTTPException, status
from sqlalchemy import func, select
from sqlalchemy.orm import Session

from app.auth.dependencies import PERFIS_EXECUTAR_OS
from app.services.os_checklist_obrigatorio import (
    CHECKLIST_COD_LOTO,
    CHECKLIST_COD_LOTO_LIDER,
    has_loto_cadeia_concluida,
    has_obrigatorio_concluido,
)
from app.models.checklist import (
    ChecklistExecutada,
    ChecklistPadrao,
    ChecklistTarefaExecutada,
    ChecklistTarefaPadrao,
)
from app.models.user import User
from app.models.work_order import WorkOrder

COD_LOTO = CHECKLIST_COD_LOTO
COD_LOTO_LIDER = CHECKLIST_COD_LOTO_LIDER
COD_FINALIZACAO = "FINALIZACAO_OS"
# Checklist de finalização só é editável/aplicável neste status (antigo EM_TESTE).
STATUS_OS_FINALIZACAO = "AGUARDANDO_APROVACAO"


def wo_em_fase_finalizacao(st: str) -> bool:
    """OS na etapa em que só a checklist FINALIZACAO_OS é editável/aplicável (legado: EM_TESTE)."""
    s = (st or "").strip().upper()
    return s in (STATUS_OS_FINALIZACAO, "EM_TESTE")


def _norm_cod(value: str | None) -> str:
    return (value or "").strip().upper()


def _padrao_checklist_ativo_por_codigo(db: Session, cod: str) -> ChecklistPadrao | None:
    """Resolve padrão ativo pelo código (mesma regra do vínculo por TAG: sem depender de maiúsculas no banco)."""
    c = _norm_cod(cod)
    if not c:
        return None
    return db.scalar(
        select(ChecklistPadrao).where(
            func.lower(ChecklistPadrao.codigo_checklist) == c.lower(),
            ChecklistPadrao.ativo.is_(True),
        )
    )


def _wo_status_str(wo: WorkOrder) -> str:
    return str(wo.status.value if hasattr(wo.status, "value") else wo.status)


def _tarefas_padrao_ordered(db: Session, checklist_padrao_id: UUID) -> list[ChecklistTarefaPadrao]:
    return list(
        db.execute(
            select(ChecklistTarefaPadrao)
            .where(ChecklistTarefaPadrao.checklist_padrao_id == checklist_padrao_id)
            .order_by(ChecklistTarefaPadrao.ordem.asc(), ChecklistTarefaPadrao.created_at.asc())
        )
        .scalars()
        .all()
    )


def _ja_existe_execucao_os(db: Session, ordem_servico_id: UUID, checklist_padrao_id: UUID) -> bool:
    return bool(
        db.scalar(
            select(ChecklistExecutada.id).where(
                ChecklistExecutada.ordem_servico_id == ordem_servico_id,
                ChecklistExecutada.checklist_padrao_id == checklist_padrao_id,
            ).limit(1)
        )
    )


def _ensure_execucao_loto_lider_na_os(
    db: Session,
    work_order: WorkOrder,
    copiado_por_id: UUID,
) -> ChecklistExecutada | None:
    """Se o padrão LOTO_LIDER estiver ativo e ainda não houver cópia na OS, cria a execução (mesmo usuário que disparou o LOTO)."""
    padrao = _padrao_checklist_ativo_por_codigo(db, COD_LOTO_LIDER)
    if not padrao or _ja_existe_execucao_os(db, work_order.id, padrao.id):
        return None
    return _inserir_execucao_e_tarefas(db, work_order.id, padrao, copiado_por_id)


def _inserir_execucao_e_tarefas(
    db: Session,
    work_order_id: UUID,
    checklist: ChecklistPadrao,
    copiado_por_id: UUID,
) -> ChecklistExecutada:
    row = ChecklistExecutada(
        ordem_servico_id=work_order_id,
        os_apontamento_id=None,
        checklist_padrao_id=checklist.id,
        usuario_id=copiado_por_id,
        nome=checklist.nome,
        descricao=checklist.descricao,
    )
    db.add(row)
    db.flush()
    for t in _tarefas_padrao_ordered(db, checklist.id):
        db.add(
            ChecklistTarefaExecutada(
                checklist_executada_id=row.id,
                ordem=t.ordem,
                tarefa=t.tarefa,
                obrigatoria=t.obrigatoria,
                executada=False,
                observacao=None,
            )
        )
    return row


def ensure_padroes_obrigatorios_na_os(db: Session, work_order: WorkOrder, user: User) -> list[UUID]:
    """Garante execuções LOTO, LOTO_LIDER e FINALIZACAO_OS (padrões ativos). Idempotente."""
    st = _wo_status_str(work_order)
    if st in ("FINALIZADA", "CANCELADA"):
        return []
    criadas: list[UUID] = []
    for cod in (COD_LOTO, COD_LOTO_LIDER, COD_FINALIZACAO):
        padrao = _padrao_checklist_ativo_por_codigo(db, cod)
        if not padrao:
            continue
        if _ja_existe_execucao_os(db, work_order.id, padrao.id):
            continue
        ex = _inserir_execucao_e_tarefas(db, work_order.id, padrao, user.id)
        criadas.append(ex.id)
    if criadas:
        db.commit()
    return criadas


def vincular_checklist_por_codigo_tag_ativo(
    db: Session,
    work_order: WorkOrder,
    tag_ativo: str,
    user: User,
) -> ChecklistExecutada:
    """
    Copia o checklist padrão cujo codigo_checklist coincide com a TAG do ativo (comparação sem distinção de maiúsculas).
    Usado em OS preventiva: tarefas específicas da máquina.
    Exige padrão ativo; idempotente se já existir cópia na OS.
    """
    tag = (tag_ativo or "").strip()
    if not tag:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Ativo sem TAG: impossivel vincular checklist preventivo por codigo.",
        )
    padrao = db.scalar(
        select(ChecklistPadrao).where(
            func.lower(ChecklistPadrao.codigo_checklist) == func.lower(tag),
            ChecklistPadrao.ativo.is_(True),
        )
    )
    if not padrao:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail=(
                f'Nao ha checklist padrao ativo com codigo_checklist igual a TAG do ativo ("{tag}"). '
                f"Cadastre em Checklists padrão um item com codigo exatamente igual a essa tag."
            ),
        )
    # Vínculo pelo TAG é etapa de abertura da OS (ex.: preventiva em AGENDADA): não exige LOTO/LOTO_LIDER concluídos.
    if _ja_existe_execucao_os(db, work_order.id, padrao.id):
        exid = db.scalar(
            select(ChecklistExecutada.id)
            .where(
                ChecklistExecutada.ordem_servico_id == work_order.id,
                ChecklistExecutada.checklist_padrao_id == padrao.id,
            )
            .limit(1)
        )
        ex = db.get(ChecklistExecutada, exid) if exid else None
        if ex:
            return ex
    ex = _inserir_execucao_e_tarefas(db, work_order.id, padrao, user.id)
    db.commit()
    db.refresh(ex)
    return ex


def copiar_checklist_padrao_para_os(
    db: Session,
    work_order: WorkOrder,
    checklist: ChecklistPadrao,
    user: User,
) -> ChecklistExecutada:
    """Copia manual (respeita perfil e duplicata). Ao aplicar LOTO, cria também LOTO_LIDER na OS se o padrão existir e ainda não houver cópia."""
    if not checklist.ativo:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Checklist padrao inativo")

    wo_st = _wo_status_str(work_order)
    cod = _norm_cod(checklist.codigo_checklist)

    if cod == COD_FINALIZACAO:
        if user.perfil_acesso not in ("LIDER", "ADMIN"):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Apenas LIDER ou ADMIN aplicam o checklist de finalizacao na OS.",
            )
        if not wo_em_fase_finalizacao(wo_st):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Checklist de finalizacao so quando a OS esta em AGUARDANDO_APROVACAO.",
            )
    elif cod == COD_LOTO_LIDER:
        if user.perfil_acesso not in ("LIDER", "ADMIN", "DIRETORIA"):
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Apenas LIDER, ADMIN ou DIRETORIA aplicam o checklist LOTO_LIDER na OS.",
            )
        if wo_em_fase_finalizacao(wo_st):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Com a OS em AGUARDANDO_APROVACAO, apenas o checklist de finalizacao pode ser aplicado.",
            )
        if not has_obrigatorio_concluido(db, work_order.id, COD_LOTO):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Conclua o checklist LOTO antes de aplicar o LOTO_LIDER.",
            )
    elif cod == COD_LOTO:
        if user.perfil_acesso not in PERFIS_EXECUTAR_OS:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao para aplicar este checklist")
        if wo_em_fase_finalizacao(wo_st):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Com a OS em AGUARDANDO_APROVACAO, apenas o checklist de finalizacao pode ser aplicado.",
            )
    else:
        if user.perfil_acesso not in PERFIS_EXECUTAR_OS:
            raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao para aplicar este checklist")
        if wo_em_fase_finalizacao(wo_st):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Com a OS em AGUARDANDO_APROVACAO, apenas o checklist de finalizacao pode ser aplicado.",
            )
        if not has_loto_cadeia_concluida(db, work_order.id):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Conclua os checklists LOTO e LOTO_LIDER antes de aplicar outros checklists nesta OS.",
            )

    if cod in (COD_LOTO, COD_LOTO_LIDER, COD_FINALIZACAO) and _ja_existe_execucao_os(db, work_order.id, checklist.id):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail=f"Ja existe checklist {cod} nesta OS.",
        )

    ex = _inserir_execucao_e_tarefas(db, work_order.id, checklist, user.id)
    if cod == COD_LOTO:
        _ensure_execucao_loto_lider_na_os(db, work_order, user.id)
    db.commit()
    db.refresh(ex)
    return ex
