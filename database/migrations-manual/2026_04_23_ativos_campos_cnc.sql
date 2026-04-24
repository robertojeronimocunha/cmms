-- Campos opcionais CNC / usinagem no cadastro de ativos (API + frontend).
-- Aplicar no PostgreSQL do CMMS após backup, se necessário:
--   sudo -u postgres psql -d cmms -v ON_ERROR_STOP=1 -f database/migrations-manual/2026_04_23_ativos_campos_cnc.sql

ALTER TABLE ativos
    ADD COLUMN IF NOT EXISTS cnc_tipo_maquina VARCHAR(40) NULL,
    ADD COLUMN IF NOT EXISTS cnc_cursos_xyz_mm VARCHAR(80) NULL,
    ADD COLUMN IF NOT EXISTS cnc_aceleracao_ms2 NUMERIC(10, 2) NULL,
    ADD COLUMN IF NOT EXISTS cnc_eixo_4 VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS cnc_eixo_5 VARCHAR(500) NULL,
    ADD COLUMN IF NOT EXISTS cnc_rpm_maximo INTEGER NULL,
    ADD COLUMN IF NOT EXISTS cnc_cone VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS cnc_pino_fixacao VARCHAR(120) NULL,
    ADD COLUMN IF NOT EXISTS cnc_tempo_troca_ferramenta_s NUMERIC(8, 2) NULL,
    ADD COLUMN IF NOT EXISTS cnc_unifilar VARCHAR(255) NULL;
