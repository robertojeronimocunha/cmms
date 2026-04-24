#!/bin/bash
# Atualiza o repositório: add, commit (mensagem interativa) e push para origin/main.
# Uso: a partir de qualquer pasta — o script muda para a raiz do projeto (/var/www/html).
#     bash scripts/update_git.sh
#     ou: chmod +x scripts/update_git.sh && ./scripts/update_git.sh

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

if ! git rev-parse --is-inside-work-tree &>/dev/null; then
    echo "Erro: não é um repositório Git: $REPO_ROOT" >&2
    exit 1
fi

echo "--- Iniciando Atualização do Git ($REPO_ROOT) ---"

if [ -z "$(git status --porcelain)" ]; then
    echo "Nenhuma alteração detectada para atualizar."
    exit 0
fi

echo "Digite o motivo/descrição desta versão:"
read -r commit_message

if [ -z "${commit_message:-}" ]; then
    commit_message="Atualização automática em $(date +'%d/%m/%Y %H:%M')"
fi

echo "Adicionando arquivos..."
git add .

if git diff --cached --quiet; then
    echo "Nada no stage (ignorado ou nada a commitar após add)."
    exit 0
fi

echo "Criando commit: $commit_message"
git commit -m "$commit_message"

BRANCH=$(git rev-parse --abbrev-ref HEAD)
if [ "$BRANCH" = "HEAD" ]; then
    echo "Erro: HEAD destacado; faça checkout a um branch antes de enviar." >&2
    exit 1
fi

echo "Enviando para o repositório remoto (origin $BRANCH)..."
if git push "origin" "$BRANCH"; then
    echo "------------------------------------------"
    echo "Sincronização concluída com sucesso!"
    echo "Versão enviada em: $(date +'%H:%M:%S')"
    echo "------------------------------------------"
else
    echo "Erro ao enviar. Verifique rede, credenciais ou conflitos (git pull --rebase antes de push)." >&2
    exit 1
fi
