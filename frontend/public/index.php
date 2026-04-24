<?php
require __DIR__ . '/../config/api_base.php';
require __DIR__ . '/../config/cmms_nav.php';
require __DIR__ . '/vendor_assets.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$branding = require __DIR__ . '/../config/branding.php';
$c = $branding['colors'];
$publicDir = __DIR__;
$logoUri = null;
if (!empty($branding['logo_file'])) {
    $lf = basename($branding['logo_file']);
    if (is_file($publicDir . '/assets/branding/' . $lf)) {
        $logoUri = cmms_public_uri('assets/branding/' . rawurlencode($lf));
    }
}
$faviconUri = null;
if (is_file($publicDir . '/img/favicon.ico')) {
    $faviconUri = cmms_public_uri('img/favicon.ico');
} elseif (!empty($branding['favicon_file'])) {
    $ff = basename($branding['favicon_file']);
    if (is_file($publicDir . '/assets/branding/' . $ff)) {
        $faviconUri = cmms_public_uri('assets/branding/' . rawurlencode($ff));
    }
}
$page = $_GET['page'] ?? 'login';
if ($page === 'maquinas') {
    $page = 'ativos';
}
$allowed = [
    'login',
    'dashboard',
    'dashboard-admin',
    'dashboard-tecnico',
    'dashboard-lubrificador',
    'dashboard-diretoria',
    'dashboard-lider',
    'dashboard-usuario',
    'ordens-servico',
    'consolidacao-os',
    'ativos',
    'setores',
    'categorias-ativos',
    'preventivas',
    'checklists',
    'lubricacao',
    'lubricacao-tarefas',
    'emulsao',
    'pecas',
    'relatorios',
    'usuarios',
    'backup',
    'agendador',
];
if (!in_array($page, $allowed, true)) {
    $page = 'login';
}

if ($page === 'relatorios' && (!isset($_GET['rel']) || (string) $_GET['rel'] === '')) {
    header('Location: /?page=relatorios&rel=cad_setores', true, 302);
    exit;
}

