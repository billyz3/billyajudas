<?php
require_once __DIR__ . '/bootstrap.php';
$slug = $slug ?? '';
$category = category_by_slug($slug);
if (!$category) { http_response_code(404); page_head('Categoria não encontrada | Billy Ajudas', 'A categoria solicitada não foi encontrada.'); echo '<main id="conteudo" class="page-hero"><p class="eyebrow">Erro 404</p><h1>Categoria não encontrada.</h1><a class="button primary" href="/categorias/">Ver categorias</a></main>'; page_footer(); exit; }
$categoryProducts = products_by_category($slug);
page_head($category['nome'] . ' | Serviços digitais | Billy Ajudas', $category['descricao']);
?>
<main id="conteudo"><section class="page-hero category-intro"><p class="eyebrow">Serviços</p><h1><?= e($category['nome']) ?></h1><p><?= e($category['descricao']) ?></p><span class="price"><?= e($category['preco_inicial']) ?></span><div class="button-row"><a class="button primary" href="<?= whatsapp_link('Olá! Quero saber mais sobre ' . $category['nome'] . '.') ?>" target="_blank" rel="noopener">Falar no WhatsApp</a><a class="button secondary" href="/categorias/">Ver todas as categorias</a></div></section>
<section class="section"><div class="section-head"><div><p class="eyebrow">Catálogo</p><h2>Escolha o serviço mais próximo da sua necessidade</h2></div></div>
<?php if ($categoryProducts): ?><div class="service-grid"><?php foreach ($categoryProducts as $product): ?>
<a class="service-card" href="<?= e($product['rota']) ?>"><span class="service-card-icon" aria-hidden="true"><?= e($product['icone'] ?? $category['icone']) ?></span><h3><?= e($product['nome']) ?></h3><p><?= e($product['resumo']) ?></p><span class="price"><?= e($product['preco']) ?></span><span class="card-link">Ver detalhes →</span></a>
<?php endforeach; ?></div><?php else: ?><p>Os serviços desta categoria estão sendo organizados. Fale conosco para receber orientação.</p><?php endif; ?>
<div class="contact-panel"><strong>Não encontrou exatamente o que precisa?</strong><p>Conte sua situação. Podemos adaptar um serviço ou montar um escopo sob medida.</p><a class="button primary" href="<?= whatsapp_link('Olá! Preciso de orientação sobre ' . $category['nome'] . '.') ?>" target="_blank" rel="noopener">Pedir orientação</a></div></section></main>
<?php page_footer(); ?>
