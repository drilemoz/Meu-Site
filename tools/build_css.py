#!/usr/bin/env python3
"""Gera style.css preservando exatamente a ordem de cascata dos módulos.

Uso: python3 tools/build_css.py [--check]
O arquivo style.css é distribuído pronto, sem dependência de Python em produção.
"""
from __future__ import annotations

import argparse
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CSS = ROOT / 'public' / 'assets' / 'css'
MODULES = CSS / 'modules'
ORDER = (
    '01-foundation.css',
    '02-installer.css',
    '03-editorial-articles.css',
    '04-transport.css',
    '05-site-layout-contact.css',
    '06-advertising.css',
    '07-editor-structured.css',
    '08-analytics.css',
    '09-editorial-admin.css',
    '10-health.css',
    '11-editor-visual.css',
    '12-media-library.css',
    '13-editor-mobile.css',
    '14-seo.css',
    '15-archive.css',
    '16-security.css',
    '17-legal-apps.css',
    '18-file-manager.css',
    '19-server-info.css',
)
OUTPUT = CSS / 'style.css'


def bundle() -> bytes:
    if sorted(p.name for p in MODULES.glob('*.css')) != sorted(ORDER):
        raise ValueError('Lista de módulos CSS divergente: atualize ORDER de forma explícita.')
    contents = []
    for filename in ORDER:
        file = MODULES / filename
        if not file.is_file():
            raise FileNotFoundError(f'Módulo CSS ausente: {filename}')
        contents.append(file.read_bytes())
    return b''.join(contents)


def main() -> int:
    parser = argparse.ArgumentParser(description='Gera ou valida o CSS consolidado IguaNews')
    parser.add_argument('--check', action='store_true', help='Verifica sem modificar style.css')
    args = parser.parse_args()
    built = bundle()
    existing = OUTPUT.read_bytes() if OUTPUT.is_file() else b''
    if args.check:
        if built != existing:
            print('ERRO: style.css divergente dos módulos. Execute python3 tools/build_css.py', file=sys.stderr)
            return 1
        print(f'CSS sincronizado: {len(ORDER)} módulos, {len(built)} bytes, 1 arquivo HTTP.')
        return 0
    if built == existing:
        print('style.css já está sincronizado.')
    else:
        OUTPUT.write_bytes(built)
        print(f'style.css atualizado: {len(ORDER)} módulos, {len(built)} bytes.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
