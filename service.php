<?php
declare(strict_types=1);
require_once __DIR__ . '/_includes/mercado-pago.php';

$categorySlug = strtolower((string) ($_GET['category'] ?? ''));
$serviceSlug = strtolower((string) ($_GET['service'] ?? ''));
$product = product_by_slugs($categorySlug, $serviceSlug);
$category = category_by_slug($categorySlug);

if (!$product || !$category) {
    http_response_code(404);
    page_head('Serviço não encontrado | Billy Ajudas', 'O serviço solicitado não foi encontrado no catálogo Billy Ajudas.');
    ?>
    <main id="conteudo" class="page-hero error-page">
        <p class="eyebrow">Erro 404</p>
        <h1>Este serviço não está disponível.</h1>
        <p>Explore o catálogo ou fale conosco para encontrar a ajuda certa.</p>
        <div class="button-row">
            <a class="button primary" href="<?= whatsapp_link('Olá! Preciso de ajuda para encontrar um serviço.') ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
            <a class="button secondary" href="/categorias/">Ver categorias</a>
        </div>
    </main>
    <?php page_footer(); exit;
}

$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
if ($requestPath === '/service.php') {
    header('Location: ' . $product['rota'], true, 301);
    exit;
}

$title = $product['seo_title'] ?? ($product['nome'] . ' | Billy Ajudas');
$description = $product['seo_description'] ?? $product['descricao'];
$message = $product['whatsapp_message'] ?? ('Olá! Quero saber mais sobre ' . $product['nome'] . '.');
$deliverables = is_array($product['entregas'] ?? null) ? $product['entregas'] : [];
$options = is_array($product['opcoes'] ?? null) ? $product['opcoes'] : [];
$related = array_values(array_filter(
    products_by_category($categorySlug),
    static fn(array $item): bool => ($item['slug'] ?? '') !== ($product['slug'] ?? '')
));

page_head($title, $description, service_schema($product, $category), (string) $product['rota']);
?>
<main id="conteudo">
    <nav class="breadcrumbs" aria-label="Navegação estrutural">
        <a href="/">Início</a><span aria-hidden="true">/</span>
        <a href="<?= e($category['rota']) ?>"><?= e($category['nome']) ?></a><span aria-hidden="true">/</span>
        <span aria-current="page"><?= e($product['nome']) ?></span>
    </nav>

    <section class="page-hero service-hero">
        <div class="service-icon tema-<?= e($category['tema']) ?>" aria-hidden="true"><?= e($product['icone'] ?? $category['icone']) ?></div>
        <p class="eyebrow"><?= e($category['nome']) ?></p>
        <h1><?= e($product['nome']) ?></h1>
        <p><?= e($product['descricao']) ?></p>
        <div class="service-meta">
            <span class="price"><?= e($product['preco']) ?></span>
            <?php if (!empty($product['prazo'])): ?><span><?= e($product['prazo']) ?></span><?php endif; ?>
        </div>
        <div class="button-row">
            <a class="button primary" href="<?= whatsapp_link($message) ?>" target="_blank" rel="noopener">Pedir pelo WhatsApp</a>
            <?php if (mp_checkout_available($product)): ?>
            <form class="inline-form" action="/checkout/create.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
                <input type="hidden" name="service" value="<?= e($serviceSlug) ?>">
                <button class="button payment" type="submit">Comprar com Mercado Pago</button>
            </form>
            <?php endif; ?>
            <a class="button secondary" href="<?= e($category['rota']) ?>">Outros serviços</a>
        </div>
        <?php if (!empty($product['observacao_preco'])): ?><p class="fine-print"><?= e($product['observacao_preco']) ?></p><?php endif; ?>
    </section>

    <section class="section service-layout">
        <div>
            <p class="eyebrow">O que você recebe</p>
            <h2>Entrega clara, combinada antes de começar</h2>
            <?php if ($deliverables): ?>
                <ul class="deliverables">
                    <?php foreach ($deliverables as $item): ?><li><?= e((string) $item) ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <aside class="contact-panel">
            <strong>Antes de contratar</strong>
            <p>Conte sua necessidade no WhatsApp. Confirmamos escopo, material necessário, prazo e valor antes de iniciar.</p>
            <a class="button primary" href="<?= whatsapp_link($message) ?>" target="_blank" rel="noopener">Conversar agora</a>
        </aside>
    </section>

    <?php if ($options): ?>
    <section class="section">
        <p class="eyebrow">Personalização</p>
        <h2>Opções disponíveis</h2>
        <div class="chips"><?php foreach ($options as $option): ?><span><?= e((string) $option) ?></span><?php endforeach; ?></div>
    </section>
    <?php endif; ?>

    <section class="section">
        <p class="eyebrow">Como funciona</p>
        <h2>Três passos, sem complicação</h2>
        <div class="steps">
            <article class="step"><strong>1. Explique o que precisa</strong><p>Envie contexto, referências e objetivo pelo WhatsApp.</p></article>
            <article class="step"><strong>2. Confirme a proposta</strong><p>Você recebe escopo, valor e prazo compatíveis com a demanda.</p></article>
            <article class="step"><strong>3. Acompanhe a entrega</strong><p>O serviço é produzido conforme o combinado e entregue no formato definido.</p></article>
        </div>
    </section>

    <section class="section faq">
        <p class="eyebrow">Dúvidas frequentes</p>
        <h2>Antes de pedir</h2>
        <details><summary>O valor mostrado é final?</summary><p><?= e($product['observacao_preco'] ?? 'O valor é confirmado antes do início, conforme o escopo escolhido.') ?></p></details>
        <details><summary>Como recebo o serviço?</summary><p>O formato de entrega depende do serviço e será registrado na proposta enviada pelo WhatsApp.</p></details>
        <details><summary>Posso pedir algo personalizado?</summary><p>Sim. Explique sua necessidade para avaliarmos se cabe neste serviço ou em um pacote sob medida.</p></details>
        <p class="fine-print">Ao contratar, você concorda com os <a href="/termos-de-servico/">Termos de serviço</a> e com a <a href="/cancelamento-e-reembolso/">política de cancelamento e reembolso</a>.</p>
    </section>

    <?php if ($related): ?>
    <section class="section">
        <div class="section-head"><div><p class="eyebrow">Veja também</p><h2>Outros serviços da categoria</h2></div></div>
        <div class="service-grid">
            <?php foreach (array_slice($related, 0, 3) as $item): ?>
            <a class="service-card" href="<?= e($item['rota']) ?>">
                <span class="service-card-icon" aria-hidden="true"><?= e($item['icone'] ?? $category['icone']) ?></span>
                <h3><?= e($item['nome']) ?></h3><p><?= e($item['resumo']) ?></p><span class="price"><?= e($item['preco']) ?></span><span class="card-link">Ver detalhes →</span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>
<?php page_footer(); ?>
