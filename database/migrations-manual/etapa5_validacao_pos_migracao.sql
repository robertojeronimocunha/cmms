-- ETAPA 5 - VALIDACAO POS MIGRACAO
-- Execute com: sudo -u postgres psql -f etapa5_validacao_pos_migracao.sql

\echo '=== 1) TABELAS OBRIGATORIAS ==='
SELECT t AS tabela, to_regclass('public.' || t) IS NOT NULL AS existe
FROM unnest(ARRAY[
    'usuarios','ativos','componentes','ordens_servico','os_apontamentos',
    'pecas','movimentacao_estoque','fornecedores','terceiros',
    'planos_manutencao','lubrificantes','pontos_lubrificacao',
    'inspecoes_emulsao','os_pecas','os_anexos','notificacoes','logs_sistema'
]) AS t
ORDER BY t;

\echo '=== 2) COLUNAS PADRAO (id, created_at, updated_at) ==='
WITH obrigatorias AS (
    SELECT unnest(ARRAY[
        'usuarios','ativos','componentes','ordens_servico','os_apontamentos',
        'pecas','movimentacao_estoque','fornecedores','terceiros',
        'planos_manutencao','lubrificantes','pontos_lubrificacao',
        'inspecoes_emulsao','os_pecas','os_anexos','notificacoes','logs_sistema'
    ]) AS tabela
)
SELECT
    o.tabela,
    bool_or(c.column_name = 'id') AS tem_id,
    bool_or(c.column_name = 'created_at') AS tem_created_at,
    bool_or(c.column_name = 'updated_at') AS tem_updated_at
FROM obrigatorias o
LEFT JOIN information_schema.columns c
  ON c.table_schema = 'public'
 AND c.table_name = o.tabela
GROUP BY o.tabela
ORDER BY o.tabela;

\echo '=== 3) TIPOS DE COLUNA (UUID / TIMESTAMPTZ) ==='
SELECT
    table_name,
    column_name,
    data_type,
    udt_name
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN (
    'usuarios','ativos','componentes','ordens_servico','os_apontamentos',
    'pecas','movimentacao_estoque','fornecedores','terceiros',
    'planos_manutencao','lubrificantes','pontos_lubrificacao',
    'inspecoes_emulsao','os_pecas','os_anexos','notificacoes','logs_sistema'
  )
  AND column_name IN ('id','created_at','updated_at')
ORDER BY table_name, column_name;

\echo '=== 4) INDICES PRINCIPAIS ==='
SELECT schemaname, tablename, indexname
FROM pg_indexes
WHERE schemaname = 'public'
  AND indexname IN (
    'idx_usuarios_email','idx_usuarios_perfil','idx_ativos_tag','idx_ativos_status',
    'idx_ativos_criticidade','idx_os_status','idx_os_ativo_id','idx_os_tecnico_id',
    'idx_os_data_abertura','idx_os_apontamentos_os_id','idx_os_pecas_os_id',
    'idx_mov_estoque_peca_id','idx_planos_ativo_id','idx_pontos_lubrificacao_ativo_id',
    'idx_emulsao_ativo_data','idx_os_anexos_os_id','idx_notificacoes_usuario_status',
    'idx_logs_entidade'
  )
ORDER BY tablename, indexname;

\echo '=== 5) TRIGGERS updated_at ==='
SELECT event_object_table AS tabela, trigger_name
FROM information_schema.triggers
WHERE trigger_schema = 'public'
  AND trigger_name LIKE 'trg\_%\_updated\_at'
ORDER BY event_object_table;

\echo '=== 6) ENUMS DE DOMINIO ==='
SELECT t.typname AS enum_name, e.enumlabel AS valor
FROM pg_type t
JOIN pg_enum e ON t.oid = e.enumtypid
WHERE t.typname IN (
  'perfil_acesso_enum','os_status_enum','criticidade_enum','ativo_status_enum',
  'prioridade_os_enum','tipo_manutencao_enum','tipo_movimento_estoque_enum',
  'canal_notificacao_enum','status_notificacao_enum'
)
ORDER BY enum_name, e.enumsortorder;

\echo '=== 7) SANIDADE DE RENOMEACAO LEGADA ==='
SELECT
  to_regclass('public.ordem_servico') AS tabela_antiga_ordem_servico,
  to_regclass('public.ordens_servico') AS tabela_nova_ordens_servico,
  to_regclass('public.pecas_os') AS tabela_antiga_pecas_os,
  to_regclass('public.os_pecas') AS tabela_nova_os_pecas;

\echo '=== FIM DA VALIDACAO ==='
