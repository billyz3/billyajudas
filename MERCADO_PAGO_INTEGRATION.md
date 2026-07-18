# Mercado Pago Checkout Pro — plano seguro

Esta documentação registra a arquitetura sem armazenar credenciais reais.

## Configuração privada esperada

Adicionar somente no `config.local.php` da hospedagem, nunca no GitHub:

```php
define('MP_ENVIRONMENT', 'test');
define('MP_PUBLIC_KEY', '');
define('MP_ACCESS_TOKEN', '');
define('MP_WEBHOOK_SECRET', '');
```

`MP_CLIENT_SECRET` não é necessário no fluxo básico do Checkout Pro e não deve ser exposto.

## Regras de implementação

1. Criar preferência no backend para cada tentativa.
2. Buscar produto, descrição e valor no catálogo do servidor.
3. Não aceitar preço enviado pelo navegador.
4. Liberar compra direta somente para serviço com preço final numérico.
5. Configurar retornos HTTPS para sucesso, pendência e falha.
6. Validar assinatura do Webhook com HMAC-SHA256.
7. Consultar o pagamento na API antes de atualizar o pedido.
8. Correlacionar com `external_reference` e tratar eventos repetidos de forma idempotente.
9. Nunca considerar o parâmetro GET da página de retorno como confirmação de pagamento.
10. Rotacionar as credenciais privadas antes da produção porque foram compartilhadas durante o desenvolvimento.

## Pendências antes de habilitar

- domínio resolvendo com SSL válido;
- catálogo com serviços de preço fixo identificados;
- Webhook Secret gerado no painel;
- credenciais de teste novas e armazenadas fora do Git;
- endpoints e armazenamento de pedidos implementados;
- testes de preferência, retornos, assinatura, retries e idempotência;
- credenciais de produção novas;
- primeiro pagamento produtivo e medição oficial de qualidade.
