<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
Auth::requireRole('admin');

$logFile = __DIR__ . '/../storage/logs/waha.log';

// Ação de limpar log
if (isset($_POST['clear'])) {
    if (file_exists($logFile)) {
        file_put_contents($logFile, '');
    }
    header('Location: waha-logs.php');
    exit;
}

// Função para ler e limpar os logs com segurança
function getSafeLogs($path, $limit = 50) {
    if (!file_exists($path)) return [];
    
    // Lê o conteúdo bruto
    $content = @file_get_contents($path);
    if (!$content) return [];

    // Remove bytes nulos e caracteres de controle que quebram o HTML
    $content = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $content);
    
    // Converte para UTF-8 ignorando caracteres inválidos remanescentes
    $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

    $lines = explode(PHP_EOL, trim($content));
    $lines = array_filter($lines); // Remove linhas vazias
    
    // Retorna as últimas N linhas, invertidas (mais recente primeiro)
    return array_reverse(array_slice($lines, -$limit));
}

$logs = getSafeLogs($logFile);
$title = 'Logs do WhatsApp';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h4><i class="bi bi-whatsapp text-success me-2"></i>Logs do WhatsApp</h4>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>Registros Recentes (Invertido)</span>
        <form method="POST" class="m-0">
            <button type="submit" name="clear" class="btn btn-modern btn-outline btn-sm text-danger" onclick="return confirm('Limpar logs?')">Limpar</button>
        </form>
    </div>
    <div class="card-body p-0" style="max-height: 70vh; overflow-y: auto;">
        <?php if ($logs): ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3" style="width: 160px;">Data/Hora</th>
                            <th>Status</th>
                            <th>Destinatário</th>
                            <th>Mensagem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $line): 
                            // Tenta extrair partes do log para formatar
                            $parts = explode(' | ', $line);
                            $date = $parts[0] ?? '---';
                            $to = str_replace('TO: ', '', $parts[1] ?? '---');
                            $msg = str_replace('MSG: ', '', $parts[2] ?? '---');
                            $http = str_replace('HTTP: ', '', $parts[3] ?? '---');
                            
                            $statusClass = (trim($http) == '201') ? 'text-success' : 'text-danger fw-bold';
                        ?>
                        <tr>
                            <td class="ps-3 text-muted"><?= htmlspecialchars($date) ?></td>
                            <td class="<?= $statusClass ?>"><?= htmlspecialchars($http) ?></td>
                            <td><?= htmlspecialchars($to) ?></td>
                            <td class="text-wrap"><?= htmlspecialchars($msg) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted text-center py-4 mb-0">Nenhum log encontrado.</p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
