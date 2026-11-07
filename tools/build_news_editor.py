#!/usr/bin/env python3
"""Monta news-editor.js na ordem original a partir de módulos de origem.

Uso: python3 tools/build_news_editor.py [--check]

Os módulos compartilham o escopo do mesmo IIFE. Não os sirva separadamente;
news-editor.js é o recurso único distribuído e funciona sem Python em produção.
"""
from __future__ import annotations

import argparse
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
JS = ROOT / 'public' / 'assets' / 'js'
MODULES = JS / 'news-editor-modules'
ORDER = (
    '01-blocks.js',
    '02-steps.js',
    '03-rich-text.js',
    '04-cover-library.js',
    '05-seo-navigation.js',
    '06-media-metadata.js',
    '07-drafts-autosave.js',
    '08-submit-init.js',
)
OUTPUT = JS / 'news-editor.js'


def bundle() -> bytes:
    names = sorted(p.name for p in MODULES.glob('*.js'))
    if names != sorted(ORDER):
        raise ValueError('Módulos JS inesperados/ausentes. Atualize ORDER explicitamente.')
    return b''.join((MODULES / filename).read_bytes() for filename in ORDER)


def main() -> int:
    parser = argparse.ArgumentParser(description='Monta ou verifica o editor IguaNews')
    parser.add_argument('--check', action='store_true', help='Somente confere o arquivo gerado')
    args = parser.parse_args()
    result = bundle()
    if not result.startswith(b'(() => {') or not result.rstrip().endswith(b'})();'):
        print('ERRO: formato do escopo do editor inválido.', file=sys.stderr)
        return 1
    current = OUTPUT.read_bytes() if OUTPUT.is_file() else b''
    if args.check:
        if result != current:
            print('ERRO: JS consolidado desatualizado. Rode python3 tools/build_news_editor.py', file=sys.stderr)
            return 1
        print(f'Editor sincronizado: {len(ORDER)} módulos; {len(result)} bytes; um recurso HTTP.')
        return 0
    if result != current:
        OUTPUT.write_bytes(result)
        print(f'Editor reconstruído: {len(ORDER)} módulos; {len(result)} bytes.')
    else:
        print('Editor já sincronizado.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
