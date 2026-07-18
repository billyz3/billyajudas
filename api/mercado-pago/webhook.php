<?php
declare(strict_types=1);

require_once __DIR__ . '/../../_includes/mercado-pago.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

function webhook_response(int $status, string $result): void {
    http_response_code($status);
    echo json_encode(['result' => $result], JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    webhook_response(405, 'method_not_allowed');
}

$rawBody = file_get_contents('php://input');
$payload = $rawBody !== false ? json_decode($rawBody, true) : null;
if (!is_array($payload)) webhook_response(400, 'invalid_json');

$type = (string) ($payload['type'] ?? $_GET['type'] ?? '');
$dataId = (string) ($_GET['data_id'] ?? $payload['data']['id'] ?? '');
$xSignature = (string) ($_SERVER['HTTP_X_SIGNATURE'] ?? '');
$xRequestId = (string) ($_SERVER['HTTP_X_REQUEST_ID'] ?? '');

if ($type !== 'payment') webhook_response(200, 'ignored_event');
if (!preg_match('/^[0-9]+$/', $dataId)) webhook_response(400, 'invalid_payment_id');
if (!mp_signature_is_valid($xSignature, $xRequestId, $dataId)) {
    mp_log('webhook_invalid_signature', ['payment_id' => $dataId, 'request_id' => $xRequestId]);
    webhook_response(401, 'invalid_signature');
}

try {
    $payment = mp_get_payment($dataId);
} catch (Throwable $error) {
    mp_log('webhook_payment_lookup_failed', ['payment_id' => $dataId, 'request_id' => $xRequestId]);
    webhook_response(503, 'lookup_failed');
}

$externalReference = (string) ($payment['external_reference'] ?? '');
$order = mp_load_order($externalReference);
if (!$order) {
    mp_log('webhook_unknown_order', ['payment_id' => $dataId, 'external_reference' => $externalReference]);
    webhook_response(200, 'unknown_order');
}

$amountMatches = abs((float) ($payment['transaction_amount'] ?? -1) - (float) ($order['amount'] ?? -2)) < 0.001;
$currencyMatches = (string) ($payment['currency_id'] ?? '') === (string) ($order['currency'] ?? 'BRL');
$collectorMatches = trim((string) MP_COLLECTOR_ID) === ''
    || (string) ($payment['collector_id'] ?? '') === trim((string) MP_COLLECTOR_ID);
$modeMatches = mp_environment() === 'production' ? !empty($payment['live_mode']) : empty($payment['live_mode']);

if (!$amountMatches || !$currencyMatches || !$collectorMatches || !$modeMatches) {
    $order['status'] = 'review_required';
    $order['payment_id'] = $dataId;
    $order['validation'] = [
        'amount' => $amountMatches,
        'currency' => $currencyMatches,
        'collector' => $collectorMatches,
        'environment' => $modeMatches,
    ];
    mp_store_order($order);
    mp_log('webhook_payment_mismatch', ['payment_id' => $dataId, 'external_reference' => $externalReference]);
    webhook_response(200, 'review_required');
}

$newStatus = mp_normalize_status((string) ($payment['status'] ?? 'pending'));
$currentStatus = (string) ($order['status'] ?? 'created');
if (mp_should_advance_status($currentStatus, $newStatus)) $order['status'] = $newStatus;
$order['payment_id'] = $dataId;
$order['payment_status_detail'] = (string) ($payment['status_detail'] ?? '');
$order['payment_method'] = (string) ($payment['payment_method_id'] ?? '');
$order['last_webhook_at'] = gmdate(DATE_ATOM);

if (!mp_store_order($order)) {
    mp_log('webhook_order_write_failed', ['payment_id' => $dataId, 'external_reference' => $externalReference]);
    webhook_response(503, 'storage_failed');
}

mp_log('webhook_processed', ['payment_id' => $dataId, 'external_reference' => $externalReference, 'status' => $order['status']]);
webhook_response(200, 'ok');
