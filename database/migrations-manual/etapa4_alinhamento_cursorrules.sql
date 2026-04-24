-- ETAPA 4 - ALINHAMENTO DO BANCO AO .CURSORRULES
-- PostgreSQL 16+
-- Execucao recomendada: ambiente homologacao primeiro.

BEGIN;

-- ==================================================
-- 0) BASE TECNICA
-- ==================================================
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ==================================================
-- 1) ENUMS DE DOMINIO
-- ==================================================
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'perfil_acesso_enum') THEN
        CREATE TYPE perfil_acesso_enum AS ENUM ('ADMIN', 'SUPERVISOR', 'MECANICO', 'LUBRIFICADOR', 'TI', 'USUARIO');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'os_status_enum') THEN
        CREATE TYPE os_status_enum AS ENUM (
            'ABERTA',
            'AGENDADA',
            'EM_EXECUCAO',
            'AGUARDANDO_PECA',
            'AGUARDANDO_TERCEIRO',
            'BLOQUEADA_SEGURANCA',
            'EM_TESTE',
            'FINALIZADA',
            'CANCELADA'
        );
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'criticidade_enum') THEN
        CREATE TYPE criticidade_enum AS ENUM ('BAIXA', 'MEDIA', 'ALTA', 'CRITICA');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'ativo_status_enum') THEN
        CREATE TYPE ativo_status_enum AS ENUM ('OPERANDO', 'PARADO', 'MANUTENCAO', 'INATIVO');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'prioridade_os_enum') THEN
        CREATE TYPE prioridade_os_enum AS ENUM ('BAIXA', 'MEDIA', 'ALTA', 'URGENTE');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'tipo_manutencao_enum') THEN
        CREATE TYPE tipo_manutencao_enum AS ENUM ('CORRETIVA', 'PREVENTIVA', 'PREDITIVA', 'MELHORIA', 'INSPECAO');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'tipo_movimento_estoque_enum') THEN
        CREATE TYPE tipo_movimento_estoque_enum AS ENUM ('ENTRADA', 'SAIDA', 'AJUSTE');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'canal_notificacao_enum') THEN
        CREATE TYPE canal_notificacao_enum AS ENUM ('SISTEMA', 'EMAIL', 'WHATSAPP');
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'status_notificacao_enum') THEN
        CREATE TYPE status_notificacao_enum AS ENUM ('PENDENTE', 'ENVIADA', 'FALHA');
    END IF;
END $$;

-- ==================================================
-- 2) FUNCOES AUXILIARES
-- ==================================================
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$;

CREATE OR REPLACE FUNCTION ensure_columns_padrao(p_table regclass)
RETURNS void
LANGUAGE plpgsql
AS $$
BEGIN
    EXECUTE format('ALTER TABLE %s ADD COLUMN IF NOT EXISTS id uuid', p_table);
    EXECUTE format('ALTER TABLE %s ALTER COLUMN id SET DEFAULT gen_random_uuid()', p_table);
    EXECUTE format('ALTER TABLE %s ADD COLUMN IF NOT EXISTS created_at timestamptz NOT NULL DEFAULT now()', p_table);
    EXECUTE format('ALTER TABLE %s ADD COLUMN IF NOT EXISTS updated_at timestamptz NOT NULL DEFAULT now()', p_table);
END;
$$;

-- ==================================================
-- 3) RENOMEACOES LEGADAS (SE EXISTIREM)
-- ==================================================
DO $$
BEGIN
    IF to_regclass('public.ordem_servico') IS NOT NULL
       AND to_regclass('public.ordens_servico') IS NULL THEN
        EXECUTE 'ALTER TABLE public.ordem_servico RENAME TO ordens_servico';
    END IF;

    IF to_regclass('public.pecas_os') IS NOT NULL
       AND to_regclass('public.os_pecas') IS NULL THEN
        EXECUTE 'ALTER TABLE public.pecas_os RENAME TO os_pecas';
    END IF;

    IF to_regclass('public.log_lubrificacao') IS NOT NULL
       AND to_regclass('public.pontos_lubrificacao') IS NULL THEN
        -- Mantem historico antigo, criaremos tabela alvo separada.
        NULL;
    END IF;
END $$;

