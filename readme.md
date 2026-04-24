# CMMS Industrial Simplificado

**Continuidade / handoff:** [`docs/CONTINUIDADE.md`](docs/CONTINUIDADE.md) — índice enxuto, caminhos de UI e rotinas (sem duplicar a API).

**Manual de uso (operadores / gestão):** [`docs/MANUAL_USO.md`](docs/MANUAL_USO.md)

**Dependências e instalação de pacotes:** [`docs/DEPENDENCIES.md`](docs/DEPENDENCIES.md) · `sudo ./scripts/install_dependencies.sh`

Sistema CMMS enxuto para pequena metalúrgica (~30 máquinas), focado em operação diária:

- manutenção corretiva
- manutenção preventiva
- lubrificação
- controle simples de peças
- histórico por máquina
- dashboard operacional rápido

## Stack

- Ubuntu 24.04 + Nginx + PHP-FPM
- Backend: FastAPI, SQLAlchemy, Alembic, JWT, Pydantic
- Frontend: PHP 8.3, Bootstrap 5, HTMX, DataTables, ApexCharts
- Banco: PostgreSQL 16+ com UUID e timestamptz

**Instalação de dependências (SO + Python + vendor):** ver [`docs/DEPENDENCIES.md`](docs/DEPENDENCIES.md) e execute `sudo ./scripts/install_dependencies.sh` na raiz do repositório.

## Perfis (`perfil_acesso`)

| Perfil | Função |
|--------|--------|
| `ADMIN` | Acesso irrestrito; único que grava cadastros mestres (ativos, peças, setores, usuários, planos, lubrificantes, pontos). |
| `TECNICO` | Executa OS (status, anexos), executa preventivas e pontos de lubrificação, inspeção de emulsão; abre OS. |
| `LUBRIFICADOR` | Mesmas permissões que `TECNICO` na **API**; no **PHP**, o menu mostra **Dashboard Lubrificação**, **Ordens de serviço** e **Lubrificação** (submenu **Tarefas** e **Óleo solúvel** — sem **Óleos** / `?page=lubricacao` nem **Cadastros**). Itens como ativos/relatórios ficam ocultos; `?page=ordens-servico` permanece para **Criar OS** e acompanhar só **as OS que abriu** no dashboard. `?page=dashboard` redireciona para o dashboard do perfil. |
| `DIRETORIA` | Somente **GET** nas rotas; exceções: **abrir OS** (`POST /ordens-servico`) e **anexar/remover anexos** (`POST`/`DELETE …/anexos`). Lista usuários em leitura. |
| `LIDER` | Como `USUARIO`, mais `PATCH` em ativos só no campo **perfil de usinagem** (leve/pesado). Com a OS em **`AGUARDANDO_APROVACAO`**, aplica e preenche o checklist **`FINALIZACAO_OS`**. Pode **registrar apontamentos** (`POST` em `/ordens-servico/.../apontamentos`) para mudar status como **TECNICO**; **finalizar** (`FINALIZADA`) e **cancelar** (`CANCELADA`) exigem o mesmo perfil que **ADMIN** (finalização com **`FINALIZACAO_OS`** concluído). |
| `USUARIO` | Abre e acompanha OS; **anexa e remove imagens/PDF** na OS como quem abre OS; não altera status nem cadastros. No **frontend**, só **Dashboard** e **Ordens de serviço**; nas rotas de cadastro/listagem abaixo a API responde **403** (exceto onde indicado). |

Para `USUARIO`, a API retorna **403** em: `GET /setores` (e detalhe), `GET /preventivas`, `GET /lubrificantes`, `GET /pontos-lubrificacao`, `GET /relatorios/ordens-servico`. Continuam permitidos, entre outros: `GET /ativos` (lista para abrir OS), `GET /pecas` (lista/busca no catálogo — ex.: sugestão ao solicitar peça na OS), `GET /checklists` (lista no detalhe da OS; **não** pode `POST …/executar` salvo regra de perfil), `GET /dashboard/resumo`, `GET`/`POST` de ordens de serviço conforme regras já descritas, `POST`/`DELETE` de anexos da OS, leitura de apontamentos/solicitações de peças da OS.

Migração de dados (perfis antigos): `database/migrations-manual/2026_consolidar_perfis.sql` (MAPEIA `MECANICO`/`SUPERVISOR` → `TECNICO`, `CONSULTA` → `DIRETORIA`, `TI` → `USUARIO`).

## Fluxo simplificado de OS

Status possíveis: `ABERTA`, `AGENDADA`, `EM_EXECUCAO`, `AGUARDANDO_PECA`, `AGUARDANDO_TERCEIRO` (aguardando terceiro, em paralelo à peça), `AGUARDANDO_APROVACAO` (antigo `EM_TESTE`), `FINALIZADA`, `CANCELADA`.

- A API **não impõe grafo fixo** de transições: a partir de qualquer estado **não terminal** (`FINALIZADA` / `CANCELADA` é terminal), qualquer outro status pode ser escolhido no apontamento, respeitando as regras abaixo.
- **Sair de `ABERTA`** para qualquer status exceto **`CANCELADA`** e **`AGENDADA`**: checklist **`LOTO`** concluído. **`ABERTA` → `AGENDADA`**: **não** exige LOTO concluído (a OS pode incluir o checklist LOTO, mas o status **AGENDADA** é o único que pode ser definido sem o LOTO executado). **`ABERTA` → `CANCELADA`**: apenas **ADMIN**/**LIDER**, sem exigência de LOTO.
- A OS em **`AGENDADA`** recebe as **cópias** de **`LOTO`** e **`FINALIZACAO_OS`** como as outras. O **LOTO concluído** **não** é exigido enquanto a OS estiver em **`AGENDADA`**, **ao sair** de **`AGENDADA`**, ou em **`ABERTA` → `AGENDADA`**; para sair de **`ABERTA`** para qualquer outro status (exceto **`CANCELADA`** e **`AGENDADA`**) a API exige LOTO concluído. A preventiva em **`/preventivas/…/executar`** nasce em **`AGENDADA`** (com `data_agendamento`) e inclui **LOTO** + **FINALIZACAO** + checklist da **TAG** quando os padrões estiverem ativos.
- **`FINALIZADA`**: apenas **ADMIN** ou **LIDER**; checklist **`FINALIZACAO_OS`** concluído (qualquer execução válida com todas as obrigatórias marcadas).
- **`CANCELADA`**: apenas **ADMIN** ou **LIDER**.
- Checklists **`FINALIZACAO_OS`** (copiar/editar tarefas): **LIDER** ou **ADMIN** com OS em **`AGUARDANDO_APROVACAO`**; neste status, **TECNICO**/**LUBRIFICADOR** só editam outras checklists, não a de finalização.
- **`FINALIZADA`**: fim do fluxo para operação; o boolean **`consolidada`** (e custos/tag defeito na tela administrativa) indica apenas que o **ADMIN** já conferiu custos e documentação para métricas — pode ocorrer dias depois, sem novo status de OS.

