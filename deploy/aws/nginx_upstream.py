#!/usr/bin/env python3
"""Valida e troca exclusivamente o fastcgi_pass de um vhost dedicado ao portal.

Não percorre includes globais e não considera comentários. Falha se há outras
raízes, mais de um upstream ou sintaxe de upstream desconhecida.
"""
from __future__ import annotations
import argparse
from pathlib import Path
import re


def inspect(text: str, site_public: str, php_version: str) -> str:
    # Comentários não participam da inspeção, mas ficam preservados na edição.
    active = "\n".join(line.split("#", 1)[0] for line in text.splitlines())
    roots = re.findall(r"(?<![\w-])root\s+([^;]+);", active)
    if len(roots) != 1 or roots[0].strip() != site_public:
        raise ValueError("vhost possui raiz ausente, adicional ou diferente")
    upstreams = re.findall(r"(?<![\w-])fastcgi_pass\s+([^;]+);", active)
    supported = {
        f"unix:/run/php/php{php_version}-fpm.sock",
        f"unix:/run/php/php{php_version}-fpm-iguatamaonline.sock",
    }
    if len(upstreams) != 1 or upstreams[0].strip() not in supported:
        raise ValueError("vhost possui upstream FastCGI ambíguo ou incompatível")
    return upstreams[0].strip()


def transform(text: str, site_public: str, php_version: str) -> str:
    inspect(text, site_public, php_version)
    # Substitui uma única linha ativa. Nunca modifica linhas comentadas.
    regex = re.compile(
        r"(?m)^([ \t]*fastcgi_pass[ \t]+)unix:/run/php/php"
        + re.escape(php_version)
        + r"-fpm(?:-iguatamaonline)?\.sock([ \t]*;[^\n]*)$"
    )
    lines = text.splitlines(keepends=True)
    matches = [i for i, line in enumerate(lines) if regex.match(line) and not line.lstrip().startswith("#")]
    if len(matches) != 1:
        raise ValueError("não há um único fastcgi_pass editável")
    i = matches[0]
    lines[i] = regex.sub(
        lambda m: m.group(1) + f"unix:/run/php/php{php_version}-fpm-iguatamaonline.sock" + m.group(2),
        lines[i], count=1,
    )
    return "".join(lines)


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--config", required=True, type=Path)
    parser.add_argument("--site-public", required=True)
    parser.add_argument("--php-version", default="8.3", choices=("8.3",))
    parser.add_argument("--apply", action="store_true")
    args = parser.parse_args()
    original = args.config.read_text(encoding="utf-8")
    updated = transform(original, args.site_public, args.php_version)
    if args.apply and updated != original:
        args.config.write_text(updated, encoding="utf-8")
    print("Vhost dedicado e fastcgi_pass validados" + ("; pool exclusivo configurado" if args.apply else ""))


if __name__ == "__main__":
    main()
