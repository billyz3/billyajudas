<?php
declare(strict_types=1);

if (is_file(__DIR__ . '/../config.local.php')) require_once __DIR__ . '/../config.local.php';

if (!defined('SITE_NAME')) define('SITE_NAME', 'Billy Ajudas');
if (!defined('SITE_URL')) define('SITE_URL', 'https://billyajudas.is-local.org');
if (!defined('PUBLIC_EMAIL')) define('PUBLIC_EMAIL', '');
if (!defined('WHATSAPP_NUMBER')) define('WHATSAPP_NUMBER', '5511932184146');

function data_file(string $name): array {
    $file = __DIR__ . '/../storage/data/' . $name . '.json';
    $content = is_file($file) ? file_get_contents($file) : false;
    $data = $content ? json_decode($content, true) : [];
    return is_array($data) ? $data : [];
}

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES, 'UTF-8'); }

function categories(): array { return data_file('categories'); }

function products(): array { return data_file('products'); }

function published_products(): array {
    return array_values(array_filter(products(), static fn(array $product): bool => ($product['status'] ?? '') === 'publicado'));
}

function products_by_category(string $categorySlug): array {
    return array_values(array_filter(
        published_products(),
        static fn(array $product): bool => ($product['categoria_slug'] ?? '') === $categorySlug
    ));
}

function product_by_slugs(string $categorySlug, string $productSlug): ?array {
    foreach (published_products() as $product) {
        if (($product['categoria_slug'] ?? '') === $categorySlug && ($product['slug'] ?? '') === $productSlug) return $product;
    }
    return null;
}

function featured_products(int $limit = 6): array {
    $featured = array_values(array_filter(published_products(), static fn(array $product): bool => !empty($product['featured'])));
    return array_slice($featured, 0, max(0, $limit));
}

function category_by_slug(string $slug): ?array {
    foreach (categories() as $category) if (($category['slug'] ?? '') === $slug) return $category;
    return null;
}

function csp_nonce(): string {
    static $nonce = null;
    if ($nonce === null) $nonce = base64_encode(random_bytes(18));
    return $nonce;
}

function send_public_security_headers(): void {
    if (headers_sent()) return;
    $nonce = csp_nonce();
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; media-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Cross-Origin-Resource-Policy: same-site');
}

function page_head(string $title, string $description, array $schema = []): void {
send_public_security_headers();
$nonce = csp_nonce();
?>
<!doctype html><html lang="pt-BR"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?></title><meta name="description" content="<?= e($description) ?>">
<?php if (http_response_code() >= 400): ?><meta name="robots" content="noindex,follow"><?php endif; ?>
<link rel="canonical" href="<?= e(current_url()) ?>">
<meta property="og:type" content="website"><meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="<?= e(SITE_NAME) ?>"><meta property="og:title" content="<?= e($title) ?>">
<meta property="og:description" content="<?= e($description) ?>"><meta property="og:url" content="<?= e(current_url()) ?>">
<meta name="twitter:card" content="summary"><meta name="twitter:title" content="<?= e($title) ?>"><meta name="twitter:description" content="<?= e($description) ?>">
<meta name="theme-color" content="#0b1020"><link rel="icon" href="/assets/img/favicon.svg" type="image/svg+xml"><link rel="stylesheet" href="/assets/css/site.css"><link rel="stylesheet" href="/assets/css/services.css"><link rel="stylesheet" href="/assets/css/institutional.css"><noscript><link rel="stylesheet" href="/assets/css/no-js.css"></noscript>
<script nonce="<?= e($nonce) ?>" type="application/ld+json"><?= json_encode(['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => SITE_NAME, 'url' => site_url()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<?php if ($schema): ?><script nonce="<?= e($nonce) ?>" type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script><?php endif; ?>
</head><body>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header"><a class="brand" href="/">Billy<span>Ajudas</span></a>
<button class="menu-toggle" aria-expanded="false" aria-controls="main-menu">Menu</button>
<nav id="main-menu" aria-label="Principal"><a href="/#servicos">Serviços</a><a href="/categorias/">Categorias</a><a href="/assinatura/">Assinatura</a><a href="/como-funciona/">Como funciona</a><a class="nav-whatsapp" href="<?= whatsapp_link('Olá! Quero conhecer os serviços.') ?>" target="_blank" rel="noopener">WhatsApp</a></nav></header>
<?php }
function page_footer(): void { ?>
<footer class="site-footer"><div class="footer-grid"><div><a class="brand" href="/">Billy<span>Ajudas</span></a><p>Serviços digitais feitos sob medida para pequenos negócios e pessoas.</p></div><nav aria-label="Institucional"><strong>Institucional</strong><a href="/como-funciona/">Como funciona</a><a href="/contato/">Contato</a><a href="/privacidade/">Privacidade</a></nav><nav aria-label="Contratação"><strong>Contratação</strong><a href="/termos-de-servico/">Termos de serviço</a><a href="/cancelamento-e-reembolso/">Cancelamento e reembolso</a><a href="/categorias/">Catálogo</a></nav></div><p class="footer-note">© <?= date('Y') ?> Billy Ajudas. Atendimento e condições confirmados antes do início de cada serviço.</p></footer><script src="/assets/js/site.js" defer></script></body></html>
<?php }
function site_url(): string {
    $configured = rtrim((string) SITE_URL, '/');
    if ($configured !== '') return $configured . '/';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return 'https://' . preg_replace('/[^a-zA-Z0-9.\-:]/', '', $host) . '/';
}
function current_url(): string {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    return rtrim(site_url(), '/') . '/' . ltrim($path, '/');
}
function whatsapp_link(string $text): string {
    $number = defined('WHATSAPP_NUMBER') ? preg_replace('/\D/', '', WHATSAPP_NUMBER) : '';
    return $number ? 'https://wa.me/' . $number . '?text=' . rawurlencode($text) : 'https://wa.me/?text=' . rawurlencode($text);
}

function service_schema(array $product): array {
    $offers = [];
    if (($product['preco_tipo'] ?? '') === 'fixo' && isset($product['preco_valor']) && is_numeric($product['preco_valor'])) {
        $offers = [
            '@type' => 'Offer',
            'priceCurrency' => 'BRL',
            'price' => number_format((float) $product['preco_valor'], 2, '.', ''),
            'url' => rtrim(site_url(), '/') . ($product['rota'] ?? '/'),
            'availability' => 'https://schema.org/InStock',
        ];
    }
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Service',
        'name' => (string) ($product['nome'] ?? ''),
        'description' => (string) ($product['descricao'] ?? ''),
        'provider' => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => site_url()],
        'url' => rtrim(site_url(), '/') . ($product['rota'] ?? '/'),
        'areaServed' => 'BR',
    ];
    if ($offers) $schema['offers'] = $offers;
    return $schema;
}
