-- Utilizador escolhido como solicitante nas OS geradas pela tarefa preventivas_vencidas (opcional).
ALTER TABLE agendador_tarefas
    ADD COLUMN IF NOT EXISTS solicitante_usuario_id UUID NULL REFERENCES usuarios(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS ix_agendador_tarefas_solicitante_usuario
    ON agendador_tarefas(solicitante_usuario_id);
