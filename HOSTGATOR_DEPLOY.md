# Deploy do Billy Ajudas na HostGator

## Dados confirmados

```yaml
conta_cpanel: "hg96b387"
home: "/home2/hg96b387"
ip_compartilhado: "69.6.213.72"
dominio_planejado: "billyajudas.is-local.org"
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
4. Anote o Document Root criado pelo cPanel.
5. Envie o conteúdo deste repositório para esse Document Root.

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

