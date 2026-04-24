-- Consolida perfis: ADMIN, TECNICO, LUBRIFICADOR, DIRETORIA, LIDER, USUARIO
-- Remove: SUPERVISOR, MECANICO, CONSULTA, TI (mapeados abaixo)
-- Execução: sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 -f 2026_consolidar_perfis.sql

BEGIN;

ALTER TABLE usuarios ALTER COLUMN perfil_acesso DROP DEFAULT;
ALTER TABLE usuarios ALTER COLUMN perfil_acesso TYPE varchar(32) USING perfil_acesso::text;

UPDATE usuarios SET perfil_acesso = 'TECNICO' WHERE perfil_acesso IN ('MECANICO', 'SUPERVISOR');
UPDATE usuarios SET perfil_acesso = 'DIRETORIA' WHERE perfil_acesso = 'CONSULTA';
UPDATE usuarios SET perfil_acesso = 'USUARIO' WHERE perfil_acesso = 'TI';

UPDATE usuarios SET perfil_acesso = 'USUARIO'
WHERE perfil_acesso NOT IN (
    'ADMIN', 'TECNICO', 'LUBRIFICADOR', 'DIRETORIA', 'LIDER', 'USUARIO'
);

DROP TYPE perfil_acesso_enum;

CREATE TYPE perfil_acesso_enum AS ENUM (
    'ADMIN',
    'TECNICO',
    'LUBRIFICADOR',
    'DIRETORIA',
    'LIDER',
    'USUARIO'
);

ALTER TABLE usuarios
    ALTER COLUMN perfil_acesso TYPE perfil_acesso_enum USING perfil_acesso::perfil_acesso_enum;

COMMIT;
