-- Checklist obrigatório LOTO_LIDER (validação pela liderança após LOTO do técnico).
-- Idempotente.

BEGIN;

INSERT INTO checklist_padrao (codigo_checklist, nome, descricao, ativo)
VALUES (
    'LOTO_LIDER',
    'Checklist LOTO — validação do líder',
    'Confirmação de bloqueio e etiquetagem pela liderança após o preenchimento operacional do LOTO.',
    true
)
ON CONFLICT (codigo_checklist) DO UPDATE
SET
    nome = EXCLUDED.nome,
    descricao = EXCLUDED.descricao,
    ativo = true,
    updated_at = now();

DELETE FROM checklist_tarefas_padrao
WHERE checklist_padrao_id IN (
    SELECT id FROM checklist_padrao WHERE codigo_checklist = 'LOTO_LIDER'
);

INSERT INTO checklist_tarefas_padrao (checklist_padrao_id, ordem, tarefa, obrigatoria)
SELECT c.id, v.ordem, v.tarefa, v.obrigatoria
FROM (SELECT id FROM checklist_padrao WHERE codigo_checklist = 'LOTO_LIDER') AS c
CROSS JOIN (
    VALUES
        (1, 'Confirmar que o checklist LOTO operacional foi concluído e registrado na OS.', true),
        (2, 'Validar em campo os bloqueios físicos e etiquetas aplicados pelo técnico.', true),
        (3, 'Confirmar identificação do responsável e comunicação à área afetada.', true),
        (4, 'Registrar aprovação para prosseguir com a intervenção nesta OS.', true)
) AS v(ordem, tarefa, obrigatoria);

COMMIT;
