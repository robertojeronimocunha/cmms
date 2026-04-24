<?php

declare(strict_types=1);

/**
 * Aparência central do CMMS — paleta tipo painel “Gestão” (navy #2c3e66, fundo claro, cards brancos).
 * Logo: frontend/public/assets/branding/
 * Favicon: fonte do projeto em /img/favicon.ico — copiado para frontend/public/img/favicon.ico
 *   (e fallback em assets/branding/ se o link antigo ainda for usado).
 */
return [
    'app_name' => 'CMMS',

    'title_login' => 'Entrar',

    'title_app_suffix' => null,

    'logo_file' => null,

    'favicon_file' => 'favicon.ico',

    'colors' => [
        /** Fundo área principal */
        'body_bg' => '#f4f7f6',

        /** Login */
        'login_gradient_top' => '#dfe6f2',
        'login_gradient_mid' => '#f4f7f6',
        'login_gradient_bot' => '#cfd9e8',

        /**
         * Navy principal (barra lateral, cabeçalhos de tabela, botões primários).
         * Referência: ~#2c3e66
         */
        'nav_primary' => '#2c3e66',
        /** Alias da barra lateral (= nav_primary) */
        'sidebar_bg' => '#2c3e66',
        'sidebar_link' => '#eef2f8',
        'sidebar_hover_bg' => '#3a5080',
        'sidebar_active_bg' => '#4a6494',
        'sidebar_text' => '#ffffff',

        /** Degradê do ícone no login */
        'login_icon_grad_start' => '#2c3e66',
        'login_icon_grad_end' => '#243552',

        /** Botão Entrar */
        'primary_button' => '#2c3e66',
        'primary_button_hover' => '#243552',

        /** Cards */
        'card_bg' => '#ffffff',
        'card_border' => '#e2e8f0',

        /** Status / KPIs (alinhado ao painel de referência) */
        'status_success' => '#28a745',
        'status_danger' => '#dc3545',
        'status_warning' => '#ffc107',
        'status_orange' => '#fd7e14',
        /** Info / abas — azul clássico; ciano para ações tipo “Reimprimir” */
        'status_info' => '#007bff',
        'accent_cyan' => '#17a2b8',
        'accent_purple' => '#6f42c1',
    ],
];
