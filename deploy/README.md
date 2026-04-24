# Deploy (referência)

## Credenciais PostgreSQL

- **Runtime (FastAPI):** `backend/.env` com `DATABASE_URL`. O systemd `cmms-api` usa `EnvironmentFile` para esse arquivo.
- **Referência única (equipe, `psql`, backup):** `deploy/db-credentials.local` — copiar de `deploy/db-credentials.local.example`; **não commitar** (está no `.gitignore`). Mantém `PGUSER`, `PGPASSWORD`, `PGDATABASE` e `DATABASE_URL` alinhados.
- **Troca de senha:** `scripts/change_cmms_db_password.sh` atualiza PostgreSQL, `backend/.env` e `deploy/db-credentials.local`.
- **Papéis:** app usa **`cmms_app`** no banco **`cmms`**. `postgres` e `admin_cmms` são do cluster (senhas fora do repositório).
- **Documentação completa:** seção **PostgreSQL** em `readme.md`.

## Nginx

- **`nginx-cmms.example.conf`**: modelo com PHP-FPM, proxy `/api/` → Uvicorn, **`location ^~ /assets/`** (evita que `.js` em falta caia no `index.php` e pareça “script inválido”), headers de segurança e exemplo de HTTPS (Let's Encrypt).
- Copie para `sites-available`, ajuste `server_name`, `root`, socket PHP (`php8.3-fpm`), teste com `sudo nginx -t` e `systemctl reload nginx`.
- **Aviso do browser (senha em HTTP):** em produção use **HTTPS** e redirecione `http://` → `https://` (ex.: `certbot --nginx -d seu.dominio`). O login deve ser servido só em TLS.
- **Falha ao carregar `/assets/vendor/...js`:** no servidor execute `bash scripts/update-frontend-vendor.sh` (a pasta `frontend/public/assets/vendor/` pode não ir no Git por causa do `.gitignore`). Confirme com `curl -I https://seu.dominio/assets/vendor/bootstrap/5.3.3/js/bootstrap.bundle.min.js` → `200`.

## API via systemd

1. Garanta `venv` e `backend/.env` (ex.: `DATABASE_URL`).
2. Ajuste usuário em `cmms-api.service` se não for `www-data`.
3. Instalação (um comando por linha; **não** use vírgulas nem cole tudo em uma linha só):

```bash
sudo cp /var/www/html/deploy/cmms-api.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now cmms-api
sudo systemctl status cmms-api
```

4. Logs: `journalctl -u cmms-api -f`

O serviço escuta em **127.0.0.1:8000** (compatível com o `proxy_pass` do Nginx).

## PHP-FPM

O pool padrão do Ubuntu (`php8.3-fpm`) já atende o `fastcgi_pass` do exemplo; ajuste apenas o socket se a versão do PHP for outra.

## Testes automatizados (API)

No diretório `backend/`:

```bash
./venv/bin/pytest
```

## Backup PostgreSQL

- **Script**: `/var/www/html/scripts/backup_postgres.sh` — lê só a linha `DATABASE_URL` de `backend/.env` (não faz `source` no arquivo inteiro — evita erro com `APP_NAME` com espaços). Se **root**, usa `sudo -u postgres pg_dump` no banco indicado na URL (evita falta de permissão do usuário da app). Com `CMMS_BACKUP_AS_APP=1`, força `pg_dump` com o usuário do `.env`.
- Gera `cmms_YYYYMMDD_HHMMSS.sql.gz` em `BACKUP_DIR` (padrão `/var/backups/cmms`), remove arquivos mais antigos que `KEEP_DAYS` (padrão **14**).
- Teste manual (cria diretório se precisar):

```bash
sudo mkdir -p /var/backups/cmms
sudo BACKUP_DIR=/var/backups/cmms /var/www/html/scripts/backup_postgres.sh
```

- **Timer systemd** (diário 03:00):

```bash
sudo cp /var/www/html/deploy/cmms-backup.service /etc/systemd/system/
sudo cp /var/www/html/deploy/cmms-backup.timer /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now cmms-backup.timer
systemctl list-timers | grep cmms-backup
```

- **Cron** (alternativa): `0 3 * * * root /var/www/html/scripts/backup_postgres.sh >> /var/log/cmms-backup.log 2>&1`

Requer `pg_dump` no PATH (`postgresql-client`).

## Go-live sem reinstalar o banco

Para remover **só** dados operacionais (OS, históricos, anexos em disco) e manter cadastros, use **`scripts/purge_historicos.sh`** — ver `scripts/README.md` e `database/README.md`. Faça backup antes.

## Restauração a partir do backup (`.sql.gz`)

Os arquivos gerados pelo script são **SQL em texto** compactado com gzip (não formato custom do `pg_dump -Fc`). A restauração típica recria o conteúdo **no banco de destino**; em ambiente compartilhado, faça backup antes e **pare a API** para evitar conexões ativas durante o restore.

1. **Opcional:** parar a API para liberar conexões: `sudo systemctl stop cmms-api`
2. **Destino:** use o mesmo banco da aplicação (ex.: `cmms`) ou crie um banco vazio e ajuste `DATABASE_URL` depois.
3. **Restaurar como superusuário** (comum em servidor Ubuntu):

```bash
# Exemplo: arquivo gerado em /var/backups/cmms/
gunzip -c /var/backups/cmms/cmms_20260401_030000.sql.gz | sudo -u postgres psql -v ON_ERROR_STOP=1 -d cmms
```

4. **Subir a API:** `sudo systemctl start cmms-api` e teste `curl -s http://127.0.0.1:8000/health`.

**Banco novo (destrutivo no destino):** só se você quiser recriar do zero o banco `cmms` (apaga dados atuais):

```bash
sudo -u postgres psql -v ON_ERROR_STOP=1 -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'cmms' AND pid <> pg_backend_pid();"
sudo -u postgres psql -v ON_ERROR_STOP=1 -c "DROP DATABASE IF EXISTS cmms;"
sudo -u postgres psql -v ON_ERROR_STOP=1 -c "CREATE DATABASE cmms OWNER cmms_app;"
gunzip -c /var/backups/cmms/cmms_YYYYMMDD_HHMMSS.sql.gz | sudo -u postgres psql -v ON_ERROR_STOP=1 -d cmms
# Reaplique GRANTs se o dump não incluir privilégios do OWNER (depende do pg_dump).
```

**Nota:** usuários/roles globais (ex.: `cmms_app`) não vêm no dump só de um banco — o papel já deve existir no cluster antes do restore em servidor novo.
