<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/waha.php';
Auth::requireRole('admin');

$waha = new WAHA();
$connected = $waha->isConnected();

$sessionInfo = $waha->getSessionStatus();
$sessionStatus = $sessionInfo['status'] === 200 ? ($sessionInfo['response']['status'] ?? '') : '';
$showQr = $sessionStatus === 'SCAN_QR_CODE' || $sessionStatus === 'STARTING';
$error  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['connect'])) {
    $result = $waha->startSession();
    if ($result['status'] === 200 || ($result['response']['status'] ?? '') === 'STARTING') {
        header('Location: whatsapp.php');
        exit;
    } else {
        $error = 'Erro ao iniciar sessão. WAHA API está rodando?';
    }
}

$title = 'WhatsApp';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-whatsapp text-success me-2"></i>WhatsApp</h4>
</div>

<div class="row g-3">
    <div class="col-md-6 mx-auto">
        <div class="card">
            <div class="card-header d-flex align-items-center gap-2">
                <span>Conexão WhatsApp</span>
                <?php if ($connected): ?>
                    <span class="badge bg-success ms-auto"><i class="bi bi-check-circle me-1"></i>Conectado</span>
                <?php elseif ($showQr): ?>
                    <span class="badge bg-warning text-dark ms-auto"><i class="bi bi-hourglass me-1"></i>Aguardando QR Code</span>
                <?php else: ?>
                    <span class="badge bg-secondary ms-auto"><i class="bi bi-x-circle me-1"></i>Desconectado</span>
                <?php endif; ?>
            </div>
            <div class="card-body text-center">
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><?= $error ?></div>
                <?php endif; ?>

                <?php if ($connected): ?>
                    <div class="py-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        <p class="mt-3 mb-0">WhatsApp conectado com sucesso!</p>
                        <small class="text-muted">As notificações serão enviadas automaticamente.</small>
                        <div class="mt-3">
                            <a href="waha-logs.php" class="btn btn-modern btn-outline btn-sm"><i class="bi bi-journal-text me-1"></i>Ver logs</a>
                        </div>
                    </div>
                <?php elseif ($sessionStatus === 'SCAN_QR_CODE'): ?>
                    <div class="py-3">
                        <img src="<?= BASE_URL ?>/api/whatsapp-qr.php?t=<?= time() ?>" alt="QR Code WhatsApp" class="img-fluid" style="max-width: 300px;" id="qrCode">
                        <p class="mt-3 mb-0">Escaneie o QR Code com o WhatsApp</p>
                        <small class="text-muted">Abra o WhatsApp > Menus > WhatsApp Web</small>
                        <div class="mt-3">
                            <a href="whatsapp.php" class="btn btn-modern btn-outline btn-sm">Verificar status</a>
                        </div>
                    </div>
                <?php elseif ($sessionStatus === 'STARTING'): ?>
                    <div class="py-4">
                        <div class="spinner-border text-success mb-3" role="status"></div>
                        <p class="mb-0">Iniciando sessão do WhatsApp...</p>
                        <small class="text-muted">A página será atualizada automaticamente.</small>
                    </div>
                    <meta http-equiv="refresh" content="5">
                <?php else: ?>
                    <div class="py-4">
                        <i class="bi bi-qr-code text-muted" style="font-size: 4rem;"></i>
                        <p class="mt-3">Clique abaixo para gerar o QR Code e conectar seu WhatsApp</p>
                        <form method="POST">
                            <button type="submit" name="connect" class="btn btn-modern btn-success">
                                <i class="bi bi-whatsapp me-1"></i>Conectar WhatsApp
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Instruções</div>
            <div class="card-body small">
                <ol class="mb-0 ps-3">
                    <li>Clique em <strong>"Conectar WhatsApp"</strong> para gerar o QR Code</li>
                    <li>Abra o WhatsApp no seu celular</li>
                    <li>Toque em <strong>Menu</strong> (3 pontinhos) > <strong>WhatsApp Web</strong></li>
                    <li>Escaneie o QR Code exibido na tela</li>
                    <li>Pronto! Notificações serão enviadas automaticamente</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