**Banco:** execute `database/migrations-manual/2026_os_aguardando_terceiro.sql`, `database/migrations-manual/2026_04_14_os_fluxo_consolidacao.sql` (enum/campos), **`database/migrations-manual/2026_04_14_usuarios_custo_hora_interno.sql`** (custo R$/h nos usuários para mão de obra na consolidação), **`database/migrations-manual/2026_04_23_usuarios_permite_trocar_senha.sql`** (`usuarios.permite_trocar_senha` para o menu «trocar senha»), **`database/migrations-manual/2026_04_23_lubrificacao_execucoes.sql`** (histórico por lubrificação: litros + observação), **`database/migrations-manual/2026_04_15_os_finalizada_sem_estado_consolidar_operacional.sql`** para realocar OS antigas que ainda estivessem em `CONSOLIDAR` / `AGUARDANDO_LIDER`, **`database/migrations-manual/2026_04_16_os_status_aguardando_aprovacao.sql`** para enum/dados `AGUARDANDO_APROVACAO` (substitui `EM_TESTE`), e **`database/migrations-manual/2026_04_22_pecas_controla_estoque.sql`** (flag `pecas.controla_estoque` para contagem no almox. / alertas / baixa na finalização de OS).

## Estrutura atual do backend

```text
backend/
├── app/
│   ├── api/
│   ├── auth/
│   ├── core/
│   ├── models/
│   ├── schemas/
│   ├── services/
│   └── main.py
├── tests/
├── pytest.ini
└── requirements.txt
```

## API REST (referência)

**Prefixo:** `/api/v1` (exceto health e documentação na raiz do Uvicorn).

**Autenticação:** `Authorization: Bearer <token JWT>` em todas as rotas abaixo, exceto `POST /auth/login`. Login retorna `access_token`.

**Documentação interativa (OpenAPI):** `GET /docs` (Swagger UI), `GET /redoc`, `GET /openapi.json` — sempre refletem o código atual.

**Health (sem prefixo):**

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/health` | Status da aplicação (`status`, `app`). |

---

### Auth (`/api/v1/auth`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `POST` | `/auth/login` | Corpo: e-mail + senha. Retorna JWT. |
| `GET` | `/auth/me` | Usuário autenticado (perfil, nome, `permite_trocar_senha`, etc.). |
| `POST` | `/auth/trocar-senha` | Corpo: `senha_atual`, `senha_nova` (mín. 6 caracteres). O utilizador altera a própria senha. **403** se `permite_trocar_senha=false` no cadastro. **400** se a senha atual estiver errada. |

---

### Dashboard (`/api/v1/dashboard`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/dashboard/resumo` | KPIs: OS abertas, **maquinas_paradas** (ativos PARADO), **os_aguardando_peca**, **os_aguardando_terceiro**, peças abaixo do mínimo (**somente** peças com `controla_estoque=true` no catálogo e `estoque_atual` ≤ `estoque_minimo`), preventivas vencidas, lubrificações do dia, etc. |

---

### Ordens de serviço (`/api/v1/ordens-servico`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/ordens-servico` | Query: `status` (opcional), `excluir_fechadas` (bool; se true, exclui FINALIZADA/CANCELADA), **`minhas`** (bool; se `true`, só OS em que o solicitante é o usuário autenticado), `limit`, `offset`. Cada item inclui `solicitante_id`, `solicitante_nome` (quem abriu a OS) e os mesmos campos de ativo do detalhe (`tag_ativo`, `ativo_descricao`, `setor_nome`, etc.). Qualquer perfil autenticado. |
| `POST` | `/ordens-servico` | Abre OS. Body: campos usuais + opcional `marcar_ativo_parado` (bool; se `true`, grava o ativo como **PARADO** no cadastro). Se `codigo_os` já existir, a API incrementa o sufixo numérico final em `+1` e tenta novamente. Perfis: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **DIRETORIA**, **USUARIO**, **LIDER**. |
| `GET` | `/ordens-servico/{work_order_id}` | Detalhe da OS. Inclui `solicitante_id`, `solicitante_nome` e dados do ativo: `tag_ativo`, `ativo_descricao`, `setor_nome`, `ativo_fabricante`, `ativo_modelo`, `ativo_numero_serie`, `ativo_data_garantia`, `ativo_status`, `ativo_criticidade`. |
| `PATCH` | `/ordens-servico/{work_order_id}/status` | **Descontinuado para uso operacional**: mudança deve ocorrer via apontamento (`POST /ordens-servico/{id}/apontamentos`). |

### Apontamentos de OS (`/api/v1/ordens-servico` — tags `os-apontamentos`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/ordens-servico/{work_order_id}/apontamentos` | Lista histórico de apontamentos (quem, quando, `data_inicio`/`data_fim`, status anterior/novo, descrição). Resposta inclui **`horas_trabalhadas`** (horas entre início e fim quando ambos existem; senão **0**). |
| `POST` | `/ordens-servico/{work_order_id}/apontamentos` | Descrição até **8000** caracteres; período (início/fim); próximo status opcional; opcional `status_ativo` (`PARADO` / `OPERANDO`). Ver **comportamento abaixo** (`SOLICITADO:`). Perfis: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **LIDER**. Ao transicionar para **`FINALIZADA`** a partir de outro status, a API dá **baixa em `pecas.estoque_atual`** para cada solicitação de peça cuja `codigo_peca` (normalizado) coincide com o `codigo_interno` de um item do catálogo com **`controla_estoque=true`**: desconta a **quantidade** da solicitação (jogo único; não rola de novo se a OS já estava finalizada). Itens com `controla_estoque=false` (uso livre) **não** alteram o estoque. |

