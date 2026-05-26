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
    ORDER BY o.created_at DESC LIMIT 10
", [$user['id']]);

$title = 'Meu Painel';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title"><?= $counts['total'] ?? 0 ?></h5>
                <p class="card-text">Total de Pedidos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h5 class="card-title"><?= $counts['andamento'] ?? 0 ?></h5>
                <p class="card-text">Em Andamento</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-secondary">
            <div class="card-body">
                <h5 class="card-title"><?= $counts['pendentes'] ?? 0 ?></h5>
                <p class="card-text">Aprovação Pendente</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title"><?= $counts['finalizados'] ?? 0 ?></h5>
                <p class="card-text">Finalizados</p>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Meus Pedidos</h4>
    <a href="orders.php?action=new" class="btn btn-primary">+ Novo Pedido</a>
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
                <?php if (empty($orders)): ?>
                <tr><td colspan="7" class="text-center py-4">Nenhum pedido encontrado.</td></tr>
                <?php else: ?>
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
