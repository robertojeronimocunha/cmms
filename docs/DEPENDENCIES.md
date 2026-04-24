# Dependências do projeto CMMS

Referência única para **sistema operativo**, **serviços**, **Python**, **PHP**, **PostgreSQL** e **ativos front-end** (vendor). A instalação automatizada está em **`scripts/install_dependencies.sh`**.

---

## Ambiente recomendado

| Componente | Versão / notas |
|------------|------------------|
| SO | **Ubuntu 24.04 LTS** (ou Debian 12+ com pacotes equivalentes) |
| Python | **3.12** (alinhado ao CI em `.github/workflows/backend-ci.yml`) |
| PostgreSQL | **16+** (`readme.md`: UUID, `timestamptz`) |
| PHP | **8.3** + PHP-FPM (frontend) |
| Nginx | Opcional em desenvolvimento; **recomendado em produção** |

---

## Pacotes do sistema (APT)

Instalados pelo script (ou equivalente manual). Nomes válidos em Ubuntu 24.04:

| Pacote | Uso no projeto |
|--------|----------------|
| `nginx` | Proxy reverso + PHP em produção (`deploy/nginx-cmms.example.conf`) |
| `postgresql` | Servidor PostgreSQL |
| `postgresql-client` | `pg_dump` / `psql` (`scripts/backup_postgres.sh`, migrações) |
| `python3.12` | Runtime da API |
| `python3.12-venv` | Ambiente virtual em `backend/venv/` |
| `python3-pip` | Bootstrap do `pip` no venv (opcional mas útil) |
| `php8.3-fpm` | Frontend PHP em produção |
| `php8.3-cli` | `php` em linha de comando / servidor embutido dev |
| `php8.3-common` | Extensões base |
| `php8.3-curl` | Recomendado (HTTP cliente, se necessário no futuro) |
| `php8.3-mbstring` | Strings multibyte (uso geral PHP) |
| `php8.3-xml` | XML/DOM comuns em ecossistema PHP |
| `curl` | `scripts/update-frontend-vendor.sh` descarrega CDNs |
| `psmisc` | `fuser` em `scripts/stop_cmms.sh` |

**Bibliotecas opcionais (compilação / binários nativos):**

| Pacote | Uso |
|--------|-----|
| `build-essential` | Compilar extensões pip se não houver wheel |
| `libpq-dev` | Necessário apenas se deixar de usar `psycopg2-binary` e compilar `psycopg2` |
| `libheif1` | Suporte HEIC em `pillow-heif` (imagens de telemóvel nos anexos) |

O script pode instalar `build-essential` e `libheif1` com a opção `--extra`.

---

## Python (`backend/requirements.txt`)

Pacotes declarados no repositório (versões livres no ficheiro; o CI usa `pip install -r requirements.txt`).

| Pacote | Função no CMMS |
|--------|------------------|
| `fastapi` | Framework HTTP / OpenAPI |
| `uvicorn` | Servidor ASGI (produção: `cmms-api.service`) |
| `python-dotenv` | Carregar `backend/.env` |
| `sqlalchemy` | ORM / acesso ao PostgreSQL |
| `psycopg2-binary` | Driver PostgreSQL (binário, sem compilar) |
| `alembic` | Migrações declaradas no projeto (uso conforme adoção) |
| `pydantic` | Validação de modelos / settings |
| `email-validator` | Validação de e-mail em schemas Pydantic |
| `python-jose` | JWT (`/auth/login`) |
| `passlib[bcrypt]` | Hash de senhas |
| `python-multipart` | Upload de ficheiros (anexos, CSV de peças) |
| `pillow` | Processamento de imagens nos anexos |
| `pillow-heif` | Leitura HEIC/HEIF para anexos |
| `httpx` | Cliente HTTP no backend (chamadas externas, testes) |
| `openpyxl` | Excel no backend (relatórios / exportações, conforme rotas) |
| `pytest` | Testes (`backend/tests/`) |

**Onde instalar:** sempre dentro de `backend/venv/` (não usar `pip install --user` global para a API).

---

## Front-end (sem Node.js)

- **PHP:** templates e `index.php`; sem `composer.json` / `package.json` no repositório.
- **JS/CSS offline:** versões fixas em `scripts/update-frontend-vendor.sh` (Bootstrap, jQuery, DataTables, HTMX, Font Awesome, ApexCharts) gravadas em `frontend/public/assets/vendor/` (muitas vezes fora do Git por `.gitignore`).

---

## PostgreSQL

- Serviço: `postgresql` (socket local, porta 5432 por omissão).
- Utilizador da aplicação típico: `cmms_app`, base `cmms` — ver `deploy/README.md` e `database/README.md`.

---

## Ferramentas só de desenvolvimento

- Scripts `start_backend.sh` / `start_frontend.sh` / `start_all.sh` — não exigem pacotes extra além dos acima.
- **Certbot / UFW:** produção — `deploy/production-hardening.md` (não incluídos no script base de dependências).

---

## Instalação rápida

```bash
# Revise opções: ./scripts/install_dependencies.sh --help
sudo ./scripts/install_dependencies.sh
```

Sem sudo (só Python + vendor, em máquina onde o APT já foi tratado):

```bash
./scripts/install_dependencies.sh --skip-apt
```

---

*Manter este ficheiro alinhado a `backend/requirements.txt` e ao script quando adicionar pacotes.*
