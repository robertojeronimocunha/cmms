-- OS operacional encerra em FINALIZADA. O campo boolean consolidada é só administrativo (custos/métricas).
-- Remove uso dos status operacionais antigos CONSOLIDAR / AGUARDANDO_LIDER (se existirem no banco).
-- Execução: sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 -f 2026_04_15_os_finalizada_sem_estado_consolidar_operacional.sql

UPDATE ordens_servico
SET status = 'EM_TESTE'::os_status_enum
WHERE status::text IN ('CONSOLIDAR', 'AGUARDANDO_LIDER');
