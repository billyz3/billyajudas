# Billy Ajudas — serviços pessoais

Site-base oficial versionado em `billyz3/billyajudas`, preparado para hospedagem PHP na HostGator.

## Configuração necessária

1. A marca oficial definida é `Billy Ajudas`.
2. O endereço planejado é `https://billyajudas.is-local.org` e aguarda aprovação/apontamento.
3. O WhatsApp confirmado é `+55 11 93218-4146`.
4. O e-mail público ainda está pendente.
5. Copie `config.local.php.example` para `config.local.php` somente se precisar sobrescrever esses valores.

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

Consulte `HOSTGATOR_DEPLOY.md` antes de publicar. O endereço IP compartilhado confirmado é `69.6.213.72`; o Document Root do novo domínio ainda precisa ser informado pelo cPanel.
