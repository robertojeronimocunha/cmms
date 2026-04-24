#!/usr/bin/env bash
# Processa tarefas da tabela agendador_tarefas (backup, preventivas, vendor frontend).
# Instalar no crontab root, ex.: */5 * * * * /var/www/html/scripts/cmms_agendador_tick.sh >> /var/log/cmms-agendador.log 2>&1
# Saída do cron: ver run_agendador_tick.py.
set -euo pipefail
REPO="$(cd "$(dirname "$0")/.." && pwd)"
PY="$REPO/backend/scripts/run_agendador_tick.py"
"$REPO/backend/venv/bin/python" "$PY"
