<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (Auth::check()) {
    header('Location: ' . BASE_URL);
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (Auth::login($email, $password)) {
        header('Location: ' . BASE_URL);
        exit;
    }
    $error = 'E-mail ou senha inválidos.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ArtES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <style>
        body { background: #1a1a2e; display: flex; align-items: center; min-height: 100vh; }
        .login-card { background: #16213e; border-radius: 15px; padding: 40px; color: #fff; box-shadow: 0 0 30px rgba(0,0,0,.3); }
        .login-card h1 { color: #e94560; font-weight: 700; }
        .form-control { background: #0f3460; border: none; color: #fff; padding: 12px; }
        .form-control:focus { background: #0f3460; color: #fff; box-shadow: 0 0 0 2px #e94560; }
        .btn-login { background: #e94560; border: none; padding: 12px; font-weight: 600; }
        .btn-login:hover { background: #d63851; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <h1>ArtES</h1>
                        <p class="text-muted">Sistema de Gerenciamento de Artes</p>
                    </div>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">E-mail</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-login btn-lg w-100 text-white">Entrar</button>
                    </form>
                    <div class="mt-4 text-center text-muted small">
                        <p>Usuários de teste:</p>
                        admin@artes.com / designer@artes.com / cliente@artes.com<br>
                        financeiro@artes.com / producao@artes.com<br>
                        <strong>Senha: 123456</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
