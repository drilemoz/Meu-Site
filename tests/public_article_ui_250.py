#!/usr/bin/env python3
"""Layout de notícia pública em largura mobile/desktop, sem internet nem banco."""
import subprocess
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
css=(ROOT/'public/assets/css/style.css').read_text()
html=subprocess.check_output(['php','-d','zend.assertions=1','-d','assert.exception=1',str(ROOT/'tests/public_article_250.php'),'--render'],text=True)
assert 'Leia também' in html
assert '1 min de leitura' in html
assert 'class="article-related-item"' in html
try:
    from playwright.sync_api import sync_playwright
except ImportError:
    print('OK: contratos do HTML; Playwright não disponível')
    raise SystemExit(0)
with sync_playwright() as p:
    browser=p.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-dev-shm-usage'])
    for width in [360,390,768,1280]:
        page=browser.new_page(viewport={'width':width,'height':760})
        page.set_content('<!doctype html><html lang="pt-BR"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'+css+'</style></head><body><main class="container page-main">'+html+'</main></body></html>')
        assert page.locator('.article-related-item').count()==2
        assert page.locator('.article-byline time').count()==1
        overflow=page.evaluate('document.documentElement.scrollWidth > window.innerWidth+1')
        assert not overflow,(width,page.evaluate('document.documentElement.scrollWidth'))
        columns=page.locator('.article-related-list').evaluate('(el) => getComputedStyle(el).flexDirection + ":" + getComputedStyle(el).display')
        if width<=760: assert columns=='column:flex', (width,columns)
        else: assert columns.endswith(':grid'),(width,columns)
        page.close()
    browser.close()
print('OK: leitura responsiva e links em 360/390/768/1280 px; sem overflow horizontal')
