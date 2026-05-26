<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#6C63FF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="<?= BASE_URL ?>/manifest.json">
    <link rel="icon" href="<?= BASE_URL ?>/assets/img/icon-192.png">
    <title><?= $title ?? 'ArtES - Sistema de Artes' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<?php if (Auth::check()): ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-artes">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>/">ArtES</a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (Auth::hasRole('client')): ?>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) === 'client' ? 'active' : '' ?>" href="<?= BASE_URL ?>/client/"><i class="bi bi-grid-fill"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/client/orders.php"><i class="bi bi-file-earmark-text-fill"></i>Meus Pedidos</a></li>
                <?php endif; ?>
                <?php if (Auth::hasRole(['admin', 'designer', 'production', 'financial'])): ?>
                <li class="nav-item"><a class="nav-link <?= basename(dirname($_SERVER['PHP_SELF'])) === 'designer' ? 'active' : '' ?>" href="<?= BASE_URL ?>/designer/"><i class="bi bi-kanban-fill"></i>Kanban</a></li>
                <?php endif; ?>
                <?php if (Auth::hasRole(['admin', 'financial'])): ?>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' && basename(dirname($_SERVER['PHP_SELF'])) === 'admin' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/"><i class="bi bi-speedometer2"></i>Admin</a></li>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'finances.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/finances.php"><i class="bi bi-cash-stack"></i>Financeiro</a></li>
                <?php endif; ?>
                <?php if (Auth::hasRole('admin')): ?>
                <li class="nav-item"><a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>" href="<?= BASE_URL ?>/admin/users.php"><i class="bi bi-people-fill"></i>Usuários</a></li>
                <?php endif; ?>
                <?php if (Auth::hasRole(['admin', 'designer', 'production'])): ?>
                <li class="nav-item"><a class="nav-link" href="<?= BASE_URL ?>/production/tv.php" target="_blank"><i class="bi bi-tv-fill"></i>TV</a></li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown user-dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <img src="<?= avatarUrl(Auth::user()) ?>" class="avatar">
                        <span class="d-none d-md-inline"><?= explode(' ', Auth::user()['name'])[0] ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                        <li class="px-3 py-2 text-center">
                            <img src="<?= avatarUrl(Auth::user()) ?>" class="rounded-circle mb-1" width="48" height="48">
                            <div class="fw-semibold small"><?= Auth::user()['name'] ?></div>
                            <span class="badge bg-primary badge-modern mt-1"><?= ROLES[Auth::role()] ?></span>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= BASE_URL ?>/auth/logout.php"><i class="bi bi-box-arrow-right"></i>Sair</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="container-fluid px-4 py-3 fade-in">
<?php endif; ?>
