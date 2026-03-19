<?php
ob_start();
require_once __DIR__ . '/../../conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['action']) && $_GET['action'] === 'check_ngrok') {
    header('Content-Type: text/plain');
    $context = stream_context_create([
        'http' => ['timeout' => 5, 'ignore_errors' => true]
    ]);
    
    $output = [];
    
    // Local SSH
    $output[] = "localhost SSH: ssh iluminatto@localhost";
    $output[] = "localhost SSH (root): ssh root@localhost";
    
    // Try ngrok tunnels
    $ports = [4040, 4041];
    $response = false;
    
    foreach ($ports as $port) {
        $response = @file_get_contents("http://localhost:$port/api/tunnels", false, $context);
        if ($response) break;
    }
    
    if ($response) {
        $data = json_decode($response, true);
        if (isset($data['tunnels'])) {
            foreach ($data['tunnels'] as $tunnel) {
                if ($tunnel['proto'] === 'tcp' && $tunnel['config']['addr'] === 'localhost:22') {
                    $url = $tunnel['public_url'];
                    preg_match('/tcp:\/\/([^:]+):(\d+)/', $url, $match);
                    if ($match) {
                        $output[] = 'Ngrok SSH: ssh iluminatto@' . $match[1] . ' -p ' . $match[2];
                    }
                }
            }
        }
    }
    
    echo implode("\n", $output);
    exit;
}

if (!$pdo_saas instanceof PDO) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Admin SaaS indisponivel</title>
        <style>
            body { font-family: sans-serif; background: #f2f4f7; color: #1f2937; }
            .box { max-width: 640px; margin: 64px auto; background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08); }
            h1 { margin-top: 0; }
        </style>
    </head>
    <body>
        <div class="box">
            <h1>Servico SaaS indisponivel</h1>
            <p>Verifique a conexao com o banco de controle (<code>barbearia_saas</code>) e tente novamente.</p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!isset($_SESSION['saas_admin_auth']) || $_SESSION['saas_admin_auth'] !== true) {
    header('Location: login.php');
    exit;
}

