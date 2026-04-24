# Continuidade do projeto CMMS — handoff

Após uma pausa, **regra única:** especificação de API, perfis, fluxo de OS, PostgreSQL e deploy em nível de produto estão em **[`readme.md`](../readme.md)**. Este arquivo **não** repete o `readme` — só aponta caminhos e rotinas úteis para retomar trabalho.

---

## Índice da documentação

| Documento | Conteúdo |
|-----------|----------|
| [`readme.md`](../readme.md) | Referência `/api/v1`, perfis, fluxo de OS, scripts, testes, deploy. **Manter atualizado** quando mudar rotas ou comportamento da API. |
| [`.cursorrules`](../.cursorrules) | Regras de produto, stack, menus, URLs de ambiente. |
| [`deploy/README.md`](../deploy/README.md) | Systemd (`cmms-api`), Nginx, credenciais, backup. |
| [`deploy/production-hardening.md`](../deploy/production-hardening.md) | Produção (HTTPS, segredos, firewall). |
| [`database/README.md`](../database/README.md) | Migrações, reset de schema. |
| [`database/migrations-manual/README.md`](../database/migrations-manual/README.md) | SQL manuais aplicados ao PostgreSQL. |
| [`scripts/README.md`](../scripts/README.md) | Backup, restore, dev, **`update_git.sh`**, **`purge_historicos.sh`**, **`install_dependencies.sh`**; helpers em `scripts/lib/`. |
| [`docs/DEPENDENCIES.md`](DEPENDENCIES.md) | **Dependências** (APT, Python, PHP, PostgreSQL, vendor JS/CSS) e opções do instalador. |
| [`docs/MANUAL_USO.md`](MANUAL_USO.md) | **Manual de uso** para utilizadores finais (login, perfis, OS, preventivas, lubrificação, emulsão, cadastros). |
| [`backend/tests/smoke_checklist.md`](../backend/tests/smoke_checklist.md) | Smoke tests da API. |

---

## Onde mexer na interface

| Tema | Caminhos |
|------|----------|
| Branding / cores / logo | `frontend/config/branding.php`, `frontend/public/assets/branding/` |
| Layout global (sidebar, CSS) | `frontend/public/index.php` |
| Telas | `frontend/views/*.php` |
| Base URL da API (login) | `frontend/config/api_base.php` |

---

## Operação rápida

```bash
# API em produção (systemd)
sudo systemctl restart cmms-api

# Testes backend
cd /var/www/html/backend && ./venv/bin/pytest tests/ -q
```

**Nova rota ou mudança de contrato da API** → atualizar o **`readme.md`** (seção *API REST*).

---

*Handoff mínimo — detalhes técnicos só no `readme.md`.*