**Regras obrigatórias de checklist para transição de status:**
- **Sair de `ABERTA`** exceto para **`CANCELADA`** (perfil) ou **`AGENDADA`**: checklist **`LOTO`** concluído (a API acrescenta **`CHECKLIST_OK: LOTO`** ao apontamento; se for **`ABERTA` → `FINALIZADA`** no mesmo passo, exige **LOTO** e **`FINALIZACAO_OS`**). **`ABERTA` → `AGENDADA`**: sem exigência de LOTO concluído. Sair de status que **não** seja **`ABERTA`** (ex.: **`AGENDADA`**) **não** aplica a regra de LOTO, salvo a finalização com **`FINALIZACAO_OS`** quando **`FINALIZADA`**.
- **`FINALIZADA`**: apenas **ADMIN** ou **LIDER**; checklist **`FINALIZACAO_OS`** concluído. A API acrescenta **`CHECKLIST_OK: FINALIZACAO_OS`**; ativo volta a **`OPERANDO`**.
- **`CANCELADA`**: apenas **ADMIN** ou **LIDER**; sem exigência de checklist (sair de **`ABERTA`** para **`CANCELADA`** não exige LOTO).

**Administrativo (métricas / custos / NFs em anexo):** o campo boolean **`consolidada`** (e `consolidada_em` / `consolidada_por_id`) indica que o **ADMIN** registrou o fechamento administrativo na tela de consolidação. **Não** existe status de OS para isso; a OS permanece **`FINALIZADA`**.

### Consolidação administrativa de OS (`/api/v1/ordens-servico` — tags `os-consolidacao`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/ordens-servico/consolidacao/pendentes` | Lista OS **`FINALIZADA`** com **`consolidada=false`** (**ADMIN**, **DIRETORIA** — leitura para acompanhamento). |
| `GET` | `/ordens-servico/{work_order_id}/consolidacao` | Dados para custos, tag de defeito, **ficha da OS** (`os_resumo`), **lista de apontamentos** com horas a partir só de `data_inicio`/`data_fim` (independente de `status_anterior`/`status_novo`), `custo_hora_usuario` e `custo_mao_obra_linha`, totais `total_horas_mao_obra_apontamentos` e `total_custo_mao_obra_sugerido`, além do resumo de horas **por status** (`resumo_horas`). O campo **`resumo_horas.horas_em_execucao`** soma o tempo **na linha do tempo** em que a OS esteve em **`EM_EXECUCAO`** **e** a soma dos intervalos início/fim declarados nos apontamentos (mesma base usada no custo de mão de obra). Campo **`horas_aguardando_aprovacao`**: valores legados `EM_TESTE` no histórico são contabilizados neste bucket. (**ADMIN**, **DIRETORIA**). |
| `POST` | `/ordens-servico/{work_order_id}/consolidacao-salvar-pecas` | **ADMIN**. OS **`FINALIZADA`** e `consolidada=false`. Body: `ajustes_pecas` (igual ao consolidar) e opcionais `custo_internos` / `custo_terceiros` (se omitidos, mantém os gravados na OS). Grava as solicitações de peças, define **`custo_pecas`** como Σ (quantidade × preço unit.) das linhas com preço, e **`custo_total`** = internos + terceiros + peças. **Não** altera `consolidada`. |
| `POST` | `/ordens-servico/{work_order_id}/consolidar` | **ADMIN**. OS **`FINALIZADA`** e `consolidada=false`. Ajusta campos administrativos e peças; define **`consolidada=true`** (status continua **`FINALIZADA`**). |

### Biblioteca TAG_Defeito (`/api/v1/tags-defeito`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/tags-defeito` | Lista catálogo de TAG_Defeito (query: `ativo`, `limit`, `offset`). |
| `POST` | `/tags-defeito` | Cria item no catálogo. Somente **ADMIN**. |
| `PATCH` | `/tags-defeito/{tag_id}` | Atualiza código/descrição/ativo. Somente **ADMIN**. |

### Solicitações de peças na OS (`/api/v1/ordens-servico` — tags `os-solicitacoes-pecas`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/ordens-servico/{work_order_id}/solicitacoes-pecas` | Lista solicitações de peças da OS (cópia de `codigo_peca` / `descricao` gravada no pedido; **sem** vínculo ao catálogo). |
| `POST` | `/ordens-servico/{work_order_id}/solicitacoes-pecas` | Registra solicitação: `quantidade`, `descricao`, `codigo_peca` (opcional). Os valores são **copiados** para a OS; alterações posteriores no cadastro de peças **não** atualizam solicitações antigas. Perfis: **ADMIN**, **TECNICO**, **LUBRIFICADOR**. |
| `PATCH` | `/ordens-servico/solicitacoes-pecas/{request_id}` | **ADMIN**: atualiza campos enviados (`codigo_peca`, `descricao`, `quantidade`, `numero_solicitacao_erp`, `preco_unitario`). Ver **comportamento abaixo** (`ALTERADO:`). |

#### Histórico no texto dos apontamentos (SOLICITADO / ALTERADO)

- **`SOLICITADO:`** — Ao gravar um apontamento (`POST …/apontamentos`), a API **acrescenta ao final** da descrição informada um bloco `SOLICITADO:` com **todas** as solicitações de peças daquela OS (solicitante, data/hora em fuso America/Sao_Paulo, descrição, código se houver, quantidade), separadas por ` | `. Se o texto total ultrapassar 8000 caracteres, é truncado com `...`.
- **`ALTERADO:`** — Ao **admin** salvar correção em `PATCH …/solicitacoes-pecas/{request_id}`, o campo `solicitante_id` **não** muda. Se algum campo alterar de fato, é criado um registro em `os_apontamentos` (mesmo status anterior/novo da OS) com texto `ALTERADO:` e o resumo das mudanças, atribuído ao admin.
- **Frontend:** detalhe da OS em `frontend/views/ordens-servico.php` (modal): ficha do ativo, histórico de apontamentos, solicitações em lista compacta; **ADMIN** edita solicitação (código, descrição, qtde, ERP, preço) e **Salvar alterações**.

