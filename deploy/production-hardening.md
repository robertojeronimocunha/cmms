# Endurecimento em produção (checklist CMMS)

Referência rápida antes de expor o sistema à internet ou à rede da empresa.

## Segredos (`backend/.env`)

- **`SECRET_KEY`:** gere uma chave forte e única (não use `trocar-em-producao`):

  ```bash
  openssl rand -hex 32
  ```

  Cole o resultado em `SECRET_KEY=` e reinicie a API: `sudo systemctl restart cmms-api`.

- **`APP_DEBUG`:** em produção use `false`.

- **`DATABASE_URL`:** senha forte; rotação: `scripts/change_cmms_db_password.sh`.

## HTTPS

- Use **Nginx** na frente com TLS (Let's Encrypt / certbot). Modelo: `deploy/nginx-cmms.example.conf` (bloco `listen 443 ssl` comentado — descomente após certificados).
- **Frontend:** o padrão do campo *API Base* vem de `frontend/config/api_base.php` (hoje `https://sgm.planifer.com.br/api/v1`). Para dev com Uvicorn direto: `cp frontend/config/local.php.example frontend/config/local.php` e use `http://127.0.0.1:8000/api/v1`. Opcional: variável de ambiente `CMMS_API_BASE` no PHP-FPM.
- Mesma origem (`https://domínio/api/v1`) evita problemas de CORS no navegador.

## Firewall (ex.: UFW)

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
sudo ufw status
```

Ajuste portas se não usar perfil Nginx (ex.: só 80/443 manualmente).

## Serviços

- `cmms-api` (FastAPI) só em **127.0.0.1:8000** — não exponha a porta publicamente; o acesso externo é via Nginx `/api/`.
- Backup: `deploy/cmms-backup.timer` + `scripts/backup_postgres.sh`.

## Após mudanças

```bash
sudo nginx -t && sudo systemctl reload nginx
sudo systemctl restart cmms-api
curl -sS https://SEU_DOMINIO/health
```
