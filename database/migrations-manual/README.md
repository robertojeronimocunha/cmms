# Migrations manuais (SQL)

Scripts SQL aplicados **manualmente** no PostgreSQL (fora do Alembic), por ordem de necessidade no ambiente.

## Índice útil

- Migrações datadas `2026_*.sql`, `2027_*.sql`: evolução do schema e dados (ver também a lista longa no **`readme.md`** raiz, secção *Banco*).
- Ficheiros **`etapa*.sql`**: conjunto legado de alinhamento / validação (já versionados **nesta pasta**):
  - `etapa4_alinhamento_cursorrules.sql`
  - `etapa5_validacao_pos_migracao.sql`
  - `etapa6_3_soft_delete_anexos.sql`
  - `etapa_simplificacao_status_perfis.sql`

Não é necessário mover estes ficheiros — o repositório já os mantém em `database/migrations-manual/`.

## Aplicação típica

```bash
sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 -f /var/www/html/database/migrations-manual/NOME_DO_SCRIPT.sql
```

Documentação geral do banco: [`database/README.md`](../README.md).
