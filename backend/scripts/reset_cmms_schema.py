#!/usr/bin/env python3
"""
Recria o schema `public` do banco CMMS alinhado aos modelos SQLAlchemy do FastAPI.

DESTRUTIVO: apaga todas as tabelas, enums e dados do schema public (legado PHP incluso).

Deve ser executado como usuário sistema `postgres` (ex.: sudo -u postgres), para poder
fazer DROP/CREATE e GRANT para cmms_app.

Uso:
  cd /var/www/html/backend && sudo -u postgres ./venv/bin/python scripts/reset_cmms_schema.py --confirm

Depois: seed do primeiro ADMIN e SQLs iniciais — ver database/README.md («Instalação nova»).
"""

from __future__ import annotations

import argparse
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
if str(ROOT) not in sys.path:
    sys.path.insert(0, str(ROOT))

from sqlalchemy import create_engine, text  # noqa: E402
from sqlalchemy.pool import NullPool  # noqa: E402

# Registra todos os modelos em Base.metadata (um import central em app.models).
from app.core.database import Base  # noqa: E402
import app.models  # noqa: F401, E402


def _pg_url(database: str) -> str:
    return f"postgresql+psycopg2://postgres@/{database}"


def _grant_app_role(conn, app_role: str) -> None:
    stmts = [
        f"GRANT USAGE ON SCHEMA public TO {app_role}",
        f"GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO {app_role}",
        f"GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO {app_role}",
        (
            f"ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public "
            f"GRANT ALL ON TABLES TO {app_role}"
        ),
        (
            f"ALTER DEFAULT PRIVILEGES FOR ROLE postgres IN SCHEMA public "
            f"GRANT ALL ON SEQUENCES TO {app_role}"
        ),
    ]
    for s in stmts:
        conn.execute(text(s))


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Remove schema legado e cria tabelas do CMMS FastAPI",
    )
    parser.add_argument(
        "--confirm",
        action="store_true",
        help="Obrigatório (confirma que você fez backup e aceita apagar tudo)",
    )
    parser.add_argument("--database", default="cmms", help="Nome do banco (default: cmms)")
    parser.add_argument(
        "--app-role",
        default="cmms_app",
        help="Role da aplicação que receberá GRANT (default: cmms_app)",
    )
    args = parser.parse_args()

    if not args.confirm:
        print(
            "ERRO: Operação destrutiva. Faça backup antes. "
            "Execute com --confirm para prosseguir.",
            file=sys.stderr,
        )
        sys.exit(1)

    url = _pg_url(args.database)
    # DDL com autocommit evita problemas com DROP SCHEMA em algumas versões do PG
    engine_ac = create_engine(
        url, poolclass=NullPool, future=True, isolation_level="AUTOCOMMIT"
    )

    print(f"DROP SCHEMA public CASCADE e recriar (banco={args.database})...")
    with engine_ac.connect() as conn:
        conn.execute(text("DROP SCHEMA IF EXISTS public CASCADE"))
        conn.execute(text("CREATE SCHEMA public"))
        conn.execute(text("GRANT ALL ON SCHEMA public TO postgres"))
        conn.execute(text("GRANT ALL ON SCHEMA public TO PUBLIC"))
    engine_ac.dispose()

    engine = create_engine(url, poolclass=NullPool, future=True)
    print("CREATE TABLE (modelos em app/models/)...")
    Base.metadata.create_all(bind=engine)

    print(f"GRANT em {args.app_role}...")
    with engine.begin() as conn:
        _grant_app_role(conn, args.app_role)

    engine.dispose()

    names = sorted(Base.metadata.tables.keys())
    print(f"OK. {len(names)} tabela(s) criadas a partir de app/models:")
    for t in names:
        print(f"  - {t}")
    print("Próximo passo: ./venv/bin/python scripts/seed_admin.py (com backend/.env)")
    print("e SQLs iniciais em database/README.md — secção «Instalação nova».")


if __name__ == "__main__":
    main()
