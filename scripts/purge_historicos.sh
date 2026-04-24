#!/usr/bin/env bash
# Purga históricos operacionais para arranque em produção (destrutivo).
# Mantém cadastros (ativos, peças, planos, pontos de lubrificação, etc.) e NÃO altera
# proxima_execucao / ultima_execucao em planos ou pontos de lubrificação.
#
# Execução normal: sem argumentos — confirmações interativas no terminal.
# Uso:
#   /var/www/html/scripts/purge_historicos.sh
#   /var/www/html/scripts/purge_historicos.sh --dry-run
# Opcional:
#   ENV_FILE=/var/www/html/backend/.env UPLOAD_DIR=/caminho/uploads /var/www/html/scripts/purge_historicos.sh

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
# shellcheck disable=SC1091
source "$ROOT/scripts/lib/cmms_env.sh"

ENV_FILE="${ENV_FILE:-$ROOT/backend/.env}"
ENV_EXAMPLE="$ROOT/backend/.env.example"
DRY_RUN=0

usage() {
  cat <<'EOF'
Purge de históricos do CMMS (destrutivo):
- remove OS, apontamentos, solicitações, anexos (BD), checklists executadas
- remove histórico de lubrificação (lubrificacao_execucoes) e emulsão (inspecoes_emulsao)
- limpa cache de emulsão nos ativos (se as colunas existirem)
- remove movimentação de estoque, notificações e logs de sistema
- zera pecas.estoque_atual (cadastro de peças mantido)
- define todos os ativos como OPERANDO
- NÃO altera datas de próxima/última execução em planos_manutencao nem pontos_lubrificacao
- após a BD, remove ficheiros em caminho_arquivo e a pasta UPLOAD_DIR/os_anexos

Uso:
  ./scripts/purge_historicos.sh          confirmações interativas (obrigatório terminal)
  ./scripts/purge_historicos.sh --dry-run   só contagens, sem alterações

Opções:
  --dry-run   mostra contagens e resumo; não altera BD nem disco
  -h, --help  exibe esta ajuda

Variáveis:
  UPLOAD_DIR  diretório de uploads (omissão: lê backend/.env ou backend/uploads)
EOF
}

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dry-run) DRY_RUN=1; shift ;;
    -h|--help) usage; exit 0 ;;
    *)
      echo "Opção desconhecida: $1" >&2
      usage >&2
      exit 1
      ;;
  esac
done

if [[ -f "$ENV_FILE" ]] && cmms_read_database_url_from_file "$ENV_FILE"; then
  :
elif [[ -f "$ENV_EXAMPLE" ]]; then
  echo "Aviso: $ENV_FILE ausente/sem DATABASE_URL. Usando $ENV_EXAMPLE." >&2
  cmms_read_database_url_from_file "$ENV_EXAMPLE" || true
fi

if [[ -z "${DATABASE_URL:-}" ]]; then
  echo "Erro: DATABASE_URL não definido em $ENV_FILE." >&2
  exit 1
fi

