#!/usr/bin/env python3
"""Layout de notícia pública em largura mobile/desktop, sem internet nem banco."""
import subprocess
import re
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
css=(ROOT/'public/assets/css/style.css').read_text()
layout=(ROOT/'app/public_views/layout.php').read_text()
top_link=re.search(r'<a class="back-to-top".*?</a>',layout,re.S)[0]
top_header=re.search(r'<header class="site-header"[^>]+>',layout)[0]
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
    page=browser.new_page(java_script_enabled=False, reduced_motion='reduce')
    for width in [320,360,390,768,1280]:
        page.set_viewport_size({'width':width,'height':760})
        page.set_content('<!doctype html><html lang="pt-BR" class="public-page"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'+css+'</style></head><body>'+top_header+'Portal</header><main class="container page-main">'+html+'<div style="height:1200px"></div></main><nav class="bottom-nav" aria-label="Navegação móvel"></nav>'+top_link+'</body></html>')
        assert page.locator('.article-related-item').count()==2
        assert page.locator('.article-byline time').count()==1
        assert page.locator('.article-author').inner_text()=='Por Redação'
        controls=[page.get_by_role('button',name='Compartilhar'),page.get_by_role('button',name='Copiar link'),page.locator('.article-reading-time')]
        boxes=[control.bounding_box() for control in controls]
        assert boxes[0]['x']<boxes[1]['x']<boxes[2]['x']
        assert len({box['y'] for box in boxes})==1 and all(box['height']>=44 for box in boxes), (width,boxes)
        assert not page.locator('[data-share-manual]').is_visible()
        assert page.locator('.article-share-status').bounding_box()['width']<=1
        overflow=page.evaluate('document.documentElement.scrollWidth > window.innerWidth+1')
        assert not overflow,(width,page.evaluate('document.documentElement.scrollWidth'))
        columns=page.locator('.article-related-list').evaluate('(el) => getComputedStyle(el).flexDirection + ":" + getComputedStyle(el).display')
        if width<=760: assert columns=='column:flex', (width,columns)
        else: assert columns.endswith(':grid'),(width,columns)
        go_top=page.get_by_role('link',name='Voltar ao topo')
        assert go_top.is_visible() and go_top.bounding_box()['width']>=44
        if width<=760:
            box=go_top.bounding_box()
            assert box['y']+box['height']<page.locator('.bottom-nav').bounding_box()['y']
        assert page.evaluate("window.scrollTo(0, document.documentElement.scrollHeight); scrollY") > 100
        go_top.click()
        assert page.evaluate("scrollY === 0 && document.activeElement.id === 'topo'")
    browser.close()
print('OK: leitura e ações responsivas, sem overflow de 320 a 1280 px, e topo fixo sem JavaScript')
