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

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5>A Receber</h5>
                <h3><?= formatMoney($totals['a_receber']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-danger">
            <div class="card-body">
                <h5>A Pagar</h5>
                <h3><?= formatMoney($totals['a_pagar']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <h5>Recebido</h5>
                <h3><?= formatMoney($totals['recebido']) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-secondary">
            <div class="card-body">
                <h5>Pago</h5>
                <h3><?= formatMoney($totals['pago']) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Nova Movimentação</h6></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-2">
                        <label class="form-label">Tipo</label>
                        <select name="type" class="form-select" required>
                            <option value="receber">A Receber</option>
                            <option value="pagar">A Pagar</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Descrição</label>
                        <input type="text" name="description" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Valor</label>
                        <input type="text" name="value" class="form-control" placeholder="0,00" required>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="pendente">Pendente</option>
                            <option value="pago">Pago</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Vencimento</label>
                        <input type="date" name="due_date" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Pedido (opcional)</label>
                        <input type="number" name="order_id" class="form-control" placeholder="#ID">
                    </div>
                    <button type="submit" name="save" class="btn btn-primary w-100">Registrar</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Movimentações</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Tipo</th>
                            <th>Descrição</th>
                            <th>Valor</th>
                            <th>Status</th>
                            <th>Vencimento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e): ?>
                        <tr>
                            <td><span class="badge bg-<?= $e['type'] === 'receber' ? 'success' : 'danger' ?>"><?= $e['type'] === 'receber' ? 'Receber' : 'Pagar' ?></span></td>
                            <td><?= htmlspecialchars($e['description']) ?></td>
                            <td><?= formatMoney($e['value']) ?></td>
                            <td>
                                <span class="badge bg-<?= $e['status'] === 'pago' ? 'success' : ($e['status'] === 'vencido' ? 'danger' : 'warning') ?>">
                                    <?= ucfirst($e['status']) ?>
                                </span>
                            </td>
                            <td><?= $e['due_date'] ? formatDate($e['due_date'], 'd/m/Y') : '—' ?></td>
                            <td>
                                <?php if ($e['status'] === 'pendente'): ?>
                                <a href="?pay=<?= $e['id'] ?>" class="btn btn-sm btn-success">Baixar</a>
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
