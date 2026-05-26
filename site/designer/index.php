<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole(['admin', 'designer', 'production', 'financial']);

$user = Auth::user();
$db = Database::getInstance();

// Ajax: update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['status'];

    $o = $db->fetch("SELECT client_id, designer_id, title FROM orders WHERE id = ?", [$orderId]);

    $db->query("UPDATE orders SET status = ? WHERE id = ?", [$newStatus, $orderId]);
    $db->insert(
        "INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'status_update', 'Status alterado para: " . ORDER_STATUS[$newStatus] . "')",
        [$orderId, $user['id']]
    );

    $statusLabel = ORDER_STATUS[$newStatus];
    if ($newStatus === 'aguardando_cliente') {
        addNotification($o['client_id'], "Pedido #{$orderId} — {$o['title']} está aguardando sua aprovação!", "/client/order-detail.php?id={$orderId}");
    } elseif ($newStatus === 'finalizado') {
        addNotification($o['client_id'], "Pedido #{$orderId} — {$o['title']} foi finalizado!", "/client/order-detail.php?id={$orderId}");
    }
    if ($o['designer_id'] && $o['designer_id'] != $user['id']) {
        addNotification($o['designer_id'], "Pedido #{$orderId} — status alterado para: {$statusLabel}", "/client/order-detail.php?id={$orderId}");
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Ajax: assign designer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign'])) {
    $orderId = (int)$_POST['order_id'];
    $designerId = (int)$_POST['designer_id'];
    $o = $db->fetch("SELECT client_id, title FROM orders WHERE id = ?", [$orderId]);
    $db->query("UPDATE orders SET designer_id = ? WHERE id = ?", [$designerId, $orderId]);
    $db->insert(
        "INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'atribuido', 'Designer atribuído ao pedido')",
        [$orderId, $user['id']]
    );
    $designerName = $db->fetch("SELECT name FROM users WHERE id = ?", [$designerId])['name'] ?? '';
    if ($designerId > 0) {
        addNotification($designerId, "Você foi atribuído ao pedido #{$orderId} — {$o['title']}", "/client/order-detail.php?id={$orderId}");
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'designer_name' => $designerName]);
    exit;
}

// Ajax: quick create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_create'])) {
    $orderId = $db->insert(
        "INSERT INTO orders (client_id, title, description, priority) VALUES (?, ?, ?, ?)",
        [$_POST['client_id'], $_POST['title'], $_POST['description'] ?? '', $_POST['priority'] ?? 'normal']
    );
    $db->insert(
        "INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'criado', 'Pedido criado via kanban')",
        [$orderId, $user['id']]
    );
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'order_id' => $orderId]);
    exit;
}