if ($page === 'login' && isset($_GET['logout']) && (string) $_GET['logout'] === '1') {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    setcookie('cmms_perfil', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
    header('Location: /?page=login', true, 302);
    exit;
}

$isLogin = $page === 'login';
$viewPage = $page;
if (str_starts_with($page, 'dashboard-')) {
    $viewPage = 'dashboard';
}
$viewPath = __DIR__ . '/../views/' . $viewPage . '.php';

$documentTitle = $isLogin
    ? ($branding['title_login'] . ' — ' . $branding['app_name'])
    : (!empty($branding['title_app_suffix'])
        ? ($branding['title_app_suffix'] . ' — ' . $branding['app_name'])
        : $branding['app_name']);

$cmmsPerfilNavCookie = isset($_COOKIE['cmms_perfil']) ? (string) $_COOKIE['cmms_perfil'] : '';
$cmmsNavPerfil = cmms_nav_normalize_perfil_cookie($cmmsPerfilNavCookie);
$cmmsNavPermitidosPaginas = cmms_nav_permitidos($cmmsNavPerfil);
$cmmsNavPermitidosMenu = cmms_nav_menu_permitidos($cmmsNavPerfil);

if (!$isLogin && $page === 'dashboard' && $cmmsNavPerfil !== null) {
    $dashDest = cmms_nav_default_dashboard_page($cmmsNavPerfil);
    header('Location: /?page=' . rawurlencode($dashDest), true, 302);
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($documentTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($faviconUri): ?>
    <link rel="icon" href="<?= htmlspecialchars($faviconUri, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
    <link href="<?= htmlspecialchars(cmms_vendor_uri('bootstrap/5.3.3/css/bootstrap.min.css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
    <link href="<?= htmlspecialchars(cmms_vendor_uri('font-awesome/6.5.2/css/all.min.css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
<?php if (!$isLogin): ?>
    <link href="<?= htmlspecialchars(cmms_vendor_uri('datatables/1.13.8/css/dataTables.bootstrap5.min.css', 'https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
<?php endif; ?>
    <meta name="theme-color" content="<?= htmlspecialchars($c['nav_primary'], ENT_QUOTES, 'UTF-8') ?>">
    <style>
        :root {
            --cmms-body-bg: <?= htmlspecialchars($c['body_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-nav-primary: <?= htmlspecialchars($c['nav_primary'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-login-g1: <?= htmlspecialchars($c['login_gradient_top'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-login-g2: <?= htmlspecialchars($c['login_gradient_mid'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-login-g3: <?= htmlspecialchars($c['login_gradient_bot'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-sidebar-bg: <?= htmlspecialchars($c['sidebar_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-sidebar-link: <?= htmlspecialchars($c['sidebar_link'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-sidebar-hover: <?= htmlspecialchars($c['sidebar_hover_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-sidebar-active: <?= htmlspecialchars($c['sidebar_active_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-sidebar-text: <?= htmlspecialchars($c['sidebar_text'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-login-icon-1: <?= htmlspecialchars($c['login_icon_grad_start'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-login-icon-2: <?= htmlspecialchars($c['login_icon_grad_end'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-primary-btn: <?= htmlspecialchars($c['primary_button'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-primary-btn-hover: <?= htmlspecialchars($c['primary_button_hover'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-card-bg: <?= htmlspecialchars($c['card_bg'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-card-border: <?= htmlspecialchars($c['card_border'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-success: <?= htmlspecialchars($c['status_success'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-danger: <?= htmlspecialchars($c['status_danger'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-warning: <?= htmlspecialchars($c['status_warning'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-orange: <?= htmlspecialchars($c['status_orange'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-info: <?= htmlspecialchars($c['status_info'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-cyan: <?= htmlspecialchars($c['accent_cyan'], ENT_QUOTES, 'UTF-8') ?>;
            --cmms-purple: <?= htmlspecialchars($c['accent_purple'], ENT_QUOTES, 'UTF-8') ?>;
            --bs-primary: <?= htmlspecialchars($c['nav_primary'], ENT_QUOTES, 'UTF-8') ?>;
            --bs-primary-rgb: 44, 62, 102;
        }
        body { background: var(--cmms-body-bg); }
<?php if ($isLogin): ?>
        body.login-body {
            background: linear-gradient(160deg, var(--cmms-login-g1) 0%, var(--cmms-login-g2) 45%, var(--cmms-login-g3) 100%);
        }
        .cmms-btn-login {
            background: var(--cmms-primary-btn) !important;
            border-color: var(--cmms-primary-btn) !important;
            color: #fff !important;
        }
        .cmms-btn-login:hover {
            background: var(--cmms-primary-btn-hover) !important;
            border-color: var(--cmms-primary-btn-hover) !important;
            color: #fff !important;
        }
<?php else: ?>
        main.col, main[class*="col-"] { background: var(--cmms-body-bg); }
        .cmms-page .cmms-page-title,
        .cmms-page h4 {
            color: var(--cmms-nav-primary);
            font-weight: 600;
        }
        .cmms-panel {
            border: 1px solid var(--cmms-card-border);
            border-radius: 10px;
            background: var(--cmms-card-bg);
        }
        .cmms-panel-accent { border-left: 4px solid var(--cmms-info); }
        .cmms-panel .card-body { background: transparent; }
        .cmms-tabs .nav-link {
            color: var(--cmms-nav-primary);
            border-color: var(--cmms-card-border);
        }
        .cmms-tabs .nav-link:hover {
            border-color: rgba(44, 62, 102, 0.35);
        }
        .cmms-tabs .nav-link.active {
            background: var(--cmms-nav-primary);
            color: #fff !important;
            border-color: var(--cmms-nav-primary);
        }
        .cmms-tab-content {
            border: 1px solid var(--cmms-card-border) !important;
            border-top: 0 !important;
            border-radius: 0 0 10px 10px;
            background: var(--cmms-card-bg);
        }
        .cmms-os-card .os-codigo {
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .cmms-os-card .os-meta { font-size: 0.82rem; }
        .cmms-os-card .os-falha {
            font-size: 0.8rem;
            max-width: 75%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 575.98px) {
            .cmms-os-card .os-falha {
                max-width: 100%;
            }
        }
        .cmms-os-card .btn-open-os {
            font-size: 0.74rem;
            padding: 0.2rem 0.5rem;
            line-height: 1.1;
            background-color: var(--cmms-info);
            border-color: var(--cmms-info);
            color: #fff !important;
        }
        .cmms-os-card .btn-open-os:hover {
            filter: brightness(0.92);
            color: #fff !important;
        }
        /* Dashboard LIDER: pílulas aferição + perfil */
        .cmms-lider-pill {
            max-width: 100%;
            align-self: center;
        }
        .cmms-lider-pill-afer {
            flex: 0 1 auto;
            width: max-content;
            min-width: 0;
            line-height: 1.35;
        }
        .cmms-lider-pill-afer--ok {
            background-color: #d1e7dd;
            border-color: #badbcc !important;
            color: #0f5132;
        }
        .cmms-lider-pill-afer--warn {
            background-color: #fff3cd;
            border-color: #ffecb5 !important;
            color: #664d03;
        }
        .cmms-lider-pill-afer--neutral {
            background-color: var(--bs-light, #f8f9fa);
            color: inherit;
        }
        .cmms-lider-pill-title {
            font-weight: 600;
            color: inherit;
            opacity: 0.85;
            margin-bottom: 0.25rem;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .cmms-lider-pill-afer--neutral .cmms-lider-pill-title {
            color: var(--bs-secondary-color, #6c757d);
        }
        .cmms-lider-pill-perfil {
            white-space: nowrap;
            flex: 0 1 auto;
        }
        /* Celular estreito: uma coluna; a partir de sm, 2; desktop largo: 4 */
        .cmms-cards-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.5rem;
            align-content: start;
        }
        @media (min-width: 576px) {
            .cmms-cards-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media (min-width: 992px) {
            .cmms-cards-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
        .sidebar { min-height: 100vh; background: var(--cmms-sidebar-bg); color: var(--cmms-sidebar-text); }
        .sidebar-nav-scroll {
            flex: 1 1 auto;
            min-height: 0;
            font-size: calc(1rem - 1pt);
        }
        .sidebar a { color: var(--cmms-sidebar-link); text-decoration: none; display: block; padding: 10px 12px; border-radius: 8px; transition: background 0.15s ease; }
        .sidebar a:hover { background: var(--cmms-sidebar-hover); color: var(--cmms-sidebar-text); }
        .sidebar a.active { background: var(--cmms-sidebar-active); color: var(--cmms-sidebar-text); }
        .sidebar-submenu {
            margin: 0.25rem 0 0.5rem 0.75rem;
            padding-left: 0.5rem;
            border-left: 1px solid rgba(255, 255, 255, 0.18);
        }
        .sidebar-submenu.d-none {
            display: none !important;
        }
        .sidebar-submenu a {
            font-size: 0.9em;
            padding-top: 0.42rem;
            padding-bottom: 0.42rem;
        }
        .sidebar-parent-toggle {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
        }
        .sidebar-parent-toggle .caret {
            font-size: 0.72em;
            opacity: 0.9;
            transition: transform 0.15s ease;
        }
        .sidebar-parent-toggle[aria-expanded="true"] .caret {
            transform: rotate(180deg);
        }
        .sidebar .sidebar-brand { color: var(--cmms-sidebar-text); }
        .sidebar hr { border-color: rgba(255, 255, 255, 0.18); }
        .sidebar-footer { min-width: 0; }
        .sidebar-user-card {
            background: rgba(255, 255, 255, 0.09);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 10px;
        }
        .sidebar-user-card .sidebar-user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--cmms-sidebar-text);
            line-height: 1.25;
            word-break: break-word;
        }
        .sidebar-user-card .sidebar-user-perfil {
            font-size: 0.72rem;
            color: rgba(255, 255, 255, 0.72);
            line-height: 1.2;
            margin-top: 0.2rem;
        }
        .sidebar-user-card-inner {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.35rem;
        }
        .sidebar-user-text {
            min-width: 0;
            flex: 1 1 auto;
        }
        .sidebar-user-actions {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.28rem;
            flex-shrink: 0;
            margin-top: 0.05rem;
        }
        .sidebar-btn-trocar-senha {
            width: 1.32rem;
            height: 1.32rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.12);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            padding: 0;
            line-height: 1;
        }
        .sidebar-btn-trocar-senha:hover,
        .sidebar-btn-trocar-senha:focus {
            background: rgba(255, 255, 255, 0.24);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.55);
        }
        .sidebar-btn-sair {
            background-color: var(--cmms-danger) !important;
            border-color: var(--cmms-danger) !important;
            color: #fff !important;
            font-weight: 600;
            border-radius: 8px;
        }
        .sidebar-btn-sair:hover,
        .sidebar-btn-sair:focus {
            filter: brightness(0.94);
            color: #fff !important;
        }
        .sidebar-btn-refresh {
            width: 1.32rem;
            height: 1.32rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.16);
            color: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.68rem;
            padding: 0;
            line-height: 1;
        }
        .sidebar-btn-refresh:hover,
        .sidebar-btn-refresh:focus {
            background: rgba(255, 255, 255, 0.28);
            color: #fff;
            border-color: rgba(255, 255, 255, 0.55);
        }
        @media (min-width: 768px) {
            .sidebar {
                position: sticky;
                top: 0;
                align-self: flex-start;
                min-height: 100vh;
                min-height: 100dvh;
                max-height: 100vh;
                max-height: 100dvh;
                overflow: hidden;
            }
            .sidebar-nav-scroll {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
        }
        .sidebar-backdrop { display: none; }
        .cmms-table-pill td::before { display: none; }
        .cmms-pill-toolbar {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: nowrap;
            margin-bottom: 0.65rem;
        }
        .cmms-pill-toolbar .form-select {
            min-width: 11rem;
        }
        .table-responsive .cmms-table-pill {
            border-collapse: separate;
            border-spacing: 0 0.55rem;
        }
        .table-responsive .cmms-table-pill thead {
            display: none;
        }
        .table-responsive .cmms-table-pill tbody,
        .table-responsive .cmms-table-pill tr,
        .table-responsive .cmms-table-pill td {
            display: block;
            width: 100% !important;
        }
        .table-responsive .cmms-table-pill tr {
            background: #fff;
            border: 1px solid var(--cmms-card-border);
            border-radius: 0.6rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.06);
            padding: 0.45rem 0.55rem;
        }
        .table-responsive .cmms-table-pill td {
            border: 0 !important;
            padding: 0.22rem 0 !important;
            white-space: normal !important;
        }
        .table-responsive .cmms-table-pill td::before {
            content: attr(data-label);
            display: inline-block;
            min-width: 7rem;
            margin-right: 0.4rem;
            color: #6c757d;
            font-size: 0.76rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .table-responsive .cmms-table-pill td.text-end,
        .table-responsive .cmms-table-pill td.text-nowrap {
            text-align: left !important;
        }
        @media (max-width: 767.98px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(86vw, 320px);
                min-height: 100dvh;
                max-height: 100dvh;
                z-index: 1045;
                transform: translateX(-100%);
                transition: transform 0.2s ease;
                overflow: hidden;
                overflow-x: hidden;
            }
            .sidebar-nav-scroll {
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            .sidebar.sidebar-open {
                transform: translateX(0);
            }
            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.45);
                z-index: 1040;
            }
            .sidebar-backdrop.sidebar-backdrop-open {
                display: block;
            }
            .cmms-pill-toolbar {
                width: 100%;
                flex-wrap: wrap;
            }
            .cmms-pill-toolbar .form-select {
                min-width: 0;
                flex: 1 1 10rem;
            }
        }
        main h4, main .h4 { color: var(--cmms-nav-primary); font-weight: 600; }
        .card.shadow-sm, .card-kpi {
            background: var(--cmms-card-bg);
            border: 1px solid var(--cmms-card-border);
            border-radius: 10px;
        }
        .card-kpi { box-shadow: 0 0.125rem 0.4rem rgba(44, 62, 102, 0.07) !important; }
        .card-kpi.card-kpi-accent { border-left-width: 4px; border-left-style: solid; padding-left: 0.25rem; }
        .card-kpi-accent-success { border-left-color: var(--cmms-success) !important; }
        .card-kpi-accent-danger { border-left-color: var(--cmms-danger) !important; }
        .card-kpi-accent-warning { border-left-color: var(--cmms-warning) !important; }
        .card-kpi-accent-orange { border-left-color: var(--cmms-orange) !important; }
        .card-kpi-accent-purple { border-left-color: var(--cmms-purple) !important; }
        .card-kpi-accent-cyan { border-left-color: var(--cmms-cyan) !important; }
        .card-kpi-accent-info { border-left-color: var(--cmms-info) !important; }
        main .table,
        main .dataTables_wrapper .table {
            --bs-table-bg: transparent;
        }
        main .table > thead > tr > th,
        main .table thead th,
        main .dataTables_wrapper table.dataTable > thead > tr > th {
            background-color: var(--cmms-nav-primary) !important;
            color: #fff !important;
            font-weight: 600;
            font-size: 0.8rem;
            border-color: rgba(255, 255, 255, 0.12) !important;
            vertical-align: middle;
            padding-top: 0.55rem;
            padding-bottom: 0.55rem;
        }
        main .table > thead.table-light > tr > th,
        main .table thead.table-light th {
            --bs-table-bg: var(--cmms-nav-primary);
            color: #fff !important;
        }
        main .table.table-striped > tbody > tr:nth-of-type(odd) > * {
            --bs-table-bg-type: rgba(44, 62, 102, 0.03);
        }
        main .btn-primary {
            --bs-btn-bg: var(--cmms-nav-primary);
            --bs-btn-border-color: var(--cmms-nav-primary);
            --bs-btn-hover-bg: #243552;
            --bs-btn-hover-border-color: #243552;
            --bs-btn-active-bg: #243552;
            --bs-btn-active-border-color: #243552;
        }
        main .btn-outline-primary {
            --bs-btn-color: var(--cmms-nav-primary);
            --bs-btn-border-color: var(--cmms-nav-primary);
            --bs-btn-hover-bg: var(--cmms-nav-primary);
            --bs-btn-hover-border-color: var(--cmms-nav-primary);
        }
        #previewImgSrc { max-width: 100%; max-height: 70vh; object-fit: contain; }
<?php endif; ?>
    </style>
</head>
<body class="<?= $isLogin ? 'login-body' : '' ?>">
<?php if ($isLogin): ?>
    <?php if (file_exists(__DIR__ . '/../views/login.php')) { include __DIR__ . '/../views/login.php'; } ?>

    <div id="cmmsToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11000;"></div>

    <script src="<?= htmlspecialchars(cmms_vendor_uri('bootstrap/5.3.3/js/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
    <script>
    (function () {
        const TOKEN_KEY = 'cmms_token';
        const API_KEY = 'cmms_api_base';
        const DEFAULT_API_BASE = <?= json_encode($cmmsApiBaseDefault, JSON_UNESCAPED_SLASHES) ?>;

        function storageGet(key) {
            return sessionStorage.getItem(key);
        }
        function storageSet(key, value) {
            sessionStorage.setItem(key, value);
        }

        function clearAllCookiesJs() {
            try {
                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                const suffix = 'path=/; Max-Age=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax' + secure;
                document.cookie = 'cmms_perfil=; ' + suffix;
                const raw = document.cookie;
                if (!raw || !String(raw).trim()) return;
                String(raw).split(';').forEach(function (c) {
                    const eq = c.indexOf('=');
                    const name = (eq > -1 ? c.slice(0, eq) : c).trim();
                    if (!name) return;
                    document.cookie = name + '=; ' + suffix;
                });
            } catch (e) { /* ignore */ }
        }

        async function clearClientState() {
            try { localStorage.clear(); } catch (e) { /* ignore */ }
            try { sessionStorage.clear(); } catch (e) { /* ignore */ }
            clearAllCookiesJs();
            try {
                if (window.caches && caches.keys) {
                    const ks = await caches.keys();
                    await Promise.all(ks.map(function (k) { return caches.delete(k); }));
                }
            } catch (e) { /* ignore */ }
        }

        window.addEventListener('keydown', function (e) {
            if (e && e.ctrlKey && String(e.key).toUpperCase() === 'F5') {
                e.preventDefault();
                clearClientState().finally(function () {
                    window.location.replace('/?page=login&logout=1&v=' + Date.now());
                });
            }
        });

        if (storageGet(TOKEN_KEY)) {
            window.location.replace('/?page=dashboard');
            return;
        }

        function getApiBase() {
            const saved = storageGet(API_KEY);
            const candidate = saved ? saved : DEFAULT_API_BASE;
            return (candidate || '').replace(/\/$/, '');
        }

        function showErr(msg) {
            const el = document.getElementById('loginError');
            el.style.display = msg ? 'block' : 'none';
            el.textContent = msg || '';
        }

        async function loginApi() {
            showErr('');
            const email = document.getElementById('loginEmail').value.trim();
            const senha = document.getElementById('loginSenha').value;
            if (!email || !senha) {
                showErr('Informe login e senha.');
                return;
            }
            const base = getApiBase();
            if (!base) {
                showErr('Configuração da API não disponível.');
                return;
            }
            const res = await fetch(base + '/auth/login', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({email: email, senha: senha})
            });
            if (!res.ok) {
                showErr('Login ou senha inválidos.');
                return;
            }
            const data = await res.json();
            storageSet(TOKEN_KEY, data.access_token);
            storageSet(API_KEY, base);
            var destinoDashboard = 'dashboard-usuario';
            try {
                const meRes = await fetch(base + '/auth/me', {
                    headers: {'Authorization': 'Bearer ' + data.access_token}
                });
                if (meRes.ok) {
                    const me = await meRes.json();
                    if (me && me.perfil_acesso) {
                        document.cookie = 'cmms_perfil=' + encodeURIComponent(me.perfil_acesso) + '; path=/; SameSite=Lax';
                        var allowedByRole = {
                            ADMIN: ['dashboard-admin', 'dashboard-tecnico', 'dashboard-lubrificador', 'dashboard-diretoria', 'dashboard-lider', 'dashboard-usuario', 'ordens-servico', 'consolidacao-os', 'ativos', 'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios', 'backup', 'agendador'],
                            TECNICO: ['dashboard', 'dashboard-tecnico', 'ordens-servico', 'ativos', 'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios'],
                            LUBRIFICADOR: ['dashboard', 'dashboard-lubrificador', 'ordens-servico', 'lubricacao-tarefas', 'emulsao'],
                            DIRETORIA: ['dashboard', 'dashboard-diretoria', 'ordens-servico', 'consolidacao-os', 'ativos', 'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios'],
                            LIDER: ['dashboard', 'dashboard-lider', 'ordens-servico', 'emulsao'],
                            USUARIO: ['dashboard', 'dashboard-usuario', 'ordens-servico']
                        };
                        var permitidos = allowedByRole[me.perfil_acesso] || ['dashboard-usuario'];
                        var slug = permitidos.filter(function (k) { return k.indexOf('dashboard-') === 0; })[0];
                        destinoDashboard = slug || 'dashboard-usuario';
                    }
                }
            } catch (e) { /* ignore */ }
            window.location.href = '/?page=' + encodeURIComponent(destinoDashboard);
        }

        document.getElementById('btnLoginApi').addEventListener('click', function (e) {
            e.preventDefault();
            loginApi().catch(function () { showErr('Não foi possível conectar à API.'); });
        });
        document.getElementById('loginSenha').addEventListener('keydown', function (e) {
            if (e.key === 'Enter') loginApi().catch(function () {});
        });
    })();
    </script>
<?php else: ?>
<script>
window.cmmsPage=<?= json_encode($page, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.cmmsViewPage=<?= json_encode($viewPage, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<div class="container-fluid">
    <div class="row">
        <aside class="col-12 col-md-3 col-lg-2 p-3 sidebar d-flex flex-column" id="sidebarMain">
            <div class="sidebar-brand mb-4 d-flex align-items-center gap-2 flex-wrap">
                <?php if ($logoUri): ?>
                    <img src="<?= htmlspecialchars($logoUri, ENT_QUOTES, 'UTF-8') ?>" alt="" class="flex-shrink-0" style="max-height: 36px; width: auto;">
                <?php else: ?>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                          style="width:40px;height:40px;background:linear-gradient(145deg,var(--cmms-login-icon-1),var(--cmms-login-icon-2));">
                        <i class="fa-solid fa-screwdriver-wrench text-white"></i>
                    </span>
                <?php endif; ?>
                <span class="h5 mb-0 fw-semibold"><?= htmlspecialchars($branding['app_name'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php cmms_render_sidebar_nav($page, $cmmsNavPermitidosPaginas, $cmmsNavPermitidosMenu, (string)($_GET['rel'] ?? '')); ?>
            <div class="sidebar-footer flex-shrink-0 pt-3">
                <div class="sidebar-user-card px-2 py-2 mb-2" id="sidebarUserCard">
                    <div class="sidebar-user-card-inner">
                        <div class="sidebar-user-text">
                            <div class="sidebar-user-name" id="sidebarUserName">Carregando…</div>
                            <div class="sidebar-user-perfil" id="sidebarUserPerfil"></div>
                        </div>
                        <div class="sidebar-user-actions">
                            <button type="button" class="sidebar-btn-refresh" id="sidebarBtnRefresh" title="Atualizar aplicação" aria-label="Atualizar aplicação">
                                <i class="fa-solid fa-rotate-right"></i>
                            </button>
                            <button type="button" class="sidebar-btn-trocar-senha d-none" id="sidebarBtnTrocarSenha" title="Trocar senha" aria-label="Trocar senha">
                                <i class="fa-solid fa-key"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <a class="btn btn-sm sidebar-btn-sair w-100" href="/?page=login" id="linkSair" role="button">
                    <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                </a>
            </div>
        </aside>
        <div id="sidebarBackdrop" class="sidebar-backdrop"></div>
        <main class="col-12 col-md-9 col-lg-10 p-4">
            <button type="button" class="btn btn-outline-primary btn-sm d-md-none mb-3" id="btnOpenSidebar">
                <i class="fa-solid fa-bars me-1"></i> Menu
            </button>
            <input type="hidden" id="apiBase" value="<?= htmlspecialchars($cmmsApiBaseDefault, ENT_QUOTES, 'UTF-8') ?>">
            <?php if (file_exists($viewPath)) { include $viewPath; } ?>
        </main>
    </div>
</div>

<div id="cmmsToastContainer" class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11000;"></div>

<div class="modal fade" id="modalTrocarSenha" tabindex="-1" aria-labelledby="modalTrocarSenhaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTrocarSenhaLabel">Trocar senha</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <form id="formTrocarSenha">
                <div class="modal-body">
                    <p class="small text-muted mb-3">Informe a senha atual e a nova senha (mínimo 6 caracteres).</p>
                    <div class="mb-2">
                        <label class="form-label" for="trocarSenhaAtual">Senha atual</label>
                        <input type="password" id="trocarSenhaAtual" class="form-control form-control-sm" required autocomplete="current-password" maxlength="128">
                    </div>
                    <div class="mb-2">
                        <label class="form-label" for="trocarSenhaNova">Nova senha</label>
                        <input type="password" id="trocarSenhaNova" class="form-control form-control-sm" required autocomplete="new-password" minlength="6" maxlength="128">
                    </div>
                    <div class="mb-0">
                        <label class="form-label" for="trocarSenhaNova2">Confirmar nova senha</label>
                        <input type="password" id="trocarSenhaNova2" class="form-control form-control-sm" required autocomplete="new-password" minlength="6" maxlength="128">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm" id="btnSubmitTrocarSenha">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= htmlspecialchars(cmms_vendor_uri('htmx/1.9.12/htmx.min.js', 'https://cdn.jsdelivr.net/npm/htmx.org@1.9.12/dist/htmx.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(cmms_vendor_uri('jquery/3.7.1/jquery.min.js', 'https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(cmms_vendor_uri('bootstrap/5.3.3/js/bootstrap.bundle.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(cmms_vendor_uri('datatables/1.13.8/js/jquery.dataTables.min.js', 'https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(cmms_vendor_uri('datatables/1.13.8/js/dataTables.bootstrap5.min.js', 'https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars(cmms_vendor_uri('apexcharts/3.54.1/apexcharts.min.js', 'https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
<script>
    (function () {
        const TOKEN_KEY = 'cmms_token';
        const API_KEY = 'cmms_api_base';

        function storageGet(key) {
            return sessionStorage.getItem(key);
        }

        function clearAllCookiesJs() {
            try {
                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                const suffix = 'path=/; Max-Age=0; expires=Thu, 01 Jan 1970 00:00:00 GMT; SameSite=Lax' + secure;
                document.cookie = 'cmms_perfil=; ' + suffix;
                const raw = document.cookie;
                if (!raw || !String(raw).trim()) return;
                String(raw).split(';').forEach(function (c) {
                    const eq = c.indexOf('=');
                    const name = (eq > -1 ? c.slice(0, eq) : c).trim();
                    if (!name) return;
                    document.cookie = name + '=; ' + suffix;
                });
            } catch (e) { /* ignore */ }
        }

        async function clearClientState() {
            try { localStorage.clear(); } catch (e) { /* ignore */ }
            try { sessionStorage.clear(); } catch (e) { /* ignore */ }
            clearAllCookiesJs();
            try {
                if (window.caches && caches.keys) {
                    const ks = await caches.keys();
                    await Promise.all(ks.map(function (k) { return caches.delete(k); }));
                }
            } catch (e) { /* ignore */ }
            try {
                if (navigator.serviceWorker && navigator.serviceWorker.getRegistrations) {
                    const regs = await navigator.serviceWorker.getRegistrations();
                    await Promise.all(regs.map(function (r) { return r.unregister(); }));
                }
            } catch (e) { /* ignore */ }
        }

        function doLogout() {
            clearClientState().finally(function () {
                window.location.href = '/?page=login&logout=1';
            });
        }

        window.addEventListener('keydown', function (e) {
            if (e && e.ctrlKey && String(e.key).toUpperCase() === 'F5') {
                e.preventDefault();
                clearClientState().finally(function () {
                    window.location.replace('/?page=login&v=' + Date.now());
                });
            }
        });

        if (!storageGet(TOKEN_KEY)) {
            window.location.replace('/?page=login');
            return;
        }

        function getApiBase() {
            const input = document.getElementById('apiBase');
            return (input.value || '').replace(/\/$/, '');
        }

        async function apiFetch(path, options = {}) {
            const token = storageGet(TOKEN_KEY);
            const headers = Object.assign({}, options.headers || {});
            if (options.body && !headers['Content-Type']) {
                headers['Content-Type'] = 'application/json';
            }
            if (token) headers['Authorization'] = 'Bearer ' + token;
            const res = await fetch(getApiBase() + path, Object.assign({}, options, {headers}));
            if (!res.ok) {
                if (res.status === 401) {
                    doLogout();
                    throw new Error('Sessão inválida. Faça login novamente.');
                }
                let msg = 'Falha na API: ' + res.status;
                try {
                    const err = await res.json();
                    if (err.detail !== undefined) {
                        msg = typeof err.detail === 'string' ? err.detail : JSON.stringify(err.detail);
                    }
                } catch (e) { /* ignore */ }
                throw new Error(msg);
            }
            if (res.status === 204) return null;
            return res.json();
        }

        const linkSair = document.getElementById('linkSair');
        if (linkSair) {
            linkSair.addEventListener('click', function (e) {
                e.preventDefault();
                doLogout();
            });
        }

        const sidebar = document.getElementById('sidebarMain');
        const sidebarBackdrop = document.getElementById('sidebarBackdrop');
        const btnOpenSidebar = document.getElementById('btnOpenSidebar');

        function openSidebar() {
            if (!sidebar || window.innerWidth >= 768) return;
            sidebar.classList.add('sidebar-open');
            if (sidebarBackdrop) sidebarBackdrop.classList.add('sidebar-backdrop-open');
        }
        function closeSidebar() {
            if (!sidebar) return;
            sidebar.classList.remove('sidebar-open');
            if (sidebarBackdrop) sidebarBackdrop.classList.remove('sidebar-backdrop-open');
        }
        if (btnOpenSidebar) {
            btnOpenSidebar.addEventListener('click', openSidebar);
        }
        if (sidebarBackdrop) {
            sidebarBackdrop.addEventListener('click', closeSidebar);
        }
        if (sidebar) {
            sidebar.querySelectorAll('a').forEach(function (a) {
                a.addEventListener('click', function () {
                    closeSidebar();
                });
            });
        }
        window.addEventListener('resize', function () {
            if (window.innerWidth >= 768) closeSidebar();
        });

        const savedBase = storageGet(API_KEY);
        if (savedBase) document.getElementById('apiBase').value = savedBase;

        if (window.jQuery && jQuery.fn && jQuery.fn.dataTable) {
            jQuery.extend(true, jQuery.fn.dataTable.defaults, {
                language: {search: 'Buscar:'},
                pageLength: 50,
                lengthChange: false
            });
        }

        function initMobilePillTables() {
            const tables = document.querySelectorAll('main .table-responsive table.table:not(.cmms-no-pill)');
            tables.forEach(function (table) {
                table.classList.add('cmms-table-pill');
                const headers = Array.from(table.querySelectorAll('thead th')).map(function (th) {
                    return (th.textContent || '').trim() || 'Campo';
                });
                table.querySelectorAll('tbody tr').forEach(function (tr) {
                    Array.from(tr.children).forEach(function (td, idx) {
                        if (td && td.tagName === 'TD' && headers[idx]) {
                            td.setAttribute('data-label', headers[idx]);
                        }
                    });
                });
            });
        }
        function initPillSortTools() {
            const tables = document.querySelectorAll('main .table-responsive table.table:not(.cmms-no-pill)');
            tables.forEach(function (table) {
                if (table.dataset.pillSortInit === '1') return;
                if (!window.jQuery || !jQuery.fn || !jQuery.fn.dataTable || !jQuery.fn.dataTable.isDataTable(table)) return;
                const dt = jQuery(table).DataTable();
                const headers = Array.from(table.querySelectorAll('thead th')).map(function (th, idx) {
                    return {idx: idx, label: (th.textContent || '').trim() || ('Campo ' + (idx + 1))};
                });
                const toolbar = document.createElement('div');
                toolbar.className = 'cmms-pill-toolbar';
                toolbar.innerHTML =
                    '<span class="small text-muted">Ordenar:</span>' +
                    '<select class="form-select form-select-sm" data-role="field"></select>' +
                    '<select class="form-select form-select-sm" data-role="dir">' +
                    '<option value="asc">Crescente</option>' +
                    '<option value="desc">Decrescente</option>' +
                    '</select>';

                const selField = toolbar.querySelector('[data-role="field"]');
                const selDir = toolbar.querySelector('[data-role="dir"]');
                headers.forEach(function (h) {
                    const o = document.createElement('option');
                    o.value = String(h.idx);
                    o.textContent = h.label;
                    selField.appendChild(o);
                });
                const currentOrder = dt.order();
                if (currentOrder && currentOrder.length) {
                    selField.value = String(currentOrder[0][0]);
                    selDir.value = String(currentOrder[0][1] || 'asc');
                }
                function applyOrder() {
                    const idx = parseInt(selField.value, 10);
                    const dir = selDir.value === 'desc' ? 'desc' : 'asc';
                    dt.order([idx, dir]).draw();
                }
                selField.addEventListener('change', applyOrder);
                selDir.addEventListener('change', applyOrder);

                const wrap = table.closest('.table-responsive');
                if (wrap && wrap.parentNode) {
                    wrap.parentNode.insertBefore(toolbar, wrap);
                    table.dataset.pillSortInit = '1';
                }
            });
        }
        initMobilePillTables();
        initPillSortTools();
        if (window.jQuery) {
            jQuery(document).on('draw.dt', function () {
                initMobilePillTables();
                initPillSortTools();
            });
        }

        async function uploadFile(path, file) {
            const token = storageGet(TOKEN_KEY);
            const fd = new FormData();
            fd.append('file', file);
            const headers = {};
            if (token) headers['Authorization'] = 'Bearer ' + token;
            const res = await fetch(getApiBase() + path, {method: 'POST', headers, body: fd, cache: 'no-store'});
            if (!res.ok) {
                let msg = 'Falha no envio: ' + res.status;
                try {
                    const err = await res.json();
                    if (err.detail !== undefined) {
                        msg = typeof err.detail === 'string' ? err.detail : JSON.stringify(err.detail);
                    }
                } catch (e) { /* ignore */ }
                throw new Error(msg);
            }
            return res.json();
        }

        async function downloadBlob(path, filename) {
            const token = storageGet(TOKEN_KEY);
            const headers = {};
            if (token) headers['Authorization'] = 'Bearer ' + token;
            const res = await fetch(getApiBase() + path, {headers});
            if (!res.ok) {
                let msg = 'Falha no download: ' + res.status;
                try {
                    const err = await res.json();
                    if (err.detail !== undefined) msg = typeof err.detail === 'string' ? err.detail : JSON.stringify(err.detail);
                } catch (e) { /* ignore */ }
                throw new Error(msg);
            }
            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename || 'download';
            a.click();
            URL.revokeObjectURL(url);
        }

        function showToast(message, variant) {
            const c = document.getElementById('cmmsToastContainer');
            if (!c) return;
            const id = 'toast-' + Date.now();
            const v = variant || 'secondary';
            const bg = v === 'success' ? 'text-bg-success'
                : v === 'danger' ? 'text-bg-danger'
                : v === 'warning' ? 'text-bg-warning' : 'text-bg-secondary';
            const wrap = document.createElement('div');
            wrap.id = id;
            wrap.className = 'toast align-items-center ' + bg + ' border-0';
            wrap.setAttribute('role', 'alert');
            wrap.setAttribute('aria-live', v === 'danger' ? 'assertive' : 'polite');
            wrap.innerHTML = '<div class="d-flex"><div class="toast-body"></div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Fechar"></button></div>';
            wrap.querySelector('.toast-body').textContent = message;
            c.appendChild(wrap);
            const isError = v === 'danger';
            const toastOptions = isError
                ? {autohide: false}
                : {autohide: true, delay: 2000};
            const t = new bootstrap.Toast(wrap, toastOptions);
            wrap.addEventListener('hidden.bs.toast', function () { wrap.remove(); });
            t.show();
        }

        async function fetchBlob(path) {
            const token = storageGet(TOKEN_KEY);
            const headers = {};
            if (token) headers['Authorization'] = 'Bearer ' + token;
            const res = await fetch(getApiBase() + path, {headers});
            if (!res.ok) {
                let msg = 'Falha: ' + res.status;
                try {
                    const err = await res.json();
                    if (err.detail !== undefined) {
                        msg = typeof err.detail === 'string' ? err.detail : JSON.stringify(err.detail);
                    }
                } catch (e) { /* ignore */ }
                throw new Error(msg);
            }
            return res.blob();
        }

        function csvDownload(rows, columns, filename) {
            const sep = ';';
            function esc(v) {
                const s = v == null ? '' : String(v);
                if (/[;"\r\n]/.test(s)) return '"' + s.replace(/"/g, '""') + '"';
                return s;
            }
            const lines = [columns.map(function (c) { return c.header; }).join(sep)];
            rows.forEach(function (row) {
                lines.push(columns.map(function (c) { return esc(row[c.key]); }).join(sep));
            });
            const blob = new Blob(['\ufeff' + lines.join('\n')], {type: 'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename || 'export.csv';
            a.click();
            URL.revokeObjectURL(url);
        }

        window.cmmsApi = {apiFetch, uploadFile, downloadBlob, fetchBlob, csvDownload};
        window.cmmsUi = {showToast};

        if (typeof window.cmmsRelatoriosInit === 'function') {
            try {
                window.cmmsRelatoriosInit();
            } catch (e) {
                console.error(e);
            }
        }
        if (typeof window.cmmsBackupInit === 'function') {
            try {
                window.cmmsBackupInit();
            } catch (e) {
                console.error(e);
            }
        }
        if (typeof window.cmmsAgendadorInit === 'function') {
            try {
                window.cmmsAgendadorInit();
            } catch (e) {
                console.error(e);
            }
        }

        function perfilLabelSidebar(p) {
            var m = {
                ADMIN: 'Administrador',
                TECNICO: 'Técnico',
                LUBRIFICADOR: 'Lubrificador',
                DIRETORIA: 'Diretoria',
                LIDER: 'LIDER',
                USUARIO: 'Usuário'
            };
            return m[p] || p || '—';
        }

        function getCookie(name) {
            var parts = ('; ' + document.cookie).split('; ' + name + '=');
            if (parts.length === 2) {
                return decodeURIComponent(parts.pop().split(';').shift() || '');
            }
            return '';
        }

        function enforcePaginaPermissoes(me) {
            if (!me) return;
            var allowedByRole = {
                ADMIN: ['dashboard-admin', 'dashboard-tecnico', 'dashboard-lubrificador', 'dashboard-diretoria', 'dashboard-lider', 'dashboard-usuario', 'ordens-servico', 'consolidacao-os', 'ativos', 'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios', 'backup', 'agendador'],
                TECNICO: ['dashboard', 'dashboard-tecnico', 'ordens-servico', 'ativos', 'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios'],
                LUBRIFICADOR: ['dashboard', 'dashboard-lubrificador', 'ordens-servico', 'lubricacao-tarefas', 'emulsao'],
                DIRETORIA: ['dashboard', 'dashboard-diretoria', 'ordens-servico', 'consolidacao-os', 'ativos', 'setores', 'categorias-ativos', 'preventivas', 'checklists', 'lubricacao', 'lubricacao-tarefas', 'emulsao', 'pecas', 'relatorios', 'usuarios'],
                LIDER: ['dashboard', 'dashboard-lider', 'ordens-servico', 'emulsao'],
                USUARIO: ['dashboard', 'dashboard-usuario', 'ordens-servico']
            };
            var p = typeof window.cmmsPage === 'string' ? window.cmmsPage : 'dashboard';
            var permitidos = allowedByRole[me.perfil_acesso] || ['dashboard', 'ordens-servico'];
            function destinoDashboardPadrao() {
                var slug = permitidos.filter(function (k) { return k.indexOf('dashboard-') === 0; })[0];
                return slug || 'dashboard-usuario';
            }
            if (p === 'dashboard') {
                window.location.replace('/?page=' + encodeURIComponent(destinoDashboardPadrao()));
                return;
            }
            if (permitidos.indexOf(p) < 0) {
                window.location.replace('/?page=' + encodeURIComponent(destinoDashboardPadrao()));
            }
        }

        async function refreshWithoutLogout() {
            try {
                if (window.caches && caches.keys) {
                    const ks = await caches.keys();
                    await Promise.all(ks.map(function (k) { return caches.delete(k); }));
                }
            } catch (e) { /* ignore */ }
            const url = new URL(window.location.href);
            url.searchParams.set('v', String(Date.now()));
            window.location.replace(url.toString());
        }

        (function loadSidebarUser() {
            var nameEl = document.getElementById('sidebarUserName');
            var perfilEl = document.getElementById('sidebarUserPerfil');
            if (!nameEl || !perfilEl) return;
            apiFetch('/auth/me')
                .then(function (me) {
                    var perfil = me.perfil_acesso || '';
                    var cookieBefore = getCookie('cmms_perfil');
                    var navIncomplete = document.querySelector('[data-cmms-nav-incomplete="1"]');
                    if (perfil) {
                        document.cookie = 'cmms_perfil=' + encodeURIComponent(perfil) + '; path=/; SameSite=Lax';
                    }
                    var needsReload = Boolean(navIncomplete || (perfil && cookieBefore !== perfil));
                    if (needsReload) {
                        var did = sessionStorage.getItem('cmms_nav_sync_reload');
                        if (!did) {
                            sessionStorage.setItem('cmms_nav_sync_reload', '1');
                            window.location.reload();
                            return;
                        }
                    }
                    sessionStorage.removeItem('cmms_nav_sync_reload');
                    nameEl.textContent = me.nome_completo || '—';
                    perfilEl.textContent = perfilLabelSidebar(me.perfil_acesso);
                    var keyBtn = document.getElementById('sidebarBtnTrocarSenha');
                    if (keyBtn) {
                        var podeTrocar = me.permite_trocar_senha !== false;
                        keyBtn.classList.toggle('d-none', !podeTrocar);
                    }
                    enforcePaginaPermissoes(me);
                })
                .catch(function () {
                    doLogout();
                });
        })();

        const refreshBtn = document.getElementById('sidebarBtnRefresh');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function () {
                refreshWithoutLogout();
            });
        }

        (function initTrocarSenhaSidebar() {
            var btn = document.getElementById('sidebarBtnTrocarSenha');
            var modalEl = document.getElementById('modalTrocarSenha');
            var form = document.getElementById('formTrocarSenha');
            if (!btn || !modalEl || !form) return;
            var modal = typeof bootstrap !== 'undefined' && bootstrap.Modal
                ? bootstrap.Modal.getOrCreateInstance(modalEl)
                : null;
            btn.addEventListener('click', function () {
                form.reset();
                if (modal) modal.show();
            });
            modalEl.addEventListener('hidden.bs.modal', function () {
                form.reset();
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var a = document.getElementById('trocarSenhaAtual');
                var n = document.getElementById('trocarSenhaNova');
                var n2 = document.getElementById('trocarSenhaNova2');
                if (!a || !n || !n2) return;
                var sa = a.value;
                var sn = n.value;
                var sn2 = n2.value;
                if (sn.length < 6) {
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('A nova senha deve ter pelo menos 6 caracteres.', 'warning');
                    return;
                }
                if (sn !== sn2) {
                    if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('A confirmação não coincide com a nova senha.', 'warning');
                    return;
                }
                var sub = document.getElementById('btnSubmitTrocarSenha');
                if (sub) sub.disabled = true;
                apiFetch('/auth/trocar-senha', {
                    method: 'POST',
                    body: JSON.stringify({ senha_atual: sa, senha_nova: sn })
                })
                    .then(function () {
                        if (modal) modal.hide();
                        form.reset();
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast('Senha alterada com sucesso.', 'success');
                    })
                    .catch(function (err) {
                        if (window.cmmsUi && window.cmmsUi.showToast) window.cmmsUi.showToast(err.message || 'Não foi possível alterar a senha.', 'danger');
                    })
                    .finally(function () {
                        if (sub) sub.disabled = false;
                    });
            });
        })();

        ['dashboard', 'relatorios', 'ordem-servico-grupo', 'cadastros', 'lubricacao-grupo', 'manutencao-grupo'].forEach(function (name) {
            const t = document.querySelector('[data-cmms-nav-parent="' + name + '"]');
            const g = document.querySelector('[data-cmms-nav-group="' + name + '"]');
            if (t && g) {
                t.addEventListener('click', function (e) {
                    e.preventDefault();
                    var expanded = t.getAttribute('aria-expanded') === 'true';
                    t.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                    g.classList.toggle('d-none', expanded);
                });
            }
        });
    })();
</script>
<?php endif; ?>
</body>
</html>
