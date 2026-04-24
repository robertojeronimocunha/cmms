-- Ajuste do CRUD de lubrificantes: COD_ERP obrigatório e único.

ALTER TABLE lubrificantes
    ADD COLUMN IF NOT EXISTS codigo_erp varchar(40);

UPDATE lubrificantes
   SET codigo_erp = 'LEGADO-' || substr(id::text, 1, 8)
 WHERE codigo_erp IS NULL OR btrim(codigo_erp) = '';

ALTER TABLE lubrificantes
    ALTER COLUMN codigo_erp SET NOT NULL;

CREATE UNIQUE INDEX IF NOT EXISTS uq_lubrificantes_codigo_erp ON lubrificantes (codigo_erp);
