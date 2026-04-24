-- Adiciona dois responsaveis opcionais no cadastro de setores

ALTER TABLE setores
    ADD COLUMN IF NOT EXISTS responsavel1_id uuid NULL REFERENCES usuarios(id),
    ADD COLUMN IF NOT EXISTS responsavel2_id uuid NULL REFERENCES usuarios(id);

CREATE INDEX IF NOT EXISTS idx_setores_responsavel1_id ON setores(responsavel1_id);
CREATE INDEX IF NOT EXISTS idx_setores_responsavel2_id ON setores(responsavel2_id);
