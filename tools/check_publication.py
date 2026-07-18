#!/usr/bin/env python3
"""Verifica DNS, vhost HTTP e SSL do Billy Ajudas sem alterar estado externo."""

from __future__ import annotations

import http.client
import socket
import ssl
import urllib.error
import urllib.request


DOMAIN = "billyajudas.is-local.org"
EXPECTED_IP = "69.6.213.72"
TIMEOUT = 12


def dns_addresses() -> list[str]:
    try:
        return sorted({item[4][0] for item in socket.getaddrinfo(DOMAIN, 80, type=socket.SOCK_STREAM)})
    except socket.gaierror:
        return []


def direct_vhost() -> tuple[int, str, dict[str, str]]:
    connection = http.client.HTTPConnection(EXPECTED_IP, 80, timeout=TIMEOUT)
    try:
        connection.request(
            "GET",
            "/",
            headers={"Host": DOMAIN, "User-Agent": "BillyAjudasPublicationCheck/1.0"},
        )
        response = connection.getresponse()
        body = response.read(256_000).decode("utf-8", "replace")
        return response.status, body, {key: value for key, value in response.getheaders()}
    finally:
        connection.close()


def public_https() -> tuple[int | None, str]:
    request = urllib.request.Request(
        f"https://{DOMAIN}/",
        headers={"User-Agent": "BillyAjudasPublicationCheck/1.0"},
    )
    try:
        with urllib.request.urlopen(request, timeout=TIMEOUT, context=ssl.create_default_context()) as response:
            return response.status, response.read(256_000).decode("utf-8", "replace")
    except (urllib.error.URLError, socket.timeout, ssl.SSLError) as error:
        return None, str(error)


def main() -> int:
    errors: list[str] = []
    addresses = dns_addresses()
    status, body, headers = direct_vhost()

    if status != 200:
        errors.append(f"vhost direto: esperado HTTP 200, recebido {status}")
    if "Billy Ajudas" not in body:
        errors.append("vhost direto: marca Billy Ajudas ausente na resposta")
    if "default-src 'self'" not in headers.get("Content-Security-Policy", ""):
        errors.append("vhost direto: Content-Security-Policy ausente")

    print("PUBLICACAO BILLY AJUDAS")
    print(f"- vhost direto em {EXPECTED_IP}: HTTP {status}")
    print(f"- DNS A/AAAA: {', '.join(addresses) if addresses else 'ausente'}")

    if errors:
        for error in errors:
            print(f"- ERRO: {error}")
        return 1

    if EXPECTED_IP not in addresses:
        print(f"- PENDENTE: criar registro A {DOMAIN} -> {EXPECTED_IP}")
        print("- SSL: aguarda DNS")
        return 3

    https_status, https_body = public_https()
    if https_status != 200 or "Billy Ajudas" not in https_body:
        print(f"- PENDENTE: HTTPS/AutoSSL ainda não validado ({https_status or https_body})")
        return 4

    print("- HTTPS: HTTP 200 com certificado válido")
    print("- STATUS: domínio, vhost e SSL prontos")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
