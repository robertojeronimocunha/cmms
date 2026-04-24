import csv
import io
from uuid import UUID

from fastapi import APIRouter, Depends, File, HTTPException, Query, UploadFile, status
from fastapi.responses import Response
from sqlalchemy import or_, select
from sqlalchemy.orm import Session

from app.auth.dependencies import PERFIS_LEITURA_PECAS, require_com_catalogo, require_roles
from app.core.database import get_db
from app.models.part import Part
from app.models.user import User
from app.schemas.part import PartCatalogImportResult, PartCreate, PartResponse, PartUpdate
from app.services.part_catalog_import import import_catalogo_from_bytes

router = APIRouter(prefix="/pecas", tags=["pecas"])


@router.get("/catalogo-export")
def export_catalogo_csv(
    _user: User = Depends(require_com_catalogo()),
    db: Session = Depends(get_db),
):
    """CSV para edição em massa / ERP. Colunas: id;codigo_interno;...;controla_estoque;localizacao_almoxarifado"""
    rows = db.execute(select(Part).order_by(Part.codigo_interno.asc())).scalars().all()
    buf = io.StringIO()
    w = csv.writer(buf, delimiter=";", quoting=csv.QUOTE_MINIMAL)
    w.writerow(
        [
            "id",
            "codigo_interno",
            "descricao",
            "fabricante",
            "estoque_atual",
            "estoque_minimo",
            "controla_estoque",
            "localizacao_almoxarifado",
        ]
    )
    for p in rows:
        w.writerow(
            [
                str(p.id),
                p.codigo_interno,
                p.descricao,
                p.fabricante or "",
                str(p.estoque_atual),
                str(p.estoque_minimo),
                "true" if p.controla_estoque else "false",
                p.localizacao_almoxarifado or "",
            ]
        )
    body = "\ufeff" + buf.getvalue()
    return Response(
        content=body.encode("utf-8"),
        media_type="text/csv; charset=utf-8",
        headers={
            "Content-Disposition": 'attachment; filename="pecas_catalogo.csv"',
        },
    )


@router.post("/catalogo-import", response_model=PartCatalogImportResult)
async def import_catalogo_csv(
    file: UploadFile = File(..., description="CSV UTF-8 (delimitador ; ou ,)"),
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    """
    Importação em massa: upsert por `codigo_interno` (preserva `id` e vínculos em solicitações de OS).
    Não altera `ordens_servico` nem linhas de `os_solicitacoes_pecas`.
    """
    raw = await file.read()
    try:
        return import_catalogo_from_bytes(raw, db)
    except ValueError as exc:
        msg = str(exc)
        code = (
            status.HTTP_413_REQUEST_ENTITY_TOO_LARGE
            if "muito grande" in msg
            else status.HTTP_400_BAD_REQUEST
        )
        raise HTTPException(status_code=code, detail=msg) from exc


@router.get("", response_model=list[PartResponse])
def list_parts(
    response: Response,
    q: str | None = Query(
        default=None,
        description="Busca por trecho em código interno ou descrição (ilike).",
        max_length=120,
    ),
    abaixo_minimo: bool = Query(default=False),
    limit: int = Query(default=50, ge=1, le=100_000),
    offset: int = Query(default=0, ge=0),
    _user: User = Depends(require_roles(*sorted(PERFIS_LEITURA_PECAS))),
    db: Session = Depends(get_db),
):
    query = select(Part)
    if abaixo_minimo:
        query = query.where(
            Part.controla_estoque.is_(True),
            Part.estoque_atual <= Part.estoque_minimo,
        )
    term = (q or "").strip()
    if term:
        pat = f"%{term}%"
        query = query.where(
            or_(Part.codigo_interno.ilike(pat), Part.descricao.ilike(pat)),
        )
    query = query.order_by(Part.codigo_interno.asc()).limit(limit).offset(offset)
    response.headers["Cache-Control"] = "no-store"
    return list(db.execute(query).scalars().all())


@router.post("", response_model=PartResponse, status_code=status.HTTP_201_CREATED)
def create_part(
    payload: PartCreate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    part = Part(**payload.model_dump())
    db.add(part)
    db.commit()
    db.refresh(part)
    return part


@router.patch("/{part_id}", response_model=PartResponse)
def update_part(
    part_id: UUID,
    payload: PartUpdate,
    _user: User = Depends(require_roles("ADMIN")),
    db: Session = Depends(get_db),
):
    part = db.get(Part, part_id)
    if not part:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail="Peca nao encontrada")
    for key, value in payload.model_dump(exclude_none=True).items():
        setattr(part, key, value)
    db.add(part)
    db.commit()
    db.refresh(part)
    return part
