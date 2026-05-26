<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole(['admin', 'designer', 'production', 'financial']);

$user = Auth::user();
$db = Database::getInstance();

// Atualizar status (drag & drop)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['status'];
    $db->query("UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId]);
    $db->insert(
        "INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'status_update', 'Status alterado para: " . ORDER_STATUS[$newStatus] . "')",
        [$orderId, $user['id']]
    );
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Atribuir designer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $orderId = $_POST['order_id'];
    $designerId = $_POST['designer_id'];
    $db->query("UPDATE orders SET designer_id = ? WHERE id = ?", [$designerId, $orderId]);
    $db->insert(
        "INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'atribuido', 'Designer atribuído ao pedido')",
        [$orderId, $user['id']]
    );
    header('Location: index.php');
    exit;
}

$orders = $db->fetchAll("
    SELECT o.*, c.name as client_name, d.name as designer_name
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.designer_id = d.id
    ORDER BY FIELD(o.priority, 'urgente','alta','normal','baixa'), o.created_at DESC
");

$designers = $db->fetchAll("SELECT id, name FROM users WHERE role IN ('designer','admin') AND active = 1");
$statuses = ['novo', 'em_producao', 'ajustes', 'aguardando_cliente', 'finalizado'];

$title = 'Kanban - Produção';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Kanban de Pedidos</h4>
</div>

<div class="kanban-board d-flex gap-3 overflow-auto" style="min-height: calc(100vh - 160px);">
    <?php foreach ($statuses as $status): ?>
    <div class="kanban-column" style="min-width: 280px; flex: 1;" data-status="<?= $status ?>">
        <div class="card h-100">
            <div class="card-header py-2 bg-<?= statusClass($status) ?> text-white">
                <strong><?= ORDER_STATUS[$status] ?></strong>
                <span class="badge bg-light text-dark float-end"><?= count(array_filter($orders, fn($o) => $o['status'] === $status)) ?></span>
            </div>
            <div class="card-body p-2 overflow-auto" style="max-height: calc(100vh - 220px);">
                <?php foreach ($orders as $o): ?>
                <?php if ($o['status'] !== $status) continue; ?>
                <div class="card mb-2 kanban-card border-<?= priorityClass($o['priority']) ?>" data-id="<?= $o['id'] ?>">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">#<?= $o['id'] ?></small>
                            <span class="badge bg-<?= priorityClass($o['priority']) ?>"><?= ORDER_PRIORITY[$o['priority']] ?></span>
                        </div>
                        <p class="mb-1 fw-bold small"><?= htmlspecialchars($o['title']) ?></p>
                        <small class="text-muted"><?= htmlspecialchars($o['client_name']) ?></small>
                        <?php if ($o['deadline']): ?>
                        <br><small class="text-<?= strtotime($o['deadline']) < time() ? 'danger' : 'muted' ?>">
                            Prazo: <?= formatDate($o['deadline'], 'd/m/Y') ?>
                        </small>
                        <?php endif; ?>
                        <hr class="my-1">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><?= htmlspecialchars($o['designer_name'] ?? '—') ?></small>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary py-0" data-bs-toggle="dropdown">⋯</button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="../client/order-detail.php?id=<?= $o['id'] ?>">Abrir</a></li>
                                    <?php if ($user['role'] === 'admin' || $user['role'] === 'designer'): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <?php foreach ($statuses as $s): ?>
                                    <?php if ($s !== $status): ?>
                                    <li><a class="dropdown-item status-change" href="#" data-id="<?= $o['id'] ?>" data-status="<?= $s ?>">→ <?= ORDER_STATUS[$s] ?></a></li>
                                    <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                    <?php if (Auth::hasRole('admin')): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#assignModal" data-id="<?= $o['id'] ?>">Atribuir Designer</a></li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Atribuir Designer -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Atribuir Designer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="assignOrderId">
                    <input type="hidden" name="assign" value="1">
                    <select name="designer_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($designers as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Atribuir</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Status change via AJAX
    document.querySelectorAll('.status-change').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.dataset.id;
            const status = this.dataset.status;
            const formData = new FormData();
            formData.append('update_status', '1');
            formData.append('order_id', orderId);
            formData.append('status', status);
            fetch('index.php', { method: 'POST', body: formData })
                .then(() => location.reload());
        });
    });

    // Assign modal
    document.querySelectorAll('[data-bs-target="#assignModal"]').forEach(el => {
        el.addEventListener('click', function() {
            document.getElementById('assignOrderId').value = this.dataset.id;
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
