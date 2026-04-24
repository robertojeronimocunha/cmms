#!/usr/bin/env bash
# Backup lógico do banco usado pelo CMMS (pg_dump compactado).
# Lê DATABASE_URL de backend/.env (formato SQLAlchemy) ou use variável de ambiente.
#
# Uso:
#   sudo BACKUP_DIR=/var/backups/cmms /var/www/html/scripts/backup_postgres.sh
#
# Agendamento: cron ou systemd timer (ver deploy/README.md e deploy/cmms-backup.timer).
#
# Por padrão, se o script rodar como root, usa `sudo -u postgres pg_dump` (evita erro de
# permissão quando o usuário da app não tem SELECT em todas as tabelas). Para forçar
# pg_dump com o usuário do DATABASE_URL: CMMS_BACKUP_AS_APP=1

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "$ROOT/scripts/lib/cmms_env.sh"

ENV_FILE="${ENV_FILE:-$ROOT/backend/.env}"
ENV_EXAMPLE="$ROOT/backend/.env.example"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/cmms}"
KEEP_DAYS="${KEEP_DAYS:-14}"

if [ -f "$ENV_FILE" ] && cmms_read_database_url_from_file "$ENV_FILE"; then
  :
elif [ -f "$ENV_EXAMPLE" ]; then
  echo "Aviso: $ENV_FILE não existe ou sem DATABASE_URL — usando $ENV_EXAMPLE (ideal: cp $ENV_EXAMPLE $ENV_FILE e ajuste)." >&2
  cmms_read_database_url_from_file "$ENV_EXAMPLE" || true
fi

if [ -z "${DATABASE_URL:-}" ]; then
  echo "Erro: DATABASE_URL não definido." >&2
  echo "  Crie o arquivo: $ENV_FILE" >&2
  echo "  Exemplo: sudo cp $ENV_EXAMPLE $ENV_FILE && sudo nano $ENV_FILE" >&2
  echo "  Ou exporte antes do backup: sudo DATABASE_URL='postgresql://...' $0" >&2
  exit 1
fi

# SQLAlchemy usa postgresql+psycopg2:// — libpq do pg_dump espera postgresql://
DUMP_URL="${DATABASE_URL/postgresql+psycopg2/postgresql}"

# Nome do banco na URL (último segmento do caminho)
DBNAME="${DUMP_URL##*/}"
DBNAME="${DBNAME%%\?*}"

mkdir -p "$BACKUP_DIR"
STAMP="$(date +%Y%m%d_%H%M%S)"
FILE="$BACKUP_DIR/cmms_${STAMP}.sql.gz"

if [ "${CMMS_BACKUP_AS_APP:-0}" = "1" ]; then
  pg_dump "$DUMP_URL" | gzip -9 >"$FILE"
elif [ "${EUID:-$(id -u)}" -eq 0 ]; then
  sudo -u postgres pg_dump --no-owner --no-acl -d "$DBNAME" | gzip -9 >"$FILE"
else
  pg_dump "$DUMP_URL" | gzip -9 >"$FILE"
fi

echo "Backup criado: $FILE ($(du -h "$FILE" | cut -f1))"

find "$BACKUP_DIR" -maxdepth 1 -name 'cmms_*.sql.gz' -mtime +"$KEEP_DAYS" -print -delete 2>/dev/null || true
