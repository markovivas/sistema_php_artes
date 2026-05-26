<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

define('DB_HOST', 'db');
define('DB_NAME', 'artes');
define('DB_USER', 'artes');
define('DB_PASS', 'artes');
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('BASE_URL', $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/orders/');

define('ROLES', [
    'admin' => 'Administrador',
    'designer' => 'Designer',
    'production' => 'Produção',
    'financial' => 'Financeiro',
    'client' => 'Cliente'
]);

define('ORDER_STATUS', [
    'novo' => 'Novo',
    'em_producao' => 'Em Produção',
    'ajustes' => 'Ajustes',
    'aguardando_cliente' => 'Aguardando Cliente',
    'finalizado' => 'Finalizado'
]);

define('ORDER_PRIORITY', [
    'urgente' => 'Urgente',
    'alta' => 'Alta',
    'normal' => 'Normal',
    'baixa' => 'Baixa'
]);
