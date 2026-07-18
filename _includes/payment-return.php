<?php
declare(strict_types=1);

require_once __DIR__ . '/mercado-pago.php';

header('Cache-Control: no-store, private, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

$returnState = $returnState ?? 'pending';
$externalReference = (string) ($_GET['external_reference'] ?? '');
$order = mp_load_order($externalReference);
$confirmedStatus = (string) ($order['status'] ?? '');
$serviceName = (string) ($order['service_name'] ?? 'seu serviço');

$content = [
    'success' => [
        'eyebrow' => 'Retorno do pagamento',
        'title' => $confirmedStatus === 'approved' ? 'Pagamento confirmado.' : 'Pagamento recebido para conferência.',
        'text' => $confirmedStatus === 'approved'
            ? 'A confirmação segura já chegou ao Billy Ajudas. Entre em contato para alinhar os dados necessários para a entrega.'
            : 'O Mercado Pago retornou ao site, mas a confirmação segura ainda depende da notificação do servidor. Não faça um novo pagamento agora.',
    ],
    'pending' => [
        'eyebrow' => 'Pagamento pendente',
        'title' => 'O pagamento ainda está em processamento.',
        'text' => 'Alguns meios de pagamento levam mais tempo para confirmar. A atualização será recebida automaticamente; guarde o comprovante.',
    ],
    'failure' => [
        'eyebrow' => 'Pagamento não concluído',
        'title' => 'A compra não foi confirmada.',
        'text' => 'Você pode voltar ao serviço e tentar novamente ou falar conosco. Nenhum serviço será iniciado sem confirmação segura do pagamento.',
    ],
];
$view = $content[$returnState] ?? $content['pending'];
page_head($view['title'] . ' | Billy Ajudas', $view['text']);
?>
<main id="conteudo" class="page-hero payment-return payment-<?= e($returnState) ?>">
    <p class="eyebrow"><?= e($view['eyebrow']) ?></p>
    <h1><?= e($view['title']) ?></h1>
    <p><?= e($view['text']) ?></p>
    <?php if ($order): ?><div class="payment-summary"><strong>Serviço</strong><span><?= e($serviceName) ?></span><strong>Situação registrada</strong><span><?= e($confirmedStatus !== '' ? $confirmedStatus : 'aguardando') ?></span></div><?php endif; ?>
    <div class="button-row">
        <a class="button primary" href="<?= whatsapp_link('Olá! Quero confirmar a situação do pagamento de ' . $serviceName . '.') ?>" target="_blank" rel="noopener">Falar no WhatsApp</a>
        <a class="button secondary" href="/categorias/">Voltar aos serviços</a>
    </div>
</main>
<?php page_footer(); ?>
