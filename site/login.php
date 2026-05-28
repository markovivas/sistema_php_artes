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
    <style>
        .login-card .form-toggle { text-align: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.06); }
        .login-card .form-toggle a { color: rgba(255,255,255,.6); text-decoration: none; font-size: .85rem; cursor: pointer; transition: color .2s; }
        .login-card .form-toggle a:hover { color: var(--primary); }
        .login-card .form-toggle a i { margin-right: 6px; }
    </style>
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <img src="<?= BASE_URL ?>/img/logo.png" alt="ArtES" height="48" class="mb-3">
                        <p class="text-white-50" style="font-size: .9rem;">Sistema de Gerenciamento de Artes</p>
                    </div>
                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-0 small"><?= $error ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success bg-success bg-opacity-25 text-white border-0 small"><?= $success ?></div>
                    <?php endif; ?>

                    <!-- Login Form -->
                    <form method="POST" id="loginForm">
                        <input type="hidden" name="action" value="login">
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="password" class="form-control" placeholder="••••••" required>
                        </div>
                        <button type="submit" class="btn btn-login btn-lg w-100 text-white">Entrar</button>
                    </form>

                    <!-- Register Form -->
                    <form method="POST" id="registerForm" style="display:none;">
                        <input type="hidden" name="action" value="register">
                        <div class="mb-3">
                            <label class="form-label">Nome completo</label>
                            <input type="text" name="name" class="form-control" placeholder="Seu nome" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" placeholder="seu@email.com" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <label class="form-label">Senha</label>
                                <input type="password" name="password" class="form-control" placeholder="Mín. 6 caracteres" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirmar senha</label>
                                <input type="password" name="confirm_password" class="form-control" placeholder="Repita a senha" required minlength="6">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp <span class="text-white-50">(opcional)</span></label>
                            <input type="text" name="whatsapp" id="whatsapp" class="form-control" placeholder="(35) 98877-0000">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Setor <span class="text-white-50">(opcional)</span></label>
                            <input type="text" name="company" class="form-control" placeholder="Nome do setor">
                        </div>
                        <button type="submit" class="btn btn-login btn-lg w-100 text-white">Cadastrar</button>
                    </form>

                    <div class="form-toggle">
                        <a href="#" id="toggleFormLink"><i class="bi bi-person-plus"></i> Cadastrar novo usuário</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        // WhatsApp mask
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
                registerForm.style.display = 'block';
                link.innerHTML = '<i class="bi bi-box-arrow-in-right"></i> Voltar para o login';
            } else {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                link.innerHTML = '<i class="bi bi-person-plus"></i> Cadastrar novo usuário';
            }
        });
    </script>
</body>
</html>
