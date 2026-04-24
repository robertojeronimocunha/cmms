"""Importação em massa do catálogo de peças (CSV), usada pela API e pelo script CLI."""

from __future__ import annotations

import csv
import io
import unicodedata
from decimal import Decimal, InvalidOperation

from sqlalchemy import select
from sqlalchemy.exc import IntegrityError
from sqlalchemy.orm import Session

from app.models.part import Part
from app.schemas.part import PartCatalogImportResult, PartCatalogImportRowError

MAX_CATALOGO_IMPORT_BYTES = 20 * 1024 * 1024


def _norm_csv_header(key: str | None) -> str:
    if not key:
        return ""
    k = "".join(
        c
        for c in unicodedata.normalize("NFKD", key.strip().lower().replace(" ", "_"))
        if not unicodedata.combining(c)
    )
    aliases = {
        "codigo": "codigo_interno",
        "cod": "codigo_interno",
        "sku": "codigo_interno",
        "erp": "codigo_interno",
        "desc": "descricao",
        "local": "localizacao_almoxarifado",
        "almoxarifado": "localizacao_almoxarifado",
        "controle_estoque": "controla_estoque",
    }
    return aliases.get(k, k)


def _parse_bool_cell(val: object, default: bool = False) -> bool:
    if val is None:
        return default
    s = str(val).strip().lower()
    if s in ("", "0", "n", "nao", "não", "false", "f", "off", "no"):
        return False
    if s in ("1", "s", "sim", "y", "yes", "true", "t", "on", "x"):
        return True
    return default


def _parse_decimal_cell(val: object, default: Decimal = Decimal("0")) -> Decimal:
    if val is None:
        return default
    s = str(val).strip()
    if not s:
        return default
    s = s.replace(",", ".")
    try:
        return Decimal(s)
    except InvalidOperation:
        raise ValueError("número inválido") from None


def _detect_delimiter(first_line: str) -> str:
    if first_line.count(";") > first_line.count(","):
        return ";"
    return ","


def import_catalogo_from_bytes(raw: bytes, db: Session) -> PartCatalogImportResult:
    """
    Upsert por `codigo_interno`. Preserva UUID de peças existentes.
    Levanta ValueError para erros de formato (mensagem curta para HTTP 400).
    """
    if len(raw) > MAX_CATALOGO_IMPORT_BYTES:
        raise ValueError("Arquivo muito grande")
    try:
        text = raw.decode("utf-8-sig")
    except UnicodeDecodeError as exc:
        raise ValueError("Arquivo deve estar em UTF-8") from exc

    if not text.strip():
        raise ValueError("Arquivo vazio")

    first_nl = text.split("\n", 1)[0] if text else ""
    delim = _detect_delimiter(first_nl)
    f = io.StringIO(text)
    reader = csv.DictReader(f, delimiter=delim)
    if not reader.fieldnames:
        raise ValueError("CSV sem cabeçalho")
    header_norm = {_norm_csv_header(h) for h in reader.fieldnames if h is not None}
    if "codigo_interno" not in header_norm:
        raise ValueError("Coluna obrigatória: codigo_interno (ou codigo / Código)")

    inseridos = 0
    atualizados = 0
    linhas_ignoradas = 0
    erros: list[PartCatalogImportRowError] = []
    line_no = 1

    for raw_row in reader:
        line_no += 1
        row: dict[str, str] = {}
        for k, v in raw_row.items():
            nk = _norm_csv_header(k)
            if not nk:
                continue
            row[nk] = (v if v is not None else "").strip()

        cod = (row.get("codigo_interno") or "").strip()
        if not cod:
            if any(row.values()):
                linhas_ignoradas += 1
                erros.append(
                    PartCatalogImportRowError(linha=line_no, detalhe="codigo_interno vazio"),
                )
            else:
                linhas_ignoradas += 1
            continue

        if len(cod) > 80:
            erros.append(
                PartCatalogImportRowError(
                    linha=line_no,
                    detalhe="codigo_interno excede 80 caracteres",
                )
            )
            continue

        desc = (row.get("descricao") or "").strip() or cod
        if len(desc) > 200:
            erros.append(
                PartCatalogImportRowError(
                    linha=line_no,
                    detalhe="descricao excede 200 caracteres",
                )
            )
            continue

        fab = (row.get("fabricante") or "").strip() or None
        if fab and len(fab) > 120:
            erros.append(
                PartCatalogImportRowError(
                    linha=line_no,
                    detalhe="fabricante excede 120 caracteres",
                )
            )
            continue

        loc = (row.get("localizacao_almoxarifado") or "").strip() or None
        if loc and len(loc) > 120:
            erros.append(
                PartCatalogImportRowError(
                    linha=line_no,
                    detalhe="localizacao_almoxarifado excede 120 caracteres",
                )
            )
            continue

        try:
            ea = _parse_decimal_cell(row.get("estoque_atual"), Decimal("0"))
            em = _parse_decimal_cell(row.get("estoque_minimo"), Decimal("0"))
        except ValueError:
            erros.append(
                PartCatalogImportRowError(
                    linha=line_no,
                    detalhe="estoque_atual ou estoque_minimo inválido",
                )
            )
            continue

        tem_ctrl = "controla_estoque" in row

        existing = db.scalar(select(Part).where(Part.codigo_interno == cod))
        try:
            with db.begin_nested():
                if existing:
                    existing.descricao = desc
                    existing.fabricante = fab
                    existing.estoque_atual = ea
                    existing.estoque_minimo = em
                    existing.localizacao_almoxarifado = loc
                    if tem_ctrl:
                        existing.controla_estoque = _parse_bool_cell(row.get("controla_estoque"), False)
                    db.flush()
                    atualizados += 1
                else:
                    ce = _parse_bool_cell(row.get("controla_estoque"), False) if tem_ctrl else False
                    db.add(
                        Part(
                            codigo_interno=cod,
                            descricao=desc,
                            fabricante=fab,
                            estoque_atual=ea,
                            estoque_minimo=em,
                            controla_estoque=ce,
                            localizacao_almoxarifado=loc,
                        )
                    )
                    db.flush()
                    inseridos += 1
        except IntegrityError:
            erros.append(
                PartCatalogImportRowError(
                    linha=line_no,
                    detalhe="conflito ao gravar (código duplicado ou outro vínculo)",
                )
            )
            continue

    db.commit()
    return PartCatalogImportResult(
        inseridos=inseridos,
        atualizados=atualizados,
        linhas_ignoradas=linhas_ignoradas,
        erros=erros,
    )
