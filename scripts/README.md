# Scripts de execução (CMMS)

Todos os caminhos usam a raiz do repositório automaticamente (funciona em qualquer clone).

**Dependências do projeto:** [`docs/DEPENDENCIES.md`](../docs/DEPENDENCIES.md) — catálogo (Ubuntu, Python, PHP, PostgreSQL, pip, vendor). **Instalação:** `sudo ./scripts/install_dependencies.sh` (APT + `backend/venv` + `pip install -r requirements.txt` + `update-frontend-vendor.sh`; opções `--skip-apt`, `--no-vendor`, `--extra`).

**Biblioteca compartilhada:** `lib/cmms_env.sh` (ler `DATABASE_URL` do `.env` sem `source` no arquivo inteiro), `lib/dev_backend.sh` (venv + `.env` para desenvolvimento).

| Script | Uso |
|--------|-----|
| `install_dependencies.sh` | **Novo servidor / dev:** pacotes APT (Nginx, PostgreSQL, Python 3.12 venv, PHP 8.3, curl, …), `pip` no `backend/venv`, vendor JS/CSS. Ver `docs/DEPENDENCIES.md`. |
| `start_backend.sh` | FastAPI + Uvicorn na porta **8000** (reload). |
| `start_frontend.sh` | Servidor embutido do PHP na porta **8080**. |
| `start_all.sh` | Backend em background + frontend em primeiro plano (um terminal). |
| `stop_cmms.sh` | Mata processos nas portas 8000 e 8080 (`fuser`). |
| `backup_postgres.sh` | `pg_dump` + gzip; usa `backend/.env`; ver `deploy/README.md`. |
| `cmms_backup_scheduled.sh` | **Cron:** PG + sistema, roda por contagem (últimas N cópias, padrão 12). Root; ver cabeçalho do script. |
| `cmms_agendador_tick.sh` | **Cron:** lê tarefas na BD (`agendador_tarefas`) e executa as vencidas; ver `deploy/cmms-agendador-cron.example`. |
| `update-frontend-vendor.sh` | Descarrega Bootstrap, jQuery, DataTables, etc. para `frontend/public/assets/vendor/` (uso offline). Pode ser agendado na UI **Manutenção → Agendador** (tarefa *Vendor frontend*). |
| `backup_sistema.sh` | **Backup amplo do servidor** (requer `sudo`): PostgreSQL (`postgresql_globals.sql` + dump do banco), `tar.gz` de `/var/www/html` (por padrão exclui `backend/venv`), Nginx, systemd `cmms*`, Let's Encrypt, `/etc/php`, crontab root; gera `RESTAURACAO.md` dentro do pacote. Saída: `$RAIZ_BACKUP/CMMS_BACKUP_<data>.tar` (padrão `/backup`). Variáveis: `RAIZ_BACKUP`, `WEB_DIR`, `CMMS_DB_NAME`, `EXCLUDE_VENV`. |
| `restore.sh` | **Restaura** um `CMMS_BACKUP_*.tar` (par com `backup_sistema.sh`): recria o banco a partir de `banco_dados_<nome>.sql`, extrai `arquivos_web.tar.gz` em `WEB_DIR`. Uso: `sudo ./scripts/restore.sh /backup/CMMS_BACKUP_....tar`. Variáveis: `WEB_DIR`, `CMMS_DB_NAME` (se houver mais de um dump no pacote). |
| `change_cmms_db_password.sh` | `ALTER USER` no PostgreSQL; atualiza `backend/.env` e `deploy/db-credentials.local`. Uso: `sudo ./scripts/change_cmms_db_password.sh` ou `CMMS_NEW_DB_PASSWORD='...'`. |
| `reset_cmms_database.sh` | **Destrutivo:** remove schema legado e recria tabelas do FastAPI (`backend/scripts/reset_cmms_schema.py`). Backup antes: `backup_postgres.sh`. Ver `database/README.md`. |
| `purge_historicos.sh` | **Destrutivo:** purga históricos para go-live (OS, checklists executadas, lubrificação/emulsão, movimentos de estoque, notificações, logs de sistema), zera `pecas.estoque_atual`, põe ativos em `OPERANDO`, remove ficheiros em `UPLOAD_DIR/os_anexos`; mantém cadastros e **não** altera datas de planos/pontos de lubrificação. Execução: `./scripts/purge_historicos.sh` (requer **terminal** com `/dev/tty`; pergunta `s/N`, mostra resumo, exige escrever exatamente `tenho certeza`). Só contagens / sem TTY para inspeção: `./scripts/purge_historicos.sh --dry-run`. |
| `update_git.sh` | **Commit e push** a partir da raiz do repositório: `git add .`, pede mensagem (ou usa texto com data), `commit` e `push` do **branch atual** para `origin`. Idempotente se não houver alterações. Requer acesso ao remoto. Uso: `./scripts/update_git.sh` (ver `chmod +x` se necessário). |
| (bootstrap) | `backend/scripts/seed_admin.py` — cria primeiro usuário ADMIN (usa `backend/.env`). Ver `database/README.md`. |

## Variáveis úteis

- `DATABASE_URL`: se não estiver definida, o padrão é o do exemplo em `backend/.env.example`.
- `SKIP_PIP=1`: pula `pip install -r requirements.txt` para subir mais rápido (depois que o venv já está instalado).

## Backend

```bash
/var/www/html/scripts/start_backend.sh
```

Ou com `.env` em `backend/` (carregado automaticamente se existir).

## Frontend

```bash
/var/www/html/scripts/start_frontend.sh
```

## Tudo em um terminal

```bash
/var/www/html/scripts/start_all.sh
```

## Parar serviços de desenvolvimento

```bash
/var/www/html/scripts/stop_cmms.sh
```

Ou `Ctrl+C` no terminal onde `start_all.sh` está rodando (o backend é encerrado pelo script).

## URLs (ambiente atual)

- Frontend: `http://sgm.planifer.com.br:8080`
- API: `http://sgm.planifer.com.br:8000/api/v1`

## Produção (Nginx + PHP-FPM)

Exemplo de configuração: `/var/www/html/deploy/nginx-cmms.example.conf` (ver também `deploy/README.md`). Com Nginx na frente, a **API Base** no sistema pode ser `https://seu-dominio/api/v1` (mesma origem, sem porta `:8000`).
