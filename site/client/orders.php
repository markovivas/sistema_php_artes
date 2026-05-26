<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole('client');

$user = Auth::user();
$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';

if ($action === 'new' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $priority = $_POST['priority'] ?? 'normal';

    $orderId = $db->insert(
        "INSERT INTO orders (client_id, title, description, priority) VALUES (?, ?, ?, ?)",
        [$user['id'], $title, $description, $priority]
    );

    $db->insert(
        "INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'criado', 'Pedido criado pelo cliente')",
        [$orderId, $user['id']]
    );

    header('Location: order-detail.php?id=' . $orderId);
    exit;
}

if ($action === 'new') {
    $title = 'Novo Pedido';
    require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-plus-circle text-primary me-2"></i>Novo Pedido</h4>
</div>
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label small">Título do Pedido</label>
                        <input type="text" name="title" class="form-control form-modern" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Briefing / Descrição</label>
                        <textarea name="description" class="form-control form-modern" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Prioridade</label>
                        <select name="priority" class="form-select form-modern">
                            <option value="baixa">Baixa</option>
                            <option value="normal" selected>Normal</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-modern btn-primary">Criar Pedido</button>
                    <a href="index.php" class="btn btn-modern btn-outline">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$orders = $db->fetchAll("
    SELECT o.*, u.name as designer_name
    FROM orders o
    LEFT JOIN users u ON o.designer_id = u.id
    WHERE o.client_id = ?
    ORDER BY FIELD(o.priority, 'urgente','alta','normal','baixa'), o.created_at DESC
", [$user['id']]);

$title = 'Meus Pedidos';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-file-earmark-text-fill text-primary me-2"></i>Meus Pedidos</h4>
    <a href="?action=new" class="btn btn-modern btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo Pedido</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>Designer</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td class="fw-semibold">#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['title']) ?></td>
                    <td><?= htmlspecialchars($o['designer_name'] ?? '—') ?></td>
                    <td><span class="badge badge-modern bg-<?= priorityClass($o['priority']) ?>"><?= ORDER_PRIORITY[$o['priority']] ?></span></td>
                    <td><span class="badge badge-modern bg-<?= statusClass($o['status']) ?>"><?= ORDER_STATUS[$o['status']] ?></span></td>
                    <td class="text-muted"><?= formatDate($o['created_at'], 'd/m/Y') ?></td>
                    <td><a href="order-detail.php?id=<?= $o['id'] ?>" class="btn btn-modern btn-outline btn-sm"><i class="bi bi-arrow-right"></i></a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
