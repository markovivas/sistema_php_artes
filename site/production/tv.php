<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole(['admin', 'designer', 'production']);

$db = Database::getInstance();

$orders = $db->fetchAll("
    SELECT o.*, c.name as client_name, d.name as designer_name,
        TIMESTAMPDIFF(MINUTE, o.created_at, NOW()) as minutes_since_creation
    FROM orders o
    JOIN users c ON o.client_id = c.id
    LEFT JOIN users d ON o.designer_id = d.id
    WHERE o.status IN ('novo', 'em_producao', 'ajustes')
    ORDER BY FIELD(o.priority, 'urgente','alta','normal','baixa'), o.created_at ASC
");

$totals = [];
foreach ($orders as $o) {
    $totals[$o['status']] = ($totals[$o['status']] ?? 0) + 1;
}
$totals['total'] = count($orders);
$totals['urgentes'] = count(array_filter($orders, fn($o) => $o['priority'] === 'urgente'));
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel TV - Produção</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            overflow: hidden;
            font-family: 'Inter', -apple-system, sans-serif;
            background: #0D0D1A;
            color: #fff;
        }

        .tv-header {
            background: linear-gradient(135deg, #1a1a2e 0%, #0d3b66 100%);
            padding: 16px 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(255,255,255,.06);
            height: 70px;
        }

        .tv-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #66c0f0, #40adec);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .tv-header .tv-clock {
            font-size: 1.3rem;
            font-weight: 600;
            color: rgba(255,255,255,.7);
            font-variant-numeric: tabular-nums;
        }

        .tv-header .tv-stats {
            display: flex;
            gap: 16px;
        }

        .tv-header .tv-stats .stat {
            text-align: center;
            padding: 4px 16px;
            border-radius: 8px;
            background: rgba(255,255,255,.04);
        }

        .tv-header .tv-stats .stat .num {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .tv-header .tv-stats .stat .lbl {
            font-size: .65rem;
            color: rgba(255,255,255,.5);
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .tv-body {
            height: calc(100vh - 70px);
            display: flex;
            gap: 12px;
            padding: 12px;
        }

        .tv-column {
            flex: 1;
            background: rgba(255,255,255,.03);
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .tv-column-header {
            padding: 12px 16px;
            font-weight: 700;
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-shrink: 0;
        }

        .tv-column-header .count {
            background: rgba(255,255,255,.15);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: .75rem;
        }

        .tv-column-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 8px;
        }

        .tv-column-scroll::-webkit-scrollbar { width: 4px; }
        .tv-column-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }

        .tv-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.06);
            border-left: 4px solid #6c757d;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 8px;
            transition: background .2s;
        }

        .tv-card:hover { background: rgba(255,255,255,.07); }
        .tv-card.urgent { border-left-color: #e33e3c; }
        .tv-card.high { border-left-color: #f7c72b; }
        .tv-card.normal { border-left-color: #40adec; }
        .tv-card.low { border-left-color: #636E72; }

        .tv-card .order-id {
            font-size: .7rem;
            color: rgba(255,255,255,.35);
            font-weight: 500;
        }

        .tv-card .order-title {
            font-size: 1rem;
            font-weight: 600;
            margin: 2px 0;
            line-height: 1.3;
        }

        .tv-card .order-meta {
            font-size: .75rem;
            color: rgba(255,255,255,.5);
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .tv-card .order-meta i { margin-right: 4px; }
        .tv-card .priority-badge {
            font-size: .6rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 600;
        }

        .tv-card .priority-badge.urgent { background: rgba(227,62,60,.2); color: #e33e3c; }
        .tv-card .priority-badge.high { background: rgba(247,199,43,.2); color: #f7c72b; }
        .tv-card .priority-badge.normal { background: rgba(64,173,236,.2); color: #40adec; }
        .tv-card .priority-badge.low { background: rgba(99,110,114,.2); color: #adb5bd; }

        .tv-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: rgba(255,255,255,.15);
        }

        .tv-empty i { font-size: 2.5rem; margin-bottom: 8px; }
        .tv-empty span { font-size: .85rem; }

        /* Finished orders footer ticker */
        .tv-ticker {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #88bd46, #72a63a);
            padding: 8px 0;
            overflow: hidden;
            height: 36px;
        }

        .tv-ticker-content {
            display: flex;
            gap: 48px;
            white-space: nowrap;
            animation: ticker 30s linear infinite;
            font-size: .8rem;
            font-weight: 500;
        }

        .tv-ticker-content span { margin-right: 48px; }

        @keyframes ticker {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }

        @media (max-width: 1200px) {
            .tv-card .order-title { font-size: .85rem; }
            .tv-card .order-meta { font-size: .7rem; }
        }
    </style>
</head>
<body>
    <div class="tv-header">
        <div class="d-flex align-items-center gap-3">
            <img src="<?= BASE_URL ?>/img/logo.png" alt="ArtES" height="32" class="me-2" style="filter:brightness(0) invert(1);">
            <h1 class="mb-0">ArtES <span style="font-weight:400;color:rgba(255,255,255,.3);-webkit-text-fill-color:rgba(255,255,255,.3);">TV</span></h1>
            <span class="badge" style="background:rgba(255,255,255,.06);color:rgba(255,255,255,.5);font-weight:400;">Produção</span>
        </div>
        <div class="tv-stats">
            <div class="stat">
                <div class="num" style="color:#e33e3c;"><?= $totals['urgentes'] ?? 0 ?></div>
                <div class="lbl">Urgentes</div>
            </div>
            <div class="stat">
                <div class="num" style="color:#40adec;"><?= $totals['novo'] ?? 0 ?></div>
                <div class="lbl">Novos</div>
            </div>
            <div class="stat">
                <div class="num" style="color:#f7c72b;"><?= $totals['em_producao'] ?? 0 ?></div>
                <div class="lbl">Produção</div>
            </div>
            <div class="stat">
                <div class="num" style="color:#66c0f0;"><?= $totals['ajustes'] ?? 0 ?></div>
                <div class="lbl">Ajustes</div>
            </div>
            <div class="stat">
                <div class="num" style="color:#fff;"><?= $totals['total'] ?? 0 ?></div>
                <div class="lbl">Total</div>
            </div>
        </div>
        <div class="tv-clock" id="clock">--:--:--</div>
    </div>

    <div class="tv-body">
        <div class="tv-column">
            <div class="tv-column-header" style="background:rgba(64,173,236,.15);color:#40adec;">
                <span><i class="bi bi-inbox-fill me-2"></i>Novos</span>
                <span class="count"><?= $totals['novo'] ?? 0 ?></span>
            </div>
            <div class="tv-column-scroll">
                <?php $hasItems = false; foreach ($orders as $o): if ($o['status'] !== 'novo') continue; $hasItems = true; ?>
                <div class="tv-card <?= $o['priority'] ?>">
                    <div class="order-id">#<?= $o['id'] ?></div>
                    <div class="order-title"><?= htmlspecialchars($o['title']) ?></div>
                    <div class="order-meta">
                        <span><i class="bi bi-person"></i><?= htmlspecialchars($o['client_name']) ?></span>
                        <?php if ($o['designer_name']): ?>
                        <span><i class="bi bi-brush"></i><?= htmlspecialchars($o['designer_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($o['deadline']): ?>
                        <span><i class="bi bi-calendar3"></i><?= formatDate($o['deadline'], 'd/m') ?></span>
                        <?php endif; ?>
                        <span class="priority-badge <?= $o['priority'] ?>"><?= ORDER_PRIORITY[$o['priority']] ?></span>
                    </div>
                </div>
                <?php endforeach; if (!$hasItems): ?>
                <div class="tv-empty"><i class="bi bi-inbox"></i><span>Nenhum pedido novo</span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tv-column">
            <div class="tv-column-header" style="background:rgba(247,199,43,.15);color:#f7c72b;">
                <span><i class="bi bi-gear-wide-connected me-2"></i>Em Produção</span>
                <span class="count"><?= $totals['em_producao'] ?? 0 ?></span>
            </div>
            <div class="tv-column-scroll">
                <?php $hasItems = false; foreach ($orders as $o): if ($o['status'] !== 'em_producao') continue; $hasItems = true; ?>
                <div class="tv-card <?= $o['priority'] ?>">
                    <div class="order-id">#<?= $o['id'] ?></div>
                    <div class="order-title"><?= htmlspecialchars($o['title']) ?></div>
                    <div class="order-meta">
                        <span><i class="bi bi-person"></i><?= htmlspecialchars($o['client_name']) ?></span>
                        <span><i class="bi bi-brush"></i><?= htmlspecialchars($o['designer_name'] ?? '—') ?></span>
                        <?php if ($o['deadline']): ?>
                        <span><i class="bi bi-calendar3"></i><?= formatDate($o['deadline'], 'd/m') ?></span>
                        <?php endif; ?>
                        <span class="priority-badge <?= $o['priority'] ?>"><?= ORDER_PRIORITY[$o['priority']] ?></span>
                    </div>
                </div>
                <?php endforeach; if (!$hasItems): ?>
                <div class="tv-empty"><i class="bi bi-gear-wide-connected"></i><span>Nenhum em produção</span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="tv-column">
            <div class="tv-column-header" style="background:rgba(64,173,236,.15);color:#40adec;">
                <span><i class="bi bi-arrow-repeat me-2"></i>Ajustes</span>
                <span class="count"><?= $totals['ajustes'] ?? 0 ?></span>
            </div>
            <div class="tv-column-scroll">
                <?php $hasItems = false; foreach ($orders as $o): if ($o['status'] !== 'ajustes') continue; $hasItems = true; ?>
                <div class="tv-card <?= $o['priority'] ?>">
                    <div class="order-id">#<?= $o['id'] ?></div>
                    <div class="order-title"><?= htmlspecialchars($o['title']) ?></div>
                    <div class="order-meta">
                        <span><i class="bi bi-person"></i><?= htmlspecialchars($o['client_name']) ?></span>
                        <span><i class="bi bi-brush"></i><?= htmlspecialchars($o['designer_name'] ?? '—') ?></span>
                        <?php if ($o['deadline']): ?>
                        <span><i class="bi bi-calendar3"></i><?= formatDate($o['deadline'], 'd/m') ?></span>
                        <?php endif; ?>
                        <span class="priority-badge <?= $o['priority'] ?>"><?= ORDER_PRIORITY[$o['priority']] ?></span>
                    </div>
                </div>
                <?php endforeach; if (!$hasItems): ?>
                <div class="tv-empty"><i class="bi bi-arrow-repeat"></i><span>Nenhum em ajustes</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="tv-ticker">
        <div class="tv-ticker-content">
            <?php
            $finished = $db->fetchAll("SELECT o.title, c.name as client_name FROM orders o JOIN users c ON o.client_id = c.id WHERE o.status = 'finalizado' AND o.updated_at >= NOW() - INTERVAL 24 HOUR ORDER BY o.updated_at DESC LIMIT 20");
            if ($finished): foreach ($finished as $f): ?>
            <span>✅ <?= htmlspecialchars($f['title']) ?> — <?= htmlspecialchars($f['client_name']) ?></span>
            <?php endforeach; else: ?>
            <span style="color:rgba(255,255,255,.7);">✅ Nenhum pedido finalizado nas últimas 24h</span>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Clock
        function updateClock() {
            const now = new Date();
            document.getElementById('clock').textContent =
                now.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        updateClock();
        setInterval(updateClock, 1000);

        // Auto refresh every 30s
        setTimeout(() => location.reload(), 30000);

        // Pause refresh on interaction
        document.addEventListener('click', () => {
            setTimeout(() => location.reload(), 30000);
        });
    </script>
</body>
</html>
