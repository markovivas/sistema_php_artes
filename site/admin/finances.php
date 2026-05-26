<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole(['admin', 'financial']);

$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $db->insert(
        "INSERT INTO finances (order_id, type, description, value, status, due_date) VALUES (?, ?, ?, ?, ?, ?)",
        [
            $_POST['order_id'] ?: null,
            $_POST['type'],
            $_POST['description'],
            str_replace(['.', ','], ['', '.'], $_POST['value']),
            $_POST['status'],
            $_POST['due_date'] ?: null
        ]
    );
    header('Location: finances.php');
    exit;
}

if (isset($_GET['pay'])) {
    $db->query("UPDATE finances SET status = 'pago', paid_date = NOW() WHERE id = ?", [$_GET['pay']]);
    header('Location: finances.php');
    exit;
}

$entries = $db->fetchAll("
    SELECT f.*, o.title as order_title
    FROM finances f
    LEFT JOIN orders o ON f.order_id = o.id
    ORDER BY f.created_at DESC
");

$totals = $db->fetch("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'receber' AND status = 'pendente' THEN value ELSE 0 END), 0) as a_receber,
        COALESCE(SUM(CASE WHEN type = 'pagar' AND status = 'pendente' THEN value ELSE 0 END), 0) as a_pagar,
        COALESCE(SUM(CASE WHEN status = 'pago' AND type = 'receber' THEN value ELSE 0 END), 0) as recebido,
        COALESCE(SUM(CASE WHEN status = 'pago' AND type = 'pagar' THEN value ELSE 0 END), 0) as pago
    FROM finances
");

$title = 'Financeiro';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-cash-stack text-success me-2"></i>Financeiro</h4>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card bg-gradient-success">
            <i class="bi bi-arrow-down-circle stat-icon"></i>
            <div class="stat-value"><?= formatMoney($totals['a_receber']) ?></div>
            <p class="stat-label">A Receber</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-danger">
            <i class="bi bi-arrow-up-circle stat-icon"></i>
            <div class="stat-value"><?= formatMoney($totals['a_pagar']) ?></div>
            <p class="stat-label">A Pagar</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-info">
            <i class="bi bi-check-circle stat-icon"></i>
            <div class="stat-value"><?= formatMoney($totals['recebido']) ?></div>
            <p class="stat-label">Recebido</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card bg-gradient-secondary">
            <i class="bi bi-check-circle-fill stat-icon"></i>
            <div class="stat-value"><?= formatMoney($totals['pago']) ?></div>
            <p class="stat-label">Pago</p>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Nova Movimentação</div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label small">Tipo</label>
                        <select name="type" class="form-select form-modern" required>
                            <option value="receber">A Receber</option>
                            <option value="pagar">A Pagar</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Descrição</label>
                        <input type="text" name="description" class="form-control form-modern" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Valor</label>
                        <input type="text" name="value" class="form-control form-modern" placeholder="0,00" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Status</label>
                        <select name="status" class="form-select form-modern">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Vencimento</label>
                        <input type="date" name="due_date" class="form-control form-modern">
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Pedido (opcional)</label>
                        <input type="number" name="order_id" class="form-control form-modern" placeholder="#ID">
                    </div>
                    <button type="submit" name="save" class="btn btn-modern btn-primary w-100">Registrar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Movimentações</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-modern">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Vencimento</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><span class="badge badge-modern bg-<?= $e['type'] === 'receber' ? 'success' : 'danger' ?>"><?= $e['type'] === 'receber' ? 'Receber' : 'Pagar' ?></span></td>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                            <td class="fw-semibold"><?= formatMoney($e['value']) ?></td>
                            <td>
                                <span class="badge badge-modern bg-<?= $e['status'] === 'pago' ? 'success' : ($e['status'] === 'vencido' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($e['status']) ?>
                                </span>
                            </td>
                            <td class="text-muted"><?= $e['due_date'] ? formatDate($e['due_date'], 'd/m/Y') : '—' ?></td>
                            <td>
                                <?php if ($e['status'] === 'pendente'): ?>
                                <a href="?pay=<?= $e['id'] ?>" class="btn btn-modern btn-outline btn-sm text-success"><i class="bi bi-check-lg"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
