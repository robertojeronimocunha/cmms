#!/usr/bin/env bash
# Altera a senha do usuário PostgreSQL usado pelo CMMS e atualiza DATABASE_URL em backend/.env
# e o arquivo de referência deploy/db-credentials.local (PG* + DATABASE_URL).
#
# Uso (interativo):
#   sudo /var/www/html/scripts/change_cmms_db_password.sh
#
# Uso (automação — evite deixar a senha no histórico do shell):
#   sudo CMMS_NEW_DB_PASSWORD='novaSenhaSegura' /var/www/html/scripts/change_cmms_db_password.sh
#
# Variáveis opcionais:
#   ENV_FILE=/caminho/backend/.env
#   CMMS_RESTART_API=1  — reinicia o serviço systemd cmms-api após alterar (se existir)
#   CMMS_SKIP_PSQL=1    — só atualiza o .env (útil se a senha já foi alterada manualmente no PG)
#
# Requisitos: Python venv em backend/venv com SQLAlchemy; cliente psql (postgresql-client).

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ENV_FILE="${ENV_FILE:-$ROOT/backend/.env}"
PY="${ROOT}/backend/venv/bin/python"
DRY_RUN=0

usage() {
  cat <<'EOF'
Altera senha PostgreSQL (usuário do CMMS), backend/.env e deploy/db-credentials.local.

Uso:
  sudo ./change_cmms_db_password.sh
  sudo CMMS_NEW_DB_PASSWORD='...' ./change_cmms_db_password.sh
  ./change_cmms_db_password.sh --dry-run

Opções: --dry-run  |  -h, --help

Variáveis: ENV_FILE  CMMS_NEW_DB_PASSWORD  CMMS_SKIP_PSQL  CMMS_RESTART_API
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *)
      echo "Opção desconhecida: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

if [[ ! -f "$ENV_FILE" ]]; then
  echo "Erro: arquivo não encontrado: $ENV_FILE" >&2
  exit 1
fi

if [[ ! -x "$PY" ]]; then
  echo "Erro: Python do venv não encontrado em $PY (crie com: cd $ROOT/backend && python3 -m venv venv && ./venv/bin/pip install -r requirements.txt)" >&2
  exit 1
fi

if [[ -n "${CMMS_NEW_DB_PASSWORD:-}" ]]; then
  NEW_PASS="$CMMS_NEW_DB_PASSWORD"
  echo "Usando senha definida em CMMS_NEW_DB_PASSWORD." >&2
else
  read -r -s -p "Nova senha: " NEW_PASS
  echo "" >&2
  read -r -s -p "Repita a senha: " NEW_PASS2
  echo "" >&2
  if [[ "$NEW_PASS" != "$NEW_PASS2" ]]; then
    echo "Erro: as senhas não coincidem." >&2
    exit 1
  fi
fi

if [[ -z "$NEW_PASS" ]]; then
  echo "Erro: senha vazia não permitida." >&2
  exit 1
fi

export ENV_FILE NEW_PASS DRY_RUN CMMS_SKIP_PSQL CMMS_RESTART_API ROOT

"$PY" <<'PY'
import os
import subprocess
import sys
from pathlib import Path

from sqlalchemy.engine.url import URL, make_url

env_file = Path(os.environ["ENV_FILE"])
new_pass = os.environ["NEW_PASS"]
dry = os.environ.get("DRY_RUN") == "1"
skip_psql = os.environ.get("CMMS_SKIP_PSQL") == "1"
restart_api = os.environ.get("CMMS_RESTART_API") == "1"

text = env_file.read_text(encoding="utf-8")
database_url = None
for line in text.splitlines():
    s = line.strip()
    if s.startswith("DATABASE_URL=") and not s.startswith("#"):
        database_url = line.split("=", 1)[1].strip().strip('"').strip("'")
        break

if not database_url:
    print("Erro: DATABASE_URL não encontrado em", env_file, file=sys.stderr)
    sys.exit(1)

old = make_url(database_url)
user = old.username or "cmms_app"

new_url = URL.create(
    drivername=old.drivername or "postgresql+psycopg2",
    username=old.username,
    password=new_pass,
    host=old.host,
    port=old.port,
    database=old.database,
    query=old.query,
)
# str(URL) mascara a senha (***); para .env precisamos da URL real.
new_url_str = new_url.render_as_string(hide_password=False)

def sql_literal(s: str) -> str:
    return "'" + s.replace("'", "''") + "'"

sql = f"ALTER USER {user} WITH PASSWORD {sql_literal(new_pass)}"

if dry:
    print("[dry-run] SQL:", sql)
    print("[dry-run] Novo DATABASE_URL:", new_url_str)
    root = Path(os.environ["ROOT"])
    print("[dry-run] Também gravaria:", root / "deploy" / "db-credentials.local")
    sys.exit(0)

if not skip_psql:
    subprocess.run(
        ["sudo", "-u", "postgres", "psql", "-v", "ON_ERROR_STOP=1", "-c", sql],
        check=True,
    )
    print("PostgreSQL: senha alterada para o usuário", user, file=sys.stderr)
else:
    print("Aviso: CMMS_SKIP_PSQL=1 — não executou ALTER USER no PostgreSQL.", file=sys.stderr)

out_lines = []
replaced = False
for line in text.splitlines(keepends=True):
    stripped = line.lstrip()
    if (
        not replaced
        and stripped.startswith("DATABASE_URL=")
        and not stripped.startswith("#")
    ):
        nl = "\n" if line.endswith("\n") else ""
        out_lines.append(f"DATABASE_URL={new_url_str}{nl}")
        replaced = True
    else:
        out_lines.append(line)

if not replaced:
    print("Erro: não foi possível substituir DATABASE_URL.", file=sys.stderr)
    sys.exit(1)

env_file.write_text("".join(out_lines), encoding="utf-8")
print("Arquivo atualizado:", env_file, file=sys.stderr)

root = Path(os.environ["ROOT"])
cred_path = root / "deploy" / "db-credentials.local"
host = old.host or "127.0.0.1"
port = old.port or 5432
dbn = old.database or "cmms"
cred_body = (
    "# CMMS — PostgreSQL (não commitar; ver deploy/db-credentials.local.example)\n"
    "# Atualizado por scripts/change_cmms_db_password.sh\n"
    f"PGHOST={host}\n"
    f"PGPORT={port}\n"
    f"PGUSER={user}\n"
    f"PGPASSWORD={new_pass}\n"
    f"PGDATABASE={dbn}\n"
    f"DATABASE_URL={new_url_str}\n"
)
cred_path.parent.mkdir(parents=True, exist_ok=True)
cred_path.write_text(cred_body, encoding="utf-8")
print("Referência atualizada:", cred_path, file=sys.stderr)

if restart_api:
    try:
        subprocess.run(
            ["sudo", "systemctl", "restart", "cmms-api"],
            check=True,
        )
        print("Serviço cmms-api reiniciado.", file=sys.stderr)
    except (subprocess.CalledProcessError, FileNotFoundError) as e:
        print("Aviso: não foi possível reiniciar cmms-api:", e, file=sys.stderr)
else:
    print(
        "Próximo passo: testar a API e, em produção com systemd, executar:",
        "sudo systemctl restart cmms-api",
        file=sys.stderr,
    )
PY

echo "Concluído."
