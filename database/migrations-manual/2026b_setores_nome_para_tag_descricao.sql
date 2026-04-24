-- Migração legada: coluna "nome" -> tag_setor + descricao.
-- Idempotente: se "nome" já foi removida, não faz nada (evita ERROR column nome does not exist).

ALTER TABLE setores ADD COLUMN IF NOT EXISTS tag_setor varchar(32);
ALTER TABLE setores ADD COLUMN IF NOT EXISTS descricao varchar(200);

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'setores' AND column_name = 'nome'
    ) THEN
        UPDATE setores SET descricao = nome WHERE descricao IS NULL AND nome IS NOT NULL;
        UPDATE setores SET tag_setor = 'X' || substr(replace(id::text, '-', ''), 1, 8)
        WHERE tag_setor IS NULL;
        ALTER TABLE setores ALTER COLUMN tag_setor SET NOT NULL;
        ALTER TABLE setores ALTER COLUMN descricao SET NOT NULL;
        ALTER TABLE setores DROP COLUMN nome;
    END IF;
END
$$;

DROP INDEX IF EXISTS uq_setores_nome_lower;

CREATE UNIQUE INDEX IF NOT EXISTS uq_setores_tag_lower ON setores (lower(tag_setor));
