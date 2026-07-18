<?php
require_once __DIR__ . '/bootstrap.php';
$slug = $slug ?? '';
$category = category_by_slug($slug);
if (!$category) { http_response_code(404); page_head('Categoria não encontrada | Billy Ajudas', 'A categoria solicitada não foi encontrada.'); echo '<main id="conteudo" class="page-hero"><p class="eyebrow">Erro 404</p><h1>Categoria não encontrada.</h1><a class="button primary" href="/categorias/">Ver categorias</a></main>'; page_footer(); exit; }
page_head($category['nome'] . ' | Serviços digitais | Billy Ajudas', $category['descricao']);
?>
<main id="conteudo"><section class="page-hero category-intro"><p class="eyebrow">Serviços</p><h1><?= e($category['nome']) ?></h1><p><?= e($category['descricao']) ?></p><span class="price"><?= e($category['preco_inicial']) ?></span><div class="button-row"><a class="button primary" href="<?= whatsapp_link('Olá! Quero saber mais sobre ' . $category['nome'] . '.') ?>" target="_blank" rel="noopener">Falar no WhatsApp</a><a class="button secondary" href="/categorias/">Ver todas as categorias</a></div></section>
<section class="section"><h2>Como podemos ajudar</h2><p>Conte o que você precisa. O escopo, prazo e valor são definidos conforme a necessidade do seu projeto.</p><div class="contact-panel"><strong>Atendimento direto e sem compromisso</strong><p>Receba uma orientação inicial pelo WhatsApp para entender o serviço mais adequado.</p><a class="button primary" href="<?= whatsapp_link('Olá! Quero um orçamento para ' . $category['nome'] . '.') ?>" target="_blank" rel="noopener">Pedir orçamento</a></div></section></main>
<?php page_footer(); ?>
