<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireLogin();

$user = Auth::user();
$db = Database::getInstance();
$orderId = $_GET['id'] ?? 0;

$order = $db->fetch("
    SELECT o.*, c.name as client_name, d.name as designer_name
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.designer_id = d.id
    WHERE o.id = ?
", [$orderId]);

if (!$order) {
    header('HTTP/1.0 404 Not Found');
    die('Pedido não encontrado.');
}

if ($user['role'] === 'client' && $order['client_id'] != $user['id']) {
    die('Acesso negado.');
}

if ($_GET['action'] === 'approve') {
    $db->query("UPDATE orders SET status = 'em_producao' WHERE id = ?", [$orderId]);
    $db->insert("INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'aprovado', 'Cliente aprovou a arte')", [$orderId, $user['id']]);
    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

if ($_GET['action'] === 'request_changes') {
    $db->query("UPDATE orders SET status = 'ajustes' WHERE id = ?", [$orderId]);
    $db->insert("INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'solicitou_ajustes', 'Cliente solicitou ajustes')", [$orderId, $user['id']]);
    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $message = trim($_POST['comment']);
    if ($message) {
        $db->insert("INSERT INTO order_comments (order_id, user_id, message) VALUES (?, ?, ?)", [$orderId, $user['id'], $message]);
    }
    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $result = uploadFile($_FILES['file'], $orderId, $user['id']);
    if (isset($result['error'])) {
        $uploadError = $result['error'];
    }
    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

$timeline = $db->fetchAll(
    "SELECT t.*, u.name as user_name FROM order_timeline t JOIN users u ON t.user_id = u.id WHERE t.order_id = ? ORDER BY t.created_at ASC", [$orderId]
);

$comments = $db->fetchAll(
    "SELECT c.*, u.name as user_name, u.role as user_role FROM order_comments c JOIN users u ON c.user_id = u.id WHERE c.order_id = ? ORDER BY c.created_at ASC", [$orderId]
);

$files = $db->fetchAll(
    "SELECT f.*, u.name as user_name FROM order_files f JOIN users u ON f.user_id = u.id WHERE f.order_id = ? ORDER BY f.version DESC, f.created_at DESC", [$orderId]
);

$title = $order['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-file-earmark-text text-primary me-2"></i>#<?= $order['id'] ?> - <?= htmlspecialchars($order['title']) ?></h4>
    <div class="d-flex gap-2">
        <span class="badge badge-modern bg-<?= priorityClass($order['priority']) ?>"><?= ORDER_PRIORITY[$order['priority']] ?></span>
        <span class="badge badge-modern bg-<?= statusClass($order['status']) ?>"><?= ORDER_STATUS[$order['status']] ?></span>
    </div>
</div>

<?php if ($order['status'] === 'aguardando_cliente' && $user['role'] === 'client'): ?>
<div class="card mb-3 border-0 bg-warning bg-opacity-10">
    <div class="card-body text-center py-3">
        <h6 class="mb-2"><i class="bi bi-exclamation-circle text-warning me-2"></i>Sua aprovação é necessária!</h6>
        <a href="?id=<?= $orderId ?>&action=approve" class="btn btn-modern btn-primary me-2"><i class="bi bi-check-lg me-1"></i>Aprovar Arte</a>
        <a href="?id=<?= $orderId ?>&action=request_changes" class="btn btn-modern btn-outline"><i class="bi bi-pencil me-1"></i>Solicitar Ajustes</a>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header">Informações do Pedido</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Cliente</small>
                        <span class="fw-semibold"><?= htmlspecialchars($order['client_name']) ?></span>
                    </div>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Designer</small>
                        <span class="fw-semibold"><?= htmlspecialchars($order['designer_name'] ?? '—') ?></span>
                    </div>
                    <?php if ($order['deadline']): ?>
                    <div class="col-sm-4">
                        <small class="text-muted d-block">Prazo</small>
                        <span class="fw-semibold"><?= formatDate($order['deadline'], 'd/m/Y') ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <hr>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['description'])) ?></p>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Comentários</span>
                <small class="text-muted"><?= count($comments) ?> mensagens</small>
            </div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="comment" class="form-control form-modern" placeholder="Digite sua mensagem..." required>
                        <button type="submit" class="btn btn-modern btn-primary"><i class="bi bi-send"></i></button>
                    </div>
                </form>
                <div class="chat-messages">
                    <?php foreach ($comments as $c): ?>
                    <div class="msg <?= $c['user_id'] === $user['id'] ? 'own' : '' ?>">
                        <img src="<?= avatarUrl(['email' => $c['user_name'] . '@artes.com', 'avatar' => null]) ?>" class="msg-avatar">
                        <div class="msg-bubble">
                            <?= nl2br(htmlspecialchars($c['message'])) ?>
                            <div class="msg-meta"><?= htmlspecialchars($c['user_name']) ?> · <?= formatDate($c['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Arquivos</span>
                <small class="text-muted"><?= count($files) ?> arquivos</small>
            </div>
            <div class="card-body">
                <?php if ($user['role'] !== 'client' || $order['status'] !== 'finalizado'): ?>
                <form method="POST" enctype="multipart/form-data" class="mb-3">
                    <div class="input-group">
                        <input type="file" name="file" class="form-control form-modern form-control-sm" required>
                        <button type="submit" class="btn btn-modern btn-primary btn-sm"><i class="bi bi-upload"></i></button>
                    </div>
                </form>
                <?php endif; ?>
                <?php if (empty($files)): ?>
                <p class="text-muted small text-center mb-0">Nenhum arquivo enviado.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($files as $f): ?>
                    <a href="<?= BASE_URL ?>/assets/uploads/orders/<?= $f['order_id'] ?>/<?= $f['filename'] ?>" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0">
                        <i class="bi bi-file-earmark fs-5 text-primary"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-truncate small fw-semibold"><?= htmlspecialchars($f['original_name'])?></div>
                            <small class="text-muted">v<?= $f['version'] ?> · <?= htmlspecialchars($f['user_name']) ?></small>
                        </div>
                        <i class="bi bi-download text-muted"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">Timeline</div>
            <div class="card-body" style="max-height: 320px; overflow-y: auto;">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($timeline as $t): ?>
                    <li class="d-flex gap-2 mb-3">
                        <div class="mt-1">
                            <span class="badge bg-primary rounded-circle p-1" style="width: 8px; height: 8px; display: block;">&nbsp;</span>
                        </div>
                        <div>
                            <small class="text-muted"><?= formatDate($t['created_at']) ?></small>
                            <p class="mb-0 small"><?= htmlspecialchars($t['description']) ?></p>
                            <small class="text-muted">— <?= htmlspecialchars($t['user_name']) ?></small>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
