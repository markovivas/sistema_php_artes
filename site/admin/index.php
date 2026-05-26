<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole(['admin', 'financial']);

$user = Auth::user();
$db = Database::getInstance();

$kpi = $db->fetch("
    SELECT
        COUNT(*) as total_orders,
        SUM(DAY(created_at) = DAY(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())) as orders_today,
        SUM(status = 'em_producao') as active_production,
        COALESCE(SUM(total_value), 0) as total_value,
        COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN total_value ELSE 0 END), 0) as monthly_revenue
    FROM orders
");

$avgTicket = $kpi['total_orders'] > 0 ? $kpi['total_value'] / $kpi['total_orders'] : 0;

$recentOrders = $db->fetchAll("
    SELECT o.*, c.name as client_name, d.name as designer_name
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.designer_id = d.id
    ORDER BY o.created_at DESC LIMIT 5
");

$title = 'Painel Administrativo';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h3><?= $kpi['orders_today'] ?></h3>
                <p class="mb-0">Pedidos Hoje</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h3><?= $kpi['active_production'] ?></h3>
                <p class="mb-0">Produção Ativa</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <h3><?= formatMoney($kpi['monthly_revenue']) ?></h3>
                <p class="mb-0">Faturamento Mensal</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <h3><?= formatMoney($avgTicket) ?></h3>
                <p class="mb-0">Ticket Médio</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Pedidos Recentes</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Designer</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                        <tr>
                            <td>#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['client_name']) ?></td>
                            <td><?= htmlspecialchars($o['designer_name'] ?? '—') ?></td>
                            <td><span class="badge bg-<?= statusClass($o['status']) ?>"><?= ORDER_STATUS[$o['status']] ?></span></td>
                            <td><?= formatMoney($o['total_value']) ?></td>
                            <td><?= formatDate($o['created_at'], 'd/m/Y') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Acesso Rápido</h6></div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="users.php" class="btn btn-outline-primary">Gerenciar Usuários</a>
                    <a href="finances.php" class="btn btn-outline-success">Financeiro</a>
                    <a href="../designer/" class="btn btn-outline-warning">Kanban</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
