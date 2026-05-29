<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (Auth::check()) {
    header('Location: ' . BASE_URL);
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        if (Auth::login($email, $password)) {
            header('Location: ' . BASE_URL);
            exit;
        }
        $error = 'E-mail ou senha inválidos.';
    } elseif ($action === 'register') {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $whatsapp = $_POST['whatsapp'] ?? '';
        $company = $_POST['company'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Preencha todos os campos obrigatórios.';
        } elseif ($password !== $confirm) {
            $error = 'As senhas não conferem.';
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter no mínimo 6 caracteres.';
        } else {
            $result = Auth::register([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'whatsapp' => $whatsapp,
                'company' => $company,
            ]);
            if ($result === true) {
                header('Location: ' . BASE_URL);
                exit;
            }
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ArtES</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
    <div class="orb orb-3"></div>
    <div class="grid-overlay"></div>

    <div class="login-shell">
        <div class="login-card">
            <div class="login-brand">
                <img src="<?= BASE_URL ?>/img/logo.png" alt="ArtES" class="login-logo">
            </div>
            <p class="login-sub" style="display:none">Sistema de Gerenciamento de Artes</p>

            <?php if ($error): ?>
                <div class="login-error"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm" class="login-form">
                <input type="hidden" name="action" value="login">
                <div class="field-group">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="seu@email.com" required>
                </div>
                <div class="field-group">
                    <label>Senha</label>
                    <input type="password" name="password" placeholder="••••••" required>
                </div>
                <button type="submit" class="login-btn">Entrar</button>
            </form>

            <form method="POST" id="registerForm" class="login-form" style="display:none;">
                <input type="hidden" name="action" value="register">
                <div class="field-group">
                    <label>Nome completo</label>
                    <input type="text" name="name" placeholder="Seu nome" required>
                </div>
                <div class="field-group">
                    <label>E-mail</label>
                    <input type="email" name="email" placeholder="seu@email.com" required>
                </div>
                <div class="field-group">
                    <label>Senha</label>
                    <input type="password" name="password" placeholder="Mín. 6 caracteres" required minlength="6">
                </div>
                <div class="field-group">
                    <label>Confirmar senha</label>
                    <input type="password" name="confirm_password" placeholder="Repita a senha" required minlength="6">
                </div>
                <div class="field-group">
                    <label>WhatsApp <span style="color:#64748b;font-weight:400;">(opcional)</span></label>
                    <input type="text" name="whatsapp" id="whatsapp" placeholder="(35) 98877-0000">
                </div>
                <div class="field-group">
                    <label>Setor <span style="color:#64748b;font-weight:400;">(opcional)</span></label>
                    <input type="text" name="company" placeholder="Nome do setor">
                </div>
                <button type="submit" class="login-btn">Cadastrar</button>
            </form>

            <div class="login-toggle">
                <a href="#" id="toggleFormLink"><i class="bi bi-person-plus"></i> Cadastrar novo usuário</a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('whatsapp')?.addEventListener('input', function(e) {
            let v = this.value.replace(/\D/g, '').slice(0, 11);
            if (v.length > 6) v = `(${v.slice(0,2)}) ${v.slice(2,7)}-${v.slice(7)}`;
            else if (v.length > 2) v = `(${v.slice(0,2)}) ${v.slice(2)}`;
            else if (v.length > 0) v = `(${v}`;
            this.value = v;
        });

        document.getElementById('toggleFormLink').addEventListener('click', function(e) {
            e.preventDefault();
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const link = this;
            if (registerForm.style.display === 'none') {
                loginForm.style.display = 'none';
                registerForm.style.display = 'flex';
                link.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Voltar para o login';
            } else {
                loginForm.style.display = 'flex';
                registerForm.style.display = 'none';
                link.innerHTML = '<i class="bi bi-person-plus"></i> Cadastrar novo usuário';
            }
        });
    </script>
</body>
</html>
