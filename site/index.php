<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
Auth::requireLogin();

$role = Auth::role();
$redirect = match ($role) {
    'client' => '/client/',
    'admin', 'designer', 'production', 'financial' => '/designer/',
    default => '/login.php'
};
header('Location: ' . BASE_URL . $redirect);
