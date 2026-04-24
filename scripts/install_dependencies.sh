#!/usr/bin/env bash
# Instala dependências do CMMS: APT (Ubuntu/Debian), venv Python + pip, opcional vendor JS/CSS.
#
# Uso:
#   sudo ./scripts/install_dependencies.sh              # completo (APT + venv + pip + vendor)
#   sudo ./scripts/install_dependencies.sh --extra      # + build-essential, libheif1
#   ./scripts/install_dependencies.sh --skip-apt         # só venv + pip (+ vendor se não --no-vendor)
#
# Documentação: docs/DEPENDENCIES.md

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SKIP_APT=0
NO_VENDOR=0
EXTRA_PKGS=0

usage() {
  cat <<'EOF'
Instala dependências do CMMS (APT, venv Python, vendor front-end).

Uso:
  sudo ./scripts/install_dependencies.sh
  ./scripts/install_dependencies.sh --skip-apt

Opções:
  --skip-apt     Não executa apt-get
  --no-vendor    Não executa update-frontend-vendor.sh
  --extra        Inclui build-essential e libheif1
  -h, --help     Esta ajuda

Documentação: docs/DEPENDENCIES.md
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --skip-apt) SKIP_APT=1; shift ;;
    --no-vendor) NO_VENDOR=1; shift ;;
    --extra) EXTRA_PKGS=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *)
      echo "Opção desconhecida: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

APT_PACKAGES=(
  nginx
  postgresql
  postgresql-client
  python3.12
  python3.12-venv
  python3-pip
  php8.3-fpm
  php8.3-cli
  php8.3-common
  php8.3-curl
  php8.3-mbstring
  php8.3-xml
  curl
  psmisc
)

if [[ "$EXTRA_PKGS" -eq 1 ]]; then
  APT_PACKAGES+=(build-essential libheif1)
fi

run_apt() {
  if [[ "$SKIP_APT" -eq 1 ]]; then
    echo "[apt] Ignorado (--skip-apt)."
    return 0
  fi
  if ! command -v apt-get >/dev/null 2>&1; then
    echo "Erro: apt-get não encontrado. Use Debian/Ubuntu ou instale os pacotes à mão (ver docs/DEPENDENCIES.md)." >&2
    exit 1
  fi
  local sudo_cmd=()
  if [[ "$(id -u)" -ne 0 ]]; then
    if command -v sudo >/dev/null 2>&1; then
      sudo_cmd=(sudo)
    else
      echo "Erro: precisa de root ou sudo para instalar pacotes APT." >&2
      exit 1
    fi
  fi
  echo "[apt] Atualizando índice e instalando pacotes..."
  "${sudo_cmd[@]}" apt-get update -qq
  DEBIAN_FRONTEND=noninteractive "${sudo_cmd[@]}" apt-get install -y "${APT_PACKAGES[@]}"
  echo "[apt] Concluído."
}

setup_venv() {
  local py=""
  if command -v python3.12 >/dev/null 2>&1; then
    py="python3.12"
  elif command -v python3 >/dev/null 2>&1; then
    py="python3"
    local v
    v="$("$py" -c 'import sys; print("%d.%d" % sys.version_info[:2])')"
    echo "[venv] Aviso: python3.12 não encontrado; a usar $py (versão $v). Recomendado 3.12." >&2
  else
    echo "Erro: Python 3 não encontrado. Instale python3.12-venv." >&2
    exit 1
  fi

  cd "$ROOT/backend"
  if [[ ! -d venv ]]; then
    echo "[venv] A criar backend/venv com $py -m venv ..."
    "$py" -m venv venv
  else
    echo "[venv] backend/venv já existe."
  fi

  echo "[pip] A instalar requirements.txt ..."
  ./venv/bin/pip install -q --upgrade pip
  ./venv/bin/pip install -q -r requirements.txt
  echo "[pip] Concluído."
}

run_vendor() {
  if [[ "$NO_VENDOR" -eq 1 ]]; then
    echo "[vendor] Ignorado (--no-vendor)."
    return 0
  fi
  if ! command -v curl >/dev/null 2>&1; then
    echo "[vendor] curl ausente — instale o pacote curl ou execute depois: bash scripts/update-frontend-vendor.sh" >&2
    return 0
  fi
  echo "[vendor] A descarregar JS/CSS para frontend/public/assets/vendor/ ..."
  bash "$ROOT/scripts/update-frontend-vendor.sh"
  echo "[vendor] Concluído."
}

run_apt
setup_venv
run_vendor

echo ""
echo "Dependências instaladas. Próximos passos:"
echo "  - PostgreSQL: criar role/base (deploy/README.md)"
echo "  - backend/.env com DATABASE_URL"
echo "  - Schema: database/README.md (reset ou migrações)"
echo "  - API: sudo systemctl enable --now cmms-api  ou  scripts/start_backend.sh"
