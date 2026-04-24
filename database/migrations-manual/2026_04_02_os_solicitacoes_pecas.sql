CREATE TABLE IF NOT EXISTS os_solicitacoes_pecas (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id uuid NOT NULL REFERENCES ordens_servico(id),
    solicitante_id uuid NOT NULL REFERENCES usuarios(id),
    codigo_peca varchar(80) NULL,
    descricao text NOT NULL,
    quantidade numeric(14,3) NOT NULL,
    numero_solicitacao_erp varchar(80) NULL,
    preco_unitario numeric(14,2) NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_os_solicitacoes_pecas_os ON os_solicitacoes_pecas (ordem_servico_id);
CREATE INDEX IF NOT EXISTS idx_os_solicitacoes_pecas_solicitante ON os_solicitacoes_pecas (solicitante_id);
