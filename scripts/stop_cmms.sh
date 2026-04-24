#!/usr/bin/env bash
# Encerra processos que escutam nas portas 8000 (API) e 8080 (PHP built-in).
# Requer permissoes para matar o processo dono da porta (normalmente o mesmo usuario).
set -euo pipefail

for port in 8000 8080; do
  if fuser "${port}/tcp" >/dev/null 2>&1; then
    fuser -k "${port}/tcp" >/dev/null 2>&1 || true
    echo "Porta ${port}: processo encerrado."
  else
    echo "Porta ${port}: nenhum processo."
  fi
done
