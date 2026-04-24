-- Reset completo da tabela setores + seed Planifer (27 setores).
-- Uso: quando não há vínculos em ativos.setor_id ou você aceita zerar setor_id.
--
-- psql "postgresql://USER:PASS@HOST:PORT/DB" -f 2026c_reset_setores_e_seed_planifer.sql
-- Depois: systemctl restart cmms-api

BEGIN;

-- Garantir que nenhum ativo aponte para UUID antigo
UPDATE public.ativos SET setor_id = NULL WHERE setor_id IS NOT NULL;

-- Remover FK de ativos -> setores (nome do constraint pode variar)
DO $$
DECLARE
    r record;
BEGIN
    FOR r IN
        SELECT c.conname
        FROM pg_constraint c
        JOIN pg_class rel ON rel.oid = c.conrelid
        JOIN pg_namespace n ON n.oid = rel.relnamespace
        WHERE n.nspname = 'public'
          AND rel.relname = 'ativos'
          AND c.contype = 'f'
          AND c.confrelid = 'public.setores'::regclass
    LOOP
        EXECUTE format('ALTER TABLE public.ativos DROP CONSTRAINT %I', r.conname);
    END LOOP;
END $$;

DROP TABLE IF EXISTS public.setores CASCADE;

CREATE TABLE public.setores (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    tag_setor varchar(32) NOT NULL,
    descricao varchar(200) NOT NULL,
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE UNIQUE INDEX uq_setores_tag ON public.setores (tag_setor);
CREATE UNIQUE INDEX uq_setores_tag_lower ON public.setores (lower(tag_setor));

ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS setor_id uuid;

ALTER TABLE public.ativos
    ADD CONSTRAINT ativos_setor_id_fkey
    FOREIGN KEY (setor_id) REFERENCES public.setores (id);

CREATE INDEX IF NOT EXISTS idx_ativos_setor_id ON public.ativos (setor_id);

INSERT INTO public.setores (tag_setor, descricao, ativo) VALUES
('ADM', 'Administrativo', true),
('ALMOX', 'Almoxarifado', true),
('BANC', 'Bancadas', true),
('CALD', 'Caldeiraria e Soldas', true),
('COMP', 'Compressores e Secadores', true),
('EROS', 'Erosão', true),
('EXP', 'Expedição', true),
('FCNC3', 'Centros de Usinagem 3º Eixo', true),
('FCNC4', 'Centros de Usinagem 4º Eixo', true),
('FCNC5', 'Centros de Usinagem 5º Eixo', true),
('FCONV', 'Fresas Convencionais', true),
('INFRA', 'Infraestrutura Predial', true),
('INSP', 'Inspeção', true),
('MAND', 'Mandrilhadoras', true),
('MANUT', 'Manutenção', true),
('MNTMEC', 'Montagem Mecânica', true),
('PCP', 'Planejamento e Controle da Produção', true),
('PROC', 'Métodos e Processos', true),
('PRT', 'Preset', true),
('REFT', 'Refeitório', true),
('RET', 'Retificas', true),
('SERR', 'Serras', true),
('TCNC', 'Tornos CNC', true),
('TCONV', 'Torno Convencional', true),
('TI', 'Tecnologia da Informação', true),
('TRESI', 'Tratamento de Resíduos', true),
('PRED', 'Baracão Principal', true);

COMMIT;
