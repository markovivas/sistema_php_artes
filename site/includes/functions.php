<?php
require_once __DIR__ . '/db.php';

function timeAgo($datetime) {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    if ($diff->y > 0) return $diff->y . ' ano' . ($diff->y > 1 ? 's' : '');
    if ($diff->m > 0) return $diff->m . ' mês' . ($diff->m > 1 ? 'es' : '');
    if ($diff->d > 0) return $diff->d . ' dia' . ($diff->d > 1 ? 's' : '');
    if ($diff->h > 0) return $diff->h . ' hora' . ($diff->h > 1 ? 's' : '');
    if ($diff->i > 0) return $diff->i . ' minuto' . ($diff->i > 1 ? 's' : '');
    return 'agora';
}

function formatDate($datetime, $format = 'd/m/Y H:i') {
    return date($format, strtotime($datetime));
}

function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

function priorityClass($priority) {
    return match ($priority) {
        'urgente' => 'danger',
        'alta' => 'warning',
        'normal' => 'info',
        'baixa' => 'secondary',
        default => 'secondary'
    };
}

function statusClass($status) {
    return match ($status) {
        'novo' => 'primary',
        'em_producao' => 'warning',
        'ajustes' => 'info',
        'aguardando_cliente' => 'secondary',
        'finalizado' => 'success',
        default => 'light'
    };
}

function avatarUrl($user) {
    if (!empty($user['avatar'])) {
        return BASE_URL . '/assets/uploads/avatars/' . $user['avatar'];
    }
    $hash = md5(strtolower(trim($user['email'])));
    return "https://www.gravatar.com/avatar/$hash?s=80&d=mp";
}

function addNotification($userId, $message, $link = null) {
    $db = Database::getInstance();
    // Garante que o diretório de logs existe para o WAHA
    $logDir = __DIR__ . '/../storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0777, true);
    }
    $db->insert(
        "INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)",
        [$userId, $message, $link]
    );
}

function getUnreadNotifications($userId) {
    $db = Database::getInstance();
    return $db->fetchAll(
        "SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL ORDER BY created_at DESC LIMIT 10",
        [$userId]
    );
}

function markNotificationsRead($userId) {
    $db = Database::getInstance();
    $db->query("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL", [$userId]);
}

function uploadFile($file, $orderId, $userId) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['png', 'jpg', 'jpeg', 'gif', 'pdf', 'psd', 'ai', 'cdr', 'mp4', 'avi', 'mov'];
    $maxSize = 100 * 1024 * 1024;

    if (!in_array($ext, $allowed)) return ['error' => 'Tipo de arquivo não permitido.'];
    if ($file['size'] > $maxSize) return ['error' => 'Arquivo muito grande. Máximo 100MB.'];

    $dir = UPLOAD_DIR . $orderId . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = uniqid() . '.' . $ext;
    $dest = $dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Erro ao salvar arquivo.'];
    }

    $db = Database::getInstance();
    $db->insert(
        "INSERT INTO order_files (order_id, user_id, filename, original_name, file_type, file_size) VALUES (?, ?, ?, ?, ?, ?)",
        [$orderId, $userId, $filename, $file['name'], $ext, $file['size']]
    );

    $timelineMsg = "Arquivo enviado: {$file['name']}";
    $db->insert(
        "INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'upload', ?)",
        [$orderId, $userId, $timelineMsg]
    );

    return ['success' => true, 'filename' => $filename];
}
