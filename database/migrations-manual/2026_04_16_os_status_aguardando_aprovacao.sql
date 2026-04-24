-- Renomeia o estado operacional EM_TESTE -> AGUARDANDO_APROVACAO (enum + dados + histórico em texto).
-- Executar após backup. Requer superusuário PostgreSQL (owner do tipo enum), ex.: sudo -u postgres psql -d cmms -f este_arquivo.sql

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_enum e
        JOIN pg_type t ON e.enumtypid = t.oid
        WHERE t.typname = 'os_status_enum' AND e.enumlabel = 'AGUARDANDO_APROVACAO'
    ) THEN
        ALTER TYPE os_status_enum ADD VALUE 'AGUARDANDO_APROVACAO';
    END IF;
END
$$;

UPDATE ordens_servico
SET status = 'AGUARDANDO_APROVACAO'::os_status_enum
WHERE status::text = 'EM_TESTE';

UPDATE os_apontamentos SET status_anterior = 'AGUARDANDO_APROVACAO' WHERE status_anterior = 'EM_TESTE';
UPDATE os_apontamentos SET status_novo = 'AGUARDANDO_APROVACAO' WHERE status_novo = 'EM_TESTE';
