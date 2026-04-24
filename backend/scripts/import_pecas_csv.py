#!/usr/bin/env python3
"""
Importa catálogo de peças a partir de um CSV (mesma lógica de POST /pecas/catalogo-import).
Uso no servidor (sem JWT), com o mesmo DATABASE_URL do backend:

  cd /var/www/html/backend && ./venv/bin/python scripts/import_pecas_csv.py /caminho/pecas.csv

Requer acesso ao PostgreSQL configurado em backend/.env.
"""

from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT))

from app.core.database import SessionLocal  # noqa: E402
from app.services.part_catalog_import import import_catalogo_from_bytes  # noqa: E402


def main() -> int:
    parser = argparse.ArgumentParser(description="Importar pecas.csv para o catálogo")
    parser.add_argument("arquivo", type=Path, help="Caminho do arquivo CSV (UTF-8)")
    args = parser.parse_args()
    path: Path = args.arquivo
    if not path.is_file():
        print(f"Arquivo não encontrado: {path}", file=sys.stderr)
        return 1
    raw = path.read_bytes()
    db = SessionLocal()
    try:
        result = import_catalogo_from_bytes(raw, db)
    except ValueError as e:
        print(str(e), file=sys.stderr)
        return 1
    finally:
        db.close()
    print(json.dumps(result.model_dump(), indent=2, ensure_ascii=False, default=str))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