# UPLOAD_DIR: env > .env > omissão
if [[ -z "${UPLOAD_DIR:-}" && -f "$ENV_FILE" ]]; then
  while IFS= read -r line || [[ -n "$line" ]]; do
    [[ "$line" =~ ^[[:space:]]*# ]] && continue
    case "$line" in
      UPLOAD_DIR=*)
        UPLOAD_DIR="${line#UPLOAD_DIR=}"
        UPLOAD_DIR="${UPLOAD_DIR%\"}"
        UPLOAD_DIR="${UPLOAD_DIR#\"}"
        UPLOAD_DIR="${UPLOAD_DIR%\'}"
        UPLOAD_DIR="${UPLOAD_DIR#\'}"
        export UPLOAD_DIR
        break
        ;;
    esac
  done <"$ENV_FILE"
fi
UPLOAD_DIR="${UPLOAD_DIR:-$ROOT/backend/uploads}"

PSQL_URL="${DATABASE_URL/postgresql+psycopg2/postgresql}"

# Conta linhas numa tabela só se existir em public (evita erro em BD só FastAPI).
_append_count_if_exists() {
  local key="$1"
  local tbl="$2"
  local out="$3"
  local has n
  has=$(psql "$PSQL_URL" -t -A -v ON_ERROR_STOP=1 -c \
    "SELECT count(*)::text FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '${tbl}';")
  if [[ "${has:-0}" == "1" ]]; then
    n=$(psql "$PSQL_URL" -t -A -v ON_ERROR_STOP=1 -c "SELECT count(*)::bigint FROM ${tbl};")
  else
    n=0
  fi
  printf '%s|%s\n' "$key" "$n" >>"$out"
}

# Escreve ficheiro tabela|contagem para o resumo (pré e pós purge).
fetch_counts_file() {
  local out="$1"
  psql "$PSQL_URL" -v ON_ERROR_STOP=1 -t -A -F'|' -c "
SELECT 'ordens_servico', count(*) FROM ordens_servico
UNION ALL SELECT 'os_apontamentos', count(*) FROM os_apontamentos
UNION ALL SELECT 'os_solicitacoes_pecas', count(*) FROM os_solicitacoes_pecas
UNION ALL SELECT 'os_anexos', count(*) FROM os_anexos
UNION ALL SELECT 'checklist_executada', count(*) FROM checklist_executada
UNION ALL SELECT 'checklist_tarefas_executada', count(*) FROM checklist_tarefas_executada
UNION ALL SELECT 'lubrificacao_execucoes', count(*) FROM lubrificacao_execucoes
UNION ALL SELECT 'inspecoes_emulsao', count(*) FROM inspecoes_emulsao;
" >"$out"
  _append_count_if_exists movimentacao_estoque movimentacao_estoque "$out"
  _append_count_if_exists notificacoes notificacoes "$out"
  psql "$PSQL_URL" -v ON_ERROR_STOP=1 -t -A -F'|' -c "
SELECT 'logs_sistema', count(*) FROM logs_sistema
UNION ALL SELECT 'pecas_com_estoque_nao_zero', count(*) FROM pecas WHERE estoque_atual <> 0
UNION ALL SELECT 'ativos_em_status_nao_operando', count(*) FROM ativos WHERE status <> 'OPERANDO';
" >>"$out"
}

# Lê uma linha do terminal (confirmações não devem consumir stdin de um pipe externo)
read_tty() {
  if [[ -r /dev/tty ]]; then
    read -r "$@" </dev/tty
  else
    read -r "$@"
  fi
}

label_for_table() {
  case "$1" in
    ordens_servico) echo 'Ordens de serviço' ;;
    os_apontamentos) echo 'Apontamentos de OS' ;;
    os_solicitacoes_pecas) echo 'Solicitações de peças nas OS' ;;
    os_anexos) echo 'Registos de anexos (ficheiros também serão removidos)' ;;
    checklist_executada) echo 'Checklists executadas' ;;
    checklist_tarefas_executada) echo 'Tarefas de checklist executadas' ;;
    lubrificacao_execucoes) echo 'Execuções de lubrificação (histórico)' ;;
    inspecoes_emulsao) echo 'Inspeções de emulsão (histórico)' ;;
    movimentacao_estoque) echo 'Movimentações de estoque' ;;
    notificacoes) echo 'Notificações' ;;
    logs_sistema) echo 'Logs de sistema' ;;
    pecas_com_estoque_nao_zero) echo 'Peças com estoque ≠ 0 (serão zeradas)' ;;
    ativos_em_status_nao_operando) echo 'Ativos que deixarão de ser OPERANDO (repostos para OPERANDO)' ;;
    *) echo "$1" ;;
  esac
}

echo "A consultar o estado atual da base de dados..."
COUNTS_FILE="$(mktemp)"
trap 'rm -f "$COUNTS_FILE"' EXIT
fetch_counts_file "$COUNTS_FILE"

print_resumo() {
  echo ""
  echo "========== Resumo do impacto =========="
  while IFS='|' read -r key n; do
    [[ -z "$key" ]] && continue
    printf '  • %s: %s\n' "$(label_for_table "$key")" "$n"
  done <"$COUNTS_FILE"
  echo "  • Cache de emulsão nos ativos: limpo (se a coluna existir)"
  echo "  • Cadastro de peças: mantido (apenas estoque zerado)"
  echo "  • Planos de manutenção e pontos de lubrificação: calendário/datas não alterados"
  echo "  • Pasta de anexos no disco: $UPLOAD_DIR/os_anexos (removida por completo após o purge)"
  echo "========================================"
  echo ""
}

