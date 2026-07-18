# Deploy do Billy Ajudas na HostGator

## Dados confirmados

```yaml
conta_cpanel: "hg96b387"
home: "/home2/hg96b387"
ip_compartilhado: "69.6.213.72"
dominio_planejado: "billyajudas.is-local.org"
document_root: "/home2/hg96b387/billyajudas.is-local.org"
registro_dns:
  tipo: "A"
  valor: "69.6.213.72"
  ttl: 3600
  cloudflare_proxy_inicial: false
```

## Antes do upload

1. Solicite `billyajudas.is-local.org` com o registro A acima.
2. No cPanel, abra **Domínios** e adicione `billyajudas.is-local.org`.
3. Desmarque **Share document root** para não sobrescrever outro site.
4. Confirme o Document Root: `/home2/hg96b387/billyajudas.is-local.org`.
5. Envie o conteúdo deste repositório diretamente para esse Document Root.

O campo técnico **Subdomínio** criado automaticamente pelo cPanel pode aparecer como
`billyajudas.is-local.org.gl-acessorios.com`. Não altere esse campo: ele é apenas o
alias interno exigido pelo cPanel e não muda o endereço público `billyajudas.is-local.org`.

## Configuração privada e escrita

1. Copie `config.local.php.example` para `config.local.php` dentro do Document Root.
2. Preencha as credenciais novas diretamente no arquivo privado da hospedagem; não faça commit desse arquivo.
3. Garanta que o PHP consiga criar e escrever em `storage/orders/` e `storage/logs/`.
4. Confirme que o navegador recebe acesso negado ao abrir `/storage/`, `/_includes/` e `/tools/`.
5. Mantenha `MP_CHECKOUT_ENABLED` como `false` durante a instalação.
6. Não use `MP_ENVIRONMENT: production` antes de validar o fluxo de teste e o Webhook.
7. Só depois dos testes, altere `MP_CHECKOUT_ENABLED` para `true`.

## SSL

Mantenha o proxy Cloudflare desligado durante o primeiro apontamento. Depois que o DNS resolver para `69.6.213.72`, abra **SSL/TLS Status** no cPanel e execute o AutoSSL para o novo domínio.

Não force redirecionamento HTTPS antes de o certificado do novo domínio estar válido.

## Teste mínimo

```text
/
/categorias/
/visual/
/canva-social/
/lojas-online/
/sites/
/automacoes/
/carreira/
/suporte-digital/
/assinatura/
/robots.txt
/sitemap.xml
```

Os endereços `/_includes/` e `/storage/` devem responder com acesso negado.
