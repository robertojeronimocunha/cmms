"""Gera bytes de planilha .xlsx (openpyxl) para exportação de relatórios."""

from __future__ import annotations

import io
from datetime import date, datetime
from decimal import Decimal
from uuid import UUID


def _cell_value(v: object) -> object:
    if v is None:
        return None
    if isinstance(v, (datetime, date)):
        return v.isoformat() if v else None
    if isinstance(v, UUID):
        return str(v)
    if isinstance(v, Decimal):
        return float(v)
    if isinstance(v, (bytes, bytearray)):
        return v.decode("utf-8", errors="replace")
    return v


def build_xlsx_bytes(
    headers: list[str],
    rows: list[list[object]],
    sheet_title: str = "Dados",
) -> bytes:
    from openpyxl import Workbook

    wb = Workbook()
    ws = wb.active
    st = (sheet_title or "Dados").replace("/", "-")[:31]
    ws.title = st
    ws.append(headers)
    for row in rows:
        ws.append([_cell_value(x) for x in row])
    buf = io.BytesIO()
    wb.save(buf)
    return buf.getvalue()
