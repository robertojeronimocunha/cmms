-- ETAPA 6.3 - SOFT DELETE PARA OS_ANEXOS
BEGIN;

ALTER TABLE os_anexos
ADD COLUMN IF NOT EXISTS deleted_at timestamptz NULL;

CREATE INDEX IF NOT EXISTS idx_os_anexos_deleted_at ON os_anexos(deleted_at);

COMMIT;
