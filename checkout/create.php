<?php
declare(strict_types=1);

require_once __DIR__ . '/../_includes/mercado-pago.php';

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    exit('Método não permitido.');
}

$origin = rtrim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''), '/');
if ($origin !== '' && $origin !== rtrim(site_url(), '/')) {
    http_response_code(403);
    exit('Origem não permitida.');
}

$token = (string) ($_POST['csrf_token'] ?? '');
if (!csrf_is_valid($token)) {
    http_response_code(403);
    exit('Sessão expirada. Volte ao serviço e tente novamente.');
}

$now = time();
$lastAttempt = isset($_SESSION['last_checkout_attempt']) ? (int) $_SESSION['last_checkout_attempt'] : 0;
if ($lastAttempt > 0 && ($now - $lastAttempt) < 5) {
    header('Retry-After: 5');
    http_response_code(429);
    exit('Aguarde alguns segundos antes de tentar novamente.');
}
$_SESSION['last_checkout_attempt'] = $now;

$categorySlug = strtolower((string) ($_POST['category'] ?? ''));
$serviceSlug = strtolower((string) ($_POST['service'] ?? ''));
if (!preg_match('/^[a-z0-9-]+$/', $categorySlug) || !preg_match('/^[a-z0-9-]+$/', $serviceSlug)) {
    http_response_code(422);
    exit('Serviço inválido.');
}

$product = product_by_slugs($categorySlug, $serviceSlug);
if (!$product || !mp_checkout_available($product)) {
    http_response_code(409);
    page_head('Compra indisponível | Billy Ajudas', 'Este serviço ainda não está disponível para compra direta.');
    ?>
    <main id="conteudo" class="page-hero error-page">
        <p class="eyebrow">Compra indisponível</p>
        <h1>Vamos confirmar os detalhes primeiro.</h1>
        <p>Este serviço ainda precisa ser combinado pelo WhatsApp antes do pagamento.</p>
        <div class="button-row"><a class="button primary" href="<?= whatsapp_link('Olá! Quero confirmar os detalhes antes de pagar por ' . ($product['nome'] ?? 'um serviço') . '.') ?>" target="_blank" rel="noopener">Falar no WhatsApp</a><a class="button secondary" href="<?= e($product['rota'] ?? '/categorias/') ?>">Voltar</a></div>
    </main>
    <?php page_footer(); exit;
}

$externalReference = mp_external_reference();
$order = [
    'external_reference' => $externalReference,
    'service_slug' => $serviceSlug,
    'category_slug' => $categorySlug,
    'service_name' => (string) $product['nome'],
    'amount' => round((float) $product['preco_valor'], 2),
    'currency' => 'BRL',
    'environment' => mp_environment(),
    'status' => 'created',
    'created_at' => gmdate(DATE_ATOM),
];

if (!mp_store_order($order)) {
    mp_log('order_storage_failed', ['external_reference' => $externalReference]);
    http_response_code(500);
    exit('Não foi possível iniciar a compra. Tente novamente mais tarde.');
}

try {
    $preference = mp_create_preference($product, $externalReference);
    $order['preference_id'] = (string) ($preference['id'] ?? '');
    $order['status'] = 'pending';
    if ($order['preference_id'] === '' || !mp_store_order($order)) throw new RuntimeException('Preferência incompleta.');
    $redirectUrl = mp_environment() === 'production'
        ? (string) ($preference['init_point'] ?? '')
        : (string) ($preference['sandbox_init_point'] ?? $preference['init_point'] ?? '');
    if (!filter_var($redirectUrl, FILTER_VALIDATE_URL) || parse_url($redirectUrl, PHP_URL_SCHEME) !== 'https') {
        throw new RuntimeException('URL de checkout inválida.');
    }
    mp_log('preference_created', ['external_reference' => $externalReference, 'status' => 'pending']);
    header('Location: ' . $redirectUrl, true, 303);
    exit;
} catch (Throwable $error) {
    $order['status'] = 'checkout_error';
    mp_store_order($order);
    mp_log('preference_failed', ['external_reference' => $externalReference, 'reason' => 'creation_failed']);
    http_response_code(502);
    page_head('Pagamento temporariamente indisponível | Billy Ajudas', 'Não foi possível abrir o pagamento agora.');
    ?>
    <main id="conteudo" class="page-hero error-page">
        <p class="eyebrow">Tente novamente</p>
        <h1>O pagamento não abriu desta vez.</h1>
        <p>Nenhuma cobrança foi confirmada por esta tentativa. Você pode voltar ao serviço ou falar conosco.</p>
        <div class="button-row"><a class="button primary" href="<?= whatsapp_link('Olá! Tive dificuldade para abrir o pagamento de ' . $product['nome'] . '.') ?>" target="_blank" rel="noopener">Pedir ajuda</a><a class="button secondary" href="<?= e($product['rota']) ?>">Voltar ao serviço</a></div>
    </main>
    <?php page_footer();
}
