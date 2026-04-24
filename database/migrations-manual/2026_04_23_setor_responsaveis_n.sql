-- Responsáveis por setor: N usuários (antes: só responsavel1_id / responsavel2_id).
-- Após aplicar, a API passa a usar a tabela setor_responsaveis; as colunas legadas em setores
-- continuam sincronizadas (1º e 2º IDs) para compatibilidade.

CREATE TABLE IF NOT EXISTS public.setor_responsaveis (
    setor_id uuid NOT NULL REFERENCES public.setores (id) ON DELETE CASCADE,
    usuario_id uuid NOT NULL REFERENCES public.usuarios (id) ON DELETE CASCADE,
    ordem integer NOT NULL DEFAULT 0,
    PRIMARY KEY (setor_id, usuario_id)
);

CREATE INDEX IF NOT EXISTS idx_setor_responsaveis_usuario_id ON public.setor_responsaveis (usuario_id);

INSERT INTO public.setor_responsaveis (setor_id, usuario_id, ordem)
SELECT id, responsavel1_id, 0
FROM public.setores
WHERE responsavel1_id IS NOT NULL
ON CONFLICT DO NOTHING;

INSERT INTO public.setor_responsaveis (setor_id, usuario_id, ordem)
SELECT id, responsavel2_id, 1
FROM public.setores
WHERE responsavel2_id IS NOT NULL
  AND (responsavel1_id IS NULL OR responsavel2_id <> responsavel1_id)
ON CONFLICT DO NOTHING;
