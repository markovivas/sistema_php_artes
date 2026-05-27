<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    exit;
}

$payload = $input['payload'] ?? [];
$from    = $payload['from'] ?? '';
$message = $payload['body'] ?? '';
$chatId  = $payload['chatId'] ?? '';
$name    = $payload['senderName'] ?? '';

if (empty($from) || empty($message)) {
    http_response_code(200);
    exit;
}

$phone = preg_replace('/\D/', '', $from);
$phone = str_replace('55', '', $phone);

$db = Database::getInstance();

$client = $db->fetch("
    SELECT c.user_id, u.name FROM clients c
    JOIN users u ON c.user_id = u.id
    WHERE REPLACE(REPLACE(REPLACE(c.whatsapp, '(', ''), ')', ''), '-', '')
          LIKE ?
", ["%$phone%"]);

if (!$client) {
    http_response_code(200);
    exit;
}

$pendingOrder = $db->fetch("
    SELECT id, title FROM orders
    WHERE client_id = ? AND status IN ('aguardando_cliente', 'em_producao', 'ajustes')
    ORDER BY updated_at DESC LIMIT 1
", [$client['user_id']]);

if ($pendingOrder) {
    $db->insert(
        "INSERT INTO order_comments (order_id, user_id, message) VALUES (?, ?, ?)",
        [$pendingOrder['id'], $client['user_id'], "[WhatsApp] $message"]
    );
}

http_response_code(200);
