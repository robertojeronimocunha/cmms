-- Biblioteca TAG_Defeito para consolidação de OS.

CREATE TABLE IF NOT EXISTS tags_defeito (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo varchar(80) NOT NULL UNIQUE,
    descricao text NOT NULL,
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_tags_defeito_ativo ON tags_defeito(ativo);

INSERT INTO tags_defeito (codigo, descricao, ativo)
VALUES
  ('MECANICA_DESGASTE', 'Desgaste mecânico', true),
  ('LUBRIFICACAO_INSUFICIENTE', 'Lubrificação insuficiente', true),
  ('ELETRICA_FALHA', 'Falha elétrica', true),
  ('OPERACAO_INCORRETA', 'Operação incorreta', true)
ON CONFLICT (codigo) DO NOTHING;
