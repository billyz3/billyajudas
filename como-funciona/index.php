<?php
declare(strict_types=1);
require_once __DIR__ . '/../_includes/bootstrap.php';
page_head('Como funciona | Billy Ajudas', 'Entenda como pedir, aprovar, acompanhar e receber um serviço digital do Billy Ajudas.');
?>
<main id="conteudo">
  <section class="page-hero"><p class="eyebrow">Processo transparente</p><h1>Da sua necessidade à entrega, sem complicação.</h1><p>Antes de começar, alinhamos escopo, materiais, valor, prazo, revisões e formato de entrega.</p><div class="button-row"><a class="button primary" href="<?= whatsapp_link('Olá! Quero explicar o serviço que preciso.') ?>" target="_blank" rel="noopener">Começar pelo WhatsApp</a><a class="button secondary" href="/categorias/">Ver serviços</a></div></section>
  <section class="section"><div class="steps"><article class="step"><strong>1. Conte sua necessidade</strong><p>Envie objetivo, referências e contexto. Para alguns serviços, também pediremos textos, fotos, logotipo ou acessos específicos.</p></article><article class="step"><strong>2. Aprove a proposta</strong><p>Você recebe o escopo, o valor final, o prazo estimado, as entregas e as condições de revisão antes do início.</p></article><article class="step"><strong>3. Acompanhe e receba</strong><p>Produzimos o combinado e entregamos pelos formatos definidos na proposta. Alterações fora do escopo são avaliadas à parte.</p></article></div></section>
  <section class="section prose"><h2>Serviços com valor “a partir de”</h2><p>O preço exibido é uma referência inicial. O valor final depende da complexidade, quantidade de peças, integrações, conteúdo fornecido e prazo solicitado. Nada começa sem sua confirmação.</p><h2>Serviços com preço fixo</h2><p>Quando a página mostrar preço final e o botão Mercado Pago estiver habilitado, a contratação poderá ser feita pelo checkout. A confirmação real do pagamento ocorre no servidor; a tela de retorno, sozinha, não comprova pagamento.</p><h2>Entrega e suporte</h2><p>O formato de entrega varia entre arquivos digitais, links editáveis, configuração técnica, orientação ou atendimento. Guarde a proposta e as mensagens de confirmação do escopo.</p></section>
</main>
<?php page_footer(); ?>
