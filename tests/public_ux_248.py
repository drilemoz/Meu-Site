#!/usr/bin/env python3
"""Regressão da experiência pública, sem abrir o ADM ou acessar rede/EC2."""
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
layout=(ROOT/'app/public_views/layout.php').read_text()
article=(ROOT/'app/public_views/routes/article.php').read_text()
home=(ROOT/'app/public_views/routes/home.php').read_text()
js=(ROOT/'public/assets/js/article-share.js').read_text()
main_js=(ROOT/'public/assets/js/main.js').read_text()
css=(ROOT/'public/assets/css/style.css').read_text()
assert 'href="#conteudo"' in layout and 'id="conteudo"' in layout
assert 'data-public-share' in article and 'data-share-copy' in article and 'data-share-manual' in article
assert 'article-share.js?v=' in layout and "if($route==='noticia' && $article)" in layout
assert 'fetchpriority="high"' in article and 'fetchpriority="high"' in home
assert 'decoding="async"' in home and 'decoding="async"' in article
assert 'data-share-url="<?= h(news_link($article)) ?>"' in article
assert 'root.dataset.shareTitle' in js and 'navigator.share' in js
assert "window.location.origin" in js and 'target.origin !== window.location.origin' in js
assert 'localStorage' not in js and 'fetch(' not in js
assert "event.key === 'Escape'" in main_js and "toggle.focus()" in main_js
assert '.article-share-manual:not([hidden])' in css and '.skip-to-content:focus' in css

try:
 from playwright.sync_api import sync_playwright
except ImportError:
 print('OK: contratos estáticos; Playwright não disponível')
 raise SystemExit(0)
with sync_playwright() as p:
 browser=p.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-dev-shm-usage'])
 page=browser.new_page(viewport={'width':390,'height':740})
 markup='''<!DOCTYPE html><html lang="pt-BR"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>'''+css+'''</style></head><body>
<a class="skip-to-content" href="#conteudo">Ir para o conteúdo</a>
<header class="site-header"><button type="button" id="menuToggle" class="mobile-menu" aria-label="Abrir menu" aria-controls="mainNav" aria-expanded="false">☰</button><nav id="mainNav" class="main-nav"><a href="/index.php">Início</a></nav></header>
<main class="container page-main" id="conteudo" tabindex="-1"><h1>Notícia de teste</h1>
<div class="article-share" data-public-share data-share-url="/index.php?p=noticia&amp;slug=teste" data-share-title="Notícia de teste">
<button type="button" data-share-trigger>Compartilhar</button><button type="button" data-share-copy>Copiar link</button>
<span data-share-status role="status" aria-live="polite"></span>
<input data-share-manual class="article-share-manual" readonly hidden aria-label="Link manual"></div></main></body></html>'''
 page.set_content(markup)
 page.add_script_tag(content=main_js)
 page.add_script_tag(content=js.replace("window.location.origin", "'https://teste.local'"))  # Origem simulada: sem rede

 menu=page.locator('#menuToggle')
 menu.click()
 assert menu.get_attribute('aria-expanded')=='true'
 assert menu.get_attribute('aria-label')=='Fechar menu'
 page.keyboard.press('Escape')
 assert menu.get_attribute('aria-expanded')=='false'
 assert page.evaluate('document.activeElement.id')=='menuToggle'
 menu.click()
 page.locator('h1').click()
 assert menu.get_attribute('aria-expanded')=='false'
 # Clipboard disponível e Web Share disponível (Android)
 page.evaluate("""() => {
   window.__copied='';window.__shared=null;
   Object.defineProperty(navigator,'clipboard',{configurable:true,value:{writeText:async t=>{window.__copied=t;}}});
   Object.defineProperty(navigator,'share',{configurable:true,value:async d=>{window.__shared=d;}});
 }""")
 page.locator('[data-share-copy]').click()
 page.wait_for_function('window.__copied.length > 0')
 assert page.evaluate('window.__copied.includes("slug=teste")')
 assert page.locator('[data-share-status]').inner_text()=='Link copiado.'
 page.locator('[data-share-trigger]').click()
 page.wait_for_function('window.__shared !== null')
 assert page.evaluate('window.__shared.title')=='Notícia de teste'
 # Sem Web Share nem clipboard: fornecer campo manual para copiar.
 page.evaluate("""() => {
   Object.defineProperty(navigator,'share',{configurable:true,value:undefined});
   Object.defineProperty(navigator,'clipboard',{configurable:true,value:undefined});
 }""")
 page.locator('[data-share-trigger]').click()
 page.wait_for_function("!document.querySelector('[data-share-manual]').hidden")
 assert page.locator('[data-share-manual]').input_value().endswith('slug=teste')
 assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth+1')
 # Link de pular conteúdo torna-se visível ao receber foco.
 skip=page.locator('.skip-to-content')
 skip.focus()
 assert skip.evaluate('(n) => getComputedStyle(n).transform') in ('none','matrix(1, 0, 0, 1, 0, 0)')
 browser.close()
print('OK: área pública, menu Escape/clique externo, compartilhar, copiar, fallback manual, foco e viewport 390px')
