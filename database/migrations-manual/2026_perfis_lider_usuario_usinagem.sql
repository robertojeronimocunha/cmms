-- Perfis: LIDER (novo); CONSULTA migrado para USUARIO; coluna usinagem nas máquinas.
-- Executar como superuser ou dono do banco: psql -f ...

-- 1) Novo valor no enum de perfil (ignore se já existir)
DO $$
BEGIN
    ALTER TYPE perfil_acesso_enum ADD VALUE 'LIDER';
EXCEPTION
    WHEN duplicate_object THEN NULL;
END
$$;

-- 2) Quem ainda está em CONSULTA vira USUARIO (perfil de solicitação / acompanhamento)
UPDATE usuarios
SET perfil_acesso = 'USUARIO'::perfil_acesso_enum
WHERE perfil_acesso = 'CONSULTA'::perfil_acesso_enum;

-- 3) Usinagem leve/pesado na máquina (texto, validado na API)
ALTER TABLE ativos
    ADD COLUMN IF NOT EXISTS perfil_usinagem varchar(10) NOT NULL DEFAULT 'LEVE';

UPDATE ativos SET perfil_usinagem = 'LEVE' WHERE perfil_usinagem IS NULL OR perfil_usinagem = '';

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'ativos_perfil_usinagem_check'
    ) THEN
        ALTER TABLE ativos
            ADD CONSTRAINT ativos_perfil_usinagem_check
            CHECK (perfil_usinagem IN ('LEVE', 'PESADO'));
    END IF;
END
$$;
