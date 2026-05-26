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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="login-card">
                    <div class="text-center mb-4">
                        <div class="logo mb-2">ArtES</div>
                        <p class="text-white-50" style="font-size: .9rem;">Sistema de Gerenciamento de Artes</p>
                    </div>
                    <?php if ($error): ?>
                        <div class="alert alert-danger bg-danger bg-opacity-25 text-white border-0 small"><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
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
                    <div class="test-users text-center text-white-50 small">
                        <p class="mb-2 fw-semibold">Usuários de teste</p>
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <span class="badge bg-white bg-opacity-10 text-white">admin@artes.com</span>
                            <span class="badge bg-white bg-opacity-10 text-white">designer@artes.com</span>
                            <span class="badge bg-white bg-opacity-10 text-white">cliente@artes.com</span>
                            <span class="badge bg-white bg-opacity-10 text-white">financeiro@artes.com</span>
                            <span class="badge bg-white bg-opacity-10 text-white">producao@artes.com</span>
                        </div>
                        <p class="mt-1 mb-0">Senha: <strong>123456</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
