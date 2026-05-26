<?php
session_start();
date_default_timezone_set('America/Sao_Paulo');

define('DB_HOST', 'db');
define('DB_NAME', 'artes');
define('DB_USER', 'artes');
define('DB_PASS', 'artes');
define('BASE_URL', 'http://localhost');
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
