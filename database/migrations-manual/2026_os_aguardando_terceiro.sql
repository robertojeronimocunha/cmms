-- Novo status de OS: AGUARDANDO_TERCEIRO (em paralelo a AGUARDANDO_PECA).
-- Executar como superuser ou dono do tipo, após backup.
-- Idempotente: ignora se o valor já existir (PostgreSQL 9.1+ ADD VALUE sem IF NOT EXISTS em versões antigas — ajuste se necessário).

DO $m$
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM pg_enum e
        JOIN pg_type t ON t.oid = e.enumtypid
        WHERE t.typname = 'os_status_enum'
          AND e.enumlabel = 'AGUARDANDO_TERCEIRO'
    ) THEN
        ALTER TYPE os_status_enum ADD VALUE 'AGUARDANDO_TERCEIRO';
    END IF;
END
$m$;
