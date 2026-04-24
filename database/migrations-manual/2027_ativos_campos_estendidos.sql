-- Ativos: numero_serie, horímetro inteiro, emulsão, datas, turnos, participação em métricas.
-- sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 -f 2027_ativos_campos_estendidos.sql

BEGIN;

-- 1) Número de série (obrigatório após backfill)
ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS numero_serie varchar(120);
UPDATE public.ativos SET numero_serie = 'N/D' WHERE numero_serie IS NULL OR btrim(numero_serie::text) = '';
ALTER TABLE public.ativos ALTER COLUMN numero_serie SET NOT NULL;

-- 2) Horímetro: varchar -> integer (arredonda decimais legados)
ALTER TABLE public.ativos RENAME COLUMN horimetro_acumulado TO horimetro_legacy;
ALTER TABLE public.ativos ADD COLUMN horimetro_acumulado integer NOT NULL DEFAULT 0;

UPDATE public.ativos SET horimetro_acumulado = (
    CASE
        WHEN horimetro_legacy IS NULL OR btrim(horimetro_legacy::text) = '' THEN 0
        WHEN btrim(horimetro_legacy::text) ~ '^[0-9]+$' THEN btrim(horimetro_legacy::text)::integer
        WHEN btrim(horimetro_legacy::text) ~ '^[0-9]+[.,][0-9]+$' THEN
            round(replace(btrim(horimetro_legacy::text), ',', '.')::numeric, 0)::integer
        ELSE 0
    END
);

ALTER TABLE public.ativos DROP COLUMN horimetro_legacy;

-- 3) Novas colunas
ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS controle_emulsao boolean NOT NULL DEFAULT false;
ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS tanque_oleo_soluvel integer NULL;
ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS data_instalacao date NULL;
ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS data_garantia date NULL;
ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS turnos smallint NULL;

-- participa_metricas: default false (novos); existentes viram true abaixo
ALTER TABLE public.ativos ADD COLUMN IF NOT EXISTS participa_metricas boolean NOT NULL DEFAULT false;

COMMENT ON COLUMN public.ativos.participa_metricas IS 'Se false, o ativo não entra em métricas agregadas.';
COMMENT ON COLUMN public.ativos.tanque_oleo_soluvel IS 'Litros (inteiro). Obrigatório > 0 quando controle_emulsao = true.';

UPDATE public.ativos SET participa_metricas = true;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_ativos_turnos') THEN
        ALTER TABLE public.ativos ADD CONSTRAINT chk_ativos_turnos
            CHECK (turnos IS NULL OR turnos IN (1, 2, 3));
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'chk_ativos_tanque_emulsao') THEN
        ALTER TABLE public.ativos ADD CONSTRAINT chk_ativos_tanque_emulsao
            CHECK (
                (NOT controle_emulsao)
                OR (tanque_oleo_soluvel IS NOT NULL AND tanque_oleo_soluvel > 0)
            );
    END IF;
END $$;

COMMIT;
