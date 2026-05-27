<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/waha.php';
Auth::requireLogin();

$user = Auth::user();
$db = Database::getInstance();
$orderId = $_GET['id'] ?? 0;

$order = $db->fetch("
    SELECT o.*, c.name as client_name, d.name as designer_name
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.designer_id = d.id
    WHERE o.id = ?
", [$orderId]);

if (!$order) { header('HTTP/1.0 404 Not Found'); die('Pedido não encontrado.'); }
if ($user['role'] === 'client' && $order['client_id'] != $user['id']) { die('Acesso negado.'); }

// ─── Aprovação ───
if ($_GET['action'] === 'approve') {
    $db->query("UPDATE orders SET status = 'em_producao' WHERE id = ?", [$orderId]);
    $db->insert("INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'aprovado', 'Cliente aprovou a arte')", [$orderId, $user['id']]);
    addNotification($order['designer_id'], "Pedido #{$orderId} foi aprovado pelo cliente.", "/client/order-detail.php?id={$orderId}");
    if ($order['designer_id']) {
        $designerData = $db->fetch("SELECT whatsapp, name FROM users WHERE id = ?", [$order['designer_id']]);
        if (!empty($designerData['whatsapp'])) {
            $waha = new WAHA();
            $chatId = WAHA::formatPhone($designerData['whatsapp']) . '@c.us';
            $waha->sendText($chatId, "{$user['name']} aprovou o pedido #{$orderId}!");
        }
    }
    header('Location: order-detail.php?id=' . $orderId); exit;
}

if ($_GET['action'] === 'request_changes') {
    $db->query("UPDATE orders SET status = 'ajustes' WHERE id = ?", [$orderId]);
    $db->insert("INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'solicitou_ajustes', 'Cliente solicitou ajustes')", [$orderId, $user['id']]);
    addNotification($order['designer_id'], "Pedido #{$orderId} — cliente solicitou ajustes.", "/client/order-detail.php?id={$orderId}");
    if ($order['designer_id']) {
        $designerData = $db->fetch("SELECT whatsapp, name FROM users WHERE id = ?", [$order['designer_id']]);
        if (!empty($designerData['whatsapp'])) {
            $waha = new WAHA();
            $chatId = WAHA::formatPhone($designerData['whatsapp']) . '@c.us';
            $waha->sendText($chatId, "{$user['name']} solicitou ajustes no pedido #{$orderId}.");
        }
    }
    header('Location: order-detail.php?id=' . $orderId); exit;
}

// ─── Editar pedido ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_order'])) {
    $db->query("UPDATE orders SET title=?, description=?, deadline=?, total_value=?, priority=? WHERE id=?",
        [$_POST['title'], $_POST['description'], $_POST['deadline'] ?: null, str_replace([',','.'],['.', ''], $_POST['total_value']), $_POST['priority'], $orderId]);
    $db->insert("INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'editado', 'Pedido atualizado')", [$orderId, $user['id']]);
    header('Location: order-detail.php?id=' . $orderId); exit;
}

// ─── Comentário ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $message = trim($_POST['comment']);
    if ($message) {
        $db->insert("INSERT INTO order_comments (order_id, user_id, message) VALUES (?, ?, ?)", [$orderId, $user['id'], $message]);
    }
    header('Location: order-detail.php?id=' . $orderId); exit;
}

// ─── Upload ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    uploadFile($_FILES['file'], $orderId, $user['id']);
    header('Location: order-detail.php?id=' . $orderId); exit;
}

// ─── Tarefas ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $db->insert("INSERT INTO tasks (order_id, user_id, title) VALUES (?, ?, ?)", [$orderId, $user['id'], $_POST['task_title']]);
    header('Location: order-detail.php?id=' . $orderId); exit;
}

if (isset($_GET['task_done'])) {
    $db->query("UPDATE tasks SET status='concluida', completed_at=NOW() WHERE id=? AND order_id=?", [$_GET['task_done'], $orderId]);
    header('Location: order-detail.php?id=' . $orderId); exit;
}

if (isset($_GET['task_undo'])) {
    $db->query("UPDATE tasks SET status='pendente', completed_at=NULL WHERE id=? AND order_id=?", [$_GET['task_undo'], $orderId]);
    header('Location: order-detail.php?id=' . $orderId); exit;
}

if (isset($_GET['task_delete'])) {
    $db->query("DELETE FROM tasks WHERE id=? AND order_id=?", [$_GET['task_delete'], $orderId]);
    header('Location: order-detail.php?id=' . $orderId); exit;
}

