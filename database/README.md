# Banco de dados CMMS

## Schema alinhado ao FastAPI

As tabelas são criadas a partir de `backend/app/models/` (SQLAlchemy). O script **`backend/scripts/reset_cmms_schema.py`** importa o pacote `app.models` por completo e executa `Base.metadata.create_all`, garantindo que **todas** as entidades abaixo entram no schema novo.

| Tabela | Modelo |
|--------|--------|
| `usuarios` | `User` |
| `setores` | `Setor` |
| `setor_responsaveis` | `SetorResponsavel` |
| `ativo_categorias` | `AtivoCategoria` |
| `ativos` | `Asset` |
| `ordens_servico` | `WorkOrder` |
| `os_apontamentos` | `WorkOrderLog` |
| `os_solicitacoes_pecas` | `WorkOrderPartRequest` |
| `os_anexos` | `WorkOrderAttachment` |
| `checklist_padrao` | `ChecklistPadrao` |
| `checklist_tarefas_padrao` | `ChecklistTarefaPadrao` |
| `checklist_executada` | `ChecklistExecutada` |
| `checklist_tarefas_executada` | `ChecklistTarefaExecutada` |
| `pecas` | `Part` |
| `planos_manutencao` | `MaintenancePlan` |
| `lubrificantes` | `Lubricant` |
| `pontos_lubrificacao` | `LubricationPoint` |
| `lubrificacao_execucoes` | `LubricationExecution` |
| `inspecoes_emulsao` | `EmulsionInspection` |
| `tags_defeito` | `TagDefeito` |
| `logs_sistema` | `SystemLog` |
| `agendador_tarefas` | `AgendadorTarefa` |

**Nota:** tabelas só do legado PHP (ex.: `movimentacao_estoque`, `os_pecas`) **não** fazem parte do schema FastAPI atual; não são criadas pelo reset.

**Bases antigas:** migrações em `database/migrations-manual/` (lista longa no `readme.md` raiz, secção *Banco*) aplicam-se sobretudo a instalações que já existiam antes de um modelo novo. Uma instalação criada **só** com `reset_cmms_schema.py` já inclui enums e colunas dos modelos atuais (ex.: cache de emulsão em `ativos`).

## Limpar só históricos operacionais (go-live)

Para **entrada em produção** mantendo **cadastros** (ativos, peças, planos, utilizadores, etc.) mas apagando **OS**, execuções de checklist, histórico de lubrificação/emulsão, movimentos de estoque, notificações, logs de sistema, **e** ficheiros de anexos no disco:

1. **Backup obrigatório** (`scripts/backup_postgres.sh` e, se aplicável, cópia da pasta `UPLOAD_DIR/os_anexos`).
2. Executar **`scripts/purge_historicos.sh`** num terminal real (confirmações interativas; ver `scripts/README.md`). Use **`--dry-run`** apenas para ver contagens.

Isto **não** recria o schema; para **apagar todo o `public` e recomeçar**, use a secção seguinte.

## Instalação nova (reset + dados mínimos)

1. **Backup obrigatório** (se houver dados a preservar):

   ```bash
   sudo BACKUP_DIR=/var/backups/cmms /var/www/html/scripts/backup_postgres.sh
   ```

2. **Reset do schema** (como root; recria `public` vazio com todas as tabelas dos modelos):

   ```bash
   sudo /var/www/html/scripts/reset_cmms_database.sh
   ```

   Ou:

   ```bash
   cd /var/www/html/backend && sudo -u postgres ./venv/bin/python scripts/reset_cmms_schema.py --confirm
   ```

3. **Primeiro ADMIN** (usa `backend/.env` / `DATABASE_URL`; não precisa ser o utilizador `postgres`):

   ```bash
   cd /var/www/html/backend
   CMMS_SEED_EMAIL=admin@suaempresa.com CMMS_SEED_PASSWORD='SenhaForte' \
     ./venv/bin/python scripts/seed_admin.py
   ```

   Senha omitida cai em padrão de **dev** (`Trocar123!`) com aviso no stderr.

4. **Dados de referência idempotentes** (fluxo de OS, consolidação, agendador). Executar com `psql` no banco `cmms` (ex.: `sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 -f …`):

   | Ordem | Ficheiro | Finalidade |
   |------:|----------|------------|
   | 1 | `migrations-manual/2026_04_14_seed_checklists_obrigatorios.sql` | Checklists **LOTO** e **FINALIZACAO_OS** |
   | 2 | `migrations-manual/2026_04_14_tags_defeito.sql` | TAGs de defeito (consolidação) |
   | 3 | `migrations-manual/2026_04_23_agendador_tarefas.sql` | Tarefas **backup** e **preventivas** (INSERT) |
   | 4 | `migrations-manual/2026_04_24_agendador_vendor_frontend.sql` | Tarefa **vendor** do frontend |
   | 5 | `migrations-manual/2026_04_23_agendador_solicitante.sql` | Coluna solicitante (seguro em BD nova; `IF NOT EXISTS`) |

5. **Opcional — carga Planifer / demo:** setores + ativos, lubrificantes:

   ```bash
   sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 \
     -f /var/www/html/database/migrations-manual/2026_04_17_seed_setores_ativos_inicial.sql
   sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 \
     -f /var/www/html/database/migrations-manual/2026_04_14_seed_lubrificantes_planifer.sql
   ```

6. **Reiniciar a API** se aplicável: `sudo systemctl restart cmms-api`.

**Atenção:** o reset apaga **todos** os dados do schema `public` no banco alvo (`cmms` por omissão).
