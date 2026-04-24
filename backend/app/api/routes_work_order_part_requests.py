from decimal import Decimal
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user, require_roles
from app.core.database import get_db
from app.models.user import User
from app.models.work_order import WorkOrder
from app.models.work_order_log import WorkOrderLog
from app.models.work_order_part_request import WorkOrderPartRequest
from app.services.os_checklist_obrigatorio import has_loto_cadeia_concluida, work_order_exige_loto_cadeia_para_interacao
from app.schemas.work_order_part_request import (
    WorkOrderPartRequestCreate,
    WorkOrderPartRequestResponse,
    WorkOrderPartRequestUpdateAdmin,
)

router = APIRouter(prefix="/ordens-servico", tags=["os-solicitacoes-pecas"])


@router.get("/{work_order_id}/solicitacoes-pecas", response_model=list[WorkOrderPartRequestResponse])
def list_part_requests(
    work_order_id: UUID,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")

    rows = (
        db.execute(
            select(WorkOrderPartRequest, User.nome_completo)
            .join(User, WorkOrderPartRequest.solicitante_id == User.id)
            .where(WorkOrderPartRequest.ordem_servico_id == work_order_id)
            .order_by(WorkOrderPartRequest.created_at.desc())
        )
        .all()
    )
    out: list[WorkOrderPartRequestResponse] = []
    for req, nome in rows:
        out.append(
            WorkOrderPartRequestResponse(
                id=req.id,
                ordem_servico_id=req.ordem_servico_id,
                solicitante_id=req.solicitante_id,
                solicitante_nome=nome,
                codigo_peca=req.codigo_peca,
                descricao=req.descricao,
                quantidade=float(req.quantidade),
                numero_solicitacao_erp=req.numero_solicitacao_erp,
                preco_unitario=float(req.preco_unitario) if req.preco_unitario is not None else None,
                created_at=req.created_at,
            )
        )
    return out


@router.post("/{work_order_id}/solicitacoes-pecas", response_model=WorkOrderPartRequestResponse, status_code=status.HTTP_201_CREATED)
def create_part_request(
    work_order_id: UUID,
    payload: WorkOrderPartRequestCreate,
    user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR")),
    db: Session = Depends(get_db),
):
    work_order = db.get(WorkOrder, work_order_id)
    if not work_order:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")

    wo_st = str(work_order.status.value if hasattr(work_order.status, "value") else work_order.status)
    if work_order_exige_loto_cadeia_para_interacao(wo_st) and not has_loto_cadeia_concluida(db, work_order.id):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Conclua os checklists LOTO e LOTO_LIDER nesta OS antes de solicitar pecas.",
        )

    descricao = payload.descricao.strip()
    codigo = (payload.codigo_peca or "").strip() or None

    req = WorkOrderPartRequest(
        ordem_servico_id=work_order_id,
        solicitante_id=user.id,
        codigo_peca=codigo,
        descricao=descricao,
        quantidade=payload.quantidade,
    )
    db.add(req)
    db.commit()
    db.refresh(req)
    return WorkOrderPartRequestResponse(
        id=req.id,
        ordem_servico_id=req.ordem_servico_id,
        solicitante_id=req.solicitante_id,
        solicitante_nome=user.nome_completo,
        codigo_peca=req.codigo_peca,
        descricao=req.descricao,
        quantidade=float(req.quantidade),
        numero_solicitacao_erp=req.numero_solicitacao_erp,
        preco_unitario=float(req.preco_unitario) if req.preco_unitario is not None else None,
        created_at=req.created_at,
    )


def _norm_cod(v: str | None) -> str | None:
    if not v:
        return None
    s = v.strip()
    return s or None


def _short_txt(t: str, n: int = 56) -> str:
    one = t.replace("\n", " ").strip()
    return one if len(one) <= n else one[: n - 1] + "…"


@router.patch("/solicitacoes-pecas/{request_id}", response_model=WorkOrderPartRequestResponse)
def update_part_request_admin(
    request_id: UUID,
    payload: WorkOrderPartRequestUpdateAdmin,
    user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    req = db.get(WorkOrderPartRequest, request_id)
    if not req:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Solicitacao nao encontrada")

    data = payload.model_dump(exclude_unset=True)
    if not data:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Nenhum campo para atualizar",
        )

    changes: list[str] = []

    if "codigo_peca" in data:
        newv = _norm_cod(data["codigo_peca"])
        oldv = _norm_cod(req.codigo_peca)
        if newv != oldv:
            changes.append(f"código: {oldv or '—'} → {newv or '—'}")
        req.codigo_peca = newv

    if "descricao" in data:
        newv = data["descricao"].strip()
        if newv != req.descricao:
            changes.append(f"descrição: «{_short_txt(req.descricao)}» → «{_short_txt(newv)}»")
        req.descricao = newv

    if "quantidade" in data:
        newq = Decimal(str(data["quantidade"]))
        if newq != req.quantidade:
            changes.append(f"quantidade: {float(req.quantidade)} → {float(newq)}")
        req.quantidade = newq

    if "numero_solicitacao_erp" in data:
        newv = _norm_cod(data["numero_solicitacao_erp"])
        oldv = _norm_cod(req.numero_solicitacao_erp)
        if newv != oldv:
            changes.append(f"Nº pedido ERP: {oldv or '—'} → {newv or '—'}")
        req.numero_solicitacao_erp = newv

    if "preco_unitario" in data:
        raw = data["preco_unitario"]
        newp: Decimal | None = None if raw is None else Decimal(str(raw))
        oldp = req.preco_unitario
        old_f = float(oldp) if oldp is not None else None
        new_f = float(newp) if newp is not None else None
        same = (old_f is None and new_f is None) or (
            old_f is not None
            and new_f is not None
            and abs(old_f - new_f) < 0.005
        )
        if not same:
            changes.append(
                f"preço unit. (R$): {old_f if old_f is not None else '—'} → {new_f if new_f is not None else '—'}"
            )
        req.preco_unitario = newp

    wo = db.get(WorkOrder, req.ordem_servico_id)
    if not wo:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="OS nao encontrada")
    st = str(wo.status.value if hasattr(wo.status, "value") else wo.status)

    if changes:
        log = WorkOrderLog(
            ordem_servico_id=req.ordem_servico_id,
            usuario_id=user.id,
            status_anterior=st,
            status_novo=st,
            descricao="ALTERADO: " + "; ".join(changes),
        )
        db.add(log)

    db.add(req)
    db.commit()
    db.refresh(req)

    solicitante_nome = db.scalar(select(User.nome_completo).where(User.id == req.solicitante_id))
    return WorkOrderPartRequestResponse(
        id=req.id,
        ordem_servico_id=req.ordem_servico_id,
        solicitante_id=req.solicitante_id,
        solicitante_nome=solicitante_nome,
        codigo_peca=req.codigo_peca,
        descricao=req.descricao,
        quantidade=float(req.quantidade),
        numero_solicitacao_erp=req.numero_solicitacao_erp,
        preco_unitario=float(req.preco_unitario) if req.preco_unitario is not None else None,
        created_at=req.created_at,
    )
