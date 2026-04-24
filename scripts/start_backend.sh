#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "$ROOT/scripts/lib/dev_backend.sh"
cmms_dev_backend_env "$ROOT"

if [ "${SKIP_PIP:-0}" != "1" ]; then
  pip install -r requirements.txt
fi

exec uvicorn app.main:app --host 0.0.0.0 --port 8000 --reload
