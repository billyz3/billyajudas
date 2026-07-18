<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!defined('MP_CHECKOUT_ENABLED')) define('MP_CHECKOUT_ENABLED', false);
if (!defined('MP_ENVIRONMENT')) define('MP_ENVIRONMENT', 'test');
if (!defined('MP_PUBLIC_KEY')) define('MP_PUBLIC_KEY', '');
if (!defined('MP_ACCESS_TOKEN')) define('MP_ACCESS_TOKEN', '');
if (!defined('MP_WEBHOOK_SECRET')) define('MP_WEBHOOK_SECRET', '');
if (!defined('MP_COLLECTOR_ID')) define('MP_COLLECTOR_ID', '');

function mp_environment(): string {
    return MP_ENVIRONMENT === 'production' ? 'production' : 'test';
}

function mp_checkout_configured(): bool {
    return MP_CHECKOUT_ENABLED === true && trim((string) MP_ACCESS_TOKEN) !== '';
}

function mp_checkout_available(array $product): bool {
    return mp_checkout_configured()
        && !empty($product['checkout_enabled'])
        && ($product['preco_tipo'] ?? '') === 'fixo'
        && isset($product['preco_valor'])
        && is_numeric($product['preco_valor'])
        && (float) $product['preco_valor'] > 0;
}

