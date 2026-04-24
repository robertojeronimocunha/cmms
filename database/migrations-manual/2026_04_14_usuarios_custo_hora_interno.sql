-- Custo/hora (R$) para custeio de mão de obra interna a partir dos apontamentos (data_inicio/data_fim).
ALTER TABLE usuarios
 ADD COLUMN IF NOT EXISTS custo_hora_interno NUMERIC(12, 2) NOT NULL DEFAULT 0;

COMMENT ON COLUMN usuarios.custo_hora_interno IS 'Custo por hora (R$) para apuração de mão de obra interna na consolidação de OS.';
