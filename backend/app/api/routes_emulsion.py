from datetime import datetime, timezone
from decimal import Decimal
from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.emulsion import EmulsionInspection
from app.models.user import User
from app.schemas.emulsion import (
    EmulsionAssetItem,
    EmulsionCorrectionCreate,
    EmulsionInspectionCreate,
    EmulsionInspectionListItem,
    EmulsionInspectionResponse,
    EmulsionTaskItem,
    EmulsionUltimasMedicoesItem,
    MedicaoEmulsaoResumo,
)
from app.services.emulsion_service import evaluate_emulsion

router = APIRouter(prefix="/emulsao", tags=["emulsao"])

_BRIX_BY_PERFIL: dict[str, tuple[str, str, str]] = {
    "LEVE": ("6.0", "10.0", "8.0"),
    "PESADO": ("10.0", "14.0", "12.0"),
}


@router.get("/ultimas-medicoes-por-ativo", response_model=list[EmulsionUltimasMedicoesItem])
def ultimas_medicoes_por_ativo(
    ativo_ids: str = Query(
        ...,
        description="UUIDs dos ativos separados por vírgula (ex.: id1,id2). Lê cache em ativos.",
    ),
    _user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR", "LIDER", "DIRETORIA")),
    db: Session = Depends(get_db),
):
    parts = [p.strip() for p in ativo_ids.split(",") if p.strip()]
    if not parts:
        return []
    try:
        uuid_list = [UUID(p) for p in parts]
    except ValueError as exc:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="ativo_ids invalido (esperado lista de UUIDs separados por virgula)",
        ) from exc

    rows = list(db.execute(select(Asset).where(Asset.id.in_(uuid_list))).scalars().all())
    by_id = {a.id: a for a in rows}

    out: list[EmulsionUltimasMedicoesItem] = []
    for aid in uuid_list:
        a = by_id.get(aid)
        if not a:
            out.append(
                EmulsionUltimasMedicoesItem(
                    ativo_id=aid,
                    ultima_concentracao=None,
                    ultima_ph=None,
                )
            )
            continue
        uc = None
        if a.emulsao_ultima_concentracao is not None and a.emulsao_ultima_concentracao_em is not None:
            uc = MedicaoEmulsaoResumo(
                valor=a.emulsao_ultima_concentracao,
                data_inspecao=a.emulsao_ultima_concentracao_em,
            )
        up = None
        if a.emulsao_ultimo_ph is not None and a.emulsao_ultimo_ph_em is not None:
            up = MedicaoEmulsaoResumo(
                valor=a.emulsao_ultimo_ph,
                data_inspecao=a.emulsao_ultimo_ph_em,
            )
        out.append(
            EmulsionUltimasMedicoesItem(
                ativo_id=aid,
                ultima_concentracao=uc,
                ultima_ph=up,
            )
        )
    return out


@router.get("/ativos", response_model=list[EmulsionAssetItem])
def list_emulsion_assets(
    somente_controle: bool = Query(default=True),
    _user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR", "LIDER")),
    db: Session = Depends(get_db),
):
    query = select(Asset).order_by(Asset.tag_ativo.asc())
    if somente_controle:
        query = query.where(Asset.controle_emulsao.is_(True))
    rows = list(db.execute(query).scalars().all())
    return [
        EmulsionAssetItem(
            id=a.id,
            tag_ativo=a.tag_ativo,
            descricao=a.descricao,
            perfil_usinagem=str(a.perfil_usinagem.value if hasattr(a.perfil_usinagem, "value") else a.perfil_usinagem),
            tanque_oleo_soluvel=a.tanque_oleo_soluvel,
        )
        for a in rows
    ]


@router.post("/inspecoes", response_model=EmulsionInspectionResponse, status_code=status.HTTP_201_CREATED)
def create_inspection(
    payload: EmulsionInspectionCreate,
    user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR")),
    db: Session = Depends(get_db),
):
    asset = db.get(Asset, payload.ativo_id)
    if not asset:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ativo nao encontrado")
    if not asset.controle_emulsao:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Ativo sem controle de emulsao habilitado")
    if payload.valor_brix is None and payload.valor_ph is None:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Informe ao menos concentração ou pH na aferição.",
        )

    perfil_usinagem = str(asset.perfil_usinagem.value if hasattr(asset.perfil_usinagem, "value") else asset.perfil_usinagem)
    brix_cfg = _BRIX_BY_PERFIL.get(perfil_usinagem, _BRIX_BY_PERFIL["LEVE"])
    asset_brix_min = Decimal(brix_cfg[0])
    asset_brix_max = Decimal(brix_cfg[1])
    asset_brix_alvo = Decimal(brix_cfg[2])

    eval_result = evaluate_emulsion(
        valor_brix=payload.valor_brix,
        valor_ph=payload.valor_ph,
        volume_tanque_litros=payload.volume_tanque_litros or asset.tanque_oleo_soluvel,
        brix_min=asset_brix_min,
        brix_max=asset_brix_max,
        brix_alvo=asset_brix_alvo,
    )

    inspection = EmulsionInspection(
        ativo_id=payload.ativo_id,
        tecnico_id=user.id,
        valor_brix=payload.valor_brix,
        valor_ph=payload.valor_ph,
        volume_tanque_litros=payload.volume_tanque_litros or asset.tanque_oleo_soluvel,
        observacoes=payload.observacoes,
        status_inspecao=eval_result["status_inspecao"],
        precisa_correcao=eval_result["precisa_correcao"],
        volume_agua_sugerido=eval_result["volume_agua_sugerido"],
        volume_oleo_sugerido=eval_result["volume_oleo_sugerido"],
    )
    # Atualiza status da máquina com base na aferição parcial/total.
    asset.status = "PARADO" if eval_result["precisa_correcao"] else "OPERANDO"
    db.add(inspection)
    db.add(asset)
    db.commit()
    db.refresh(inspection)
    ts = inspection.data_inspecao
    if payload.valor_brix is not None and ts is not None:
        asset.emulsao_ultima_concentracao = inspection.valor_brix
        asset.emulsao_ultima_concentracao_em = ts
    if payload.valor_ph is not None and ts is not None:
        asset.emulsao_ultimo_ph = inspection.valor_ph
        asset.emulsao_ultimo_ph_em = ts
    db.add(asset)
    db.commit()
    db.refresh(asset)
    return inspection