$orders = $db->fetchAll("
    SELECT o.*, c.name as client_name, c.email as client_email, d.name as designer_name,
        (SELECT COUNT(*) FROM order_files WHERE order_id = o.id) as file_count,
        (SELECT COUNT(*) FROM order_comments WHERE order_id = o.id) as comment_count
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.designer_id = d.id
    ORDER BY FIELD(o.priority, 'urgente','alta','normal','baixa'), o.created_at DESC
");

$designers = $db->fetchAll("SELECT id, name, email, avatar FROM users WHERE role IN ('designer','admin') AND active = 1");
$clients = $db->fetchAll("SELECT u.id, u.name, u.email FROM users u WHERE u.role = 'client' AND u.active = 1 ORDER BY u.name");
$statuses = ['novo', 'em_producao', 'ajustes', 'aguardando_cliente', 'finalizado'];

function deadlineInfo($deadline) {
    if (!$deadline) return null;
    $now = time();
    $d = strtotime($deadline);
    $diff = $d - $now;
    $days = round($diff / 86400);
    if ($diff < 0) return ['class' => 'bg-danger text-white', 'label' => abs($days) . 'd atrasado'];
    if ($days == 0) return ['class' => 'bg-warning text-dark', 'label' => 'Hoje'];
    if ($days == 1) return ['class' => 'bg-warning text-dark', 'label' => 'Amanhã'];
    if ($days <= 3) return ['class' => 'bg-warning text-dark', 'label' => "{$days}d"];
    return ['class' => 'bg-light text-muted', 'label' => formatDate($deadline, 'd/m')];
}

$title = 'Kanban - Produção';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h4 class="mb-0">Kanban de Pedidos</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#quickCreateModal">
        <i class="bi bi-plus-lg"></i> Novo Pedido
    </button>
</div>

<div class="filter-bar mb-3">
    <i class="bi bi-funnel text-muted"></i>
    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Buscar pedido..." style="max-width: 240px;">
    <select id="filterPriority" class="form-select form-select-sm" style="max-width: 150px;">
        <option value="">Todas prioridades</option>
        <option value="urgente">Urgente</option>
        <option value="alta">Alta</option>
        <option value="normal">Normal</option>
        <option value="baixa">Baixa</option>
    </select>
    <select id="filterDesigner" class="form-select form-select-sm" style="max-width: 180px;">
        <option value="">Todos designers</option>
        <?php foreach ($designers as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
    </select>
</div>

<div class="kanban-board d-flex gap-3 overflow-auto">
    <?php foreach ($statuses as $status): ?>
    <?php $colOrders = array_filter($orders, fn($o) => $o['status'] === $status); ?>
    <div class="kanban-column" data-status="<?= $status ?>">
        <div class="card h-100">
            <div class="card-header py-2 bg-<?= statusClass($status) ?> text-white d-flex align-items-center gap-2">
                <strong class="small"><?= ORDER_STATUS[$status] ?></strong>
                <span class="badge-count ms-auto"><?= count($colOrders) ?></span>
            </div>
            <div class="card-body p-2 kanban-dropzone" style="max-height: calc(100vh - 260px); overflow-y: auto;">
                <?php if (empty($colOrders)): ?>
                <div class="kanban-column-empty">
                    <i class="bi bi-inbox"></i>
                    <small>Nenhum pedido</small>
                </div>
                <?php endif; ?>

                <?php foreach ($orders as $o): ?>
                <?php if ($o['status'] !== $status) continue; ?>
                <div class="card mb-2 kanban-card border-<?= priorityClass($o['priority']) ?>"
                     data-id="<?= $o['id'] ?>"
                     data-priority="<?= $o['priority'] ?>"
                     data-designer="<?= $o['designer_id'] ?? '' ?>"
                     data-search="<?= strtolower(htmlspecialchars($o['title'] . ' ' . $o['client_name'] . ' #' . $o['id'])) ?>">
                    <div class="card-body">
                        <div class="d-flex align-items-start gap-1 mb-1">
                            <span class="priority-dot <?= priorityClass($o['priority']) ?> mt-1"></span>
                            <div class="flex-grow-1 min-w-0">
                                <div class="card-title text-truncate">
                                    <a href="../client/order-detail.php?id=<?= $o['id'] ?>"><?= htmlspecialchars($o['title']) ?></a>
                                </div>
                                <small class="text-muted">#<?= $o['id'] ?> · <?= htmlspecialchars($o['client_name']) ?></small>
                            </div>
                        </div>

                        <?php $dl = deadlineInfo($o['deadline']); if ($dl): ?>
                        <div class="mt-1">
                            <span class="deadline-badge <?= $dl['class'] ?>">
                                <i class="bi bi-calendar3"></i> <?= $dl['label'] ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer">
                        <div class="d-flex align-items-center gap-2">
                            <?php if ($o['designer_name']): ?>
                            <img src="<?= avatarUrl(['email' => $o['designer_name'] . '@artes.com', 'avatar' => null]) ?>" class="designer-avatar" title="<?= htmlspecialchars($o['designer_name']) ?>">
                            <?php else: ?>
                            <span class="text-muted small">—</span>
                            <?php endif; ?>
                            <span class="file-count text-muted">
                                <?php if ($o['file_count'] > 0): ?>
                                <i class="bi bi-paperclip"></i> <?= $o['file_count'] ?>
                                <?php endif; ?>
                                <?php if ($o['comment_count'] > 0): ?>
                                <i class="bi bi-chat ms-1"></i> <?= $o['comment_count'] ?>
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if (Auth::hasRole('admin')): ?>
                        <button class="btn btn-sm btn-light py-0 px-1 assign-designer" data-id="<?= $o['id'] ?>" data-designer="<?= $o['designer_id'] ?? '' ?>" title="Atribuir Designer">
                            <i class="bi bi-person-plus"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Quick Create -->
<div class="modal fade" id="quickCreateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h6 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Novo Pedido</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="quickCreateForm">
                <div class="modal-body">
                    <div class="mb-2">
                        <label class="form-label small">Cliente</label>
                        <select name="client_id" class="form-select" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($clients as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Título</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Descrição</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Prioridade</label>
                        <select name="priority" class="form-select">
                            <option value="baixa">Baixa</option>
                            <option value="normal" selected>Normal</option>
                            <option value="alta">Alta</option>
                            <option value="urgente">Urgente</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Criar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Assign Designer -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Atribuir Designer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <select id="assignDesignerId" class="form-select">
                    <option value="">Sem designer</option>
                    <?php foreach ($designers as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="assignBtn">Salvar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let assignOrderId = null;

    // ── Drag & Drop ──
    document.querySelectorAll('.kanban-dropzone').forEach(el => {
        new Sortable(el, {
            group: 'kanban',
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            filter: '.kanban-column-empty',
            onEnd: function(evt) {
                const card = evt.item;
                const newStatus = evt.to.closest('.kanban-column').dataset.status;
                const orderId = card.dataset.id;

                if (card.dataset.status === newStatus) return;

                const formData = new FormData();
                formData.append('update_status', '1');
                formData.append('order_id', orderId);
                formData.append('status', newStatus);

                fetch('index.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(d => {
                        if (d.success) updateCounts();
                    });
            }
        });
    });

    // ── Status change (dropdown) ──
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
                .then(r => r.json())
                .then(() => location.reload());
        });
    });

    // ── Assign designer ──
    document.querySelectorAll('.assign-designer').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            assignOrderId = this.dataset.id;
            document.getElementById('assignDesignerId').value = this.dataset.designer || '';
            new bootstrap.Modal(document.getElementById('assignModal')).show();
        });
    });

    document.getElementById('assignBtn').addEventListener('click', function() {
        if (!assignOrderId) return;
        const designerId = document.getElementById('assignDesignerId').value;
        const formData = new FormData();
        formData.append('assign', '1');
        formData.append('order_id', assignOrderId);
        formData.append('designer_id', designerId || 0);
        fetch('index.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(() => location.reload());
    });

    // ── Quick Create ──
    document.getElementById('quickCreateForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('quick_create', '1');
        fetch('index.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(d => {
                if (d.success) location.reload();
            });
    });

    // ── Filters ──
    function filterCards() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const priority = document.getElementById('filterPriority').value;
        const designer = document.getElementById('filterDesigner').value;

        document.querySelectorAll('.kanban-card').forEach(card => {
            const matchSearch = !search || card.dataset.search.includes(search);
            const matchPriority = !priority || card.dataset.priority === priority;
            const matchDesigner = !designer || card.dataset.designer === designer;
            card.style.display = (matchSearch && matchPriority && matchDesigner) ? '' : 'none';
        });

        updateCounts();
    }

    function updateCounts() {
        document.querySelectorAll('.kanban-column').forEach(col => {
            const visible = col.querySelectorAll('.kanban-card:not([style*="display: none"])').length;
            col.querySelector('.badge-count').textContent = visible;
        });
    }

    document.getElementById('searchInput').addEventListener('input', filterCards);
    document.getElementById('filterPriority').addEventListener('change', filterCards);
    document.getElementById('filterDesigner').addEventListener('change', filterCards);
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
