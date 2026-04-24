# Smoke Test API (Simplificado)

Use este checklist apos subir a API em `http://sgm.planifer.com.br:8000`.

## 0) Testes automatizados (opcional)

- No diretorio `backend/`: `./venv/bin/pytest` — deve passar sem banco (health + 401 sem token).

## 1) Health

- `GET /health` retorna `200`.

## 2) Login

- `POST /api/v1/auth/login` com usuario valido retorna token JWT.
- `POST /api/v1/auth/login` com senha invalida retorna `401`.

## 3) Dashboard

- `GET /api/v1/dashboard/resumo` com token retorna JSON com contagens (KPIs).

## 4) Usuários

- `GET /api/v1/usuarios` com token de `ADMIN` retorna lista.
- `POST /api/v1/usuarios` cria usuario novo.
- `PATCH /api/v1/usuarios/{id}` atualiza perfil/ativo.

## 5) Ativos

- `POST /api/v1/ativos` cria ativo (token **ADMIN**).
- `GET /api/v1/ativos` lista com paginação (`limit/offset`).
- `PATCH /api/v1/ativos/{id}` atualiza campos (**ADMIN** ou **LIDER** só usinagem).

## 6) Peças

- `POST /api/v1/pecas` cria peça.
- `GET /api/v1/pecas?abaixo_minimo=true` filtra abaixo do estoque mínimo.
- `PATCH /api/v1/pecas/{id}` ajusta estoque.

## 7) Ordens de Serviço

- `POST /api/v1/ordens-servico` cria OS com status `ABERTA`.
- Mudança de status: `POST /api/v1/ordens-servico/{id}/apontamentos` (**ADMIN**, **TECNICO**, **LUBRIFICADOR**, **LIDER**). Ex.: `AGUARDANDO_APROVACAO` → `FINALIZADA` com checklist `FINALIZACAO_OS` concluído (finalização só **ADMIN**/**LIDER**).
- **LIDER**: preenche checklist `FINALIZACAO_OS` com OS em `AGUARDANDO_APROVACAO`; cancela OS via apontamento (**ADMIN** ou **LIDER**).
- Consolidação administrativa (`consolidada=true`, custos): `GET .../consolidacao`, `POST .../consolidar` (**ADMIN**), OS `FINALIZADA` ou `CANCELADA` (cancelada permanece cancelada após consolidar).

## 8) Relatórios

- `GET /api/v1/relatorios/ordens-servico` retorna JSON; com `formato=csv` retorna arquivo CSV.

## 9) Preventivas e lubrificação

- `GET /api/v1/preventivas` lista planos; `vencidas=true` filtra atrasados.
- `POST /api/v1/preventivas` cria plano; `POST /api/v1/preventivas/{id}/executar` registra execução.
- `GET/POST /api/v1/lubrificantes` cadastro de lubrificantes.
- `GET/POST /api/v1/pontos-lubrificacao` pontos por máquina; `POST .../{id}/executar` registra lubrificação (corpo JSON: `quantidade_oleo_litros` > 0 em litros; `observacao` opcional).

## 10) Anexos

- `POST /api/v1/ordens-servico/{id}/anexos` aceita PDF e imagens (vários formatos; HEIC com `pillow-heif`); imagens grandes são comprimidas para JPEG.
- Upload acima do limite retorna `400`.
- `GET /api/v1/ordens-servico/{id}/anexos` lista anexos ativos.
- `GET /api/v1/ordens-servico/anexos/{attachment_id}/download` baixa arquivo.
- `DELETE /api/v1/ordens-servico/anexos/{attachment_id}` marca soft delete.
