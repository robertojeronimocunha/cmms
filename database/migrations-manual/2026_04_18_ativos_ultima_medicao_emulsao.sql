-- Última concentração (Brix) e último pH com data/hora em cada ativo (cache alinhado ao histórico em inspecoes_emulsao).
-- Aplicar após deploy do backend que grava estes campos em POST /emulsao/inspecoes.

ALTER TABLE ativos
    ADD COLUMN IF NOT EXISTS emulsao_ultima_concentracao numeric(8, 3),
    ADD COLUMN IF NOT EXISTS emulsao_ultima_concentracao_em timestamptz,
    ADD COLUMN IF NOT EXISTS emulsao_ultimo_ph numeric(8, 3),
    ADD COLUMN IF NOT EXISTS emulsao_ultimo_ph_em timestamptz;

COMMENT ON COLUMN ativos.emulsao_ultima_concentracao IS 'Último Brix registrado (cache; fonte: inspecoes_emulsao)';
COMMENT ON COLUMN ativos.emulsao_ultima_concentracao_em IS 'Data/hora da última medição de concentração';
COMMENT ON COLUMN ativos.emulsao_ultimo_ph IS 'Último pH registrado (cache)';
COMMENT ON COLUMN ativos.emulsao_ultimo_ph_em IS 'Data/hora da última medição de pH';

-- Backfill a partir do histórico (última linha por ativo onde valor não é nulo)
UPDATE ativos a
SET
    emulsao_ultima_concentracao = s.valor_brix,
    emulsao_ultima_concentracao_em = s.data_inspecao
FROM (
    SELECT DISTINCT ON (ativo_id)
        ativo_id,
        valor_brix,
        data_inspecao
    FROM inspecoes_emulsao
    WHERE valor_brix IS NOT NULL
    ORDER BY ativo_id, data_inspecao DESC
) AS s
WHERE a.id = s.ativo_id;

UPDATE ativos a
SET
    emulsao_ultimo_ph = s.valor_ph,
    emulsao_ultimo_ph_em = s.data_inspecao
FROM (
    SELECT DISTINCT ON (ativo_id)
        ativo_id,
        valor_ph,
        data_inspecao
    FROM inspecoes_emulsao
    WHERE valor_ph IS NOT NULL
    ORDER BY ativo_id, data_inspecao DESC
) AS s
WHERE a.id = s.ativo_id;
