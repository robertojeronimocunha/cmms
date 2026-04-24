#!/usr/bin/env bash
# Backup amplo do CMMS no servidor: aplicação, banco PostgreSQL (schema+dados + roles globais),
# Nginx, unidades systemd cmms*, TLS Let's Encrypt (se existir), árvore /etc/php (pools FPM),
# crontab do root. Gera um .tar único em $RAIZ_BACKUP.
#
# Uso típico (como root):
#   sudo /var/www/html/scripts/backup_sistema.sh
#
# Variáveis opcionais:
#   RAIZ_BACKUP=/backup          destino do arquivo final
#   WEB_DIR=/var/www/html        raiz do projeto
#   CMMS_DB_NAME=cmms            nome do banco (senão tenta ler backend/.env)
#   EXCLUDE_VENV=1               1 = não incluir backend/venv (recomendado; recriar com pip)
#   EXCLUDE_VENV=0               incluir venv completo (arquivo muito maior)
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "$REPO_ROOT/scripts/lib/cmms_env.sh"

DATA="$(date +%Y-%m-%d_%H-%M-%S)"
RAIZ_BACKUP="${RAIZ_BACKUP:-/backup}"
PASTA_TEMP="cmms_${DATA}"
DESTINO="${RAIZ_BACKUP}/${PASTA_TEMP}"
WEB_DIR="${WEB_DIR:-/var/www/html}"
EXCLUDE_VENV="${EXCLUDE_VENV:-1}"

DB_NAME="${CMMS_DB_NAME:-cmms}"

if [ -z "${CMMS_DB_NAME:-}" ] && [ -f "$WEB_DIR/backend/.env" ]; then
  if x="$(cmms_extract_dbname_from_env_file "$WEB_DIR/backend/.env" 2>/dev/null)"; then
    DB_NAME="$x"
  fi
fi

require_root_for_system() {
  if [ "${EUID:-$(id -u)}" -ne 0 ]; then
    echo "Erro: execute como root (sudo) para ler /etc, Let's Encrypt e pg_dump como postgres." >&2
    exit 1
  fi
}

require_root_for_system

mkdir -p "$DESTINO"
echo "--- BACKUP CMMS (completo) — $DATA ---"
echo "WEB_DIR=$WEB_DIR  DB_NAME=$DB_NAME  DESTINO=$DESTINO"

echo "[1/7] PostgreSQL: roles globais + dump do banco \"$DB_NAME\"..."
if ! sudo -u postgres psql -Atqc "SELECT 1 FROM pg_database WHERE datname = '$DB_NAME'" | grep -q 1; then
  echo "Aviso: banco \"$DB_NAME\" não encontrado no cluster; tentando mesmo assim o pg_dump." >&2
fi
sudo -u postgres pg_dumpall --globals-only >"$DESTINO/postgresql_globals.sql"
sudo -u postgres pg_dump --no-owner --no-acl "$DB_NAME" >"$DESTINO/banco_dados_${DB_NAME}.sql"

echo "[2/7] Arquivos do projeto ($WEB_DIR)..."
TAR_EXCLUDE=(--exclude='__pycache__' --exclude='*.pyc')
if [ "$EXCLUDE_VENV" = "1" ]; then
  TAR_EXCLUDE+=(--exclude='backend/venv')
  echo "  Excluído: backend/venv (use EXCLUDE_VENV=0 para incluir o venv)"
fi
tar -czf "$DESTINO/arquivos_web.tar.gz" "${TAR_EXCLUDE[@]}" -C "$WEB_DIR" .

echo "[3/7] Nginx (/etc/nginx)..."
if [ -d /etc/nginx ]; then
  tar -czf "$DESTINO/etc_nginx.tar.gz" -C /etc nginx
else
  echo "Nginx não encontrado em /etc/nginx" >"$DESTINO/etc_nginx_AUSENTE.txt"
fi

echo "[4/7] Systemd (unidades cmms*)..."
mapfile -t _sd_units < <(find /etc/systemd/system -maxdepth 1 -name 'cmms*' -type f 2>/dev/null | sort || true)
if [ "${#_sd_units[@]}" -gt 0 ]; then
  _names=()
  for _f in "${_sd_units[@]}"; do
    _names+=("${_f##*/}")
  done
  tar -czf "$DESTINO/etc_systemd_cmms.tar.gz" -C /etc/systemd/system "${_names[@]}"
else
  echo "Nenhuma unidade /etc/systemd/system/cmms* encontrada." >"$DESTINO/etc_systemd_cmms_AUSENTE.txt"
fi

echo "[5/7] TLS Let's Encrypt (/etc/letsencrypt)..."
if [ -d /etc/letsencrypt ]; then
  tar -czf "$DESTINO/etc_letsencrypt.tar.gz" -C /etc letsencrypt
else
  echo "Diretório /etc/letsencrypt não existe (HTTP sem TLS ou outro caminho)." >"$DESTINO/etc_letsencrypt_AUSENTE.txt"