if (!function_exists('admin_h')) {
    function admin_h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('admin_set_flash')) {
    function admin_set_flash($type, $message)
    {
        if (!isset($_SESSION['saas_admin_flash'])) {
            $_SESSION['saas_admin_flash'] = [];
        }

        $_SESSION['saas_admin_flash'][] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}

if (!function_exists('admin_pull_flashes')) {
    function admin_pull_flashes()
    {
        $flashes = isset($_SESSION['saas_admin_flash']) && is_array($_SESSION['saas_admin_flash'])
            ? $_SESSION['saas_admin_flash']
            : [];

        unset($_SESSION['saas_admin_flash']);

        return $flashes;
    }
}

if (!function_exists('admin_csrf_token')) {
    function admin_csrf_token()
    {
        if (empty($_SESSION['saas_admin_csrf'])) {
            $_SESSION['saas_admin_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['saas_admin_csrf'];
    }
}

if (!function_exists('admin_csrf_input')) {
    function admin_csrf_input()
    {
        return '<input type="hidden" name="csrf_token" value="' . admin_h(admin_csrf_token()) . '">';
    }
}

if (!function_exists('admin_csrf_validate')) {
    function admin_csrf_validate()
    {
        $token = isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : '';
        if ($token === '' || empty($_SESSION['saas_admin_csrf']) || !hash_equals($_SESSION['saas_admin_csrf'], $token)) {
            throw new Exception('Token de seguranca invalido. Atualize a pagina e tente novamente.');
        }
    }
}

if (!function_exists('admin_redirect')) {
    function admin_redirect($page, $params = [])
    {
        $query = ['page' => $page];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $query[$key] = $value;
        }

        header('Location: index.php?' . http_build_query($query));
        exit;
    }
}

if (!function_exists('admin_request_action')) {
    function admin_request_action()
    {
        return isset($_POST['action']) ? trim((string) $_POST['action']) : '';
    }
}

if (!function_exists('admin_format_date')) {
    function admin_format_date($value, $withTime = false)
    {
        if (!$value) {
            return '-';
        }

        $timestamp = strtotime((string) $value);
        if (!$timestamp) {
            return '-';
        }

        return $withTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
    }
}

if (!defined('SAAS_ADMIN_APP')) {
    define('SAAS_ADMIN_APP', true);
}

$page = isset($_GET['page']) ? preg_replace('/[^a-z_]/', '', (string) $_GET['page']) : 'dashboard';

if ($page === 'logout') {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$allowedPages = ['dashboard', 'empresas', 'planos', 'assinaturas', 'tuneis', 'admins', 'sistemas'];

$menuCounters = [
    'empresas' => 0,
    'planos' => 0,
    'assinaturas' => 0,
    'tuneis' => 0,
    'admins' => 0,
    'sistemas' => 0,
];

try {
    $menuCounters['empresas'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas")->fetchColumn();
    $menuCounters['planos'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM planos WHERE ativo = 'Sim'")->fetchColumn();
    $menuCounters['assinaturas'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas_assinaturas WHERE status = 'Trial'")->fetchColumn();
    $menuCounters['tuneis'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM empresas_tunnels")->fetchColumn();
    $menuCounters['admins'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM saas_admins WHERE ativo = 'Sim'")->fetchColumn();
    $menuCounters['sistemas'] = (int) $pdo_saas->query("SELECT COUNT(*) FROM sistemas WHERE ativo = 'Sim'")->fetchColumn();
} catch (Exception $e) {
    admin_set_flash('warning', 'Alguns indicadores nao puderam ser carregados.');
}

$pageFile = __DIR__ . '/' . $page . '.php';
if (!file_exists($pageFile)) {
    $page = 'dashboard';
    $pageFile = __DIR__ . '/dashboard.php';
}

$flashes = admin_pull_flashes();
$currentAdminEmail = isset($_SESSION['saas_admin_email']) ? (string) $_SESSION['saas_admin_email'] : 'admin@superzap.fun';
$pageTitle = [
    'dashboard' => 'Visao Geral',
    'empresas' => 'Empresas',
    'planos' => 'Planos',
    'assinaturas' => 'Assinaturas',
    'tuneis' => 'Tuneis',
    'admins' => 'Administradores',
    'sistemas' => 'Sistemas',
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin SaaS | Barbearia Pro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-900: #04131d;
            --bg-850: #0c2536;
            --bg-800: #14354d;
            --accent: #f59e0b;
            --accent-soft: rgba(245, 158, 11, 0.16);
            --mint: #10b981;
            --sky: #38bdf8;
            --danger: #ef4444;
            --surface: #ffffff;
            --surface-alt: #f4f7fb;
            --text-main: #122032;
            --text-muted: #6b7280;
            --border: #dde3ec;
            --shadow: 0 12px 30px rgba(8, 20, 36, 0.1);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text-main);
            background: radial-gradient(circle at 8% 0%, rgba(56, 189, 248, 0.12), transparent 38%),
                radial-gradient(circle at 92% 8%, rgba(245, 158, 11, 0.12), transparent 34%),
                var(--surface-alt);
            font-family: 'Outfit', sans-serif;
        }

        h1, h2, h3, h4, h5, h6 {
            font-family: 'Sora', sans-serif;
            letter-spacing: -0.015em;
        }

        .page-shell {
            display: flex;
            min-height: 100vh;
            overflow: hidden;
        }

        .app-sidebar {
            width: 278px;
            background: linear-gradient(160deg, var(--bg-900), var(--bg-800));
            color: #f9fafb;
            padding: 26px 18px 20px;
            position: relative;
            z-index: 20;
            box-shadow: 18px 0 36px rgba(4, 19, 29, 0.2);
        }

        .app-sidebar::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(25deg, rgba(245, 158, 11, 0.12), transparent 40%, rgba(56, 189, 248, 0.08));
        }

        .brand {
            position: relative;
            z-index: 1;
            padding: 14px 14px 18px;
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.04);
            margin-bottom: 18px;
        }

        .brand-title {
            margin: 0;
            font-size: 1.08rem;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #fff;
        }

        .brand-title i {
            color: var(--accent);
        }

        .brand-subtitle {
            margin: 7px 0 0;
            color: rgba(255, 255, 255, 0.74);
            font-size: 0.84rem;
        }

        .sidebar-nav {
            position: relative;
            z-index: 1;
            margin-top: 16px;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            border-radius: 14px;
            padding: 10px 12px;
            margin-bottom: 8px;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .sidebar-link:hover {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.18);
            background: rgba(255, 255, 255, 0.09);
            text-decoration: none;
        }

        .sidebar-link.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.24), rgba(56, 189, 248, 0.22));
            border-color: rgba(16, 185, 129, 0.55);
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.06);
        }

        .sidebar-link .label {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-counter {
            min-width: 28px;
            padding: 2px 8px;
            border-radius: 999px;
            text-align: center;
            font-size: 0.74rem;
            color: #f9fafb;
            background: rgba(255, 255, 255, 0.17);
        }

        .sidebar-link.active .sidebar-counter {
            background: rgba(255, 255, 255, 0.26);
        }

        .sidebar-footer {
            position: relative;
            z-index: 1;
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid rgba(255, 255, 255, 0.15);
        }

        .sidebar-footer a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #fca5a5;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .content-shell {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding: 22px 28px 14px;
        }

        .topbar h1 {
            margin: 0;
            font-size: 1.35rem;
        }

        .topbar p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: 0.92rem;
        }

        .admin-meta {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            border: 1px solid var(--border);
            padding: 8px 12px;
            border-radius: 12px;
            color: #334155;
            font-size: 0.88rem;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);
        }

        .admin-meta i {
            color: var(--sky);
        }

        .main-area {
            padding: 0 28px 30px;
            animation: page-in 0.35s ease;
        }

        .panel-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: var(--shadow);
        }

        .flash-stack {
            margin-bottom: 14px;
        }

        .flash-stack .alert {
            border: 0;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 4px 10px;
            line-height: 1.3;
            white-space: nowrap;
        }

        .status-success { background: #dcfce7; color: #166534; }
        .status-warning { background: #fef3c7; color: #92400e; }
        .status-danger { background: #fee2e2; color: #991b1b; }
        .status-muted { background: #e2e8f0; color: #334155; }
        .status-info { background: #dbeafe; color: #1d4ed8; }

        .table-modern {
            margin-bottom: 0;
        }

        .table-modern thead th {
            border-top: 0;
            border-bottom: 1px solid var(--border);
            color: #64748b;
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
            padding: 12px 14px;
            background: #f8fafc;
        }

        .table-modern td {
            border-top: 1px solid #edf2f7;
            padding: 12px 14px;
            vertical-align: middle;
        }

        .btn-soft {
            border: 1px solid var(--border);
            background: #fff;
            color: #1f2937;
        }

        .btn-soft:hover {
            border-color: #c6d2df;
            background: #f9fbfd;
        }

        .btn-brand {
            background: linear-gradient(120deg, #0f766e, #1d4ed8);
            border: 0;
            color: #fff;
            box-shadow: 0 9px 20px rgba(15, 118, 110, 0.22);
        }

        .btn-brand:hover {
            color: #fff;
            opacity: 0.95;
        }

        .page-heading {
            margin-bottom: 14px;
        }

        .page-heading h2 {
            margin: 0;
            font-size: 1.18rem;
        }

        .page-heading p {
            margin: 4px 0 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .mobile-menu {
            display: none;
        }

        .main-area .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .main-area .table-modern {
            min-width: 760px;
        }

        .main-area .form-row {
            margin-right: -6px;
            margin-left: -6px;
        }

        .main-area .form-row > [class*="col-"] {
            padding-right: 6px;
            padding-left: 6px;
        }

        .main-area .modal-dialog {
            max-width: 720px;
        }

        @keyframes page-in {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 991px) {
            body.sidebar-open {
                overflow: hidden;
            }

            body.sidebar-open::before {
                content: '';
                position: fixed;
                inset: 0;
                background: rgba(4, 19, 29, 0.55);
                z-index: 10;
            }

            .app-sidebar {
                position: fixed;
                inset: 0 auto 0 0;
                transform: translateX(-105%);
                transition: transform 0.25s ease;
                width: 280px;
            }

            .sidebar-open .app-sidebar {
                transform: translateX(0);
            }

            .mobile-menu {
                display: inline-flex;
            }

            .topbar {
                padding: 16px;
            }

            .main-area {
                padding: 0 16px 20px;
            }

            .main-area .form-row > [class*="col-"] {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .admin-meta {
                display: none;
            }
        }

        @media (max-width: 767px) {
            .topbar {
                flex-direction: column;
                align-items: flex-start;
            }

            .topbar h1 {
                font-size: 1.18rem;
                margin-top: 6px;
            }

            .main-area {
                padding: 0 12px 16px;
            }

            .main-area .btn {
                width: 100%;
                margin-bottom: 8px;
            }

            .main-area .btn.btn-sm {
                width: auto;
                margin-bottom: 0;
            }

            .main-area .modal-dialog {
                width: calc(100% - 16px);
                margin: 8px auto;
            }
        }
    </style>
</head>
<body>
<div class="page-shell" id="saasAdminApp">
    <aside class="app-sidebar">
        <div class="brand">
            <h2 class="brand-title"><i class="fa fa-scissors"></i> Admin SaaS</h2>
            <p class="brand-subtitle">Gestao completa de empresas, planos, assinaturas e tuneis</p>
        </div>

        <nav class="sidebar-nav">
            <a href="?page=dashboard" class="sidebar-link <?= $page === 'dashboard' ? 'active' : '' ?>">
                <span class="label"><i class="fa fa-chart-line"></i> Dashboard</span>
            </a>
            <a href="?page=empresas" class="sidebar-link <?= $page === 'empresas' ? 'active' : '' ?>">
                <span class="label"><i class="fa fa-building"></i> Empresas</span>
                <span class="sidebar-counter"><?= (int) $menuCounters['empresas'] ?></span>
            </a>
            <a href="?page=planos" class="sidebar-link <?= $page === 'planos' ? 'active' : '' ?>">
                <span class="label"><i class="fa fa-layer-group"></i> Planos</span>
                <span class="sidebar-counter"><?= (int) $menuCounters['planos'] ?></span>
            </a>
            <a href="?page=assinaturas" class="sidebar-link <?= $page === 'assinaturas' ? 'active' : '' ?>">
                <span class="label"><i class="fa fa-credit-card"></i> Assinaturas</span>
                <span class="sidebar-counter"><?= (int) $menuCounters['assinaturas'] ?></span>
            </a>
            <a href="?page=tuneis" class="sidebar-link <?= $page === 'tuneis' ? 'active' : '' ?>">
                <span class="label"><i class="fa fa-network-wired"></i> Tuneis</span>
                <span class="sidebar-counter"><?= (int) $menuCounters['tuneis'] ?></span>
            </a>
            <a href="?page=sistemas" class="sidebar-link <?= $page === 'sistemas' ? 'active' : '' ?>">
                <span class="label"><i class="fa fa-layer-group"></i> Sistemas</span>
                <span class="sidebar-counter"><?= (int) $menuCounters['sistemas'] ?></span>
            </a>
            <a href="?page=admins" class="sidebar-link <?= $page === 'admins' ? 'active' : '' ?>">
                <span class="label"><i class="fa fa-user-shield"></i> Admins</span>
                <span class="sidebar-counter"><?= (int) $menuCounters['admins'] ?></span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="?page=logout"><i class="fa fa-sign-out-alt"></i> Encerrar sessao</a>
        </div>
    </aside>

    <main class="content-shell">
        <header class="topbar">
            <div>
                <button class="btn btn-soft mobile-menu" type="button" id="mobileMenuToggle">
                    <i class="fa fa-bars"></i>
                </button>
                <h1><?= admin_h($pageTitle[$page]) ?></h1>
                <p>Painel administrativo da plataforma multiempresa</p>
            </div>
            <div class="admin-meta">
                <i class="fa fa-user-shield"></i>
                <?= admin_h($currentAdminEmail) ?>
            </div>
        </header>

        <section class="main-area">
            <?php if (!empty($flashes)): ?>
                <div class="flash-stack">
                    <?php foreach ($flashes as $flash):
                        $type = isset($flash['type']) ? $flash['type'] : 'info';
                        $message = isset($flash['message']) ? $flash['message'] : '';
                    ?>
                        <div class="alert alert-<?= admin_h($type) ?> mb-2"><?= admin_h($message) ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php include $pageFile; ?>
        </section>
    </main>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var app = document.getElementById('saasAdminApp');
        var toggle = document.getElementById('mobileMenuToggle');
        if (!app || !toggle) {
            return;
        }

        toggle.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-open');
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 991) {
                document.body.classList.remove('sidebar-open');
            }
        });

        var sidebarLinks = app.querySelectorAll('.app-sidebar a');
        sidebarLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 991) {
                    document.body.classList.remove('sidebar-open');
                }
            });
        });

        app.addEventListener('click', function (event) {
            if (window.innerWidth > 991) {
                return;
            }
            var clickedSidebar = event.target.closest('.app-sidebar');
            var clickedToggle = event.target.closest('#mobileMenuToggle');
            if (!clickedSidebar && !clickedToggle) {
                document.body.classList.remove('sidebar-open');
            }
        });
    })();
</script>
</body>
</html>
<?php
ob_end_flush();
?>
