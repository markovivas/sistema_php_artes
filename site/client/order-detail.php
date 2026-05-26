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

// Aprovação
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

// Comentário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $message = trim($_POST['comment']);
    if ($message) {
        $db->insert(
            "INSERT INTO order_comments (order_id, user_id, message) VALUES (?, ?, ?)",
            [$orderId, $user['id'], $message]
        );
    }
    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

// Upload de arquivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $result = uploadFile($_FILES['file'], $orderId, $user['id']);
    if (isset($result['error'])) {
        $uploadError = $result['error'];
    }
    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

$timeline = $db->fetchAll(
    "SELECT t.*, u.name as user_name FROM order_timeline t JOIN users u ON t.user_id = u.id WHERE t.order_id = ? ORDER BY t.created_at ASC",
    [$orderId]
);

$comments = $db->fetchAll(
    "SELECT c.*, u.name as user_name, u.role as user_role FROM order_comments c JOIN users u ON c.user_id = u.id WHERE c.order_id = ? ORDER BY c.created_at ASC",
    [$orderId]
);

$files = $db->fetchAll(
    "SELECT f.*, u.name as user_name FROM order_files f JOIN users u ON f.user_id = u.id WHERE f.order_id = ? ORDER BY f.version DESC, f.created_at DESC",
    [$orderId]
);

$title = $order['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">#<?= $order['id'] ?> - <?= htmlspecialchars($order['title']) ?></h5>
                <div>
                    <span class="badge bg-<?= priorityClass($order['priority']) ?> me-1"><?= ORDER_PRIORITY[$order['priority']] ?></span>
                    <span class="badge bg-<?= statusClass($order['status']) ?>"><?= ORDER_STATUS[$order['status']] ?></span>
                </div>
            </div>
            <div class="card-body">
                <p><strong>Cliente:</strong> <?= htmlspecialchars($order['client_name']) ?></p>
                <p><strong>Designer:</strong> <?= htmlspecialchars($order['designer_name'] ?? '—') ?></p>
                <?php if ($order['deadline']): ?>
                <p><strong>Prazo:</strong> <?= formatDate($order['deadline'], 'd/m/Y') ?></p>
                <?php endif; ?>
                <hr>
                <p><?= nl2br(htmlspecialchars($order['description'])) ?></p>
            </div>
        </div>

        <?php if ($order['status'] === 'aguardando_cliente' && $user['role'] === 'client'): ?>
        <div class="card mb-3 border-warning">
            <div class="card-body text-center">
                <h5>Sua aprovação é necessária!</h5>
                <a href="?id=<?= $orderId ?>&action=approve" class="btn btn-success me-2">Aprovar Arte</a>
                <a href="?id=<?= $orderId ?>&action=request_changes" class="btn btn-warning">Solicitar Ajustes</a>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Comentários</h6></div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="comment" class="form-control" placeholder="Digite sua mensagem..." required>
                        <button type="submit" class="btn btn-primary">Enviar</button>
                    </div>
                </form>
                <div class="chat-messages" style="max-height: 300px; overflow-y: auto;">
                    <?php foreach ($comments as $c): ?>
                    <div class="mb-2 <?= $c['user_id'] === $user['id'] ? 'text-end' : '' ?>">
                        <small class="text-muted"><?= htmlspecialchars($c['user_name']) ?> - <?= formatDate($c['created_at']) ?></small>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($c['message'])) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><h6 class="mb-0">Arquivos</h6></div>
            <div class="card-body">
                <?php if ($user['role'] !== 'client' || $order['status'] !== 'finalizado'): ?>
                <form method="POST" enctype="multipart/form-data" class="mb-3">
                    <div class="mb-2">
                        <input type="file" name="file" class="form-control form-control-sm" required>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary w-100">Upload</button>
                </form>
                <?php endif; ?>
                <div class="list-group">
                    <?php foreach ($files as $f): ?>
                    <a href="<?= BASE_URL ?>/assets/uploads/orders/<?= $f['order_id'] ?>/<?= $f['filename'] ?>" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">v<?= $f['version'] ?></small>
                            <?= htmlspecialchars($f['original_name']) ?>
                            <br><small class="text-muted"><?= $f['user_name'] ?></small>
                        </div>
                        <i class="bi bi-download"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h6 class="mb-0">Timeline</h6></div>
            <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($timeline as $t): ?>
                    <li class="mb-2 pb-2 border-bottom">
                        <small class="text-muted"><?= formatDate($t['created_at']) ?></small>
                        <p class="mb-0"><?= htmlspecialchars($t['description']) ?></p>
                        <small class="text-muted">— <?= htmlspecialchars($t['user_name']) ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
