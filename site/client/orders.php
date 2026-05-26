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
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Novo Pedido</h5></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Título do Pedido</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Briefing / Descrição</label>
                        <textarea name="description" class="form-control" rows="6" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Prioridade</label>
                        <select name="priority" class="form-select">
                            <option value="baixa">Baixa</option>
                            <option value="normal" selected>Normal</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Criar Pedido</button>
                    <a href="index.php" class="btn btn-secondary">Cancelar</a>
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
    ORDER BY o.created_at DESC
", [$user['id']]);

$title = 'Meus Pedidos';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Meus Pedidos</h4>
    <a href="?action=new" class="btn btn-primary">+ Novo Pedido</a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Título</th>
                    <th>Designer</th>
                    <th>Prioridade</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= $o['id'] ?></td>
                    <td><?= htmlspecialchars($o['title']) ?></td>
                    <td><?= htmlspecialchars($o['designer_name'] ?? '—') ?></td>
                    <td><span class="badge bg-<?= priorityClass($o['priority']) ?>"><?= ORDER_PRIORITY[$o['priority']] ?></span></td>
                    <td><span class="badge bg-<?= statusClass($o['status']) ?>"><?= ORDER_STATUS[$o['status']] ?></span></td>
                    <td><?= formatDate($o['created_at'], 'd/m/Y') ?></td>
                    <td><a href="order-detail.php?id=<?= $o['id'] ?>" class="btn btn-sm btn-outline-primary">Detalhes</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
