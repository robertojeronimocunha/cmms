"""Regras de checklists obrigatórios por OS (LOTO, finalização)."""

from uuid import UUID

from sqlalchemy import select
from sqlalchemy.orm import Session

from app.models.checklist import ChecklistExecutada, ChecklistPadrao, ChecklistTarefaExecutada
from app.models.user import User

CHECKLIST_COD_LOTO = "LOTO"
CHECKLIST_COD_FINALIZACAO = "FINALIZACAO_OS"


def has_obrigatorio_concluido(db: Session, work_order_id: UUID, codigo_checklist: str) -> bool:
    checklist_id = db.scalar(
        select(ChecklistPadrao.id).where(ChecklistPadrao.codigo_checklist == codigo_checklist, ChecklistPadrao.ativo.is_(True))
    )
    if not checklist_id:
        return False

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
        return False

    for exec_id in exec_ids:
        pendente = db.scalar(
            select(ChecklistTarefaExecutada.id).where(
                ChecklistTarefaExecutada.checklist_executada_id == exec_id,
                ChecklistTarefaExecutada.obrigatoria.is_(True),
                ChecklistTarefaExecutada.executada.is_(False),
            )
        )
        if not pendente:
            return True
    return False


def _execucao_finalizacao_concluida(db: Session, exec_id: UUID) -> bool:
    pendente = db.scalar(
        select(ChecklistTarefaExecutada.id).where(
            ChecklistTarefaExecutada.checklist_executada_id == exec_id,
            ChecklistTarefaExecutada.obrigatoria.is_(True),
            ChecklistTarefaExecutada.executada.is_(False),
        )
    )
    return pendente is None


def _preenchimento_obrigatorias_por_lider(db: Session, exec_id: UUID) -> bool:
    obrig = list(
        db.execute(
            select(ChecklistTarefaExecutada).where(
                ChecklistTarefaExecutada.checklist_executada_id == exec_id,
                ChecklistTarefaExecutada.obrigatoria.is_(True),
            )
        )
        .scalars()
        .all()
    )
    if not obrig:
        return False
    for t in obrig:
        if not t.executada:
            return False
        if t.ultimo_preenchimento_por_id is None:
            return False
        u = db.get(User, t.ultimo_preenchimento_por_id)
        if not u or str(u.perfil_acesso) != "LIDER":
            return False
    return True


def has_finalizacao_concluida_execucao_criada_por_lider(db: Session, work_order_id: UUID) -> bool:
    """FINALIZACAO_OS concluída e válida para técnico: cópia por LIDER OU todas obrigatórias preenchidas por LIDER (último save)."""
    checklist_id = db.scalar(
        select(ChecklistPadrao.id).where(
            ChecklistPadrao.codigo_checklist == CHECKLIST_COD_FINALIZACAO,
            ChecklistPadrao.ativo.is_(True),
        )
    )
    if not checklist_id:
        return False

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
    for exec_id in exec_ids:
        if not _execucao_finalizacao_concluida(db, exec_id):
            continue
        ex = db.get(ChecklistExecutada, exec_id)
        if not ex:
            continue
        copiador = db.get(User, ex.usuario_id)
        if copiador and str(copiador.perfil_acesso) == "LIDER":
            return True
        if _preenchimento_obrigatorias_por_lider(db, exec_id):
            return True
    return False
