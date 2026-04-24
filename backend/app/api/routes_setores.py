from uuid import UUID

from fastapi import APIRouter, Depends, HTTPException, Query, status
from sqlalchemy import delete, func, select
from sqlalchemy.orm import Session, selectinload

from app.auth.dependencies import require_com_catalogo, require_leitura_setores, require_roles
from app.core.database import get_db
from app.models.asset import Asset
from app.models.setor import Setor
from app.models.setor_responsavel import SetorResponsavel
from app.models.user import User
from app.schemas.setor import SetorCreate, SetorResponsavelItem, SetorResponse, SetorUpdate

router = APIRouter(prefix="/setores", tags=["setores"])


def _norm_tag(t: str) -> str:
    return t.strip()


def _validar_responsavel_ids(db: Session, ids: list[UUID]) -> None:
    for uid in ids:
        if not db.get(User, uid):
            raise HTTPException(
                status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
                detail="Usuario responsavel nao encontrado",
            )


def _sync_responsaveis(db: Session, row: Setor, ids: list[UUID]) -> None:
    db.execute(delete(SetorResponsavel).where(SetorResponsavel.setor_id == row.id))
    for i, uid in enumerate(ids):
        db.add(SetorResponsavel(setor_id=row.id, usuario_id=uid, ordem=i))
    row.responsavel1_id = ids[0] if ids else None
    row.responsavel2_id = ids[1] if len(ids) > 1 else None


def _nome_por_usuario(db: Session, uids: set[UUID]) -> dict[UUID, str | None]:
    if not uids:
        return {}
    q = select(User.id, User.nome_completo).where(User.id.in_(uids))
    return {uid: nome for uid, nome in db.execute(q).all()}


def _setor_to_response(
    db: Session,
    row: Setor,
    nomes: dict[UUID, str | None] | None = None,
) -> SetorResponse:
    links = sorted(row.responsaveis_assoc, key=lambda x: x.ordem)
    uids = [l.usuario_id for l in links]
    if nomes is None:
        nomes = _nome_por_usuario(db, set(uids))
    resp_items = [SetorResponsavelItem(usuario_id=uid, nome_completo=nomes.get(uid)) for uid in uids]
    r1id = uids[0] if uids else None
    r2id = uids[1] if len(uids) > 1 else None
    return SetorResponse(
        id=row.id,
        tag_setor=row.tag_setor,
        descricao=row.descricao,
        responsaveis=resp_items,
        responsavel1_id=r1id,
        responsavel1_nome=nomes.get(r1id) if r1id else None,
        responsavel2_id=r2id,
        responsavel2_nome=nomes.get(r2id) if r2id else None,
        ativo=row.ativo,
    )


def _setores_to_responses(db: Session, rows: list[Setor]) -> list[SetorResponse]:
    all_uids: set[UUID] = set()
    for r in rows:
        for l in r.responsaveis_assoc:
            all_uids.add(l.usuario_id)
    nomes = _nome_por_usuario(db, all_uids)
    return [_setor_to_response(db, r, nomes) for r in rows]


def _get_setor_carregado(db: Session, sid: UUID) -> Setor:
    return db.execute(
        select(Setor).options(selectinload(Setor.responsaveis_assoc)).where(Setor.id == sid)
    ).scalar_one()


@router.get("", response_model=list[SetorResponse])
def list_setores(
    ativo: bool | None = Query(default=None, description="true/false = filtrar; omitir = todos"),
    limit: int = Query(default=200, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_leitura_setores()),
    db: Session = Depends(get_db),
):
    query = (
        select(Setor)
        .options(selectinload(Setor.responsaveis_assoc))
        .order_by(Setor.tag_setor.asc())
        .limit(limit)
        .offset(offset)
    )
    if ativo is not None:
        query = query.where(Setor.ativo == ativo)
    rows = list(db.execute(query).scalars().unique().all())
    return _setores_to_responses(db, rows)


@router.get("/{setor_id}", response_model=SetorResponse)
def get_setor(
    setor_id: UUID,
    _user: User = Depends(require_leitura_setores()),
    db: Session = Depends(get_db),
):
    row = db.get(Setor, setor_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Setor nao encontrado")
    _ = row.responsaveis_assoc
    return _setor_to_response(db, row)


@router.post("", response_model=SetorResponse, status_code=status.HTTP_201_CREATED)
def create_setor(
    payload: SetorCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    tag = _norm_tag(payload.tag_setor)
    desc = payload.descricao.strip()
    exists = db.scalar(
        select(Setor.id).where(func.lower(Setor.tag_setor) == tag.lower()),
    )
    if exists:
        raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Ja existe setor com esta tag")
    _validar_responsavel_ids(db, payload.responsavel_ids)
    row = Setor(
        tag_setor=tag,
        descricao=desc,
        responsavel1_id=None,
        responsavel2_id=None,
        ativo=payload.ativo,
    )
    db.add(row)
    db.flush()
    _sync_responsaveis(db, row, payload.responsavel_ids)
    db.commit()
    row = _get_setor_carregado(db, row.id)
    return _setor_to_response(db, row)


@router.patch("/{setor_id}", response_model=SetorResponse)
def update_setor(
    setor_id: UUID,
    payload: SetorUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(Setor, setor_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Setor nao encontrado")
    data = payload.model_dump(exclude_none=True)
    if "tag_setor" in data:
        tag = _norm_tag(data["tag_setor"])
        conflict = db.scalar(
            select(Setor.id).where(
                func.lower(Setor.tag_setor) == tag.lower(),
                Setor.id != setor_id,
            )
        )
        if conflict:
            raise HTTPException(status_code=status.HTTP_409_CONFLICT, detail="Ja existe setor com esta tag")
        row.tag_setor = tag
    if "descricao" in data:
        row.descricao = data["descricao"].strip()
    if "responsavel_ids" in data:
        _validar_responsavel_ids(db, data["responsavel_ids"])
        _sync_responsaveis(db, row, data["responsavel_ids"])
    if "ativo" in data:
        row.ativo = data["ativo"]
    db.add(row)
    db.commit()
    row = _get_setor_carregado(db, row.id)
    return _setor_to_response(db, row)


@router.delete("/{setor_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_setor(
    setor_id: UUID,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    row = db.get(Setor, setor_id)
    if not row:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Setor nao encontrado")
    em_uso = db.scalar(select(Asset.id).where(Asset.setor_id == setor_id).limit(1))
    if em_uso:
        raise HTTPException(
            status_code=status.HTTP_409_CONFLICT,
            detail="Setor em uso por ativos; desative ou reatribua os ativos antes.",
        )
    db.delete(row)
    db.commit()
    return None
