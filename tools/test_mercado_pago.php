<?php
declare(strict_types=1);

define('MP_CHECKOUT_ENABLED', true);
define('MP_ENVIRONMENT', 'test');
define('MP_ACCESS_TOKEN', 'unit-test-token');
define('MP_WEBHOOK_SECRET', 'unit-test-secret');

require_once __DIR__ . '/../_includes/mercado-pago.php';

$failures = [];

function expect_true(bool $condition, string $message): void {
    global $failures;
    if (!$condition) $failures[] = $message;
}

expect_true(mp_checkout_configured(), 'checkout deveria estar configurado no ambiente unitário');

$fixedProduct = [
    'checkout_enabled' => true,
    'preco_tipo' => 'fixo',
    'preco_valor' => 49.90,
];
expect_true(mp_checkout_available($fixedProduct), 'serviço fixo deveria ser elegível');
expect_true(!mp_checkout_available(array_merge($fixedProduct, ['checkout_enabled' => false])), 'serviço desabilitado não pode comprar');
expect_true(!mp_checkout_available(array_merge($fixedProduct, ['preco_tipo' => 'a_partir_de'])), 'preço inicial não pode comprar');
expect_true(!mp_checkout_available(array_merge($fixedProduct, ['preco_valor' => 0])), 'preço zero não pode comprar');

$token = csrf_token();
expect_true((bool) preg_match('/^[a-f0-9]{64}$/', $token), 'token CSRF deve ter 64 caracteres hexadecimais');
expect_true(csrf_is_valid($token), 'token CSRF emitido deveria ser válido');
expect_true(!csrf_is_valid('token-incorreto'), 'token CSRF incorreto deveria ser rejeitado');

$externalReference = mp_external_reference();
expect_true((bool) preg_match('/^ba_[0-9]{8}_[0-9]{6}_[a-f0-9]{16}$/', $externalReference), 'referência externa fora do padrão');
expect_true(mp_order_path($externalReference) !== null, 'referência externa válida deveria gerar caminho');
expect_true(mp_order_path('../config.local.php') === null, 'travessia de diretório deveria ser rejeitada');
expect_true(mp_order_path('ba_20260718_120000_ABCDEF0123456789') === null, 'hexadecimal maiúsculo deveria ser rejeitado');

$testOrder = [
    'external_reference' => $externalReference,
    'status' => 'created',
    'amount' => 49.90,
    'currency' => 'BRL',
];
expect_true(mp_store_order($testOrder), 'pedido unitário deveria ser persistido');
$loadedOrder = mp_load_order($externalReference);
expect_true(is_array($loadedOrder), 'pedido unitário deveria ser carregado');
expect_true(($loadedOrder['external_reference'] ?? '') === $externalReference, 'pedido carregado perdeu a referência');
expect_true(abs((float) ($loadedOrder['amount'] ?? 0) - 49.90) < 0.001, 'pedido carregado perdeu o valor');
$testOrderPath = mp_order_path($externalReference);
if ($testOrderPath && is_file($testOrderPath)) unlink($testOrderPath);

expect_true(mp_normalize_status('approved') === 'approved', 'approved deveria ser preservado');
expect_true(mp_normalize_status('partially_refunded') === 'refunded', 'partially_refunded deveria normalizar para refunded');
expect_true(mp_normalize_status('in_process') === 'pending', 'status intermediário deveria normalizar para pending');
expect_true(mp_should_advance_status('created', 'pending'), 'created deveria avançar para pending');
expect_true(mp_should_advance_status('pending', 'approved'), 'pending deveria avançar para approved');
expect_true(!mp_should_advance_status('approved', 'pending'), 'approved não pode regredir para pending');
expect_true(mp_should_advance_status('approved', 'refunded'), 'approved deveria avançar para refunded');
expect_true(!mp_should_advance_status('review_required', 'approved'), 'review_required exige revisão manual');

$dataId = '123456';
$requestId = 'request-unit';
$timestamp = '1700000000';
$manifest = 'id:' . $dataId . ';request-id:' . $requestId . ';ts:' . $timestamp . ';';
$hash = hash_hmac('sha256', $manifest, 'unit-test-secret');
$signature = 'ts=' . $timestamp . ',v1=' . $hash;
expect_true(mp_signature_is_valid($signature, $requestId, $dataId), 'assinatura HMAC correta deveria ser aceita');
expect_true(!mp_signature_is_valid($signature, 'request-tampered', $dataId), 'request id alterado deveria invalidar assinatura');
expect_true(!mp_signature_is_valid('ts=' . $timestamp . ',v1=' . str_repeat('0', 64), $requestId, $dataId), 'hash alterado deveria ser rejeitado');
expect_true(!mp_signature_is_valid('', $requestId, $dataId), 'assinatura vazia deveria ser rejeitada');

if ($failures) {
    fwrite(STDERR, "TESTE MERCADO PAGO FALHOU\n");
    foreach ($failures as $failure) fwrite(STDERR, '- ' . $failure . PHP_EOL);
    exit(1);
}

echo "TESTE MERCADO PAGO OK\n";
echo "- elegibilidade de checkout: protegida\n";
echo "- CSRF: validado\n";
echo "- referências e armazenamento: validados\n";
echo "- estados: progressão monotônica validada\n";
echo "- assinatura HMAC: validada\n";
