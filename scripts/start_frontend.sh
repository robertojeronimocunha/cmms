#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT/frontend/public"

if ! command -v php >/dev/null 2>&1; then
  echo "php nao encontrado no PATH" >&2
  exit 1
fi

exec php -S 0.0.0.0:8080
