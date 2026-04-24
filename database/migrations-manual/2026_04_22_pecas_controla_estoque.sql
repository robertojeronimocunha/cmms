-- Indica se a peça exige contagem no almoxarifado (baixa na OS) e alerta de mínimo no dashboard.
ALTER TABLE pecas
    ADD COLUMN IF NOT EXISTS controla_estoque boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN pecas.controla_estoque IS 'Se true, baixa estoque_atual ao finalizar OS (código da solicitação = codigo_interno) e entra no KPI abaixo do mínimo.';
