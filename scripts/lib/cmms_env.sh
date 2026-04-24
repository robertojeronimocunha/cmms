# Funções compartilhadas para ler DATABASE_URL do backend/.env sem fazer source do arquivo inteiro
# (valores com espaço em APP_NAME=… quebrariam o shell).
# Uso: ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" && source "$ROOT/scripts/lib/cmms_env.sh"

cmms_read_database_url_from_file() {
  local f="$1" line
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    case "$line" in
      DATABASE_URL=*)
        DATABASE_URL="${line#DATABASE_URL=}"
        DATABASE_URL="${DATABASE_URL%\"}"
        DATABASE_URL="${DATABASE_URL#\"}"
        DATABASE_URL="${DATABASE_URL%\'}"
        DATABASE_URL="${DATABASE_URL#\'}"
        export DATABASE_URL
        return 0
        ;;
    esac
  done <"$f"
  return 1
}

# Extrai o nome do banco do último segmento da URL em uma linha DATABASE_URL=...
cmms_dbname_from_database_url_line() {
  local url="$1" path
  url="${url/postgresql+psycopg2/postgresql}"
  path="${url##*/}"
  path="${path%%\?*}"
  if [ -n "$path" ]; then
    printf '%s\n' "$path"
    return 0
  fi
  return 1
}

cmms_extract_dbname_from_env_file() {
  local f="$1" line url
  [ -f "$f" ] || return 1
  while IFS= read -r line || [ -n "$line" ]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    case "$line" in
      DATABASE_URL=*)
        url="${line#DATABASE_URL=}"
        url="${url%\"}"
        url="${url#\"}"
        url="${url%\'}"
        url="${url#\'}"
        cmms_dbname_from_database_url_line "$url"
        return $?
        ;;
    esac
  done <"$f"
  return 1
}
