#!/usr/bin/env bash
# Recria o schema public do CMMS a partir de todos os modelos em app/models (SQLAlchemy).
# Uso: sudo /var/www/html/scripts/reset_cmms_database.sh
# Antes: backup — sudo BACKUP_DIR=/var/backups/cmms /var/www/html/scripts/backup_postgres.sh
# Depois: seed_admin.py + SQLs iniciais — database/README.md

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT/backend"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Execute com sudo (precisa rodar o Python como usuário postgres)." >&2
  exit 1
fi

exec sudo -u postgres ./venv/bin/python scripts/reset_cmms_schema.py --confirm "$@"
