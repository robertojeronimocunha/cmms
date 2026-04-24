-- Permite aferição parcial de emulsão (apenas concentração ou apenas pH).

ALTER TABLE inspecoes_emulsao
    ALTER COLUMN valor_brix DROP NOT NULL,
    ALTER COLUMN valor_ph DROP NOT NULL;
