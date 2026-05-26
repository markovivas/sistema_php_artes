<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireLogin();
$db = Database::getInstance();
$user = Auth::user();

// Mark single as read
if (isset($_GET['read'])) {
    $db->query("UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?", [(int)$_GET['read'], $user['id']]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Mark all as read
if (isset($_GET['read_all'])) {
    $db->query("UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL", [$user['id']]);
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Get notifications
$unread = $db->fetchAll("SELECT * FROM notifications WHERE user_id = ? AND read_at IS NULL ORDER BY created_at DESC LIMIT 10", [$user['id']]);
$count = count($unread);

ob_start();
if (empty($unread)):
    echo '<li class="text-center py-3 text-muted small">Nenhuma notificação</li>';
else:
    foreach ($unread as $n):
        $link = $n['link'] ? BASE_URL . $n['link'] : '#';
?>
<li><a class="dropdown-item notif-item" href="<?= $link ?>" data-id="<?= $n['id'] ?>">
    <div class="small"><?= htmlspecialchars($n['message']) ?></div>
    <small class="text-muted"><?= timeAgo($n['created_at']) ?></small>
</a></li>
<?php
    endforeach; ?>
<li><hr class="dropdown-divider my-1"></li>
<li class="text-center"><a class="dropdown-item small text-primary" href="#" id="markAllRead">Marcar todas como lidas</a></li>
<?php endif;
$html = ob_get_clean();

header('Content-Type: application/json');
echo json_encode(['count' => $count, 'html' => $html]);