@router.get("/inspecoes", response_model=list[EmulsionInspectionListItem])
def list_inspections(
    pendentes: bool = Query(default=False),
    limit: int = Query(default=100, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR", "LIDER", "DIRETORIA")),
    db: Session = Depends(get_db),
):
    query = (
        select(EmulsionInspection, Asset.tag_ativo, Asset.perfil_usinagem)
        .join(Asset, EmulsionInspection.ativo_id == Asset.id)
        .order_by(EmulsionInspection.data_inspecao.desc())
        .limit(limit)
        .offset(offset)
    )
    if pendentes:
        query = query.where(EmulsionInspection.precisa_correcao.is_(True), EmulsionInspection.data_correcao.is_(None))
    rows = db.execute(query).all()
    return [
        EmulsionInspectionListItem(
            id=i.id,
            ativo_id=i.ativo_id,
            tag_ativo=tag,
            perfil_usinagem=str(pu.value if hasattr(pu, "value") else pu),
            data_inspecao=i.data_inspecao,
            valor_brix=i.valor_brix,
            valor_ph=i.valor_ph,
            status_inspecao=i.status_inspecao,
            precisa_correcao=i.precisa_correcao,
            volume_agua_sugerido=i.volume_agua_sugerido,
            volume_oleo_sugerido=i.volume_oleo_sugerido,
            data_correcao=i.data_correcao,
        )
        for i, tag, pu in rows
    ]


@router.get("/tarefas-ajuste", response_model=list[EmulsionTaskItem])
def list_adjust_tasks(
    limit: int = Query(default=100, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR", "LIDER", "DIRETORIA")),
    db: Session = Depends(get_db),
):
    query = (
        select(EmulsionInspection, Asset.tag_ativo, Asset.perfil_usinagem)
        .join(Asset, EmulsionInspection.ativo_id == Asset.id)
        .where(EmulsionInspection.precisa_correcao.is_(True))
        .order_by(EmulsionInspection.data_correcao.asc().nullsfirst(), EmulsionInspection.data_inspecao.asc())
        .limit(limit)
        .offset(offset)
    )
    rows = db.execute(query).all()
    out: list[EmulsionTaskItem] = []
    for i, tag, pu in rows:
        out.append(
            EmulsionTaskItem(
                inspecao_id=i.id,
                ativo_id=i.ativo_id,
                tag_ativo=tag,
                perfil_usinagem=str(pu.value if hasattr(pu, "value") else pu),
                data_inspecao=i.data_inspecao,
                volume_agua_sugerido=i.volume_agua_sugerido,
                volume_oleo_sugerido=i.volume_oleo_sugerido,
                volume_agua_real=i.volume_agua_real,
                volume_oleo_real=i.volume_oleo_real,
                status="CONCLUIDA" if i.data_correcao else "PENDENTE",
            )
        )
    return out


@router.post("/inspecoes/{inspection_id}/executar-ajuste", response_model=EmulsionInspectionResponse)
def execute_adjustment(
    inspection_id: str,
    payload: EmulsionCorrectionCreate,
    _user: User = Depends(require_roles("ADMIN", "TECNICO", "LUBRIFICADOR")),
    db: Session = Depends(get_db),
):
    inspection = db.get(EmulsionInspection, inspection_id)
    if not inspection:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Inspecao nao encontrada")
    if not inspection.precisa_correcao:
        raise HTTPException(status_code=status.HTTP_422_UNPROCESSABLE_ENTITY, detail="Inspecao nao requer correcao")

    inspection.volume_agua_real = payload.volume_agua_real
    inspection.volume_oleo_real = payload.volume_oleo_real
    inspection.data_correcao = datetime.now(timezone.utc)
    if payload.observacoes is not None:
        base = (inspection.observacoes or "").strip()
        extra = payload.observacoes.strip()
        inspection.observacoes = (base + "\n" + extra).strip() if (base and extra) else (extra or base or None)

    asset = db.get(Asset, inspection.ativo_id)
    if asset:
        asset.status = "OPERANDO"
        db.add(asset)

    db.add(inspection)
    db.commit()
    db.refresh(inspection)
    return inspection
