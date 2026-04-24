<?php

declare(strict_types=1);

/**
 * Itens de menu por perfil (espelha a matriz usada em index.php para permissão de páginas).
 * Chaves = page=… do frontend.
 */
function cmms_nav_allowed_map(): array
{
    return [
        'ADMIN' => [
            'dashboard-admin', 'dashboard-tecnico', 'dashboard-lubrificador', 'dashboard-diretoria',
            'dashboard-lider', 'dashboard-usuario', 'ordens-servico', 'consolidacao-os', 'ativos',
            'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios',
            'backup', 'agendador',
        ],
        'TECNICO' => [
            'dashboard', 'dashboard-tecnico', 'ordens-servico', 'ativos', 'setores', 'categorias-ativos', 'preventivas',
            'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios',
        ],
        'LUBRIFICADOR' => [
            'dashboard', 'dashboard-lubrificador', 'ordens-servico', 'lubricacao-tarefas', 'emulsao',
        ],
        'DIRETORIA' => [
            'dashboard', 'dashboard-diretoria', 'ordens-servico', 'consolidacao-os', 'ativos', 'setores', 'categorias-ativos', 'preventivas',
            'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios',
        ],
        'LIDER' => [
            'dashboard', 'dashboard-lider', 'ordens-servico', 'emulsao',
        ],
        'USUARIO' => [
            'dashboard', 'dashboard-usuario', 'ordens-servico',
        ],
    ];
}

function cmms_nav_normalize_perfil_cookie(?string $raw): ?string
{
    if ($raw === null || $raw === '') {
        return null;
    }
    $p = strtoupper(trim($raw));
    $map = cmms_nav_allowed_map();
    return array_key_exists($p, $map) ? $p : null;
}

function cmms_nav_permitidos(?string $perfil): ?array
{
    if ($perfil === null) {
        return null;
    }
    $map = cmms_nav_allowed_map();
    return $map[$perfil] ?? null;
}

/**
 * Itens de menu por perfil.
 * Separado das permissões de página para evitar montar links que o perfil não deve visualizar no menu.
 */
function cmms_nav_menu_allowed_map(): array
{
    return [
        'ADMIN' => [
            'dashboard-admin', 'dashboard-tecnico', 'dashboard-lubrificador', 'dashboard-diretoria',
            'dashboard-lider', 'dashboard-usuario', 'ordens-servico', 'consolidacao-os', 'ativos',
            'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios',
            'backup', 'agendador',
        ],
        'TECNICO' => [
            'dashboard', 'dashboard-tecnico', 'ordens-servico', 'ativos', 'setores', 'categorias-ativos', 'preventivas',
            'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios',
        ],
        'LUBRIFICADOR' => [
            'dashboard', 'dashboard-lubrificador', 'lubricacao-tarefas', 'emulsao',
        ],
        'DIRETORIA' => [
            'dashboard', 'dashboard-diretoria', 'ordens-servico', 'consolidacao-os', 'ativos', 'setores', 'categorias-ativos', 'preventivas',
            'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios',
        ],
        'LIDER' => [
            'dashboard', 'dashboard-lider', 'ordens-servico',
        ],
        'USUARIO' => [
            'dashboard', 'dashboard-usuario', 'ordens-servico',
        ],
    ];
}

function cmms_nav_menu_permitidos(?string $perfil): ?array
{
    if ($perfil === null) {
        return null;
    }
    $map = cmms_nav_menu_allowed_map();
    return $map[$perfil] ?? null;
}

/**
 * Primeiro slug dashboard-* permitido ao perfil (igual ao destino usado no JS após login).
 */
function cmms_nav_default_dashboard_page(string $perfil): string
{
    $permitidos = cmms_nav_permitidos($perfil);
    if ($permitidos !== null) {
        foreach ($permitidos as $p) {
            if (str_starts_with($p, 'dashboard-')) {
                return $p;
            }
        }
    }

    return 'dashboard-usuario';
}

