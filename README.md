# Billy Ajudas — serviços pessoais

Site-base oficial versionado em `billyz3/billyajudas`, preparado para hospedagem PHP na HostGator.

O contrato técnico e os critérios de conclusão estão em `PROJECT_ARCHITECTURE.md`. O plano seguro do Checkout Pro está em `MERCADO_PAGO_INTEGRATION.md`.

## Configuração necessária

1. A marca oficial definida é `Billy Ajudas`.
2. O endereço planejado é `https://billyajudas.is-local.org` e aguarda aprovação/apontamento.
3. O WhatsApp confirmado é `+55 11 93218-4146`.
4. O e-mail público ainda está pendente.
5. Copie `config.local.php.example` para `config.local.php` somente se precisar sobrescrever esses valores.

## Pagamento

O Checkout Pro está preparado apenas para serviços com preço fixo. Sem configuração privada válida, o botão de compra não aparece e o WhatsApp continua como canal principal. Consulte `MERCADO_PAGO_INTEGRATION.md`; nunca coloque Access Token, Client Secret ou Webhook Secret no repositório.

## Qualidade e publicação

- `python tools/validate_site.py`: valida catálogo, sitemap, arquivos e ausência de segredos.
- `python tools/smoke_local.py http://127.0.0.1:8765`: testa as rotas com o servidor local.
- `python tools/smoke_production.py https://billyajudas.is-local.org`: audita a publicação sem escrever dados.
- `tools/deploy_hostgator.sh`: atualiza a cópia Git e sincroniza a produção preservando configuração, pedidos e logs.

O GitHub Actions repete lint PHP, validação do catálogo e smoke HTTP em pushes e pull requests.

## Editar categorias

Edite storage/data/categories.json. Cada item possui nome, slug, rota, descrição, preço inicial, ícone e tema visual. Mantenha os slugs e as rotas sincronizados com suas pastas para evitar links quebrados.

## Editar a assinatura

O card do catálogo está em storage/data/products.json. O conteúdo e os exemplos de pacote estão em assinatura/index.php. Não há cobrança automática: o WhatsApp é o canal para definir escopo e valor.

## SEO e tráfego orgânico

- Publique uma página específica por serviço e mantenha títulos e descrições únicos.
- Cadastre o domínio no Google Search Console e envie sitemap.xml.
- Produza conteúdos úteis que respondam dúvidas reais de pequenos negócios, com links para as categorias relacionadas.
- Inclua endereço ou área de atendimento e Perfil da Empresa no Google se o atendimento for local.
- Não instale dois plugins de SEO ao mesmo tempo; em WordPress, use Rank Math ou Yoast, mais Site Kit e um plugin de cache adequado à hospedagem.

## HostGator

Consulte `HOSTGATOR_DEPLOY.md` antes de publicar. O endereço IP compartilhado confirmado é `69.6.213.72`; o Document Root confirmado é `/home2/hg96b387/billyajudas.is-local.org`.
