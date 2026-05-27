<?php
class WAHA {
    private string $baseUrl = 'http://waha:3000';
    private string $apiKey;
    private string $logFile;

    public function __construct() {
        $this->apiKey = 'dec771db080c466da9a621b11e457358';
        $this->logFile = __DIR__ . '/../storage/logs/waha.log';
    }

    private function log(string $chatId, string $text, array $result): void {
        $dir = dirname($this->logFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $resp = !empty($result['error']) ? 'ERROR: ' . $result['error'] : json_encode($result['response']);
        $line = date('Y-m-d H:i:s') . ' | TO: ' . $chatId . ' | MSG: ' . $text . ' | HTTP: ' . $result['status'] . ' | RESP: ' . $resp . PHP_EOL;
        // Força escrita em UTF-8 removendo possíveis caracteres nulos de codificações antigas
        file_put_contents($this->logFile, $line, FILE_APPEND | LOCK_EX);
    }

    public function sendText(string $chatId, string $text): array {
        $result = $this->post('/api/sendText', [
            'session' => 'default',
            'chatId'  => $chatId,
            'text'    => $text,
        ]);
        $this->log($chatId, $text, $result);
        return $result;
    }

    public function sendFile(string $chatId, string $url, string $filename): array {
        return $this->post('/api/sendFile', [
            'session'  => 'default',
            'chatId'   => $chatId,
            'url'      => $url,
            'filename' => $filename,
        ]);
    }

    public function sendLink(string $chatId, string $url, string $title, string $description = ''): array {
        return $this->post('/api/sendLink', [
            'session'     => 'default',
            'chatId'      => $chatId,
            'link'        => $url,
            'title'       => $title,
            'description' => $description,
        ]);
    }

    public function getSessions(): array {
        return $this->get('/api/sessions');
    }

    public function startSession(): array {
        $sessions = $this->getSessions();
        $exists = false;
        if ($sessions['status'] === 200 && is_array($sessions['response'])) {
            foreach ($sessions['response'] as $s) {
                if (($s['name'] ?? '') === 'default') $exists = true;
            }
        }
        if (!$exists) {
            $this->post('/api/sessions', ['name' => 'default']);
        }
        return $this->post('/api/sessions/default/start', []);
    }

    public function getSessionQr(): array {
        return $this->get('/api/default/auth/qr');
    }

    public function getSessionStatus(): array {
        return $this->get('/api/sessions/default');
    }

    private function headers(): array {
        return [
            'Content-Type: application/json',
            "x-api-key: {$this->apiKey}",
        ];
    }

    private function post(string $endpoint, array $data): array {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => $this->headers(),
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $result = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'status' => $httpCode, 
            'response' => json_decode($result, true),
            'error' => $error ?: ($httpCode === 0 ? 'Falha na conexão com WAHA' : null)
        ];
    }

    private function get(string $endpoint): array {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER     => $this->headers(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $httpCode, 'response' => json_decode($result, true)];
    }

    public function isConnected(): bool {
        $session = $this->getSessionStatus();
        if ($session['status'] !== 200 || empty($session['response'])) return false;
        return !empty($session['response']['me']);
    }

    public static function formatPhone(string $phone): string {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) > 0 && substr($phone, 0, 2) !== '55') {
            $phone = '55' . $phone;
        }

        // Regra para o Brasil: Se o número tem 13 dígitos (55 + DDD + 9 + 8 dígitos), 
        // removemos o 9º dígito (posicionado no índice 4) para compatibilidade com o WhatsApp API.
        if (strlen($phone) === 13 && substr($phone, 0, 2) === '55') {
            $phone = substr($phone, 0, 4) . substr($phone, 5);
        }

        return $phone;
    }
}
