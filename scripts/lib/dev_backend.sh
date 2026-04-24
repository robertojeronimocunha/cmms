# Prepara venv, .env e DATABASE_URL padrão para scripts de desenvolvimento do backend.
# Uso: ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" && source "$ROOT/scripts/lib/dev_backend.sh" && cmms_dev_backend_env "$ROOT"

cmms_dev_backend_env() {
  local root="$1"
  cd "$root/backend"

  if [ ! -d "venv" ]; then
    python3 -m venv venv
  fi

  # shellcheck disable=SC1091
  source venv/bin/activate

  if [ -f .env ]; then
    set -a
    # shellcheck disable=SC1091
    source .env
    set +a
  fi

  # Fallback só se não existir .env: mesmo banco que .env.example (não é produção).
  export DATABASE_URL="${DATABASE_URL:-postgresql+psycopg2://cmms_app:Cmms123@127.0.0.1:5432/cmms}"
}