function secure_session_start(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_name('billyajudas_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string {
    secure_session_start();
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid(string $token): bool {
    secure_session_start();
    return isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && $token !== ''
        && hash_equals($_SESSION['csrf_token'], $token);
}

function mp_orders_directory(): string {
    return __DIR__ . '/../storage/orders';
}

function mp_logs_directory(): string {
    return __DIR__ . '/../storage/logs';
}

function mp_ensure_private_directory(string $directory): bool {
    return is_dir($directory) || mkdir($directory, 0750, true);
}

function mp_external_reference(): string {
    return 'ba_' . gmdate('Ymd_His') . '_' . bin2hex(random_bytes(8));
}

function mp_order_path(string $externalReference): ?string {
    if (!preg_match('/^ba_[0-9]{8}_[0-9]{6}_[a-f0-9]{16}$/', $externalReference)) return null;
    return mp_orders_directory() . '/' . $externalReference . '.json';
}

function mp_store_order(array $order): bool {
    $reference = (string) ($order['external_reference'] ?? '');
    $path = mp_order_path($reference);
    if (!$path || !mp_ensure_private_directory(mp_orders_directory())) return false;
    $order['updated_at'] = gmdate(DATE_ATOM);
    $json = json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;
    $temporary = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
    if (file_put_contents($temporary, $json . PHP_EOL, LOCK_EX) === false) return false;
    if (!rename($temporary, $path)) {
        @unlink($temporary);
        return false;
    }
    @chmod($path, 0640);
    return true;
}

function mp_load_order(string $externalReference): ?array {
    $path = mp_order_path($externalReference);
    if (!$path || !is_file($path)) return null;
    $content = file_get_contents($path);
    $order = $content !== false ? json_decode($content, true) : null;
    return is_array($order) ? $order : null;
}

function mp_log(string $event, array $context = []): void {
    $allowed = array_intersect_key($context, array_flip([
        'external_reference', 'payment_id', 'status', 'http_status', 'reason', 'request_id',
    ]));
    $record = ['time' => gmdate(DATE_ATOM), 'event' => $event, 'context' => $allowed];
    if (!mp_ensure_private_directory(mp_logs_directory())) return;
    file_put_contents(
        mp_logs_directory() . '/mercado-pago.log',
        json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function mp_api_request(string $method, string $path, ?array $body = null, array $headers = []): array {
    if (!function_exists('curl_init')) throw new RuntimeException('Extensão cURL indisponível no servidor.');
    if (!mp_checkout_configured()) throw new RuntimeException('Mercado Pago ainda não está configurado.');
    $url = 'https://api.mercadopago.com' . $path;
    $requestHeaders = array_merge([
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Accept: application/json',
        'Content-Type: application/json',
    ], $headers);
    $curl = curl_init($url);
    if ($curl === false) throw new RuntimeException('Não foi possível iniciar a conexão.');
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => strtoupper($method),
        CURLOPT_HTTPHEADER => $requestHeaders,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 18,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_FOLLOWLOCATION => false,
    ];
    if ($body !== null) {
        $encoded = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) throw new RuntimeException('Não foi possível preparar a requisição.');
        $options[CURLOPT_POSTFIELDS] = $encoded;
    }
    curl_setopt_array($curl, $options);
    $responseBody = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    if ($responseBody === false || $error !== '') throw new RuntimeException('Falha de comunicação com o meio de pagamento.');
    $decoded = json_decode((string) $responseBody, true);
    if (!is_array($decoded)) throw new RuntimeException('Resposta inválida do meio de pagamento.');
    if ($status < 200 || $status >= 300) {
        mp_log('api_error', ['http_status' => $status, 'reason' => (string) ($decoded['message'] ?? 'unknown')]);
        throw new RuntimeException('O meio de pagamento não aceitou a solicitação.');
    }
    return ['status' => $status, 'data' => $decoded];
}

function mp_create_preference(array $product, string $externalReference): array {
    $baseUrl = rtrim(site_url(), '/');
    $payload = [
        'items' => [[
            'id' => (string) $product['slug'],
            'title' => (string) $product['nome'],
            'description' => (string) $product['resumo'],
            'quantity' => 1,
            'currency_id' => 'BRL',
            'unit_price' => round((float) $product['preco_valor'], 2),
        ]],
        'external_reference' => $externalReference,
        'back_urls' => [
            'success' => $baseUrl . '/pagamento/sucesso/',
            'pending' => $baseUrl . '/pagamento/pendente/',
            'failure' => $baseUrl . '/pagamento/falha/',
        ],
        'auto_return' => 'approved',
        'notification_url' => $baseUrl . '/api/mercado-pago/webhook.php',
        'statement_descriptor' => 'BILLY AJUDAS',
        'metadata' => ['service_slug' => (string) $product['slug']],
    ];
    $idempotencyKey = hash('sha256', $externalReference);
    return mp_api_request('POST', '/checkout/preferences', $payload, ['X-Idempotency-Key: ' . $idempotencyKey])['data'];
}

function mp_get_payment(string $paymentId): array {
    if (!preg_match('/^[0-9]+$/', $paymentId)) throw new RuntimeException('Identificador de pagamento inválido.');
    return mp_api_request('GET', '/v1/payments/' . rawurlencode($paymentId))['data'];
}

function mp_signature_is_valid(string $xSignature, string $xRequestId, string $dataId): bool {
    $secret = trim((string) MP_WEBHOOK_SECRET);
    if ($secret === '' || $xSignature === '') return false;
    $signatureParts = [];
    foreach (explode(',', $xSignature) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key !== '' && $value !== '') $signatureParts[$key] = $value;
    }
    $timestamp = $signatureParts['ts'] ?? '';
    $receivedHash = $signatureParts['v1'] ?? '';
    if ($timestamp === '' || !preg_match('/^[a-f0-9]{64}$/i', $receivedHash)) return false;
    $manifestParts = [];
    if ($dataId !== '') $manifestParts[] = 'id:' . strtolower($dataId);
    if ($xRequestId !== '') $manifestParts[] = 'request-id:' . $xRequestId;
    $manifestParts[] = 'ts:' . $timestamp;
    $manifest = implode(';', $manifestParts) . ';';
    $calculated = hash_hmac('sha256', $manifest, $secret);
    return hash_equals(strtolower($receivedHash), $calculated);
}

function mp_normalize_status(string $status): string {
    switch ($status) {
        case 'approved': return 'approved';
        case 'rejected': return 'rejected';
        case 'cancelled': return 'cancelled';
        case 'refunded':
        case 'partially_refunded': return 'refunded';
        case 'charged_back': return 'charged_back';
        default: return 'pending';
    }
}

function mp_status_rank(string $status): int {
    $ranks = [
        'created' => 0,
        'pending' => 10,
        'rejected' => 20,
        'cancelled' => 20,
        'approved' => 30,
        'refunded' => 40,
        'charged_back' => 50,
        'review_required' => 100,
    ];
    return $ranks[$status] ?? 0;
}

function mp_should_advance_status(string $currentStatus, string $newStatus): bool {
    if ($currentStatus === 'review_required') return $newStatus === 'review_required';
    return mp_status_rank($newStatus) >= mp_status_rank($currentStatus);
}
