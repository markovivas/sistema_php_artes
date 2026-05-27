<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
Auth::requireRole('admin');

$apiKey = 'dec771db080c466da9a621b11e457358';

$ch = curl_init('http://waha:3000/api/default/auth/qr');
curl_setopt_array($ch, [
    CURLOPT_HTTPHEADER     => ["x-api-key: $apiKey"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
]);
$result = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'QR code not available', 'status' => $httpCode]);
    exit;
}

header('Content-Type: ' . ($contentType ?: 'image/png'));
header('Cache-Control: no-cache');
echo $result;
