<?php
declare(strict_types=1);
require_once __DIR__ . '/_includes/bootstrap.php';
http_response_code(404);
page_head('Página não encontrada | Billy Ajudas', 'A página solicitada não foi encontrada. Explore os serviços Billy Ajudas ou fale pelo WhatsApp.');
?>
<main id="conteudo" class="page-hero error-page">
    <p class="eyebrow">Erro 404</p>
    <h1>Essa página não existe ou mudou de endereço.</h1>
    <p>Você pode explorar as categorias ou explicar sua necessidade diretamente pelo WhatsApp.</p>
    <div class="button-row">
        <a class="button primary" href="<?= whatsapp_link('Olá! Preciso de ajuda para encontrar um serviço.') ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
        <a class="button secondary" href="/categorias/">Ver categorias</a>
    </div>
</main>
<?php page_footer(); ?>
