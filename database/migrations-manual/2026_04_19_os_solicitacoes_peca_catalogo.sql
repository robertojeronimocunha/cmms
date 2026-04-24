-- Vínculo opcional da solicitação de peça ao cadastro oficial (pecas).
ALTER TABLE os_solicitacoes_pecas
    ADD COLUMN IF NOT EXISTS peca_catalogo_id uuid NULL REFERENCES pecas(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_os_solicitacoes_pecas_peca_catalogo
    ON os_solicitacoes_pecas (peca_catalogo_id);
