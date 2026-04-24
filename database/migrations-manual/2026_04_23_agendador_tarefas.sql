-- Tarefas agendadas (backup completo, preventivas vencidas). Aplicar no PostgreSQL do CMMS.
-- O executor é scripts/cmms_agendador_tick.sh (cron root, ex. */5).

CREATE TABLE IF NOT EXISTS agendador_tarefas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    chave TEXT NOT NULL UNIQUE,
    titulo TEXT NOT NULL,
    ativo BOOLEAN NOT NULL DEFAULT true,
    intervalo_minutos INT NOT NULL CHECK (intervalo_minutos >= 5 AND intervalo_minutos <= 525600),
    ultima_execucao_em TIMESTAMPTZ NULL,
    proxima_execucao_em TIMESTAMPTZ NULL,
    ultimo_ok BOOLEAN NULL,
    ultimo_mensagem TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

INSERT INTO agendador_tarefas (chave, titulo, ativo, intervalo_minutos, proxima_execucao_em)
VALUES
    (
        'backup_completo',
        'Backup PostgreSQL e sistema (mantém as 12 cópias mais recentes de cada tipo)',
        true,
        360,
        NOW() + INTERVAL '6 hours'
    ),
    (
        'preventivas_vencidas',
        'Gerar OS preventivas para planos com data vencida',
        true,
        60,
        NOW() + INTERVAL '1 hour'
    )
ON CONFLICT (chave) DO NOTHING;
