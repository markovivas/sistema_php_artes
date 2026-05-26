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

<div class="page-header">
    <h4><i class="bi bi-speedometer2 text-primary me-2"></i>Painel Administrativo</h4>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-primary">
            <i class="bi bi-calendar-check-fill stat-icon"></i>
            <div class="stat-value"><?= $kpi['orders_today'] ?></div>
            <p class="stat-label">Pedidos Hoje</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-warning">
            <i class="bi bi-gear-wide-connected stat-icon"></i>
            <div class="stat-value"><?= $kpi['active_production'] ?></div>
            <p class="stat-label">Produção Ativa</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <i class="bi bi-graph-up-arrow stat-icon"></i>
            <div class="stat-value"><?= formatMoney($kpi['monthly_revenue']) ?></div>
            <p class="stat-label">Faturamento Mensal</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <i class="bi bi-ticket-perforated-fill stat-icon"></i>
            <div class="stat-value"><?= formatMoney($avgTicket) ?></div>
            <p class="stat-label">Ticket Médio</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Pedidos Recentes</div>
            <div class="card-body p-0">
                <table class="table table-modern">
                    <thead>
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
                            <td class="fw-semibold">#<?= $o['id'] ?></td>
                            <td><?= htmlspecialchars($o['client_name']) ?></td>
                            <td><?= htmlspecialchars($o['designer_name'] ?? '—') ?></td>
                            <td><span class="badge badge-modern bg-<?= statusClass($o['status']) ?>"><?= ORDER_STATUS[$o['status']] ?></span></td>
                            <td><?= formatMoney($o['total_value']) ?></td>
                            <td class="text-muted"><?= formatDate($o['created_at'], 'd/m/Y') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Acesso Rápido</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="users.php" class="btn btn-modern btn-outline text-start"><i class="bi bi-people-fill me-2 text-primary"></i>Usuários</a>
                    <a href="finances.php" class="btn btn-modern btn-outline text-start"><i class="bi bi-cash-stack me-2 text-success"></i>Financeiro</a>
                    <a href="../designer/" class="btn btn-modern btn-outline text-start"><i class="bi bi-kanban-fill me-2 text-warning"></i>Kanban</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
