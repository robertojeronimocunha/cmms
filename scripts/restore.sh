#!/usr/bin/env bash
# Restaura pacote gerado por scripts/backup_sistema.sh (arquivo CMMS_BACKUP_*.tar).
#
# Uso (como root):
#   sudo /var/www/html/scripts/restore.sh /backup/CMMS_BACKUP_2026-04-14_12-00-00.tar
#
# Conteúdo esperado dentro do .tar: uma pasta cmms_<data>/ com:
#   - banco_dados_<nome_do_banco>.sql (formato atual do backup_sistema.sh)
#   - banco_dados_full.sql (legado, ainda aceito se existir)
#   - arquivos_web.tar.gz
#   - postgresql_globals.sql, RESTAURACAO.md, etc. (não aplicados aqui — servidor novo: siga RESTAURACAO.md)
#
# Variáveis opcionais:
#   WEB_DIR=/var/www/html     destino dos arquivos do projeto
#   CMMS_DB_NAME=cmms        força nome do banco (útil se houver vários banco_dados_*.sql)
#
# AVISO: apaga o diretório de banco indicado e o conteúdo atual de WEB_DIR.

set -euo pipefail

WEB_DIR="${WEB_DIR:-/var/www/html}"
ARQUIVO="${1:-}"

usage() {
  sed -n '2,22p' "$0" | sed 's/^# \{0,1\}//'
}

if [[ -z "$ARQUIVO" || "$ARQUIVO" == "-h" || "$ARQUIVO" == "--help" ]]; then
  usage
  [[ -n "$ARQUIVO" ]] && exit 0
  exit 1
fi

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Erro: execute como root (sudo)." >&2
  exit 1
fi

if [[ ! -f "$ARQUIVO" ]]; then
  echo "Erro: arquivo não encontrado: $ARQUIVO" >&2
  exit 1
fi

TMPDIR=$(mktemp -d)
cleanup() { rm -rf "$TMPDIR"; }
trap cleanup EXIT

echo "--- INICIANDO RESTAURAÇÃO DO SISTEMA ---"
echo "-> Extraindo backup principal..."
tar -xf "$ARQUIVO" -C "$TMPDIR"

mapfile -t _dirs < <(find "$TMPDIR" -mindepth 1 -maxdepth 1 -type d -name 'cmms_*' | sort)
if [[ ${#_dirs[@]} -ne 1 ]]; then
  echo "Erro: esperado exatamente uma pasta cmms_* no arquivo; encontrado: ${#_dirs[@]}" >&2
  ls -la "$TMPDIR" >&2 || true
  exit 1
fi
PASTA="${_dirs[0]}"

resolve_dump() {
  local d="$1"
  if [[ -f "$d/banco_dados_full.sql" ]]; then
    echo "$d/banco_dados_full.sql"
    return 0
  fi
  mapfile -t dumps < <(find "$d" -maxdepth 1 -type f -name 'banco_dados_*.sql' ! -name 'banco_dados_full.sql' | sort)
  if [[ ${#dumps[@]} -eq 0 ]]; then
    echo "Erro: nenhum banco_dados_*.sql nem banco_dados_full.sql em $d" >&2
    return 1
  fi
  if [[ ${#dumps[@]} -eq 1 ]]; then
    echo "${dumps[0]}"
    return 0
  fi
  local dbwant="${CMMS_DB_NAME:-}"
  if [[ -z "$dbwant" && -f "$d/manifesto.txt" ]]; then
    dbwant=$(grep -E '^PostgreSQL banco:' "$d/manifesto.txt" | head -1 | sed 's/^PostgreSQL banco:[[:space:]]*//')
  fi
  if [[ -n "$dbwant" ]]; then
    local want="$d/banco_dados_${dbwant}.sql"
    if [[ -f "$want" ]]; then
      echo "$want"
      return 0
    fi
  fi
  echo "Erro: vários arquivos banco_dados_*.sql; defina CMMS_DB_NAME ou use backup com um único dump." >&2
  ls -1 "$d"/banco_dados_*.sql >&2 || true
  return 1
}

DUMP=$(resolve_dump "$PASTA") || exit 1

DB_NAME="${CMMS_DB_NAME:-}"
if [[ -z "$DB_NAME" ]]; then
  base=$(basename "$DUMP" .sql)
  DB_NAME="${base#banco_dados_}"
fi
if [[ -z "$DB_NAME" || "$DB_NAME" == "$DUMP" ]]; then
  echo "Erro: não foi possível deduzir o nome do banco a partir de $DUMP" >&2
  exit 1
fi

if [[ ! -f "$PASTA/arquivos_web.tar.gz" ]]; then
  echo "Erro: arquivos_web.tar.gz não encontrado em $PASTA" >&2
  exit 1
fi

echo "-> Recriando banco \"$DB_NAME\" (dump: $(basename "$DUMP"))..."

sudo -u postgres dropdb --if-exists "$DB_NAME"
if sudo -u postgres psql -Atqc "SELECT 1 FROM pg_roles WHERE rolname='cmms_app'" 2>/dev/null | grep -q 1; then
  sudo -u postgres createdb -O cmms_app "$DB_NAME"
else
  echo "Aviso: role cmms_app não encontrada; criando banco sem -O. Em servidor novo importe postgresql_globals.sql antes (ver RESTAURACAO.md no pacote)." >&2
  sudo -u postgres createdb "$DB_NAME"
fi

sudo -u postgres psql -d "$DB_NAME" -v ON_ERROR_STOP=1 -f "$DUMP"

echo "-> Substituindo arquivos em $WEB_DIR..."
mkdir -p "$WEB_DIR"
rm -rf "${WEB_DIR:?}"/*
tar -xzf "$PASTA/arquivos_web.tar.gz" -C "$WEB_DIR/"

if id www-data &>/dev/null; then
  chown -R www-data:www-data "$WEB_DIR"
else
  echo "Aviso: usuário www-data não existe; não foi possível chown." >&2
fi

echo "--- RESTAURAÇÃO FINALIZADA ---"
echo "Banco: $DB_NAME | Arquivos: $WEB_DIR"
echo "Reinicie serviços se necessário (ex.: systemctl restart cmms-api nginx php*-fpm)."
