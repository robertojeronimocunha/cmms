#!/usr/bin/env bash
# Executa backup PostgreSQL + backup de sistema e mantém só as N cópias mais recentes de cada tipo.
# Destinado ao crontab root (backup_sistema.sh exige root).
#
# Variáveis opcionais:
#   CMMS_SCHEDULE_BACKUP_DIR   destino dos .sql.gz (padrão: /var/backups/cmms)
#   CMMS_SCHEDULE_RAIZ_BACKUP  destino dos .tar de sistema (padrão: /backup)
#   CMMS_SCHEDULE_KEEP         quantas cópias manter de cada tipo (padrão: 12)
#   CMMS_SCHEDULE_SKIP_SYSTEM  1 = não executa backup_sistema.sh (só PG)
#   KEEP_DAYS_PG               repasse ao backup_postgres.sh (padrão: 99999 = só rotação por contagem aqui)
#
# Crontab (root), a cada 6 horas:
#   0 */6 * * * /var/www/html/scripts/cmms_backup_scheduled.sh >> /var/log/cmms-backup-cron.log 2>&1
#
set -euo pipefail

REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${CMMS_SCHEDULE_BACKUP_DIR:-/var/backups/cmms}"
RAIZ_BACKUP="${CMMS_SCHEDULE_RAIZ_BACKUP:-/backup}"
KEEP="${CMMS_SCHEDULE_KEEP:-12}"
SKIP_SYSTEM="${CMMS_SCHEDULE_SKIP_SYSTEM:-0}"
export KEEP_DAYS="${KEEP_DAYS_PG:-99999}"

log() { echo "[$(date -Is)] $*"; }

if [ "${EUID:-$(id -u)}" -ne 0 ]; then
  echo "Execute como root (ex.: sudo $0)." >&2
  exit 1
fi

prune_by_count() {
  local dir="$1"
  local label="$2"
  local globpat="$3"
  [ -d "$dir" ] || return 0
  local n="$KEEP"
  mapfile -t sorted < <(
    find "$dir" -maxdepth 1 -type f -name "$globpat" -printf '%T@\t%p\n' 2>/dev/null \
      | sort -nr | cut -f2-
  )
  [ "${#sorted[@]}" -le "$n" ] && return 0
  local i
  for ((i = n; i < ${#sorted[@]}; i++)); do
    log "Removendo $label antigo: ${sorted[$i]}"
    rm -f -- "${sorted[$i]}"
  done
}

log "Início — PG dir=$BACKUP_DIR sistema dir=$RAIZ_BACKUP manter=$KEEP"

export BACKUP_DIR
if ! "$REPO/scripts/backup_postgres.sh"; then
  log "ERRO: backup_postgres.sh falhou."
  exit 1
fi

prune_by_count "$BACKUP_DIR" "pg_dump" "cmms_*.sql.gz"

if [ "$SKIP_SYSTEM" = "1" ]; then
  log "SKIP_SYSTEM=1 — backup de sistema não executado."
else
  export RAIZ_BACKUP
  export WEB_DIR="${WEB_DIR:-$REPO}"
  if ! "$REPO/scripts/backup_sistema.sh"; then
    log "ERRO: backup_sistema.sh falhou."
    exit 1
  fi
  prune_by_count "$RAIZ_BACKUP" "sistema" "CMMS_BACKUP_*.tar"
fi

log "Concluído."