function cmms_h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Monta o <nav> lateral apenas com itens permitidos (sem depender de JS para ocultar).
 *
 * @param  array<string>|null $permitidosPaginas null = cookie ausente/inválido (primeira carga ou sessão antiga)
 * @param  array<string>|null $permitidosMenu    itens de menu liberados para exibição por perfil
 */
function cmms_render_sidebar_nav(string $page, ?array $permitidosPaginas, ?array $permitidosMenu = null, string $relAtual = ''): void
{
    if ($permitidosPaginas === null || $permitidosMenu === null) {
        echo '<nav class="sidebar-nav-scroll flex-grow-1 min-h-0" aria-label="Menu principal" data-cmms-nav-incomplete="1">';
        echo '<div class="small text-white-50 px-2 py-2">Carregando menu…</div>';
        echo '</nav>';

        return;
    }

    $menuDashboard = [
        'dashboard-admin' => 'Admin',
        'dashboard-tecnico' => 'Técnico',
        'dashboard-lubrificador' => 'Lubrificação',
        'dashboard-diretoria' => 'Diretoria',
        'dashboard-lider' => 'LIDER',
        'dashboard-usuario' => 'Usuário',
    ];
    $menu = [
        'ordens-servico' => 'Ordens de Serviço',
        'consolidacao-os' => 'Consolidação',
        'ativos' => 'Ativos',
        'setores' => 'Setores',
        'categorias-ativos' => 'Categorias',
        'preventivas' => 'Preventivas',
        'checklists' => 'Checklists',
        'lubricacao' => 'Óleos',
        'lubricacao-tarefas' => 'Tarefas',
        'emulsao' => 'Óleo solúvel',
        'pecas' => 'Itens',
        'relatorios' => 'Relatórios',
        'usuarios' => 'Usuários',
        'backup' => 'Backup',
        'agendador' => 'Agendador',
    ];

    $isAdmin = in_array('dashboard-admin', $permitidosMenu, true);
    $dashParentActive = str_starts_with($page, 'dashboard-') ? 'active' : '';
    $dashSimpleActive = ($page === 'dashboard' || str_starts_with($page, 'dashboard-')) ? 'active' : '';

    echo '<nav class="sidebar-nav-scroll flex-grow-1 min-h-0" aria-label="Menu principal">';

    if ($isAdmin) {
        echo '<a class="' . cmms_h($dashParentActive) . ' sidebar-parent-toggle" href="#" data-cmms-nav-parent="dashboard" aria-expanded="false">';
        echo '<span>Dashboard</span>';
        echo '<i class="fa-solid fa-chevron-down caret"></i>';
        echo '</a>';
        echo '<div class="sidebar-submenu d-none" data-cmms-nav-group="dashboard">';
        foreach ($menuDashboard as $key => $label) {
            if (!in_array($key, $permitidosMenu, true)) {
                continue;
            }
            $active = ($page === $key) ? 'active' : '';
            echo '<a class="' . cmms_h($active) . '" href="?page=' . cmms_h($key) . '">' . cmms_h($label) . '</a>';
        }
        echo '</div>';
    } elseif (in_array('dashboard-tecnico', $permitidosMenu, true)) {
        $a = ($page === 'dashboard-tecnico' || $page === 'dashboard') ? 'active' : '';
        echo '<a class="' . cmms_h($a) . '" href="?page=dashboard-tecnico">Dashboard Técnico</a>';
    } elseif (in_array('dashboard-lubrificador', $permitidosMenu, true)) {
        $a = ($page === 'dashboard-lubrificador' || $page === 'dashboard') ? 'active' : '';
        echo '<a class="' . cmms_h($a) . '" href="?page=dashboard-lubrificador">Dashboard Lubrificação</a>';
    } elseif (in_array('dashboard-diretoria', $permitidosMenu, true)) {
        $a = ($page === 'dashboard-diretoria' || $page === 'dashboard') ? 'active' : '';
        echo '<a class="' . cmms_h($a) . '" href="?page=dashboard-diretoria">Dashboard Diretoria</a>';
    } elseif (in_array('dashboard-lider', $permitidosMenu, true)) {
        $a = ($page === 'dashboard-lider' || $page === 'dashboard') ? 'active' : '';
        echo '<a class="' . cmms_h($a) . '" href="?page=dashboard-lider">Dashboard LIDER</a>';
    } elseif (in_array('dashboard-usuario', $permitidosMenu, true)) {
        $a = ($page === 'dashboard-usuario' || $page === 'dashboard') ? 'active' : '';
        echo '<a class="' . cmms_h($a) . '" href="?page=dashboard-usuario">Dashboard Usuário</a>';
    } elseif (in_array('dashboard', $permitidosMenu, true)) {
        echo '<a class="' . cmms_h($dashSimpleActive) . '" href="?page=dashboard">Dashboard</a>';
    }

    $mostrarSubRelatorios = in_array('relatorios', $permitidosMenu, true);
    $mostrarRelUsuarios = in_array('usuarios', $permitidosMenu, true);

    $ordemServicoSubPages = ['ordens-servico', 'preventivas', 'consolidacao-os'];
    $mostrarOrdemServicoNav = false;
    foreach ($ordemServicoSubPages as $op) {
        if (in_array($op, $permitidosMenu, true)) {
            $mostrarOrdemServicoNav = true;
            break;
        }
    }
    $ordemServicoParentActive = in_array($page, $ordemServicoSubPages, true) ? 'active' : '';

    $cadastrosPages = ['ativos', 'setores', 'categorias-ativos', 'checklists', 'lubricacao', 'pecas', 'usuarios'];
    $mostrarCadastros = false;
    foreach ($cadastrosPages as $cp) {
        if (in_array($cp, $permitidosMenu, true)) {
            $mostrarCadastros = true;
            break;
        }
    }
    $cadastrosParentActive = in_array($page, $cadastrosPages, true) ? 'active' : '';

    $lubricacaoSubPages = ['lubricacao-tarefas', 'emulsao'];
    $mostrarLubricacaoNav = false;
    foreach ($lubricacaoSubPages as $lp) {
        if (in_array($lp, $permitidosMenu, true)) {
            $mostrarLubricacaoNav = true;
            break;
        }
    }
    $lubricacaoParentActive = in_array($page, $lubricacaoSubPages, true) ? 'active' : '';

    $manutencaoSubPages = ['backup', 'agendador'];
    $mostrarManutencaoNav = false;
    foreach ($manutencaoSubPages as $mp) {
        if (in_array($mp, $permitidosMenu, true)) {
            $mostrarManutencaoNav = true;
            break;
        }
    }
    $manutencaoParentActive = in_array($page, $manutencaoSubPages, true) ? 'active' : '';

    // Ordem fixa: Ordem de Serviço → Lubrificação → Cadastros → Manutenção → Relatórios
    if ($mostrarOrdemServicoNav) {
        echo '<a class="' . cmms_h($ordemServicoParentActive) . ' sidebar-parent-toggle" href="#" data-cmms-nav-parent="ordem-servico-grupo" aria-expanded="false">';
        echo '<span>Ordem de Serviço</span>';
        echo '<i class="fa-solid fa-chevron-down caret"></i>';
        echo '</a>';
        echo '<div class="sidebar-submenu d-none" data-cmms-nav-group="ordem-servico-grupo">';
        foreach ($ordemServicoSubPages as $ok) {
            if (!in_array($ok, $permitidosMenu, true)) {
                continue;
            }
            $olabel = $menu[$ok];
            $oactive = ($page === $ok) ? 'active' : '';
            echo '<a class="' . cmms_h($oactive) . '" href="?page=' . cmms_h($ok) . '">' . cmms_h($olabel) . '</a>';
        }
        echo '</div>';
    }

    if ($mostrarLubricacaoNav) {
        echo '<a class="' . cmms_h($lubricacaoParentActive) . ' sidebar-parent-toggle" href="#" data-cmms-nav-parent="lubricacao-grupo" aria-expanded="false">';
        echo '<span>Lubrificação</span>';
        echo '<i class="fa-solid fa-chevron-down caret"></i>';
        echo '</a>';
        echo '<div class="sidebar-submenu d-none" data-cmms-nav-group="lubricacao-grupo">';
        foreach ($lubricacaoSubPages as $lk) {
            if (!in_array($lk, $permitidosMenu, true)) {
                continue;
            }
            $llabel = $menu[$lk];
            $lactive = ($page === $lk) ? 'active' : '';
            echo '<a class="' . cmms_h($lactive) . '" href="?page=' . cmms_h($lk) . '">' . cmms_h($llabel) . '</a>';
        }
        echo '</div>';
    }

    if ($mostrarCadastros) {
        echo '<a class="' . cmms_h($cadastrosParentActive) . ' sidebar-parent-toggle" href="#" data-cmms-nav-parent="cadastros" aria-expanded="false">';
        echo '<span>Cadastros</span>';
        echo '<i class="fa-solid fa-chevron-down caret"></i>';
        echo '</a>';
        echo '<div class="sidebar-submenu d-none" data-cmms-nav-group="cadastros">';
        foreach ($cadastrosPages as $ck) {
            if (!in_array($ck, $permitidosMenu, true)) {
                continue;
            }
            $clabel = $menu[$ck];
            $cactive = ($page === $ck) ? 'active' : '';
            echo '<a class="' . cmms_h($cactive) . '" href="?page=' . cmms_h($ck) . '">' . cmms_h($clabel) . '</a>';
        }
        echo '</div>';
    }

    if ($mostrarManutencaoNav) {
        echo '<a class="' . cmms_h($manutencaoParentActive) . ' sidebar-parent-toggle" href="#" data-cmms-nav-parent="manutencao-grupo" aria-expanded="false">';
        echo '<span>Manutenção</span>';
        echo '<i class="fa-solid fa-chevron-down caret"></i>';
        echo '</a>';
        echo '<div class="sidebar-submenu d-none" data-cmms-nav-group="manutencao-grupo">';
        foreach ($manutencaoSubPages as $mk) {
            if (!in_array($mk, $permitidosMenu, true)) {
                continue;
            }
            $mlabel = $menu[$mk];
            $mactive = ($page === $mk) ? 'active' : '';
            echo '<a class="' . cmms_h($mactive) . '" href="?page=' . cmms_h($mk) . '">' . cmms_h($mlabel) . '</a>';
        }
        echo '</div>';
    }

    if ($mostrarSubRelatorios) {
        $relParentActive = $page === 'relatorios' ? 'active' : '';
        echo '<a class="' . cmms_h($relParentActive) . ' sidebar-parent-toggle" href="#" data-cmms-nav-parent="relatorios" aria-expanded="false">';
        echo '<span>Relatórios</span>';
        echo '<i class="fa-solid fa-chevron-down caret"></i>';
        echo '</a>';
        echo '<div class="sidebar-submenu d-none" data-cmms-nav-group="relatorios">';

        $itensRel = [
            'cad_setores' => 'Lista de setores',
            'cad_ativos' => 'Lista de ativos',
        ];
        if ($mostrarRelUsuarios) {
            $itensRel['cad_usuarios'] = 'Lista de usuários';
        }
        $itensRel['os_geral'] = 'OS';
        $itensRel['custo_ativo'] = 'Custo por ativo';
        $itensRel['custo_setor'] = 'Custo por setor';
        $itensRel['kpis_ativo'] = 'KPIs ativo';
        $itensRel['kpis_setor'] = 'KPIs setor';

        foreach ($itensRel as $rk => $rlabel) {
            $ra = ($page === 'relatorios' && $relAtual === $rk) ? 'active' : '';
            echo '<a class="' . cmms_h($ra) . '" href="?page=relatorios&amp;rel=' . cmms_h($rk) . '">' . cmms_h($rlabel) . '</a>';
        }
        echo '</div>';
    }

    echo '</nav>';
}
