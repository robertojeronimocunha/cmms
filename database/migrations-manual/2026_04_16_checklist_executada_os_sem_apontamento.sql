-- Checklists executadas vinculadas à OS (apontamento opcional).
-- Histórico de preenchimento por tarefa.

ALTER TABLE checklist_executada
    ALTER COLUMN os_apontamento_id DROP NOT NULL;

ALTER TABLE checklist_tarefas_executada
    ADD COLUMN IF NOT EXISTS ultimo_preenchimento_por_id uuid NULL REFERENCES usuarios(id),
    ADD COLUMN IF NOT EXISTS ultimo_preenchimento_em timestamptz NULL;

CREATE INDEX IF NOT EXISTS idx_checklist_tarefas_exec_preenchimento    ON checklist_tarefas_executada(ultimo_preenchimento_por_id)
    WHERE ultimo_preenchimento_por_id IS NOT NULL;

COMMENT ON COLUMN checklist_executada.os_apontamento_id IS 'Opcional: apontamento legado; checklists novas ficam só na OS.';
COMMENT ON COLUMN checklist_executada.usuario_id IS 'Quem copiou/gerou a instância do checklist na OS.';
COMMENT ON COLUMN checklist_tarefas_executada.ultimo_preenchimento_por_id IS 'Último usuário que alterou OK/observação desta tarefa.';
