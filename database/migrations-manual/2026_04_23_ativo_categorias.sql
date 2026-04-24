-- Tipos/categorias de ativo (lista editável) + FK em ativos.

CREATE TABLE IF NOT EXISTS public.ativo_categorias (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    nome varchar(120) NOT NULL,
    ordem integer NOT NULL DEFAULT 0,
    created_at timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT uq_ativo_categorias_nome UNIQUE (nome)
);

CREATE INDEX IF NOT EXISTS idx_ativo_categorias_ordem ON public.ativo_categorias (ordem);

INSERT INTO public.ativo_categorias (nome, ordem) VALUES
    ('Fresa Convencional', 10),
    ('Fresa CNC', 20),
    ('Torno Convencional', 30),
    ('Torno CNC', 40),
    ('Computador', 50),
    ('Ar Condicionado', 60),
    ('Retifica', 70),
    ('Compressor', 80),
    ('Máquina de Erosão', 90),
    ('Predial', 100)
ON CONFLICT (nome) DO NOTHING;

ALTER TABLE public.ativos
    ADD COLUMN IF NOT EXISTS categoria_id uuid NULL REFERENCES public.ativo_categorias (id);

CREATE INDEX IF NOT EXISTS idx_ativos_categoria_id ON public.ativos (categoria_id);