---

### Anexos de OS (`/api/v1/ordens-servico` — tags `os-anexos`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `POST` | `/ordens-servico/{work_order_id}/anexos` | Upload multipart (`file`). Query opcional: `os_apontamento_id` para vincular o anexo a um apontamento específico. Aceita PDF (até `MAX_UPLOAD_SIZE_BYTES`) e imagens nos formatos usuais (JPEG, PNG, WEBP, GIF, BMP, TIFF, ICO, HEIC/HEIF com `pillow-heif`, AVIF se suportado pelo Pillow, etc.); o que o Pillow reconhece como imagem também entra. O **envio bruto** da imagem pode ir até `MAX_INGRESS_IMAGE_BYTES` (padrão 50 MB); depois ficam em JPEG redimensionado (`IMAGE_MAX_DIMENSION_PX`) e comprimido até caber em `MAX_UPLOAD_SIZE_BYTES`. Mesmos perfis que podem **abrir OS**: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **DIRETORIA**, **USUARIO**, **LIDER**. |
| `GET` | `/ordens-servico/{work_order_id}/anexos` | Lista anexos. Query: `incluir_removidos` (somente **ADMIN**) e `os_apontamento_id` (filtra anexos de um apontamento). |
| `GET` | `/ordens-servico/anexos/{attachment_id}/download` | Download do arquivo. |
| `DELETE` | `/ordens-servico/anexos/{attachment_id}` | Marca anexo como removido (`deleted_at`). Mesmos perfis que podem **abrir OS** (alinhado ao upload). |

---

### Usuários (`/api/v1/usuarios`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/usuarios` | Query: `ativo`, `limit`, `offset`. Itens incluem `custo_hora_interno` (R$/h) e `permite_trocar_senha` (pode usar o fluxo «trocar senha» no menu). **ADMIN**, **DIRETORIA** (leitura). |
| `GET` | `/usuarios/{user_id}` | Detalhe. **ADMIN**, **DIRETORIA**. |
| `POST` | `/usuarios` | Cria usuário (body: `custo_hora_interno` ≥ 0; `permite_trocar_senha` default `true`). Só **ADMIN**. |
| `PATCH` | `/usuarios/{user_id}` | Atualiza dados/senha/login/`custo_hora_interno`/`permite_trocar_senha`. Só **ADMIN**. |
| `DELETE` | `/usuarios/{user_id}` | Desativa o usuário (`ativo=false`). Só **ADMIN** (não pode desativar a si mesmo). |

> Observação: o campo histórico `email` na tabela/API é utilizado como **login** no produto e não exige formato de e-mail.  
> **`custo_hora_interno`:** valor em R$/h cadastrado no usuário; na consolidação, cada apontamento com início e fim gera `horas_trabalhadas` e linha `horas × custo_hora_interno` do **autor do apontamento** (não depende dos status da transição).

---

### Setores (`/api/v1/setores`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/setores` | Query: `ativo` (filtrar ativos no cadastro), `limit`, `offset`. Retorna também `responsavel1_id/nome` e `responsavel2_id/nome` (opcionais). **LIDER** pode listar (leitura; painel por setor). |
| `GET` | `/setores/{setor_id}` | Detalhe. **LIDER** pode ler. |
| `POST` | `/setores` | Cria setor (tag + descrição + `responsavel1_id`/`responsavel2_id` opcionais). Só **ADMIN**. |
| `PATCH` | `/setores/{setor_id}` | Atualiza, incluindo responsáveis opcionais. Só **ADMIN**. |
| `DELETE` | `/setores/{setor_id}` | Remove se nenhum ativo vinculado. Só **ADMIN**. |

---

### Ativos (`/api/v1/ativos`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/ativos` | Query: `status` (filtro), `limit` (máx. 2000), `offset`. Lista com `setor_nome`. Resposta inclui cache de emulsão (leitura): `emulsao_ultima_concentracao`, `emulsao_ultima_concentracao_em`, `emulsao_ultimo_ph`, `emulsao_ultimo_ph_em` (atualizados ao registrar aferição em **POST /emulsao/inspecoes**; histórico em `inspecoes_emulsao`). |
| `GET` | `/ativos/{asset_id}` | Detalhe do ativo (mesmos campos de cache de emulsão na resposta). |
| `POST` | `/ativos` | Cria. Só **ADMIN**. `tag_ativo` única. |
| `PATCH` | `/ativos/{asset_id}` | **ADMIN**: todos os campos; **LIDER**: apenas `perfil_usinagem`. |
| `DELETE` | `/ativos/{asset_id}` | Exclui se não houver vínculos bloqueadores. Só **ADMIN**. |

Campos do ativo (corpo JSON): `tag_ativo`, `descricao`, `numero_serie` (obrigatório), `fabricante`, `modelo`, `setor_id` (UUID ou omitir), `criticidade` (`BAIXA`…`CRITICA`, default `MEDIA`), `status` (default `OPERANDO`), `horimetro_acumulado` (inteiro ≥ 0), `controle_lubrificacao`, `controle_emulsao`, `tanque_oleo_soluvel` (inteiro; se `controle_emulsao` for verdadeiro, obrigatório e maior que zero), `data_instalacao` e `data_garantia` (ISO `YYYY-MM-DD` ou omitir), `turnos` (1, 2 ou 3 ou omitir), `participa_metricas` (default `false`: ativo não entra em métricas agregadas), `perfil_usinagem` (`LEVE` ou `PESADO`). **Opcionais (CNC / cadastro estendido):** `cnc_tipo_maquina` (ex.: `EIXOS_2`…`EIXOS_6`), `cnc_cursos_xyz_mm`, `cnc_aceleracao_ms2`, `cnc_eixo_4`, `cnc_eixo_5`, `cnc_rpm_maximo`, `cnc_cone`, `cnc_pino_fixacao`, `cnc_tempo_troca_ferramenta_s`, `cnc_unifilar` — ver migração **`database/migrations-manual/2026_04_23_ativos_campos_cnc.sql`** em bases já existentes.

