#!/usr/bin/env bash
# Manutenção do ficheiro de log do cron do agendador (esvaziar ou manter últimas N linhas).
# Destinado a execução como root ou via sudo -n pela API (www-data); ver deploy/sudoers-cmms-backup-ui.example
#
# Uso:
#   sudo /var/www/html/scripts/cmms_agendador_log_maint.sh esvaziar
#   sudo /var/www/html/scripts/cmms_agendador_log_maint.sh reter 5000
#
set -euo pipefail

if [ "${EUID:-0}" -ne 0 ]; then
  echo "Erro: execute como root ou via sudo." >&2
  exit 1
fi

LOG="${CMMS_AGENDADOR_LOG:-/var/log/cmms-agendador.log}"
ACT="${1:-}"
N="${2:-5000}"

case "$ACT" in
  esvaziar)
    : > "$LOG"
    ;;
  reter)
    if ! [[ "$N" =~ ^[0-9]+$ ]] || [ "$N" -lt 1 ]; then
      echo "Erro: N de linhas inválido." >&2
      exit 1
    fi
    if [ ! -f "$LOG" ]; then
      touch "$LOG"
      exit 0
    fi
    tmp="$(mktemp "${TMPDIR:-/tmp}/cmms-ag-log.XXXXXX")"
    tail -n "$N" "$LOG" > "$tmp"
    cat "$tmp" > "$LOG"
    rm -f "$tmp"
    ;;
  *)
    echo "Uso: $0 esvaziar | $0 reter <linhas>" >&2
    exit 1
    ;;
esac