fi

echo "[6/7] PHP (/etc/php — pools e config)..."
if [ -d /etc/php ]; then
  tar -czf "$DESTINO/etc_php.tar.gz" -C /etc php
else
  echo "Sem /etc/php neste host." >"$DESTINO/etc_php_AUSENTE.txt"
fi

echo "[7/7] Crontab root + manifesto + instruções..."
{
  crontab -l 2>/dev/null || echo "# (sem crontab para root)"
} >"$DESTINO/crontab_root.txt"

{
  echo "Backup CMMS (completo)"
  echo "======================"
  echo "Gerado em: $DATA"
  echo "Host: $(hostname -f 2>/dev/null || hostname)"
  echo "Usuário: $(whoami)"
  echo "WEB_DIR: $WEB_DIR"
  echo "PostgreSQL banco: $DB_NAME"
  echo "EXCLUDE_VENV: $EXCLUDE_VENV (1=venv não incluído em arquivos_web.tar.gz)"
  echo ""
  echo "Conteúdo:"
  echo "  - postgresql_globals.sql     (CREATE ROLE, etc.; necessário em servidor novo)"
  echo "  - banco_dados_${DB_NAME}.sql (dump do banco)"
  echo "  - arquivos_web.tar.gz        (projeto; secrets em backend/.env se existirem)"
  echo "  - etc_nginx.tar.gz           (/etc/nginx)"
  echo "  - etc_systemd_cmms.tar.gz ou *_AUSENTE.txt"
  echo "  - etc_letsencrypt.tar.gz ou *_AUSENTE.txt"
  echo "  - etc_php.tar.gz ou *_AUSENTE.txt"
  echo "  - crontab_root.txt"
  echo "  - RESTAURACAO.md"
  echo ""
  echo "AVISO: Este arquivo contém credenciais se estiverem em .env ou no dump de roles."
} >"$DESTINO/manifesto.txt"

{
  echo "# Restauração (resumo)"
  echo ""
  echo "Parâmetros deste backup: banco **${DB_NAME}**, diretório web **${WEB_DIR}**."
  echo ""
  echo "## 1. PostgreSQL (servidor novo ou vazio)"
  echo ""
  echo "    sudo -u postgres psql -f postgresql_globals.sql postgres"
  echo "    sudo -u postgres createdb -O cmms_app ${DB_NAME}   # ajuste o dono conforme globals.sql"
  echo "    sudo -u postgres psql -d ${DB_NAME} -f banco_dados_${DB_NAME}.sql"
  echo ""
  echo "Se o role \`cmms_app\` não existir, crie conforme \`deploy/\` e \`readme.md\`."
  echo ""
  echo "## 2. Aplicação"
  echo ""
  echo "    sudo mkdir -p ${WEB_DIR}"
  echo "    sudo tar -xzf arquivos_web.tar.gz -C ${WEB_DIR}"
  echo "    cd ${WEB_DIR}/backend && python3 -m venv venv && ./venv/bin/pip install -r requirements.txt"
  echo ""
  echo "## 3. Nginx, PHP, systemd, TLS"
  echo ""
  echo "    sudo tar -xzf etc_nginx.tar.gz -C /"
  echo "    sudo tar -xzf etc_php.tar.gz -C /                 # se o arquivo existir"
  echo "    sudo tar -xzf etc_systemd_cmms.tar.gz -C /etc/systemd/system"
  echo "    sudo tar -xzf etc_letsencrypt.tar.gz -C /           # se existir"
  echo "    sudo systemctl daemon-reload"
  echo "    sudo nginx -t && sudo systemctl reload nginx"
  echo "    sudo systemctl enable --now cmms-api"
  echo ""
  echo "Ajuste \`server_name\`, socket PHP-FPM e usuários se o novo servidor for diferente."
  echo ""
  echo "## 4. Crontab do root"
  echo ""
  echo "    sudo crontab crontab_root.txt"
  echo ""
  echo "Ver também \`deploy/cmms-backup.timer\` no projeto após extrair \`arquivos_web.tar.gz\`."
} >"$DESTINO/RESTAURACAO.md"

echo "--- Empacotando ---"
cd "$RAIZ_BACKUP"
FINAL="CMMS_BACKUP_${DATA}.tar"
tar -cf "$FINAL" "$PASTA_TEMP"
rm -rf "$PASTA_TEMP"

echo "--- BACKUP CONCLUÍDO ---"
echo "Arquivo: $RAIZ_BACKUP/$FINAL"
ls -lh "$RAIZ_BACKUP/$FINAL"

# Permite que o processo da API (www-data) liste/apague o .tar pela interface, quando aplicável.
if command -v getent >/dev/null 2>&1 && getent passwd www-data >/dev/null 2>&1; then
  chown www-data:www-data "$RAIZ_BACKUP/$FINAL" 2>/dev/null || true
fi
