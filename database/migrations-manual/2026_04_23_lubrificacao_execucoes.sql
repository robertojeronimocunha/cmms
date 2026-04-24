-- Histórico por execução: quantidade de óleo (litros) e observação da ronda.

CREATE TABLE IF NOT EXISTS public.lubrificacao_execucoes (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ponto_lubrificacao_id uuid NOT NULL REFERENCES public.pontos_lubrificacao (id) ON DELETE CASCADE,
    usuario_id uuid NULL REFERENCES public.usuarios (id) ON DELETE SET NULL,
    executado_em timestamptz NOT NULL DEFAULT now(),
    quantidade_oleo_litros numeric(12, 3) NOT NULL,
    observacao text NULL
);

CREATE INDEX IF NOT EXISTS ix_lubrificacao_execucoes_ponto ON public.lubrificacao_execucoes (ponto_lubrificacao_id);
CREATE INDEX IF NOT EXISTS ix_lubrificacao_execucoes_executado ON public.lubrificacao_execucoes (executado_em DESC);