**Instalação nova do PostgreSQL** (reset, `seed_admin`, checklists, agendador, seeds opcionais): ver **`database/README.md`** (*Instalação nova*). Carga opcional de **setores** e **ativos**: `database/migrations-manual/2026_04_17_seed_setores_ativos_inicial.sql`.

---

### Peças / estoque (`/api/v1/pecas`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/pecas` | Query: `q` (opcional, trecho em código interno ou descrição, `ilike`), `abaixo_minimo` (bool; se `true`, retorna só itens com **`controla_estoque=true`** e `estoque_atual` ≤ `estoque_minimo`), `limit` (máx. **100000** por requisição), `offset`. Cada item inclui **`controla_estoque`** (bool, padrão `false`). Leitura: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **DIRETORIA**, **LIDER**, **USUARIO** (importação/exportação em massa seguem rotas próprias e só **ADMIN** onde indicado). |
| `GET` | `/pecas/catalogo-export` | Download **CSV** (UTF-8 com BOM, delimitador `;`): catálogo completo com colunas `id`, `codigo_interno`, `descricao`, `fabricante`, `estoque_atual`, `estoque_minimo`, **`controla_estoque`** (`true`/`false`), `localizacao_almoxarifado`. **ADMIN**, **TECNICO**, **DIRETORIA** (mesmo acesso de leitura do catálogo). |
| `POST` | `/pecas/catalogo-import` | **ADMIN**. `multipart/form-data` com campo `file` (CSV UTF-8; delimitador `;` ou `,`). Cabeçalho obrigatório mapeável para `codigo_interno` (aceita aliases `codigo`, `cod`, `erp`, etc.). Coluna opcional **`controla_estoque`** (ou `controle_estoque`): `sim`/`não`/`true`/`false`/`0`/`1` etc. Se a coluna **não** existir no arquivo, o valor existente no banco é **mantido** em atualizações; em **linha nova** assume `false`. A coluna `id` (se existir no arquivo) é **ignorada** na importação — a localização é **só** por `codigo_interno`, preservando o UUID já gravado no banco ao atualizar. **Upsert por `codigo_interno`**: linha existente é **atualizada** mantendo o mesmo **UUID**. **Não** altera `ordens_servico` nem linhas em `os_solicitacoes_pecas` (a OS guarda só cópia dos dados no momento do pedido). Linhas novas recebem novo UUID. Resposta: `inseridos`, `atualizados`, `linhas_ignoradas`, `erros` (lista com `linha` e `detalhe`). Tamanho máximo do arquivo: 6 MB. |
| `POST` | `/pecas` | Cria peça (body: campos de catálogo, incl. opcional `controla_estoque`, padrão `false`). Só **ADMIN**. |
| `PATCH` | `/pecas/{part_id}` | Atualiza (`part_id` = UUID da peça), incl. `controla_estoque`. Só **ADMIN**. |

> Se `POST /pecas/catalogo-import` ou `GET /pecas/catalogo-export` retornarem **405** ou **404**, o processo da API em produção provavelmente está **desatualizado** (sem essas rotas): reinicie o serviço Uvicorn (`cmms-api`) após o deploy. O parâmetro `{part_id}` nas rotas de peça aceita **apenas UUID**, para não colidir com caminhos como `/pecas/catalogo-import`.

**Frontend:** `?page=pecas` — card “Catálogo ERP” com exportação completa e importação (somente **ADMIN**).

---

### Preventivas — planos (`/api/v1/preventivas`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/preventivas` | Query: `vencidas` (somente com próxima execução no passado), **`somente_ativos`** (se `true`, só planos com flag `ativo`; padrão `false` = lista todos), `limit`, `offset`. |
| `POST` | `/preventivas` | Cria plano. Só **ADMIN**. |
| `PATCH` | `/preventivas/{plan_id}` | Atualiza plano (campos enviados). Pode incluir **`ativo_id`** para trocar o ativo do plano. Para anular `proxima_execucao`, envie `proxima_execucao: null`. Só **ADMIN**. |
| `DELETE` | `/preventivas/{plan_id}` | Remove o plano (não afeta OS já geradas). Só **ADMIN**. |
| `POST` | `/preventivas/{plan_id}/executar` | **Registra execução e abre OS preventiva** (`tipo_manutencao=PREVENTIVA`, **`status=AGENDADA`**, `data_agendamento` = instante da execução) no ativo do plano, com `solicitante` = usuário autenticado; cód. OS no formato `OS-PRV-…` (único). Garante na OS os padrões **LOTO** e **FINALIZACAO_OS** (se ativos) e o checklist cuja `codigo_checklist` = **TAG do ativo** (ex.: `N002-RP-T63`) — o checklist **deve** existir no catálogo com código igual (ignorando maiúsculas) à tag, senão a API responde **422**; o ativo precisa ter TAG. Com **AGENDADA**, o LOTO pode constar **sem** estar concluído; a conclusão do LOTO é exigida na API **ao sair de `ABERTA`**. Atualiza `ultima_execucao` e `proxima_execucao` do plano. Resposta: `ExecutarPreventivaResponse` com `plano` e `ordem_servico`. **ADMIN**, **TECNICO**, **LUBRIFICADOR**. |

