-- Estrutura de setores (tag + descrição) e coluna setor_id em ativos.
-- Depois rode: psql -f .../seed_setores_planifer.sql
-- Em seguida, se ainda existir coluna texto ativos.setor, execute o UPDATE abaixo e o DROP.

CREATE TABLE IF NOT EXISTS setores (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    tag_setor varchar(32) NOT NULL,
    descricao varchar(200) NOT NULL,
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX IF NOT EXISTS uq_setores_tag ON setores (tag_setor);
CREATE UNIQUE INDEX IF NOT EXISTS uq_setores_tag_lower ON setores (lower(tag_setor));

ALTER TABLE ativos ADD COLUMN IF NOT EXISTS setor_id uuid REFERENCES setores(id);

-- Após carregar seed_setores_planifer.sql, vincular máquinas pelo texto antigo (se existir):
-- UPDATE ativos a SET setor_id = s.id FROM setores s
-- WHERE a.setor IS NOT NULL AND trim(a.setor) <> '' AND lower(trim(a.setor)) = lower(s.descricao);
-- ALTER TABLE ativos DROP COLUMN IF EXISTS setor;

CREATE INDEX IF NOT EXISTS idx_ativos_setor_id ON ativos (setor_id);