-- ==================================================
-- 4) TABELAS OBRIGATORIAS
-- ==================================================
CREATE TABLE IF NOT EXISTS usuarios (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    nome_completo varchar(160) NOT NULL,
    email varchar(180) NOT NULL UNIQUE,
    senha_hash text NOT NULL,
    perfil_acesso perfil_acesso_enum NOT NULL,
    ativo boolean NOT NULL DEFAULT true,
    ultimo_login timestamptz NULL,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS ativos (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    tag_ativo varchar(80) NOT NULL UNIQUE,
    descricao varchar(200) NOT NULL,
    fabricante varchar(120),
    modelo varchar(120),
    numero_serie varchar(120),
    data_instalacao timestamptz,
    data_garantia timestamptz,
    setor varchar(120),
    criticidade criticidade_enum NOT NULL DEFAULT 'MEDIA',
    status ativo_status_enum NOT NULL DEFAULT 'OPERANDO',
    horimetro_acumulado numeric(12,2) NOT NULL DEFAULT 0,
    controle_lubrificacao boolean NOT NULL DEFAULT false,
    controle_emulsao boolean NOT NULL DEFAULT false,
    possui_tanque boolean NOT NULL DEFAULT false,
    observacoes text,
    dados jsonb NOT NULL DEFAULT '{}'::jsonb,
    deleted_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS componentes (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ativo_id uuid NOT NULL REFERENCES ativos(id),
    nome varchar(160) NOT NULL,
    descricao text,
    criticidade criticidade_enum NOT NULL DEFAULT 'MEDIA',
    deleted_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS fornecedores (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    razao_social varchar(180) NOT NULL,
    nome_fantasia varchar(180),
    cnpj varchar(20),
    email varchar(180),
    telefone varchar(40),
    contato varchar(120),
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS terceiros (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    fornecedor_id uuid REFERENCES fornecedores(id),
    nome varchar(180) NOT NULL,
    especialidade varchar(120),
    email varchar(180),
    telefone varchar(40),
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS pecas (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo_interno varchar(80) NOT NULL UNIQUE,
    descricao varchar(200) NOT NULL,
    fabricante varchar(120),
    unidade_medida varchar(20) NOT NULL DEFAULT 'UN',
    estoque_atual numeric(14,3) NOT NULL DEFAULT 0,
    estoque_minimo numeric(14,3) NOT NULL DEFAULT 0,
    custo_unitario numeric(14,4) NOT NULL DEFAULT 0,
    localizacao_almoxarifado varchar(120),
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS ordens_servico (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    codigo_os varchar(40) NOT NULL UNIQUE,
    ativo_id uuid NOT NULL REFERENCES ativos(id),
    solicitante_id uuid NOT NULL REFERENCES usuarios(id),
    tecnico_id uuid REFERENCES usuarios(id),
    tipo_manutencao tipo_manutencao_enum NOT NULL DEFAULT 'CORRETIVA',
    prioridade prioridade_os_enum NOT NULL DEFAULT 'MEDIA',
    status os_status_enum NOT NULL DEFAULT 'ABERTA',
    data_abertura timestamptz NOT NULL DEFAULT now(),
    data_agendamento timestamptz,
    data_inicio_real timestamptz,
    data_conclusao_real timestamptz,
    falha_sintoma text,
    causa_raiz text,
    solucao text,
    observacoes text,
    deleted_at timestamptz,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS os_apontamentos (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id uuid NOT NULL REFERENCES ordens_servico(id) ON DELETE CASCADE,
    tecnico_id uuid NOT NULL REFERENCES usuarios(id),
    apontamento text NOT NULL,
    horas_apontadas numeric(8,2) DEFAULT 0,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS os_pecas (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id uuid NOT NULL REFERENCES ordens_servico(id) ON DELETE CASCADE,
    peca_id uuid NOT NULL REFERENCES pecas(id),
    quantidade numeric(14,3) NOT NULL,
    custo_unitario_snapshot numeric(14,4) NOT NULL DEFAULT 0,
    custo_total_snapshot numeric(14,4) NOT NULL DEFAULT 0,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS movimentacao_estoque (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    peca_id uuid NOT NULL REFERENCES pecas(id),
    tipo_movimento tipo_movimento_estoque_enum NOT NULL,
    quantidade numeric(14,3) NOT NULL,
    custo_unitario_snapshot numeric(14,4) NOT NULL DEFAULT 0,
    origem varchar(120),
    referencia_id uuid,
    observacoes text,
    usuario_id uuid REFERENCES usuarios(id),
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS planos_manutencao (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ativo_id uuid NOT NULL REFERENCES ativos(id),
    titulo varchar(160) NOT NULL,
    descricao text,
    periodicidade_dias int NOT NULL,
    ultima_execucao timestamptz,
    proxima_execucao timestamptz,
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS lubrificantes (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    nome varchar(120) NOT NULL UNIQUE,
    fabricante varchar(120),
    especificacao varchar(120),
    ativo boolean NOT NULL DEFAULT true,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS pontos_lubrificacao (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ativo_id uuid NOT NULL REFERENCES ativos(id),
    lubrificante_id uuid REFERENCES lubrificantes(id),
    descricao_ponto varchar(180) NOT NULL,
    periodicidade_dias int NOT NULL,
    ultima_execucao timestamptz,
    proxima_execucao timestamptz,
    observacoes text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS inspecoes_emulsao (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ativo_id uuid NOT NULL REFERENCES ativos(id),
    tecnico_id uuid NOT NULL REFERENCES usuarios(id),
    data_inspecao timestamptz NOT NULL DEFAULT now(),
    valor_brix numeric(8,3) NOT NULL,
    valor_ph numeric(8,3) NOT NULL,
    temperatura_emulsao numeric(8,3),
    volume_tanque_litros numeric(12,2),
    status_inspecao varchar(40) NOT NULL,
    observacoes text,
    precisa_correcao boolean NOT NULL DEFAULT false,
    volume_agua_sugerido numeric(12,3),
    volume_oleo_sugerido numeric(12,3),
    volume_agua_real numeric(12,3),
    volume_oleo_real numeric(12,3),
    data_correcao timestamptz,
    foto_teste text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS os_anexos (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    ordem_servico_id uuid NOT NULL REFERENCES ordens_servico(id) ON DELETE CASCADE,
    usuario_id uuid NOT NULL REFERENCES usuarios(id),
    nome_arquivo varchar(255) NOT NULL,
    caminho_arquivo text NOT NULL,
    mime_type varchar(120) NOT NULL,
    tamanho_bytes bigint NOT NULL CHECK (tamanho_bytes > 0),
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS notificacoes (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id uuid REFERENCES usuarios(id),
    titulo varchar(180) NOT NULL,
    mensagem text NOT NULL,
    canal canal_notificacao_enum NOT NULL DEFAULT 'SISTEMA',
    status status_notificacao_enum NOT NULL DEFAULT 'PENDENTE',
    referencia_tipo varchar(80),
    referencia_id uuid,
    enviado_em timestamptz,
    erro_envio text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS logs_sistema (
    id uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    usuario_id uuid REFERENCES usuarios(id),
    acao varchar(120) NOT NULL,
    entidade varchar(120),
    entidade_id uuid,
    nivel varchar(20) NOT NULL DEFAULT 'INFO',
    detalhes jsonb NOT NULL DEFAULT '{}'::jsonb,
    ip_origem varchar(64),
    user_agent text,
    created_at timestamptz NOT NULL DEFAULT now(),
    updated_at timestamptz NOT NULL DEFAULT now()
);

-- ==================================================
-- 5) AJUSTE DE TABELAS LEGADAS COM CAMPOS PADRAO
-- ==================================================
DO $$
DECLARE
    t regclass;
BEGIN
    FOREACH t IN ARRAY ARRAY[
        to_regclass('public.usuarios'),
        to_regclass('public.ativos'),
        to_regclass('public.componentes'),
        to_regclass('public.ordens_servico'),
        to_regclass('public.os_apontamentos'),
        to_regclass('public.pecas'),
        to_regclass('public.movimentacao_estoque'),
        to_regclass('public.fornecedores'),
        to_regclass('public.terceiros'),
        to_regclass('public.planos_manutencao'),
        to_regclass('public.lubrificantes'),
        to_regclass('public.pontos_lubrificacao'),
        to_regclass('public.inspecoes_emulsao'),
        to_regclass('public.os_pecas'),
        to_regclass('public.os_anexos'),
        to_regclass('public.notificacoes'),
        to_regclass('public.logs_sistema')
    ]
    LOOP
        IF t IS NOT NULL THEN
            PERFORM ensure_columns_padrao(t);
        END IF;
    END LOOP;
END $$;

-- ==================================================
-- 6) INDICES ESSENCIAIS
-- ==================================================
CREATE INDEX IF NOT EXISTS idx_usuarios_email ON usuarios(email);
CREATE INDEX IF NOT EXISTS idx_usuarios_perfil ON usuarios(perfil_acesso);

CREATE INDEX IF NOT EXISTS idx_ativos_tag ON ativos(tag_ativo);
CREATE INDEX IF NOT EXISTS idx_ativos_status ON ativos(status);
CREATE INDEX IF NOT EXISTS idx_ativos_criticidade ON ativos(criticidade);

CREATE INDEX IF NOT EXISTS idx_os_status ON ordens_servico(status);
CREATE INDEX IF NOT EXISTS idx_os_ativo_id ON ordens_servico(ativo_id);
CREATE INDEX IF NOT EXISTS idx_os_tecnico_id ON ordens_servico(tecnico_id);
CREATE INDEX IF NOT EXISTS idx_os_data_abertura ON ordens_servico(data_abertura);

CREATE INDEX IF NOT EXISTS idx_os_apontamentos_os_id ON os_apontamentos(ordem_servico_id);
CREATE INDEX IF NOT EXISTS idx_os_pecas_os_id ON os_pecas(ordem_servico_id);
CREATE INDEX IF NOT EXISTS idx_mov_estoque_peca_id ON movimentacao_estoque(peca_id);
CREATE INDEX IF NOT EXISTS idx_planos_ativo_id ON planos_manutencao(ativo_id);
CREATE INDEX IF NOT EXISTS idx_pontos_lubrificacao_ativo_id ON pontos_lubrificacao(ativo_id);
CREATE INDEX IF NOT EXISTS idx_emulsao_ativo_data ON inspecoes_emulsao(ativo_id, data_inspecao DESC);
CREATE INDEX IF NOT EXISTS idx_os_anexos_os_id ON os_anexos(ordem_servico_id);
CREATE INDEX IF NOT EXISTS idx_notificacoes_usuario_status ON notificacoes(usuario_id, status);
CREATE INDEX IF NOT EXISTS idx_logs_entidade ON logs_sistema(entidade, entidade_id);

-- ==================================================
-- 7) TRIGGERS updated_at
-- ==================================================
DO $$
DECLARE
    r record;
BEGIN
    FOR r IN
        SELECT tablename
        FROM pg_tables
        WHERE schemaname = 'public'
          AND tablename IN (
            'usuarios','ativos','componentes','ordens_servico','os_apontamentos',
            'pecas','movimentacao_estoque','fornecedores','terceiros','planos_manutencao',
            'lubrificantes','pontos_lubrificacao','inspecoes_emulsao','os_pecas',
            'os_anexos','notificacoes','logs_sistema'
          )
    LOOP
        EXECUTE format('DROP TRIGGER IF EXISTS trg_%I_updated_at ON %I', r.tablename, r.tablename);
        EXECUTE format(
            'CREATE TRIGGER trg_%I_updated_at BEFORE UPDATE ON %I FOR EACH ROW EXECUTE FUNCTION set_updated_at()',
            r.tablename, r.tablename
        );
    END LOOP;
END $$;

-- ==================================================
-- 8) CONSTRAINTS DE SANIDADE
-- ==================================================
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'chk_os_datas_consistentes'
    ) THEN
        ALTER TABLE ordens_servico
        ADD CONSTRAINT chk_os_datas_consistentes
        CHECK (
            (data_inicio_real IS NULL OR data_inicio_real >= data_abertura)
            AND
            (data_conclusao_real IS NULL OR data_conclusao_real >= data_abertura)
        );
    END IF;
END $$;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint
        WHERE conname = 'chk_pecas_estoque_nao_negativo'
    ) THEN
        ALTER TABLE pecas
        ADD CONSTRAINT chk_pecas_estoque_nao_negativo
        CHECK (estoque_atual >= 0 AND estoque_minimo >= 0);
    END IF;
END $$;

COMMIT;

-- FIM DO SCRIPT
