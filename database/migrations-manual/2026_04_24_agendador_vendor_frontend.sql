-- Tarefa agendada: atualizar JS/CSS vendor do frontend (bash scripts/update-frontend-vendor.sh).
-- Requer internet no servidor no momento da execução e permissão de escrita em frontend/public/assets/vendor/
-- pelo utilizador do processo da API (ex.: www-data).
-- Intervalo padrão: 1440 minutos (24 h).

INSERT INTO agendador_tarefas (chave, titulo, ativo, intervalo_minutos, proxima_execucao_em)
VALUES (
    'vendor_frontend',
    'Atualizar dependências JS/CSS do frontend (update-frontend-vendor.sh)',
    true,
    1440,
    NOW() + INTERVAL '1 day'
)
ON CONFLICT (chave) DO NOTHING;
