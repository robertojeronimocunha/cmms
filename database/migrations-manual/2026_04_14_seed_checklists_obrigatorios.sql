-- Seed de checklists obrigatorios de processo:
-- - LOTO
-- - FINALIZACAO_OS
--
-- Script idempotente: pode ser executado mais de uma vez.

BEGIN;

INSERT INTO checklist_padrao (codigo_checklist, nome, descricao, ativo)
VALUES
    ('LOTO', 'Checklist LOTO', 'Checklist de bloqueio e etiquetagem antes do inicio da manutencao.', true),
    ('FINALIZACAO_OS', 'Checklist de finalização da OS', 'Validação final antes de encerrar a OS (preenchimento pelo LIDER em EM_TESTE).', true)
ON CONFLICT (codigo_checklist) DO UPDATE
SET
    nome = EXCLUDED.nome,
    descricao = EXCLUDED.descricao,
    ativo = true,
    updated_at = now();

DELETE FROM checklist_tarefas_padrao
WHERE checklist_padrao_id IN (
    SELECT id
    FROM checklist_padrao
    WHERE codigo_checklist IN ('LOTO', 'FINALIZACAO_OS')
);

WITH cte AS (
    SELECT id, codigo_checklist
    FROM checklist_padrao
    WHERE codigo_checklist IN ('LOTO', 'FINALIZACAO_OS')
)
INSERT INTO checklist_tarefas_padrao (checklist_padrao_id, ordem, tarefa, obrigatoria)
SELECT cte.id, t.ordem, t.tarefa, t.obrigatoria
FROM cte
JOIN (
    VALUES
        ('LOTO', 1, 'Desligar o equipamento e confirmar ausencia de energia.', true),
        ('LOTO', 2, 'Aplicar bloqueio fisico em todas as fontes de energia.', true),
        ('LOTO', 3, 'Aplicar etiqueta de bloqueio com identificacao do responsavel.', true),
        ('LOTO', 4, 'Testar tentativa de partida para validar bloqueio efetivo.', true),
        ('LOTO', 5, 'Comunicar liberacao da manutencao para a area envolvida.', true),

        ('FINALIZACAO_OS', 1, 'Confirmar conclusao tecnica do servico executado.', true),
        ('FINALIZACAO_OS', 2, 'Retirar ferramentas, materiais e limpar a area.', true),
        ('FINALIZACAO_OS', 3, 'Restabelecer condicoes normais e liberar o equipamento.', true),
        ('FINALIZACAO_OS', 4, 'Registrar testes finais e resultado operacional.', true),
        ('FINALIZACAO_OS', 5, 'Validar apontamento final e anexos obrigatorios.', true)
) AS t(codigo_checklist, ordem, tarefa, obrigatoria)
    ON t.codigo_checklist = cte.codigo_checklist
ORDER BY cte.codigo_checklist, t.ordem;

COMMIT;