// ─── Controle de tempo ───
if (isset($_GET['timer_start'])) {
    $active = $db->fetch("SELECT id FROM time_entries WHERE order_id=? AND user_id=? AND end_time IS NULL", [$orderId, $user['id']]);
    if (!$active) {
        $db->insert("INSERT INTO time_entries (order_id, user_id, start_time) VALUES (?, ?, NOW())", [$orderId, $user['id']]);
        $db->insert("INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'timer_start', 'Temporizador iniciado')", [$orderId, $user['id']]);
    }
    header('Location: order-detail.php?id=' . $orderId); exit;
}

if (isset($_GET['timer_stop'])) {
    $active = $db->fetch("SELECT id, start_time FROM time_entries WHERE order_id=? AND user_id=? AND end_time IS NULL", [$orderId, $user['id']]);
    if ($active) {
        $diff = time() - strtotime($active['start_time']);
        $db->query("UPDATE time_entries SET end_time=NOW(), duration=? WHERE id=?", [$diff, $active['id']]);
        $db->insert("INSERT INTO order_timeline (order_id, user_id, action, description) VALUES (?, ?, 'timer_stop', 'Temporizador parado')", [$orderId, $user['id']]);
    }
    header('Location: order-detail.php?id=' . $orderId); exit;
}

// ─── Data ───
$timeline = $db->fetchAll("SELECT t.*, u.name as user_name FROM order_timeline t JOIN users u ON t.user_id = u.id WHERE t.order_id = ? ORDER BY t.created_at ASC", [$orderId]);
$comments = $db->fetchAll("SELECT c.*, u.name as user_name, u.role as user_role FROM order_comments c JOIN users u ON c.user_id = u.id WHERE c.order_id = ? ORDER BY c.created_at ASC", [$orderId]);
$files = $db->fetchAll("SELECT f.*, u.name as user_name FROM order_files f JOIN users u ON f.user_id = u.id WHERE f.order_id = ? ORDER BY f.version DESC, f.created_at DESC", [$orderId]);
$tasks = $db->fetchAll("SELECT * FROM tasks WHERE order_id = ? ORDER BY FIELD(status,'pendente','concluida'), created_at ASC", [$orderId]);
$timeEntries = $db->fetchAll("SELECT * FROM time_entries WHERE order_id = ? AND user_id = ? ORDER BY start_time DESC LIMIT 20", [$orderId, $user['id']]);
$activeTimer = $db->fetch("SELECT id, start_time, TIMESTAMPDIFF(SECOND, start_time, NOW()) as elapsed FROM time_entries WHERE order_id=? AND user_id=? AND end_time IS NULL", [$orderId, $user['id']]);
$totalTime = $db->fetch("SELECT COALESCE(SUM(duration),0) as total FROM time_entries WHERE order_id=? AND user_id=?", [$orderId, $user['id']]);
$tasksTotal = count($tasks);
$tasksDone = count(array_filter($tasks, fn($t) => $t['status'] === 'concluida'));

$canEdit = Auth::hasRole(['admin', 'designer']);
$title = $order['title'];
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-file-earmark-text text-primary me-2"></i>#<?= $order['id'] ?> - <?= htmlspecialchars($order['title']) ?></h4>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge badge-modern bg-<?= priorityClass($order['priority']) ?>"><?= ORDER_PRIORITY[$order['priority']] ?></span>
        <span class="badge badge-modern bg-<?= statusClass($order['status']) ?>"><?= ORDER_STATUS[$order['status']] ?></span>
        <?php if ($canEdit): ?>
        <button class="btn btn-modern btn-outline btn-sm" data-bs-toggle="modal" data-bs-target="#editModal"><i class="bi bi-pencil"></i></button>
        <?php endif; ?>
    </div>
</div>

