<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole('client');

$user = Auth::user();
$db = Database::getInstance();

$counts = $db->fetch("
    SELECT
        COUNT(*) as total,
        SUM(status = 'em_producao') as andamento,
        SUM(status = 'aguardando_cliente') as pendentes,
        SUM(status = 'finalizado') as finalizados
    FROM orders WHERE client_id = ?
", [$user['id']]);

$orders = $db->fetchAll("
    SELECT o.*, u.name as designer_name
    FROM orders o
    LEFT JOIN users u ON o.designer_id = u.id
    WHERE o.client_id = ?
    ORDER BY FIELD(o.priority, 'urgente','alta','normal','baixa'), o.created_at DESC LIMIT 10
", [$user['id']]);

$title = 'Meu Painel';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-house-fill text-primary me-2"></i>Meu Painel</h4>
    <a href="orders.php?action=new" class="btn btn-modern btn-primary"><i class="bi bi-plus-lg me-1"></i>Novo Pedido</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-primary">
            <i class="bi bi-box-seam-fill stat-icon"></i>
            <div class="stat-value"><?= $counts['total'] ?? 0 ?></div>
            <p class="stat-label">Total de Pedidos</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-warning">
            <i class="bi bi-arrow-repeat stat-icon"></i>
            <div class="stat-value"><?= $counts['andamento'] ?? 0 ?></div>
            <p class="stat-label">Em Andamento</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <i class="bi bi-clock-history stat-icon"></i>
            <div class="stat-value"><?= $counts['pendentes'] ?? 0 ?></div>
            <p class="stat-label">Aprovação Pendente</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <i class="bi bi-check2-all stat-icon"></i>
            <div class="stat-value"><?= $counts['finalizados'] ?? 0 ?></div>
            <p class="stat-label">Finalizados</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Últimos Pedidos</span>
        <a href="orders.php" class="btn btn-sm btn-modern btn-outline">Ver todos</a>
    </div>
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
                <?php if (empty($orders)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Nenhum pedido encontrado.</td></tr>
                <?php else: ?>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
