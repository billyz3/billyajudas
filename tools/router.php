<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/'));

if (strpos($path, "\0") !== false || strpos($path, '..') !== false) {
    require $root . '/404.php';
    return true;
}

if (preg_match('#^/(?:_includes|storage|tools)(?:/|$)#i', $path)
    || preg_match('#/(?:config\.local\.php(?:\.example)?|\.gitignore|BRAND_STATUS\.md|HOSTGATOR_DEPLOY\.md|MERCADO_PAGO_INTEGRATION\.md|PROJECT_ARCHITECTURE\.md|README\.md)$#i', $path)) {
    http_response_code(403);
    echo 'Acesso negado.';
    return true;
}

$candidate = $root . str_replace('/', DIRECTORY_SEPARATOR, $path);
if ($path !== '/' && is_file($candidate)) return false;
if (is_dir($candidate) && is_file(rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php')) {
    require rtrim($candidate, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php';
    return true;
}

if ($path === '/') {
    require $root . '/index.php';
    return true;
}

if (preg_match('#^/visual/arte-para-instagram/?$#i', $path)) {
    header('Location: /canva-social/cinco-artes-canva-personalizadas/', true, 301);
    return true;
}

if (preg_match('#^/(visual|canva-social|lojas-online|sites|automacoes|carreira|suporte-digital)/([a-z0-9-]+)/?$#', $path, $matches)) {
    $_GET['category'] = $matches[1];
    $_GET['service'] = $matches[2];
    require $root . '/service.php';
    return true;
}

require $root . '/404.php';
return true;