**Frontend** `?page=preventivas`: layout alinhado ao catálogo de checklists (aba “Planos”, filtros e tabela com ações em ícones: executar, editar, ativar/desativar, excluir — CRUD completo para **ADMIN**; **TECNICO**/**LUBRIFICADOR** podem executar planos ativos).

---

### Checklists (`/api/v1/checklists`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/checklists` | Lista checklists padrão. Query: `ativo` (opcional), `limit`, `offset`. **`USUARIO`**: mesma listagem (uso no detalhe da OS; gravação em execução segue `POST …/executar`). **LIDER** vê apenas o checklist com código **`FINALIZACAO_OS`**. |
| `POST` | `/checklists` | Cria checklist padrão. Só **ADMIN**. |
| `PATCH` | `/checklists/{checklist_id}` | Atualiza checklist padrão. Só **ADMIN**. |
| `DELETE` | `/checklists/{checklist_id}` | Exclui checklist padrão sem execução vinculada. Só **ADMIN**. |
| `GET` | `/checklists/{checklist_id}/tarefas` | Lista tarefas do checklist padrão. |
| `POST` | `/checklists/{checklist_id}/tarefas` | Adiciona tarefa padrão. Só **ADMIN**. |
| `PATCH` | `/checklists/tarefas/{task_id}` | Atualiza tarefa padrão. Só **ADMIN**. |
| `DELETE` | `/checklists/tarefas/{task_id}` | Remove tarefa padrão. Só **ADMIN**. |
| `POST` | `/checklists/ordens-servico/{work_order_id}/garantir-padroes-obrigatorios` | Garante na OS execução **`LOTO`** e **`FINALIZACAO_OS`** (padrões ativos), se a OS **não** está **`FINALIZADA`** nem **`CANCELADA`** (inclui com status **`AGENDADA`**). Idempotente. Corpo vazio `{}`. Retorno: `criadas`, `checklist_executada_ids`. |
| `POST` | `/checklists/ordens-servico/{work_order_id}/executar` | Copia checklist padrão para a **OS** (sem vínculo obrigatório com apontamento). **`os_apontamento_id`** na execução fica nulo. **ADMIN**, **TECNICO**, **LUBRIFICADOR** — exceto **`FINALIZACAO_OS`**, que só **LIDER** ou **ADMIN** aplicam manualmente com OS em **`AGUARDANDO_APROVACAO`**. Com OS em **`AGUARDANDO_APROVACAO`**, só o checklist de finalização pode ser aplicado manualmente. **`LOTO`** e **`FINALIZACAO_OS`**: no máximo **uma** execução por OS cada (retorno **409** se já existir). Resposta inclui `codigo_checklist`. |
| `GET` | `/checklists/ordens-servico/{work_order_id}/executar` | Lista todas as checklists executadas da OS (`codigo_checklist` em cada item). |
| `GET` | `/checklists/ordens-servico/{work_order_id}/historico` | Histórico de execuções na OS: `usuario_id` / `usuario_nome` = **quem copiou** a instância; `os_apontamento_id` opcional (legado, se houver). |
| `GET` | `/checklists/ordens-servico/{work_order_id}/obrigatorios-status` | Situação dos obrigatórios (`LOTO`, `FINALIZACAO_OS`). Em **`FINALIZACAO_OS`**, `concluido_copia_lider`: concluído e (**cópia** feita por **LIDER** ou **todas** as tarefas obrigatórias com último preenchimento por **LIDER** — ver tarefas `ultimo_preenchimento_por_id`). |
| `GET` | `/checklists/execucoes/{execution_id}/tarefas` | Lista tarefas da execução; inclui `ultimo_preenchimento_por_id`, `ultimo_preenchimento_em`, `ultimo_preenchimento_por_nome` (**quem** alterou OK/observação por último). |
| `PATCH` | `/checklists/execucoes/tarefas/{task_id}` | Marca tarefa e/ou observação; grava **último preenchimento** (`ultimo_preenchimento_por_id`, `ultimo_preenchimento_em`). `FINALIZACAO_OS`: **LIDER** com OS em **`AGUARDANDO_APROVACAO`** ou **ADMIN**; demais checklists: **ADMIN**/**TECNICO**/**LUBRIFICADOR**, não em **`AGUARDANDO_APROVACAO`** exceto finalização. |

---

### Relatórios (`/api/v1/relatorios`)

Rotas abaixo exigem perfil com acesso a “catálogo” (**ADMIN**, **TECNICO**, **LUBRIFICADOR**, **DIRETORIA**) — mesma regra de `require_com_catalogo` (perfil **USUARIO** recebe **403**). Exceção: **`GET /relatorios/cadastros/usuarios`** é só **ADMIN** e **DIRETORIA**.

Parâmetro comum **`formato`:** `json` (padrão) ou `xlsx` (planilha OpenXML) onde indicado; **`GET /relatorios/ordens-servico`** aceita também `csv` (UTF-8 com BOM, `;`).

Relatórios com **`data_inicio`** e **`data_fim`**: quando ambos são obrigatórios, o fim do dia (UTC) está incluído no intervalo; quando opcionais, informe os dois ou nenhum (`422` se só um).

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/relatorios/ordens-servico` | Query: `ativo_id` (opcional), `data_inicio` / `data_fim` opcionais (juntos), `formato` = `json`, `csv` ou `xlsx`. Até 2000 linhas; JSON inclui `solicitante_nome`; CSV usa coluna `aberto_por`. |
| `GET` | `/relatorios/cadastros/setores` | Lista setores (até 5000); `formato` = `json` ou `xlsx` (responsáveis por nome). |
| `GET` | `/relatorios/cadastros/ativos` | Lista ativos com setor agregado; `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/cadastros/usuarios` | **ADMIN**, **DIRETORIA**. Usuários com `custo_hora_interno`; `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/os/consolidadas` | OS **FINALIZADA** e **`consolidada=true`**; filtro opcional por `data_conclusao_real` (`data_inicio` / `data_fim`); `formato` = `json` ou `xlsx` (custos, solicitante, técnico). |
| `GET` | `/relatorios/custos/por-ativo` | **`data_inicio`** e **`data_fim`** obrigatórios. Soma custos de OS finalizadas no período (regra de alinhamento interno/terceiros/peças vs. `custo_total`); inclui idade do ativo (anos); `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/custos/por-setor` | Mesmo período obrigatório e mesma lógica de custos, agregado por setor do ativo; `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/metricas/mttr-por-ativo` | MTTR médio (horas) por ativo: média de `(data_conclusao_real − data_abertura)` de OS **FINALIZADAS** com conclusão no período; `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/metricas/mttr-por-setor` | MTTR médio por setor (mesma base); `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/metricas/mtbf-por-ativo` | Só **CORRETIVAS** com `data_abertura` no período; MTBF = média das horas entre aberturas consecutivas **por ativo** (mínimo 2 ocorrências); `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/metricas/mtbf-por-setor` | Média simples dos MTBF por ativo agrupados por setor; `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/metricas/kpis-por-ativo` | KPIs no período: turnos do cadastro (1–3), horas de operação do período (dias × turnos × 8 h), fator relógio→operação, MTTR/MTBF contextualizados a partir de **corretivas** finalizadas (quando houver), disponibilidade aproximada **MTBF/(MTBF+MTTR)**; sem corretivas, MTTR deriva da média “relógio” das OS finalizadas ajustada pelo fator; `formato` = `json` ou `xlsx`. |
| `GET` | `/relatorios/metricas/kpis-por-setor` | Agregação por setor (médias dos indicadores por ativo no setor); `formato` = `json` ou `xlsx`. |

---

### Lubrificantes (`/api/v1/lubrificantes`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/lubrificantes` | Query: `limit`, `offset`. Retorna `codigo_erp`, `nome`, `fabricante`, `especificacao`, `ativo`. |
| `POST` | `/lubrificantes` | Cria. Campos: `codigo_erp` (obrigatório, único) e `nome` (obrigatório, único). Só **ADMIN**. |
| `PATCH` | `/lubrificantes/{lubricant_id}` | Atualiza os mesmos campos; valida unicidade de `codigo_erp` e `nome`. Só **ADMIN**. |

---

### Pontos de lubrificação (`/api/v1/pontos-lubrificacao`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/pontos-lubrificacao` | Query: `ativo_id` (opcional), `limit`, `offset`. |
| `POST` | `/pontos-lubrificacao` | Cria ponto. Só **ADMIN**. |
| `PATCH` | `/pontos-lubrificacao/{point_id}` | Atualiza ponto. Só **ADMIN**. |
| `POST` | `/pontos-lubrificacao/{point_id}/executar` | Registra execução. Corpo JSON: **`quantidade_oleo_litros`** (obrigatório, valor positivo, em **litros**) e **`observacao`** (opcional, até 2000 caracteres — anomalias na ronda). Grava linha em `lubrificacao_execucoes` e atualiza `ultima_execucao` / `proxima_execucao` do ponto. **ADMIN**, **TECNICO**, **LUBRIFICADOR**. |

---

### Emulsão (`/api/v1/emulsao`)

| Método | Caminho | Descrição |
|--------|---------|-----------|
| `GET` | `/emulsao/ativos` | Lista ativos para emulsão. Query: `somente_controle` (default `true`). Perfis: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **LIDER**. |
| `GET` | `/emulsao/ultimas-medicoes-por-ativo` | Query obrigatória: `ativo_ids` = UUIDs separados por vírgula. Lê o **cache** em `ativos` (`emulsao_*`), preenchido em **POST /emulsao/inspecoes** (última concentração e último pH podem ter datas diferentes). Corpo: lista de `{ ativo_id, ultima_concentracao: { valor, data_inspecao } \| null, ultima_ph: { valor, data_inspecao } \| null }`. Perfis: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **LIDER**, **DIRETORIA**. |
| `POST` | `/emulsao/inspecoes` | Nova inspeção de emulsão (aferição). Aceita preenchimento parcial (**concentração** e/ou **pH**; ao menos um obrigatório), valida ativo com controle de emulsão, calcula necessidade de correção por perfil de usinagem e atualiza status do ativo (`PARADO` quando requer correção, `OPERANDO` quando ok). Faixas de concentração: **LEVE 6-10 (alvo 8)** e **PESADO 10-14 (alvo 12)**; sugestão de ajuste usa o alvo do perfil (água para reduzir, óleo para elevar). Grava histórico em `inspecoes_emulsao` e **atualiza no ativo** os campos `emulsao_ultima_concentracao` / `emulsao_ultima_concentracao_em` se veio Brix, e `emulsao_ultimo_ph` / `emulsao_ultimo_ph_em` se veio pH. **ADMIN**, **TECNICO**, **LUBRIFICADOR**. |
| `GET` | `/emulsao/inspecoes` | Lista aferições. Query: `pendentes`, `limit`, `offset`. Perfis: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **LIDER**, **DIRETORIA**. |
| `GET` | `/emulsao/tarefas-ajuste` | Lista tarefas de ajuste geradas por aferições com correção necessária. Perfis: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **LIDER**, **DIRETORIA**. |
| `POST` | `/emulsao/inspecoes/{inspection_id}/executar-ajuste` | Registra execução de ajuste (água/óleo real aplicado), conclui tarefa e retorna ativo para `OPERANDO`. **ADMIN**, **TECNICO**, **LUBRIFICADOR**. |

**Frontend:** `?page=emulsao` (no menu, **Óleo solúvel** dentro de **Lubrificação**). Acesso à página: **ADMIN**, **TECNICO**, **LUBRIFICADOR**, **DIRETORIA**, **LIDER** (matriz em `frontend/config/cmms_nav.php` e `enforcePaginaPermissoes` em `frontend/public/index.php`; **LUBRIFICADOR** vê **Tarefas** e **Óleo solúvel** sob **Lubrificação**, sem **Óleos**). **ADMIN** e **LIDER** alteram perfil de usinagem (LEVE/PESADO) na primeira aba; aferição e ajustes pelos perfis com permissão de escrita na API acima.

---

### Manutenção desta documentação

Ao incluir ou alterar rotas no FastAPI (`backend/app/api/`), atualize esta seção para ficar alinhada ao OpenAPI. Conferência rápida no servidor:

```bash
cd /var/www/html/backend && ./venv/bin/python -c "
from app.main import app
for r in app.routes:
    if hasattr(r, 'methods') and hasattr(r, 'path'):
        for m in sorted(r.methods - {'HEAD'}):
            if r.path.startswith('/api/') or r.path == '/health':
                print(f'{m:6} {r.path}')
"
```

## Rodando em desenvolvimento

Ver `scripts/README.md` para variáveis (`SKIP_PIP`, `DATABASE_URL`) e detalhes.

### Backend (porta 8000)

```bash
/var/www/html/scripts/start_backend.sh
```

### Frontend (porta 8080)

```bash
/var/www/html/scripts/start_frontend.sh
```

### Backend + frontend no mesmo terminal

```bash
/var/www/html/scripts/start_all.sh
```

### Encerrar processos nas portas 8000 e 8080

```bash
/var/www/html/scripts/stop_cmms.sh
```

### URLs no ambiente atual

- **Login (raiz):** `https://sgm.planifer.com.br/` — tela de entrada; após autenticar, redireciona para o dashboard.
- Frontend (dev PHP embutido): `http://sgm.planifer.com.br:8080`
- API base (direto Uvicorn): `http://sgm.planifer.com.br:8000/api/v1` — em produção com Nginx, use `https://sgm.planifer.com.br/api/v1` (campo na tela de login vem de `frontend/config/api_base.php`).

### PostgreSQL — papéis, senhas e onde está documentado

> **Segurança:** não commitar segredos em repositório **público**. No servidor, use **`backend/.env`** (API) e o arquivo de referência **`deploy/db-credentials.local`** (lista abaixo); ambos estão no **`.gitignore`**.

**Arquivo único de referência (equipe / backup lógico):** `deploy/db-credentials.local` — copie de `deploy/db-credentials.local.example` em instalações novas. Contém `PGUSER`, `PGPASSWORD`, `PGDATABASE` e `DATABASE_URL` num só lugar. O script `scripts/change_cmms_db_password.sh` **atualiza PostgreSQL, `backend/.env` e `deploy/db-credentials.local`** juntos.

| Papel (role) | Uso | Senha / local |
|----------------|-----|----------------|
| **`cmms_app`** | Usuário da API CMMS (`DATABASE_URL`) | Ver `backend/.env` e `deploy/db-credentials.local`. Servidor atual (ref. interna): **`CMMPlanifer2026`** — URL: `postgresql+psycopg2://cmms_app:CMMPlanifer2026@127.0.0.1:5432/cmms` |
| **`postgres`** | Superusuário do cluster PostgreSQL | Definida na instalação do SO/PostgreSQL; **não** fica no repositório do CMMS. |
| **`admin_cmms`** | Papel administrativo opcional no cluster | Senha definida no servidor; **não** documentada no código. |

**Host/porta:** `127.0.0.1` / `5432`. **Banco da aplicação:** `cmms` (não usar o banco `postgres` para dados do CMMS).

**Desenvolvimento / clone:** `backend/.env.example` usa senha de exemplo **`Cmms123`** só para testes locais após `cp .env.example .env` — **não** é a senha do servidor em produção.

**Trocar senha:** `scripts/change_cmms_db_password.sh` (PostgreSQL + `.env` + `deploy/db-credentials.local`).

Se a senha contiver `@`, codifique como `%40` em `DATABASE_URL`. Listar papéis: `sudo -u postgres psql -c "\du"`.

### Testes automatizados (API)

No diretório `backend/` (com venv ativado ou `./venv/bin/pytest`):

```bash
cd /var/www/html/backend && ./venv/bin/pytest
```

**Integração com PostgreSQL (login):** usam o banco configurado em `backend/.env`. Criam e removem um usuário de teste por execução.

```bash
cd /var/www/html/backend && CMMS_INTEGRATION_TESTS=1 ./venv/bin/pytest tests/test_integration_login.py -v
```

## Próximo foco

- Retomada após pausa: **[`docs/CONTINUIDADE.md`](docs/CONTINUIDADE.md)** (índice + UI + operações).
- **Nginx + HTTPS** (certbot) e `curl https://sgm.planifer.com.br/health` via proxy.
- **Segredos:** `SECRET_KEY` forte e `APP_DEBUG=false` — `deploy/production-hardening.md`.
- CI: `.github/workflows/backend-ci.yml`; integração PG: `CMMS_INTEGRATION_TESTS=1`.
- Evoluções futuras: QR nas máquinas, notificações (ver `.cursorrules`).

## Deploy (referência rápida)

- Nginx + headers + modelo HTTPS: `deploy/nginx-cmms.example.conf`
- Unidade systemd da API: `deploy/cmms-api.service`
- Backup PG + timer: `scripts/backup_postgres.sh`, `deploy/cmms-backup.service`, `deploy/cmms-backup.timer`
- Passos: `deploy/README.md`
- **Produção (segredos / HTTPS / firewall):** `deploy/production-hardening.md`
- **CI (GitHub Actions):** `.github/workflows/backend-ci.yml`
- **Go-live (só histórico, mantém cadastros):** `scripts/purge_historicos.sh` — apaga OS, históricos operacionais, zera estoque das peças, limpa anexos no disco; confirmações interativas (`tenho certeza`) ou `--dry-run` para contagens. Não substitui o reset total do schema (`scripts/reset_cmms_database.sh` + `database/README.md`).

## Estrutura padrão recomendada

```text
/var/www/html
├── backend/
├── frontend/
│   ├── config/          (api_base.php; local.php opcional para dev)
│   └── public/
├── scripts/
│   ├── README.md
│   ├── install_dependencies.sh
│   ├── lib/
│   │   ├── cmms_env.sh       (leitura segura de DATABASE_URL no .env)
│   │   └── dev_backend.sh    (venv + .env para start_backend / start_all)
│   ├── start_backend.sh
│   ├── start_frontend.sh
│   ├── start_all.sh
│   ├── stop_cmms.sh
│   ├── backup_postgres.sh
│   ├── backup_sistema.sh
│   ├── restore.sh
│   ├── change_cmms_db_password.sh
│   ├── reset_cmms_database.sh
│   └── purge_historicos.sh
├── database/
│   ├── README.md
│   └── migrations-manual/
├── deploy/
│   ├── README.md
│   ├── production-hardening.md
│   ├── db-credentials.local.example
│   ├── nginx-cmms.example.conf
│   ├── cmms-api.service
│   ├── cmms-backup.service
│   └── cmms-backup.timer
├── docs/
│   ├── CONTINUIDADE.md    (handoff: índice + UI + operações; sem duplicar a API)
│   ├── DEPENDENCIES.md    (APT, Python, PHP, PostgreSQL, pip, vendor)
│   └── MANUAL_USO.md      (manual para utilizadores finais)
├── readme.md
```