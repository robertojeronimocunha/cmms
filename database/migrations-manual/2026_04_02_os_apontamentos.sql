-- Cria apontamentos de OS e vinculo opcional de anexos ao apontamento.

CREATE TABLE IF NOT EXISTS os_apontamentos (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id uuid NOT NULL REFERENCES ordens_servico(id),
    usuario_id uuid NOT NULL REFERENCES usuarios(id),
    status_anterior varchar(30) NOT NULL,
    status_novo varchar(30) NOT NULL,
    descricao text NOT NULL,
    data_inicio timestamptz NULL,
    data_fim timestamptz NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_os_apontamentos_os ON os_apontamentos (ordem_servico_id);
CREATE INDEX IF NOT EXISTS idx_os_apontamentos_usuario ON os_apontamentos (usuario_id);
CREATE INDEX IF NOT EXISTS idx_os_apontamentos_created_at ON os_apontamentos (created_at DESC);

ALTER TABLE os_anexos
    ADD COLUMN IF NOT EXISTS os_apontamento_id uuid NULL REFERENCES os_apontamentos(id);

CREATE INDEX IF NOT EXISTS idx_os_anexos_apontamento ON os_anexos (os_apontamento_id);
