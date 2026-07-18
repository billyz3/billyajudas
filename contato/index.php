<?php
declare(strict_types=1);
require_once __DIR__ . '/../_includes/bootstrap.php';
page_head('Contato | Billy Ajudas', 'Fale com o Billy Ajudas pelo WhatsApp para pedir orçamento, suporte ou informações sobre os serviços.');
?>
<main id="conteudo"><section class="page-hero"><p class="eyebrow">Fale com o Billy Ajudas</p><h1>Explique o que você precisa.</h1><p>O atendimento começa pelo WhatsApp. Envie sua necessidade, objetivo e referências para receber orientação inicial.</p><div class="button-row"><a class="button primary" href="<?= whatsapp_link('Olá! Vim pelo site Billy Ajudas e preciso de ajuda com: ') ?>" target="_blank" rel="noopener">Abrir WhatsApp</a><a class="button secondary" href="/categorias/">Ver catálogo</a></div></section><section class="section prose"><h2>Para agilizar o atendimento</h2><ul class="info-list"><li><strong>Explique o objetivo</strong>Diga o que você quer resolver e para quem é o projeto.</li><li><strong>Envie referências</strong>Compartilhe links, imagens ou exemplos que ajudem a entender o resultado esperado.</li><li><strong>Informe o prazo desejado</strong>O prazo final será confirmado após avaliar o escopo e a disponibilidade.</li></ul><?php if (PUBLIC_EMAIL !== ''): ?><h2>E-mail</h2><p><a href="mailto:<?= e(PUBLIC_EMAIL) ?>"><?= e(PUBLIC_EMAIL) ?></a></p><?php endif; ?></section></main>
<?php page_footer(); ?>
