#!/usr/bin/env bash
# Apaga um .sql.gz de backup de BD sob CMMS_BACKUP_DB_DELETE_DIR (só cmms_*.sql.gz).
set -euo pipefail

DIR="${CMMS_BACKUP_DB_DELETE_DIR:-}"
name="${1:-}"

if [[ ! "$name" =~ ^cmms_[0-9]{8}_[0-9]{6}\.sql\.gz$ ]]; then
  echo "Nome de arquivo inválido." >&2
  exit 1
fi
if [[ -z "$DIR" ]]; then
  echo "CMMS_BACKUP_DB_DELETE_DIR não definido." >&2
  exit 1
fi

path="${DIR%/}/$name"
if [[ ! -f "$path" ]]; then
  echo "Arquivo não encontrado: $path" >&2
  exit 1
fi

rm -f -- "$path"
echo "Removido: $path"
