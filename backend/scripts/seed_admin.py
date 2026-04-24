#!/usr/bin/env python3
"""
Cria o primeiro usuário ADMIN no banco (uso único / bootstrap).

A API POST /usuarios exige token ADMIN — por isso este script grava direto no PostgreSQL.

Uso:
  cd /var/www/html/backend && ./venv/bin/python scripts/seed_admin.py

Variáveis opcionais (senão usa padrões abaixo):
  CMMS_SEED_EMAIL      (default: admin@cmms.local)
  CMMS_SEED_PASSWORD
  CMMS_SEED_NOME       (default: Administrador CMMS)

Exemplo explícito:
  CMMS_SEED_EMAIL=ti@empresa.com CMMS_SEED_PASSWORD='SenhaForte123' \\
    ./venv/bin/python scripts/seed_admin.py

Se já existir usuário com o mesmo e-mail, o script não altera nada e termina com código 0.
"""

from __future__ import annotations

import argparse
import os
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(ROOT))

from sqlalchemy import select  # noqa: E402

from app.auth.security import get_password_hash  # noqa: E402
from app.core.database import SessionLocal  # noqa: E402
from app.models.user import User  # noqa: E402


def main() -> None:
    parser = argparse.ArgumentParser(description="Seed do primeiro usuário ADMIN")
    parser.add_argument(
        "--email",
        default=os.environ.get("CMMS_SEED_EMAIL", "admin@cmms.local"),
    )
    parser.add_argument(
        "--nome",
        default=os.environ.get("CMMS_SEED_NOME", "Administrador CMMS"),
    )
    parser.add_argument(
        "--password",
        default=os.environ.get("CMMS_SEED_PASSWORD"),
        help="Se omitido, usa CMMS_SEED_PASSWORD no ambiente ou valor padrão (apenas dev)",
    )
    args = parser.parse_args()

    password = args.password or os.environ.get("CMMS_SEED_PASSWORD")
    if not password:
        password = "Trocar123!"
        print(
            "Aviso: usando senha padrão de desenvolvimento. "
            "Defina CMMS_SEED_PASSWORD ou --password.",
            file=sys.stderr,
        )

    db = SessionLocal()
    try:
        existing = db.execute(select(User).where(User.email == args.email)).scalar_one_or_none()
        if existing:
            print(f"Já existe usuário com e-mail {args.email} — nada a fazer.")
            return

        user = User(
            nome_completo=args.nome,
            email=args.email,
            senha_hash=get_password_hash(password),
            perfil_acesso="ADMIN",
            ativo=True,
        )
        db.add(user)
        db.commit()
        db.refresh(user)
        print(f"ADMIN criado: {args.email} (id={user.id})")
    finally:
        db.close()


if __name__ == "__main__":
    main()
