#!/usr/bin/env bash
# Um terminal: sobe API (porta 8000) em background e PHP (8080) em primeiro plano.
# Ctrl+C encerra o frontend e o script mata o backend.
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "$ROOT/scripts/lib/dev_backend.sh"
cmms_dev_backend_env "$ROOT"

if [ "${SKIP_PIP:-0}" != "1" ]; then
  pip install -r requirements.txt
fi

uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload &
BACK_PID=$!

cleanup() {
  if kill -0 "$BACK_PID" 2>/dev/null; then
    kill "$BACK_PID" 2>/dev/null || true
    wait "$BACK_PID" 2>/dev/null || true
  fi
}
trap cleanup INT TERM EXIT

cd "$ROOT/frontend/public"
if ! command -v php >/dev/null 2>&1; then
  echo "php nao encontrado no PATH" >&2
  exit 1
fi
php -S 0.0.0.0:8080
