-- Modulo de checklist (padrao + execucao por apontamento de OS)

CREATE TABLE IF NOT EXISTS checklist_padrao (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo_checklist varchar(40) NOT NULL UNIQUE,
    nome varchar(160) NOT NULL,
    descricao text,
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS checklist_tarefas_padrao (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    checklist_padrao_id uuid NOT NULL REFERENCES checklist_padrao(id) ON DELETE CASCADE,
    ordem integer NOT NULL DEFAULT 1,
    tarefa text NOT NULL,
    obrigatoria boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS checklist_executada (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id uuid NOT NULL REFERENCES ordens_servico(id) ON DELETE CASCADE,
    os_apontamento_id uuid NOT NULL REFERENCES os_apontamentos(id) ON DELETE CASCADE,
    checklist_padrao_id uuid NOT NULL REFERENCES checklist_padrao(id),
    usuario_id uuid NOT NULL REFERENCES usuarios(id),
    nome varchar(160) NOT NULL,
    descricao text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS checklist_tarefas_executada (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    checklist_executada_id uuid NOT NULL REFERENCES checklist_executada(id) ON DELETE CASCADE,
    ordem integer NOT NULL DEFAULT 1,
    tarefa text NOT NULL,
    obrigatoria boolean NOT NULL DEFAULT true,
    executada boolean NOT NULL DEFAULT false,
    observacao text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_checklist_tarefas_padrao_checklist
    ON checklist_tarefas_padrao(checklist_padrao_id, ordem);
CREATE INDEX IF NOT EXISTS idx_checklist_executada_os
    ON checklist_executada(ordem_servico_id, os_apontamento_id);
CREATE INDEX IF NOT EXISTS idx_checklist_tarefas_exec_checklist
    ON checklist_tarefas_executada(checklist_executada_id, ordem);