if [[ "$DRY_RUN" -eq 1 ]]; then
  print_resumo
  echo "DRY-RUN: nenhuma alteração foi aplicada."
  exit 0
fi

if [[ ! -r /dev/tty ]]; then
  echo "Erro: este script precisa de um terminal interativo (/dev/tty) para confirmação." >&2
  echo "Use --dry-run para apenas listar contagens." >&2
  exit 1
fi

echo ""
echo "AVISO: operação destrutiva — apaga históricos operacionais no PostgreSQL e ficheiros de anexos."
read_tty -p "Tem certeza que deseja limpar os históricos? (s/N): " resp
case "${resp}" in
  s|S|sim|SIM|yes|YES) ;;
  *)
    echo "Operação cancelada."
    exit 0
    ;;
esac

echo ""
echo "Segue o resumo do que será apagado ou alterado:"
print_resumo

echo "Para confirmar a operação IRREVERSÍVEL, escreva exatamente: tenho certeza"
read_tty -p "> " frase
if [[ "$frase" != "tenho certeza" ]]; then
  echo "Texto de confirmação incorreto. Operação cancelada."
  exit 1
fi

ANEXO_PATHS="$(mktemp)"
trap 'rm -f "$ANEXO_PATHS" "$COUNTS_FILE"' EXIT

echo "A recolher caminhos de anexos (ficheiros no disco)..."
psql "$PSQL_URL" -v ON_ERROR_STOP=1 -t -A -c "SELECT caminho_arquivo FROM os_anexos;" >"$ANEXO_PATHS"

echo "Executando purge no banco..."
psql "$PSQL_URL" -v ON_ERROR_STOP=1 -c "
BEGIN;
DELETE FROM checklist_tarefas_executada;
DELETE FROM checklist_executada;
DELETE FROM os_anexos;
DELETE FROM os_solicitacoes_pecas;
DELETE FROM os_apontamentos;
DELETE FROM ordens_servico;
DELETE FROM lubrificacao_execucoes;
DELETE FROM inspecoes_emulsao;
DO \$\$
BEGIN
  IF EXISTS (
    SELECT 1 FROM information_schema.columns
    WHERE table_schema = 'public' AND table_name = 'ativos' AND column_name = 'emulsao_ultima_concentracao'
  ) THEN
    UPDATE ativos SET
      emulsao_ultima_concentracao = NULL,
      emulsao_ultima_concentracao_em = NULL,
      emulsao_ultimo_ph = NULL,
      emulsao_ultimo_ph_em = NULL;
  END IF;
END \$\$;
DO \$\$
BEGIN
  IF to_regclass('public.movimentacao_estoque') IS NOT NULL THEN
    DELETE FROM movimentacao_estoque;
  END IF;
  IF to_regclass('public.notificacoes') IS NOT NULL THEN
    DELETE FROM notificacoes;
  END IF;
END \$\$;
UPDATE pecas SET estoque_atual = 0, updated_at = NOW();
DELETE FROM logs_sistema;
UPDATE ativos
   SET status = 'OPERANDO',
       updated_at = NOW()
 WHERE status <> 'OPERANDO';
COMMIT;
"

echo "A remover ficheiros de anexos referenciados..."
while IFS= read -r fpath; do
  [[ -z "$fpath" ]] && continue
  rm -f -- "$fpath"
done <"$ANEXO_PATHS"

if [[ -d "$UPLOAD_DIR/os_anexos" ]]; then
  echo "A remover diretório residual de anexos: $UPLOAD_DIR/os_anexos"
  rm -rf "$UPLOAD_DIR/os_anexos"
fi

echo "Pós-check (validação)..."
POST_COUNTS="$(mktemp)"
fetch_counts_file "$POST_COUNTS"
while IFS='|' read -r k n; do
  [[ -z "$k" ]] && continue
  printf '  %-42s %s\n' "$k" "$n"
done <"$POST_COUNTS"
rm -f "$POST_COUNTS"

echo "Purge de históricos concluído."
