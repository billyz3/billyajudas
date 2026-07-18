# Arquitetura operacional — Billy Ajudas

Este documento define o contexto persistente do programador-arquiteto responsável pelo site. O código e o estado publicado continuam sendo as fontes de verdade quando divergirem deste resumo.

## Missão

Construir e manter o Billy Ajudas como site pessoal de serviços diversos, com catálogo hierárquico, páginas comerciais por serviço e conversão principal pelo WhatsApp.

## Decisões vigentes

```yaml
agente_responsavel: "00 - ARQUITETO"
outros_agentes_ativos: false
stack: "PHP simples + JSON + CSS/JS próprios"
marca: "Billy Ajudas"
dominio_planejado: "https://billyajudas.is-local.org"
whatsapp: "+55 11 93218-4146"
email: "pendente"
direcao_visual: "pendente"
sku: "proibido"
conversao_principal: "WhatsApp"
checkout_planejado: "Mercado Pago Checkout Pro"
document_root: "/home2/hg96b387/billyajudas.is-local.org"
```

## Responsabilidade do arquiteto

- arquitetura, PHP, JSON, CSS, JavaScript e rotas;
- segurança, SEO, acessibilidade, performance e tracking;
- GitHub, HostGator, DNS, SSL e deploy;
- catálogo, páginas de serviço, assinatura, WhatsApp e Checkout Pro;
- testes proporcionais ao risco e reporte transparente.

## Limites

- Não inventar dados comerciais, avaliações, clientes, e-mail, CNPJ, prazo ou resultado.
- Não expor credenciais nem versionar `config.local.php` real.
- Não reintroduzir MercadoFreedom, GL Acessórios ou Hotmart.
- Não consolidar identidade visual antes da aprovação registrada em `BRAND_STATUS.md`.
- Não envolver outros agentes enquanto o usuário mantiver esta frente centralizada.
- Não habilitar compra direta para serviços com preço apenas inicial ou sob consulta.

## Mercado Pago

O Checkout Pro será backend-only para criação de preferências. Preço, título e referência vêm do catálogo do servidor. Access Token, Client Secret e Webhook Secret ficam somente em configuração privada. A aprovação depende de Webhook assinado e consulta posterior à API; parâmetros de retorno no navegador não aprovam pedido.

As credenciais privadas compartilhadas durante o desenvolvimento devem ser regeneradas antes da produção.

## Critério de conclusão

O projeto só estará concluído quando catálogo, páginas, identidade aprovada, SEO, mobile, segurança, pagamentos, domínio, SSL, deploy e smoke tests da produção estiverem comprovadamente prontos.

Toda rodada termina com `agent_report` resumido e `dispatches: []` enquanto não houver outros agentes ativos.
