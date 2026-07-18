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

## Estado confirmado em 2026-07-18

```yaml
dominio_no_cpanel: true
document_root: "/home2/hg96b387/billyajudas.is-local.org"
arquivos_publicados: true
vhost_com_host_header: "HTTP 200"
arquivos_privados: "HTTP 403"
dns_publico: "registro A ainda ausente"
ssl: "aguardando DNS"
```

O servidor já entrega o Billy Ajudas quando recebe o `Host` correto. Enquanto o DNS A
estiver ausente, o endereço público não resolve e o AutoSSL não consegue concluir.

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

## Deploy pelo Terminal do cPanel

Depois de aprovar e mesclar a Pull Request no `main`, execute:

```bash
cd /home2/hg96b387
curl -fsSLo deploy-billy-ajudas.sh https://raw.githubusercontent.com/billyz3/billyajudas/main/tools/deploy_hostgator.sh
bash deploy-billy-ajudas.sh
```

O script usa o repositório público por HTTPS, valida o projeto e sincroniza o conteúdo para
`/home2/hg96b387/billyajudas.is-local.org`. Ele preserva deliberadamente:

```text
config.local.php
storage/orders/
storage/logs/
```

Depois do deploy e do SSL, valide:

```bash
python3 /home2/hg96b387/billyajudas-repo/tools/smoke_production.py https://billyajudas.is-local.org
```

Para verificar separadamente DNS, vhost e SSL, execute:

```bash
python3 /home2/hg96b387/billyajudas-repo/tools/check_publication.py
```

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