<?php if ($order['status'] === 'aguardando_cliente' && $user['role'] === 'client'): ?>
<div class="card mb-3 border-0 bg-warning bg-opacity-10">
    <div class="card-body text-center py-3">
        <h6 class="mb-2"><i class="bi bi-exclamation-circle text-warning me-2"></i>Sua aprovação é necessária!</h6>
        <a href="?id=<?= $orderId ?>&action=approve" class="btn btn-modern btn-primary me-2"><i class="bi bi-check-lg me-1"></i>Aprovar Arte</a>
        <a href="?id=<?= $orderId ?>&action=request_changes" class="btn btn-modern btn-outline"><i class="bi bi-pencil me-1"></i>Solicitar Ajustes</a>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-md-8">
        <!-- Info -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>Informações</span>
                <?php if ($order['total_value'] > 0): ?><span class="fw-bold text-success"><?= formatMoney($order['total_value']) ?></span><?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-sm-4"><small class="text-muted d-block">Cliente</small><span class="fw-semibold"><?= htmlspecialchars($order['client_name']) ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Designer</small><span class="fw-semibold"><?= htmlspecialchars($order['designer_name'] ?? '—') ?></span></div>
                    <div class="col-sm-4"><small class="text-muted d-block">Prazo</small><span class="fw-semibold"><?= $order['deadline'] ? formatDate($order['deadline'], 'd/m/Y') : '—' ?></span></div>
                </div>
                <hr>
                <p class="mb-0"><?= nl2br(htmlspecialchars($order['description'])) ?></p>
            </div>
        </div>

        <!-- Tarefas -->
        <?php if ($canEdit): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Checklist</span>
                <?php if ($tasksTotal > 0): ?><small class="text-muted"><?= $tasksDone ?>/<?= $tasksTotal ?></small><?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" class="input-group mb-3">
                    <input type="text" name="task_title" class="form-control form-modern" placeholder="Nova tarefa..." required>
                    <button type="submit" name="add_task" class="btn btn-modern btn-primary btn-sm">Adicionar</button>
                </form>
                <?php if (empty($tasks)): ?>
                <p class="text-muted small text-center mb-0">Nenhuma tarefa.</p>
                <?php else: ?>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($tasks as $t): ?>
                    <li class="d-flex align-items-center gap-2 py-1 <?= $t['status'] === 'concluida' ? 'text-muted' : '' ?>">
                        <?php if ($t['status'] === 'concluida'): ?>
                        <a href="?id=<?= $orderId ?>&task_undo=<?= $t['id'] ?>" class="text-success"><i class="bi bi-check-circle-fill"></i></a>
                        <span style="text-decoration: line-through;"><?= htmlspecialchars($t['title']) ?></span>
                        <?php else: ?>
                        <a href="?id=<?= $orderId ?>&task_done=<?= $t['id'] ?>" class="text-secondary"><i class="bi bi-circle"></i></a>
                        <span><?= htmlspecialchars($t['title']) ?></span>
                        <?php endif; ?>
                        <a href="?id=<?= $orderId ?>&task_delete=<?= $t['id'] ?>" class="ms-auto text-danger small" onclick="return confirm('Excluir tarefa?')"><i class="bi bi-x"></i></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Comentários -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between"><span>Comentários</span><small class="text-muted"><?= count($comments) ?> mensagens</small></div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="input-group">
                        <input type="text" name="comment" class="form-control form-modern" placeholder="Digite sua mensagem..." required>
                        <button type="submit" class="btn btn-modern btn-primary"><i class="bi bi-send"></i></button>
                    </div>
                </form>
                <div class="chat-messages">
                    <?php foreach ($comments as $c): ?>
                    <div class="msg <?= $c['user_id'] === $user['id'] ? 'own' : '' ?>">
                        <img src="<?= avatarUrl(['email' => $c['user_name'] . '@artes.com', 'avatar' => null]) ?>" class="msg-avatar">
                        <div class="msg-bubble">
                            <?= nl2br(htmlspecialchars($c['message'])) ?>
                            <div class="msg-meta"><?= htmlspecialchars($c['user_name']) ?> · <?= formatDate($c['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Timer -->
        <?php if ($canEdit): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock me-1"></i>Tempo</span>
                <span class="fw-bold"><?= sprintf('%dh %02dm', $totalTime['total'] / 3600, ($totalTime['total'] / 60) % 60) ?></span>
            </div>
            <div class="card-body text-center">
                <?php if ($activeTimer): ?>
                <div class="mb-2"><span class="badge bg-danger" id="liveTimer" data-start="<?= strtotime($activeTimer['start_time']) ?>">00:00:00</span></div>
                <a href="?id=<?= $orderId ?>&timer_stop" class="btn btn-modern btn-danger w-100"><i class="bi bi-stop-fill me-1"></i>Parar</a>
                <?php else: ?>
                <a href="?id=<?= $orderId ?>&timer_start" class="btn btn-modern btn-success w-100"><i class="bi bi-play-fill me-1"></i>Iniciar</a>
                <?php endif; ?>
                <hr class="my-2">
                <div style="max-height:160px;overflow-y:auto;font-size:.75rem;">
                    <?php foreach ($timeEntries as $te): ?>
                    <div class="d-flex justify-content-between text-muted py-1">
                        <span><?= formatDate($te['start_time'], 'd/m H:i') ?></span>
                        <span><?= $te['end_time'] ? sprintf('%dh %02dm', $te['duration']/3600, ($te['duration']/60)%60) : '⏳' ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Arquivos -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between"><span>Arquivos</span><small class="text-muted"><?= count($files) ?></small></div>
            <div class="card-body">
                <?php if ($user['role'] !== 'client' || $order['status'] !== 'finalizado'): ?>
                <form method="POST" enctype="multipart/form-data" class="mb-3">
                    <div class="input-group">
                        <input type="file" name="file" class="form-control form-modern form-control-sm" required>
                        <button type="submit" class="btn btn-modern btn-primary btn-sm"><i class="bi bi-upload"></i></button>
                    </div>
                </form>
                <?php endif; ?>
                <?php if (empty($files)): ?>
                <p class="text-muted small text-center mb-0">Nenhum arquivo.</p>
                <?php else: ?>
                <div class="list-group list-group-flush">
                    <?php foreach ($files as $f): ?>
                    <a href="<?= BASE_URL ?>/assets/uploads/orders/<?= $f['order_id'] ?>/<?= $f['filename'] ?>" target="_blank" class="list-group-item list-group-item-action d-flex align-items-center gap-2 px-0">
                        <i class="bi bi-file-earmark fs-5 text-primary"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="text-truncate small fw-semibold"><?= htmlspecialchars($f['original_name']) ?></div>
                            <small class="text-muted">v<?= $f['version'] ?> · <?= htmlspecialchars($f['user_name']) ?></small>
                        </div>
                        <i class="bi bi-download text-muted"></i>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline -->
        <div class="card">
            <div class="card-header">Timeline</div>
            <div class="card-body" style="max-height: 320px; overflow-y: auto;">
                <ul class="list-unstyled mb-0">
                    <?php foreach ($timeline as $t): ?>
                    <li class="d-flex gap-2 mb-3">
                        <div class="mt-1"><span class="badge bg-primary rounded-circle p-1" style="width:8px;height:8px;display:block;">&nbsp;</span></div>
                        <div>
                            <small class="text-muted"><?= formatDate($t['created_at']) ?></small>
                            <p class="mb-0 small"><?= htmlspecialchars($t['description']) ?></p>
                            <small class="text-muted">— <?= htmlspecialchars($t['user_name']) ?></small>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<?php if ($canEdit): ?>
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header bg-gradient-primary text-white"><h6 class="modal-title">Editar Pedido #<?= $orderId ?></h6><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-2"><label class="form-label small">Título</label><input type="text" name="title" class="form-control form-modern" value="<?= htmlspecialchars($order['title']) ?>" required></div>
                    <div class="mb-2"><label class="form-label small">Descrição</label><textarea name="description" class="form-control form-modern" rows="4"><?= htmlspecialchars($order['description']) ?></textarea></div>
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label small">Prazo</label><input type="date" name="deadline" class="form-control form-modern" value="<?= $order['deadline'] ?>"></div>
                        <div class="col-6"><label class="form-label small">Valor (R$)</label><input type="text" name="total_value" class="form-control form-modern" value="<?= number_format($order['total_value'], 2, ',', '.') ?>" placeholder="0,00"></div>
                    </div>
                    <div class="mb-2"><label class="form-label small">Prioridade</label>
                        <select name="priority" class="form-select form-modern">
                            <?php foreach (ORDER_PRIORITY as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $order['priority'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" name="edit_order" class="btn btn-modern btn-primary">Salvar</button></div>
            </form>
        </div>
    </div>
</div>

<script>
// Live timer
<?php if ($activeTimer): ?>
(function() {
    const start = parseInt(document.getElementById('liveTimer').dataset.start);
    function tick() {
        const sec = Math.floor((Date.now() / 1000) - start);
        const h = String(Math.floor(sec / 3600)).padStart(2, '0');
        const m = String(Math.floor((sec % 3600) / 60)).padStart(2, '0');
        const s = String(sec % 60).padStart(2, '0');
        document.getElementById('liveTimer').textContent = h + ':' + m + ':' + s;
    }
    tick();
    setInterval(tick, 1000);
})();
<?php endif; ?>
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
