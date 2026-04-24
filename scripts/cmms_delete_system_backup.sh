#!/usr/bin/env bash
# Apaga um .tar de backup de sistema sob RAIZ_BACKUP (só nomes CMMS_BACKUP_*.tar).
# Chamado pela API como: sudo -n env RAIZ_BACKUP=... ./cmms_delete_system_backup.sh <nome>
set -euo pipefail

RAIZ_BACKUP="${RAIZ_BACKUP:-/backup}"
name="${1:-}"

if [[ ! "$name" =~ ^CMMS_BACKUP_[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{2}-[0-9]{2}(-[0-9]{2})?\.tar$ ]]; then
  echo "Nome de arquivo inválido." >&2
  exit 1
fi

path="${RAIZ_BACKUP%/}/$name"
if [[ ! -f "$path" ]]; then
  echo "Arquivo não encontrado: $path" >&2
  exit 1
fi

rm -f -- "$path"
echo "Removido: $path"
