<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Sistema de Artes' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php if (Auth::check()): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>/">ArtES</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (Auth::hasRole('client')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/client/">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/client/orders.php">Meus Pedidos</a></li>
                <?php endif; ?>
                <?php if (Auth::hasRole(['admin', 'designer', 'production'])): ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/designer/">Kanban</a></li>
                <?php endif; ?>
                <?php if (Auth::hasRole(['admin', 'financial'])): ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/">Admin</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/finances.php">Financeiro</a></li>
                <?php endif; ?>
                <?php if (Auth::hasRole('admin')): ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/admin/users.php">Usuários</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <img src="<?= avatarUrl(Auth::user()) ?>" class="rounded-circle me-1" width="24" height="24">
                        <?= Auth::user()['name'] ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text text-muted"><?= ROLES[Auth::role()] ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php">Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid mt-3">
<?php endif; ?>
