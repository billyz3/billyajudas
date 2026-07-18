#!/usr/bin/env python3
"""Smoke test de produção do Billy Ajudas, sem alterar dados remotos."""

from __future__ import annotations

import json
import sys
import urllib.error
import urllib.request
import xml.etree.ElementTree as ET
from io import BytesIO
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BASE = (sys.argv[1] if len(sys.argv) > 1 else "https://billyajudas.is-local.org").rstrip("/")


def request(path: str):
    req = urllib.request.Request(BASE + path, headers={"User-Agent": "BillyAjudasProductionSmoke/1.0"})
    try:
        with urllib.request.urlopen(req, timeout=15) as response:
            return response.status, dict(response.headers), response.geturl(), response.read()
    except urllib.error.HTTPError as error:
        return error.code, dict(error.headers), error.geturl(), error.read()


def main() -> int:
    if not BASE.startswith("https://"):
        print("A URL de produção precisa usar HTTPS.")
        return 2

    errors: list[str] = []
    categories = json.loads((ROOT / "storage/data/categories.json").read_text(encoding="utf-8"))
    products = json.loads((ROOT / "storage/data/products.json").read_text(encoding="utf-8"))
    routes = [
        "/", "/categorias/", "/assinatura/", "/como-funciona/", "/contato/",
        "/privacidade/", "/termos-de-servico/", "/cancelamento-e-reembolso/",
    ]
    routes += [item["rota"] for item in categories]
    routes += [item["rota"] for item in products if item["rota"] != "/assinatura/"]

    for route in routes:
        status, headers, final_url, body = request(route)
        text = body.decode("utf-8", "replace")
        if status != 200:
            errors.append(f"{route}: HTTP {status}")
            continue
        if not final_url.startswith(BASE + "/") and final_url != BASE:
            errors.append(f"{route}: saiu do domínio esperado ({final_url})")
        if "5511932184146" not in text:
            errors.append(f"{route}: WhatsApp confirmado ausente")
        if "default-src 'self'" not in headers.get("Content-Security-Policy", ""):
            errors.append(f"{route}: CSP ausente")
        if "fatal error" in text.lower() or "warning:" in text.lower():
            errors.append(f"{route}: erro PHP visível")

    for protected in ("/_includes/bootstrap.php", "/storage/data/products.json", "/tools/validate_site.py", "/config.local.php.example"):
        status, _, _, _ = request(protected)
        if status != 403:
            errors.append(f"{protected}: esperado 403, recebido {status}")

    for endpoint in ("/checkout/create.php", "/api/mercado-pago/webhook.php"):
        status, _, _, _ = request(endpoint)
        if status != 405:
            errors.append(f"{endpoint}: esperado 405 no GET, recebido {status}")

    status, _, _, body = request("/sitemap.xml")
    if status != 200:
        errors.append(f"sitemap.xml: HTTP {status}")
    else:
        tree = ET.parse(BytesIO(body))
        namespace = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
        urls = [node.text or "" for node in tree.findall("sm:url/sm:loc", namespace)]
        if not urls or any(not url.startswith(BASE + "/") for url in urls):
            errors.append("sitemap.xml: domínio inesperado ou sem URLs")

    status, _, _, body = request("/robots.txt")
    if status != 200 or f"Sitemap: {BASE}/sitemap.xml" not in body.decode("utf-8", "replace"):
        errors.append("robots.txt: sitemap final ausente")

    status, _, _, body = request("/rota-que-nao-existe/")
    if status != 404 or 'name="robots" content="noindex,follow"' not in body.decode("utf-8", "replace"):
        errors.append("404 público incorreto")

    if errors:
        print("PRODUCTION SMOKE FALHOU")
        for error in errors:
            print(f"- {error}")
        return 1

    print("PRODUCTION SMOKE OK")
    print(f"- base: {BASE}")
    print(f"- rotas comerciais: {len(routes)}")
    print("- HTTPS, CSP, catálogo, WhatsApp e arquivos privados: OK")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
