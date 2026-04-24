-- Atualiza fluxo de status da OS e adiciona campos de consolidação administrativa.
-- Execute no banco cmms (recomendado com usuário owner/superuser).

ALTER TYPE os_status_enum ADD VALUE IF NOT EXISTS 'AGENDADA';
-- Legado: valor CONSOLIDAR no enum pode existir em bancos antigos; o codigo atual nao usa mais este status operacional.
ALTER TYPE os_status_enum ADD VALUE IF NOT EXISTS 'CONSOLIDAR';

ALTER TABLE ordens_servico
    ADD COLUMN IF NOT EXISTS consolidada boolean NOT NULL DEFAULT false,
    ADD COLUMN IF NOT EXISTS consolidada_em timestamptz NULL,
    ADD COLUMN IF NOT EXISTS consolidada_por_id uuid NULL REFERENCES usuarios(id),
    ADD COLUMN IF NOT EXISTS tag_defeito varchar(120) NULL,
    ADD COLUMN IF NOT EXISTS custo_internos numeric(12,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS custo_terceiros numeric(12,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS custo_pecas numeric(12,2) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS custo_total numeric(12,2) NOT NULL DEFAULT 0;

CREATE INDEX IF NOT EXISTS idx_ordens_servico_consolidada ON ordens_servico(consolidada);
CREATE INDEX IF NOT EXISTS idx_ordens_servico_consolidada_por ON ordens_servico(consolidada_por_id);
