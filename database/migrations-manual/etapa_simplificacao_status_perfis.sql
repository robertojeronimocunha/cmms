-- Simplificacao de perfis e status conforme novo .cursorrules
-- Execute: sudo -u postgres psql -f /var/www/html/etapa_simplificacao_status_perfis.sql

BEGIN;

-- Etapa 1: garantir novo valor no enum de perfil
DO $$
BEGIN
    IF EXISTS (
        SELECT 1
        FROM pg_type t
        JOIN pg_enum e ON e.enumtypid = t.oid
        WHERE t.typname = 'perfil_acesso_enum'
          AND e.enumlabel = 'CONSULTA'
    ) THEN
        NULL;
    ELSE
        ALTER TYPE perfil_acesso_enum ADD VALUE 'CONSULTA';
    END IF;
END $$;

COMMIT;

BEGIN;

-- 1) PERFIS: migracao para conjunto simples
-- alvo: ADMIN, SUPERVISOR, MECANICO, LUBRIFICADOR, CONSULTA
UPDATE usuarios
SET perfil_acesso = 'CONSULTA'
WHERE perfil_acesso IN ('TI', 'USUARIO');

-- 2) STATUS OS: remover estados complexos via mapeamento
-- alvo: ABERTA, EM_EXECUCAO, AGUARDANDO_PECA, EM_TESTE, FINALIZADA, CANCELADA

UPDATE ordens_servico
SET status = 'ABERTA'
WHERE status = 'AGENDADA';

UPDATE ordens_servico
SET status = 'AGUARDANDO_PECA'
WHERE status = 'AGUARDANDO_TERCEIRO';

UPDATE ordens_servico
SET status = 'EM_EXECUCAO'
WHERE status = 'BLOQUEADA_SEGURANCA';

-- 3) recria enum de status com apenas valores simplificados
DO $$
BEGIN
    IF EXISTS (SELECT 1 FROM pg_type WHERE typname = 'os_status_enum') THEN
        ALTER TYPE os_status_enum RENAME TO os_status_enum_old;
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'os_status_enum') THEN
        CREATE TYPE os_status_enum AS ENUM (
            'ABERTA',
            'EM_EXECUCAO',
            'AGUARDANDO_PECA',
            'EM_TESTE',
            'FINALIZADA',
            'CANCELADA'
        );
    END IF;
END $$;

ALTER TABLE ordens_servico
    ALTER COLUMN status DROP DEFAULT;

ALTER TABLE ordens_servico
    ALTER COLUMN status TYPE os_status_enum
    USING status::text::os_status_enum;

ALTER TABLE ordens_servico
    ALTER COLUMN status SET DEFAULT 'ABERTA'::os_status_enum;

DROP TYPE IF EXISTS os_status_enum_old;

COMMIT;
