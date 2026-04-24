from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import select
from sqlalchemy.orm import Session

from app.auth.dependencies import get_current_user, require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.ativo_categoria import AtivoCategoria
from app.models.emulsion import EmulsionInspection
from app.models.lubrication_point import LubricationPoint
from app.models.maintenance_plan import MaintenancePlan
from app.models.user import User
from app.models.work_order import WorkOrder
from app.schemas.asset import AssetCreate, AssetResponse, AssetUpdate

router = APIRouter(prefix="/ativos", tags=["ativos"])

_GESTAO = frozenset({"ADMIN"})


def _validar_categoria_id(db: Session, categoria_id: UUID | None) -> None:
    if categoria_id is None:
        return
    if not db.get(AtivoCategoria, categoria_id):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Categoria de ativo nao encontrada",
        )


def _ensure_emulsao_tanque(asset: Asset) -> None:
    if asset.controle_emulsao and (asset.tanque_oleo_soluvel is None or asset.tanque_oleo_soluvel <= 0):
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="Com controle de emulsao ativo, informe tanque_oleo_soluvel (inteiro maior que zero)",
        )


def _apply_update_gestao(asset: Asset, data: dict) -> None:
    for key, value in data.items():
        setattr(asset, key, value)


@router.get("", response_model=list[AssetResponse])
def list_assets(
    status_filter: str | None = Query(default=None, alias="status"),
    limit: int = Query(default=50, ge=1, le=2000),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    query = select(Asset).order_by(Asset.tag_ativo.asc()).limit(limit).offset(offset)
    if status_filter:
        query = query.where(Asset.status == status_filter)
    return list(db.execute(query).scalars().all())


@router.get("/{asset_id}", response_model=AssetResponse)
def get_asset(
    asset_id: UUID,
    _user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    asset = db.get(Asset, asset_id)
    if not asset:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ativo nao encontrado")
    return asset


@router.post("", response_model=AssetResponse, status_code=status.HTTP_201_CREATED)
def create_asset(
    payload: AssetCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    if db.scalar(select(Asset.id).where(Asset.tag_ativo == payload.tag_ativo).limit(1)):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Ja existe ativo com esta tag",
        )
    _validar_categoria_id(db, payload.categoria_id)
    asset = Asset(**payload.model_dump())
    _ensure_emulsao_tanque(asset)
    db.add(asset)
    db.commit()
    db.refresh(asset)
    return asset


@router.patch("/{asset_id}", response_model=AssetResponse)
def update_asset(
    asset_id: UUID,
    payload: AssetUpdate,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    asset = db.get(Asset, asset_id)
    if not asset:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ativo nao encontrado")

    data = payload.model_dump(exclude_none=True)
    if "categoria_id" in data:
        _validar_categoria_id(db, data["categoria_id"])

    if user.perfil_acesso in _GESTAO:
        if "tag_ativo" in data and data["tag_ativo"] != asset.tag_ativo:
            exists = db.scalar(
                select(Asset.id).where(Asset.tag_ativo == data["tag_ativo"], Asset.id != asset_id).limit(1)
            )
            if exists:
                raise HTTPException(
                    status_code=status.HTTP_409_CONFLICT,
                    detail="Ja existe outro ativo com esta tag",
                )
        _apply_update_gestao(asset, data)
    elif user.perfil_acesso == "LIDER":
        extra = set(data.keys()) - {"perfil_usinagem"}
        if extra:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="LIDER só pode alterar o perfil de usinagem (leve/pesado)",
            )
        if "perfil_usinagem" in data:
            asset.perfil_usinagem = data["perfil_usinagem"]
    else:
        raise HTTPException(status_code=status.HTTP_403_FORBIDDEN, detail="Sem permissao para editar ativo")

    _ensure_emulsao_tanque(asset)
    db.add(asset)
    db.commit()
    db.refresh(asset)
    return asset


@router.delete("/{asset_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_asset(
    asset_id: UUID,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    asset = db.get(Asset, asset_id)
    if not asset:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Ativo nao encontrado")

    if db.scalar(select(WorkOrder.id).where(WorkOrder.ativo_id == asset_id).limit(1)):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Ativo possui ordens de servico vinculadas",
        )
    if db.scalar(select(MaintenancePlan.id).where(MaintenancePlan.ativo_id == asset_id).limit(1)):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Ativo possui planos de preventiva vinculados",
        )
    if db.scalar(select(LubricationPoint.id).where(LubricationPoint.ativo_id == asset_id).limit(1)):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Ativo possui pontos de lubrificacao vinculados",
        )
    if db.scalar(select(EmulsionInspection.id).where(EmulsionInspection.ativo_id == asset_id).limit(1)):
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Ativo possui inspecoes de emulsao vinculadas",
        )

    db.delete(asset)
    db.commit()
    return None
