#!/usr/bin/env python3
"""Valida o catálogo e as rotas públicas do Billy Ajudas sem depender de PHP local."""

from __future__ import annotations

import json
import re
import sys
import xml.etree.ElementTree as ET
from pathlib import Path


ROOT = Path(__file__).resolve().parents[1]
DOMAIN = "https://billyajudas.is-local.org"
CATEGORY_FIELDS = {"nome", "slug", "rota", "descricao", "preco_inicial", "icone", "tema"}
PRODUCT_FIELDS = {
    "nome",
    "slug",
    "categoria",
    "categoria_slug",
    "rota",
    "resumo",
    "descricao",
    "preco",
    "preco_tipo",
    "preco_valor",
    "observacao_preco",
    "modelo",
    "icone",
    "featured",
    "prazo",
    "entregas",
    "opcoes",
    "whatsapp_message",
    "checkout_enabled",
    "status",
}


def load_json(relative: str) -> list[dict]:
    with (ROOT / relative).open(encoding="utf-8") as handle:
        value = json.load(handle)
    if not isinstance(value, list):
        raise ValueError(f"{relative} precisa conter uma lista JSON")
    return value


def contains_key(value: object, forbidden: str) -> bool:
    if isinstance(value, dict):
        return forbidden in value or any(contains_key(item, forbidden) for item in value.values())
    if isinstance(value, list):
        return any(contains_key(item, forbidden) for item in value)
    return False


def main() -> int:
    errors: list[str] = []
    categories = load_json("storage/data/categories.json")
    products = load_json("storage/data/products.json")
    category_slugs: set[str] = set()
    category_routes: set[str] = set()

    for index, category in enumerate(categories):
        missing = CATEGORY_FIELDS - category.keys()
        if missing:
            errors.append(f"categories[{index}] sem campos: {sorted(missing)}")
        slug = str(category.get("slug", ""))
        route = str(category.get("rota", ""))
        if slug in category_slugs:
            errors.append(f"categoria duplicada: {slug}")
        category_slugs.add(slug)
        category_routes.add(route)
        if route != f"/{slug}/":
            errors.append(f"rota de categoria incoerente: {slug} -> {route}")
        if not (ROOT / slug / "index.php").is_file():
            errors.append(f"pagina de categoria ausente: {slug}/index.php")

    product_routes: set[str] = set()
    product_slugs: set[tuple[str, str]] = set()
    for index, product in enumerate(products):
        missing = PRODUCT_FIELDS - product.keys()
        if missing:
            errors.append(f"products[{index}] sem campos: {sorted(missing)}")
        category_slug = str(product.get("categoria_slug", ""))
        slug = str(product.get("slug", ""))
        route = str(product.get("rota", ""))
        key = (category_slug, slug)
        if key in product_slugs:
            errors.append(f"servico duplicado: {category_slug}/{slug}")
        product_slugs.add(key)
        if route in product_routes:
            errors.append(f"rota de servico duplicada: {route}")
        product_routes.add(route)
        if category_slug != "assinatura" and category_slug not in category_slugs:
            errors.append(f"categoria inexistente em {slug}: {category_slug}")
        expected = "/assinatura/" if category_slug == "assinatura" else f"/{category_slug}/{slug}/"
        if route != expected:
            errors.append(f"rota incoerente em {slug}: esperado {expected}, recebido {route}")
        if product.get("status") != "publicado":
            errors.append(f"servico fora de publicado no catalogo publico: {slug}")
        if not str(product.get("whatsapp_message", "")).strip():
            errors.append(f"mensagem de WhatsApp ausente: {slug}")
        if product.get("preco_tipo") == "fixo" and not isinstance(product.get("preco_valor"), (int, float)):
            errors.append(f"preco fixo sem valor numerico: {slug}")
        if product.get("checkout_enabled") and product.get("preco_tipo") != "fixo":
            errors.append(f"checkout habilitado sem preco fixo: {slug}")

    if contains_key(categories, "sku") or contains_key(products, "sku"):
        errors.append("campo proibido sku detectado no catalogo")

    tree = ET.parse(ROOT / "sitemap.xml")
    namespace = {"sm": "http://www.sitemaps.org/schemas/sitemap/0.9"}
    sitemap_urls = [node.text or "" for node in tree.findall("sm:url/sm:loc", namespace)]
    if len(sitemap_urls) != len(set(sitemap_urls)):
        errors.append("sitemap contém URLs duplicadas")
    expected_routes = {"/", "/categorias/", "/assinatura/"} | category_routes | product_routes
    expected_urls = {DOMAIN + route for route in expected_routes}
    missing_urls = sorted(expected_urls - set(sitemap_urls))
    extra_urls = sorted(set(sitemap_urls) - expected_urls)
    if missing_urls:
        errors.append(f"sitemap sem URLs: {missing_urls}")
    if extra_urls:
        errors.append(f"sitemap com URLs inesperadas: {extra_urls}")

    public_extensions = {".php", ".js", ".css", ".json", ".xml", ".txt", ".htaccess"}
    forbidden_terms = ("mercadofreedom", "gl acessórios", "gl acessorios", "hotmart")
    secret_patterns = (
        re.compile(r"APP_USR-[A-Za-z0-9_-]{20,}"),
        re.compile(r"TEST-[A-Za-z0-9_-]{20,}"),
    )
    for path in ROOT.rglob("*"):
        if not path.is_file() or ".git" in path.parts or "work" in path.parts:
            continue
        if path.suffix.lower() not in public_extensions and path.name != ".htaccess":
            continue
        text = path.read_text(encoding="utf-8", errors="ignore")
        lowered = text.lower()
        for term in forbidden_terms:
            if term in lowered:
                errors.append(f"termo legado '{term}' em {path.relative_to(ROOT)}")
        for pattern in secret_patterns:
            if pattern.search(text):
                errors.append(f"possivel credencial exposta em {path.relative_to(ROOT)}")

    if errors:
        print("VALIDACAO FALHOU")
        for error in errors:
            print(f"- {error}")
        return 1

    print("VALIDACAO OK")
    print(f"- categorias: {len(categories)}")
    print(f"- servicos: {len(products)}")
    print(f"- urls no sitemap: {len(sitemap_urls)}")
    print("- SKU: ausente")
    print("- credenciais reais: ausentes")
    return 0


if __name__ == "__main__":
    sys.exit(main())
