<?php
require_once __DIR__ . '/../../conexao.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['saas_admin_auth']) && $_SESSION['saas_admin_auth'] === true) {
    header('Location: index.php');
    exit;
}

$erro = '';

function admin_login_h($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function saas_admin_buscar($pdo, $email)
{
    $query = $pdo->prepare("SELECT id, nome, email, senha, ativo, super_admin FROM saas_admins WHERE email = :email AND ativo = 'Sim' LIMIT 1");
    $query->bindValue(':email', strtolower($email));
    $query->execute();
    return $query->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');

    if ($email === '' || $senha === '') {
        $erro = 'Preencha todos os campos.';
    } elseif (!$pdo_saas) {
        $erro = 'Servico indisponivel. Tente novamente.';
    } else {
        try {
            $pdo_saas->query("SELECT 1 FROM saas_admins LIMIT 1");
        } catch (Exception $e) {
            $pdo_saas->query("CREATE TABLE IF NOT EXISTS saas_admins (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(150) NOT NULL,
                email VARCHAR(150) NOT NULL UNIQUE,
                senha VARCHAR(255) NOT NULL,
                ativo ENUM('Sim', 'Nao') DEFAULT 'Sim',
                super_admin TINYINT(1) DEFAULT 0,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_email (email),
                INDEX idx_ativo (ativo)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            $count = $pdo_saas->query("SELECT COUNT(*) FROM saas_admins")->fetchColumn();
            if ((int) $count === 0) {
                $hash = password_hash('admin123', PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo_saas->prepare("INSERT INTO saas_admins (nome, email, senha, ativo, super_admin) VALUES ('Administrador', 'admin@superzap.fun', ?, 'Sim', 1)")->execute([$hash]);
            }
        }

        $admin = saas_admin_buscar($pdo_saas, $email);
        
        if ($admin && password_verify($senha, $admin['senha'])) {
            session_regenerate_id(true);
            $_SESSION['saas_admin_auth'] = true;
            $_SESSION['saas_admin_id'] = (int) $admin['id'];
            $_SESSION['saas_admin_email'] = $admin['email'];
            $_SESSION['saas_admin_nome'] = $admin['nome'];
            $_SESSION['saas_admin_super'] = (int) $admin['super_admin'] === 1;
            $_SESSION['saas_admin_login_at'] = date('Y-m-d H:i:s');

            $update = $pdo_saas->prepare("UPDATE saas_admins SET atualizado_em = NOW() WHERE id = :id");
            $update->bindValue(':id', (int) $admin['id'], PDO::PARAM_INT);
            $update->execute();

            header('Location: index.php');
            exit;
        }

        $erro = 'Credenciais invalidas ou conta inativa.';
    }
}

if (isset($_GET['sair'])) {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Admin SaaS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #0f172a;
            --muted: #64748b;
            --accent: #f59e0b;
            --sea: #0f766e;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background:
                radial-gradient(circle at 10% 12%, rgba(245, 158, 11, 0.3), transparent 36%),
                radial-gradient(circle at 90% 20%, rgba(56, 189, 248, 0.25), transparent 38%),
                linear-gradient(145deg, #021522 0%, #0c2a3e 58%, #163f5c 100%);
            font-family: 'Outfit', sans-serif;
            color: var(--ink);
            padding: 20px;
        }

        .auth-card {
            width: 100%;
            max-width: 430px;
            border-radius: 22px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 30px 70px rgba(2, 10, 23, 0.45);
            animation: rise 0.45s ease;
        }

        .auth-head {
            padding: 26px 28px 18px;
            background: linear-gradient(135deg, #072338, #124665);
            color: #fff;
        }

        .auth-head h1 {
            margin: 0;
            font-family: 'Sora', sans-serif;
            font-size: 1.24rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .auth-head h1 i {
            color: var(--accent);
        }

        .auth-head p {
            margin: 8px 0 0;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.88rem;
        }

        .auth-body {
            padding: 24px 28px 28px;
        }

        .auth-body label {
            font-weight: 600;
            color: #1f2937;
            font-size: 0.9rem;
        }

        .auth-body .form-control {
            border-radius: 12px;
            border: 1px solid #d1d9e5;
            padding: 11px 12px;
            min-height: 44px;
        }

        .auth-body .form-control:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 0.22rem rgba(34, 197, 94, 0.15);
        }

        .btn-login {
            width: 100%;
            border: 0;
            border-radius: 12px;
            min-height: 46px;
            font-weight: 600;
            background: linear-gradient(120deg, var(--sea), #1d4ed8);
            color: #fff;
            box-shadow: 0 9px 18px rgba(15, 118, 110, 0.25);
        }

        .btn-login:hover {
            color: #fff;
            opacity: 0.96;
        }

        .auth-note {
            margin-top: 16px;
            padding: 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: var(--muted);
            font-size: 0.82rem;
        }

        .auth-note code {
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 6px;
            color: #0f172a;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-head">
            <h1><i class="fa fa-scissors"></i> Admin SaaS</h1>
            <p>Controle central de empresas e operacao da plataforma</p>
        </div>

        <div class="auth-body">
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= admin_login_h($erro) ?></div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" class="form-control" required placeholder="admin@superzap.fun" autofocus>
                </div>

                <div class="form-group">
                    <label for="senha">Senha</label>
                    <input id="senha" type="password" name="senha" class="form-control" required placeholder="Digite sua senha">
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fa fa-sign-in-alt mr-1"></i> Entrar no painel
                </button>
            </form>

            <div class="auth-note">
                Credenciais padrao: <code>admin@superzap.fun</code> / <code>admin123</code>
            </div>
        </div>
    </div>
</body>
</html>
