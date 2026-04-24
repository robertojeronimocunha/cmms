-- Setores Planifer (tag + descrição). Idempotente via ON CONFLICT (tag_setor).
-- Pré-requisito: tabela setores com índice único em tag_setor (ver 2026_setores.sql).

INSERT INTO setores (tag_setor, descricao, ativo) VALUES
('ADM', 'Administrativo', true),
('ALMOX', 'Almoxarifado', true),
('BANC', 'Bancadas', true),
('CALD', 'Caldeiraria e Soldas', true),
('COMP', 'Compressores e Secadores', true),
('EROS', 'Erosão', true),
('EXP', 'Expedição', true),
('FCNC3', 'Centros de Usinagem 3º Eixo', true),
('FCNC4', 'Centros de Usinagem 4º Eixo', true),
('FCNC5', 'Centros de Usinagem 5º Eixo', true),
('FCONV', 'Fresas Convencionais', true),
('INFRA', 'Infraestrutura Predial', true),
('INSP', 'Inspeção', true),
('MAND', 'Mandrilhadoras', true),
('MANUT', 'Manutenção', true),
('MNTMEC', 'Montagem Mecânica', true),
('PCP', 'Planejamento e Controle da Produção', true),
('PROC', 'Métodos e Processos', true),
('PRT', 'Preset', true),
('REFT', 'Refeitório', true),
('RET', 'Retificas', true),
('SERR', 'Serras', true),
('TCNC', 'Tornos CNC', true),
('TCONV', 'Torno Convencional', true),
('TI', 'Tecnologia da Informação', true),
('TRESI', 'Tratamento de Resíduos', true),
('PRED', 'Baracão Principal', true)
ON CONFLICT (tag_setor) DO UPDATE SET
    descricao = EXCLUDED.descricao,
    ativo = EXCLUDED.ativo,
    updated_at = now();

-- Opcional: migrar ativos.setor texto para setor_id (rode só se a coluna setor ainda existir)
-- UPDATE ativos a SET setor_id = s.id FROM setores s
-- WHERE a.setor IS NOT NULL AND trim(a.setor) <> '' AND lower(trim(a.setor)) = lower(s.descricao);
-- ALTER TABLE ativos DROP COLUMN IF EXISTS setor;
