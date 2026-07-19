#!/usr/bin/env python3
"""Smoke test HTTP do Billy Ajudas em um servidor PHP local."""

from __future__ import annotations

import json
import re
import sys
import urllib.error
import urllib.request
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
BASE = (sys.argv[1] if len(sys.argv) > 1 else "http://127.0.0.1:8765").rstrip("/")


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, req, fp, code, msg, headers, newurl):
        return None


def request(path: str, method: str = "GET", body: bytes | None = None, headers: dict[str, str] | None = None):
    req = urllib.request.Request(BASE + path, data=body, method=method, headers=headers or {})
    try:
        with urllib.request.urlopen(req, timeout=8) as response:
            return response.status, dict(response.headers), response.read().decode("utf-8", "replace")
    except urllib.error.HTTPError as error:
        return error.code, dict(error.headers), error.read().decode("utf-8", "replace")


def request_without_redirect(path: str):
    req = urllib.request.Request(BASE + path)
    opener = urllib.request.build_opener(NoRedirect)
    try:
        with opener.open(req, timeout=8) as response:
            return response.status, dict(response.headers)
    except urllib.error.HTTPError as error:
        return error.code, dict(error.headers)


def main() -> int:
    errors: list[str] = []
    categories = json.loads((ROOT / "storage/data/categories.json").read_text(encoding="utf-8"))
    products = json.loads((ROOT / "storage/data/products.json").read_text(encoding="utf-8"))
    routes = [
        "/", "/categorias/", "/assinatura/", "/como-funciona/", "/contato/",
        "/privacidade/", "/termos-de-servico/", "/cancelamento-e-reembolso/",
        "/robots.txt", "/sitemap.xml",
    ]
    routes += [item["rota"] for item in categories]
    routes += [item["rota"] for item in products if item["rota"] != "/assinatura/"]
    routes += ["/pagamento/sucesso/", "/pagamento/pendente/", "/pagamento/falha/"]

    for route in routes:
        status, headers, text = request(route)
        if status != 200:
            errors.append(f"{route}: HTTP {status}")
        if route.endswith("/") and route not in ("/pagamento/sucesso/", "/pagamento/pendente/", "/pagamento/falha/"):
            if "5511932184146" not in text:
                errors.append(f"{route}: WhatsApp confirmado ausente")
        lowered = text.lower()
        if "fatal error" in lowered or "warning:" in lowered or "notice:" in lowered:
            errors.append(f"{route}: erro PHP visível")
        if route.startswith("/pagamento/") and "noindex, nofollow" not in headers.get("X-Robots-Tag", ""):
            errors.append(f"{route}: X-Robots-Tag ausente")
        if route.endswith(".php") is False and route.endswith((".txt", ".xml")) is False:
            if "default-src 'self'" not in headers.get("Content-Security-Policy", ""):
                errors.append(f"{route}: Content-Security-Policy ausente")

    status, _, text = request("/canva-social/cinco-artes-canva-personalizadas/")
    if status != 200 or "Comprar com Mercado Pago" in text:
        errors.append("checkout apareceu sem credencial privada configurada")
    canonical_match = re.search(r'<link rel="canonical" href="([^"]+)">', text)
    expected_canonical = "https://billyajudas.is-local.org/canva-social/cinco-artes-canva-personalizadas/"
    if not canonical_match or canonical_match.group(1) != expected_canonical:
        errors.append("serviço: canonical não corresponde à rota oficial com barra final")
    schema_blocks = re.findall(r'<script[^>]+type="application/ld\+json"[^>]*>(.*?)</script>', text, re.DOTALL)
    parsed_schema = []
    for block in schema_blocks:
        try:
            parsed_schema.append(json.loads(block))
        except json.JSONDecodeError:
            errors.append("serviço: JSON-LD inválido")
    graph_types = {
        item.get("@type")
        for schema in parsed_schema
        for item in schema.get("@graph", [])
        if isinstance(schema, dict) and isinstance(item, dict)
    }
    if not {"Service", "BreadcrumbList"}.issubset(graph_types):
        errors.append("serviço: schema Service/BreadcrumbList ausente")
    status, headers = request_without_redirect("/canva-social/cinco-artes-canva-personalizadas")
    if status != 301 or headers.get("Location") != "/canva-social/cinco-artes-canva-personalizadas/":
        errors.append("serviço sem barra final: redirecionamento 301 canônico ausente")

    status, _, text = request("/visual/arte-para-instagram/")
    if status != 200 or "5 Artes Personalizadas no Canva" not in text:
        errors.append("alias /visual/arte-para-instagram/ não chegou ao serviço esperado")

    for protected in ("/_includes/bootstrap.php", "/storage/data/products.json", "/tools/validate_site.py", "/config.local.php.example"):
        status, _, _ = request(protected)
        if status != 403:
            errors.append(f"{protected}: esperado 403, recebido {status}")

    status, _, _ = request("/checkout/create.php")
    if status != 405:
        errors.append(f"checkout GET: esperado 405, recebido {status}")

    webhook_payload = json.dumps({"type": "payment", "data": {"id": "123456"}}).encode()
    status, _, _ = request(
        "/api/mercado-pago/webhook.php",
        method="POST",
        body=webhook_payload,
        headers={"Content-Type": "application/json", "X-Signature": "ts=1,v1=" + "0" * 64, "X-Request-Id": "smoke"},
    )
    if status != 401:
        errors.append(f"webhook sem segredo: esperado 401, recebido {status}")

    status, _, not_found_text = request("/rota-que-nao-existe/")
    if status != 404:
        errors.append(f"404 amigável: esperado 404, recebido {status}")
    if 'name="robots" content="noindex,follow"' not in not_found_text:
        errors.append("404 amigável: meta noindex ausente")

    if errors:
        print("SMOKE FALHOU")
        for error in errors:
            print(f"- {error}")
        return 1

    print("SMOKE OK")
    print(f"- rotas públicas verificadas: {len(routes)}")
    print("- WhatsApp: correto")
    print("- arquivos privados: protegidos")
    print("- checkout sem credenciais: oculto")
    print("- webhook sem assinatura válida: rejeitado")
    print("- 404: correto")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
