#!/usr/bin/env python3
"""Valida painel da busca em Chromium local, sem banco/EC2/acesso externo."""
from __future__ import annotations
from pathlib import Path
import json
import re

ROOT = Path(__file__).resolve().parents[1]
LAYOUT = (ROOT/'app/public_views/layout.php').read_text()
JS = (ROOT/'public/assets/js/search-suggest.js').read_text()
CSS = (ROOT/'public/assets/css/style.css').read_text()
assert 'data-public-search' in LAYOUT and 'public-search-options' in LAYOUT
assert 'innerHTML' not in JS and 'textContent' in JS, 'Dados da API devem virar texto, não HTML'
assert '--search-room' in JS and 'visualViewport' in JS, 'Adaptação ao teclado móvel ausente'
assert 'role="combobox"' in LAYOUT and 'role="listbox"' in LAYOUT
assert 'Ver mais notícias' in LAYOUT, 'Rodapé deve distinguir busca de notícias e horários de ônibus'
match=re.search(r'<form class="header-search".*?</form>', LAYOUT)
assert match
form=match.group(0).replace('<?= h($search) ?>','')
html=f'''<!DOCTYPE html><html lang="pt-BR"><head><meta name="viewport" content="width=device-width,initial-scale=1"><meta charset="utf-8"><style>{CSS}</style></head><body><header class="site-header"><div class="header-upper container" style="padding-top:95px;padding-bottom:20px;max-width:480px">{form}</div></header><main><h1>Teste local da busca</h1></main><script src="/search-suggest.js"></script></body></html>'''
items=[
 {'type':'Notícia','label':'Arcoense Leo Boyka conquista título no Brasília International Open','url':'/noticia/leo-boyka'},
 {'type':'Notícia','label':'Homem resiste à abordagem policial e PM apreende munição em Arcos','url':'/noticia/homem-resiste'},
 {'type':'Notícia','label':'<img src=x onerror=alert(1)> Arcos','url':'/index.php?p=noticia&slug=literal'},
 {'type':'Ônibus','label':'Arcos → Bambuí','url':'/index.php?p=onibus&origem=Arcos&destino=Bambui'},
 {'type':'Ônibus','label':'Arcos → Formiga','url':'/index.php?p=onibus&origem=Arcos&destino=Formiga'},
]
try:
 from playwright.sync_api import sync_playwright
 with sync_playwright() as p:
  browser=p.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-dev-shm-usage'])
  page=browser.new_page(viewport={'width':390,'height':680},device_scale_factor=1)
  page.set_content(html)
  page.evaluate('window.fetch = async () => ({ok:true, json: async () => ' + json.dumps(items,ensure_ascii=False) + '})')
  page.add_script_tag(content=JS)
  field=page.locator('[name=q]')
  field.fill('Arc')
  page.wait_for_selector('.header-search-suggestion:nth-child(5)',timeout=5000)
  assert page.locator('.header-search-suggestion').count()==5
  assert page.locator('.header-search-suggestion').first.get_attribute('href')=='/noticia/leo-boyka', 'Notícia amigável sumiu do painel'
  assert page.locator('.header-search-suggestion').nth(1).get_attribute('href')=='/noticia/homem-resiste'
  assert page.locator('.header-search-suggestion').nth(2).get_attribute('href').startswith('/index.php?p=noticia'), 'URL legada sumiu'
  assert page.locator('.header-search-suggestion-icon svg').count()==5
  assert page.locator('.header-search-suggestion-title mark').count()>=3
  assert 'Ver mais notícias' in page.locator('.header-search-all').inner_text()
  assert page.locator('img').count()==0, 'Texto malicioso virou imagem'
  assert page.locator('.header-search-suggestion').nth(3).get_attribute('href').startswith('/index.php?p=onibus')
  maxheight=page.locator('[data-search-suggestions]').evaluate('(n)=>parseFloat(getComputedStyle(n).maxHeight)')
  assert maxheight<=380, maxheight
  page.screenshot(path='/mnt/data/iguanews-147-search-preview.png')
  page.keyboard.press('ArrowDown')
  assert page.locator('#search-result-0').get_attribute('aria-selected')=='true'
  page.keyboard.press('Escape')
  assert page.locator('[data-search-suggestions]').is_hidden()
  field.fill('Arc')
  page.wait_for_selector('.header-search-suggestion:nth-child(5)',timeout=5000)
  assert page.evaluate('document.documentElement.scrollWidth <= window.innerWidth+1'), 'Overflow horizontal em 390px'
  # Simula viewport reduzido pelo teclado virtual após visualViewport.resize.
  page.evaluate('''() => {
    const base=window.visualViewport;
    Object.defineProperty(base,'height',{configurable:true,value:400});
    base.dispatchEvent(new Event('resize'));
  }''')
  remaining=page.locator('[data-search-suggestions]').evaluate('(n)=>parseFloat(n.style.getPropertyValue("--search-room"))')
  assert remaining < 380, remaining
  # Regressão 1.0.149: URLs amigáveis de editoria e notícias e rejeição de redirecionamentos externos.
  mixed = [
    {'type':'Editoria','label':'Cidade de Arcos','url':'/categoria/cidade'},
    {'type':'Notícia','label':'Arcos campeão','url':'/noticia/arcos-campeao'},
    {'type':'Ônibus','label':'Arcos → Bambuí','url':'/index.php?p=onibus&origem=Arcos&destino=Bambui'},
    {'type':'Notícia','label':'Arcos externo','url':'https://exemplo.com/noticia/externo'},
    {'type':'Notícia','label':'Arcos protocolo','url':'javascript:alert(1)'},
    {'type':'Notícia','label':'Arcos caminho','url':'//exemplo.com/noticia/teste'},
    {'type':'Notícia','label':'Arcos rota arbitrária','url':'/admin.php'},
  ]
  page.evaluate('(rows) => {window.fetch = async () => ({ok:true, json: async () => rows});}', mixed)
  field.fill('Arco')
  page.wait_for_function("document.querySelectorAll('.header-search-suggestion').length === 3", timeout=5000)
  assert [x.get_attribute('href') for x in page.locator('.header-search-suggestion').all()] == [
    '/categoria/cidade','/noticia/arcos-campeao','/index.php?p=onibus&origem=Arcos&destino=Bambui'
  ], 'URLs amigáveis ou proteção contra links inseguros com falha'
  page.evaluate('window.fetch = async () => ({ok:true, json: async () => []})')
  field.fill('zz')
  page.wait_for_function("document.querySelector('[data-search-caption]').textContent.includes('Nenhum resultado')", timeout=5000)
  page.evaluate('() => { window.fetch = async () => { throw new Error("sem rede"); }; }')
  field.fill('zzz')
  page.wait_for_function("document.querySelector('[data-search-caption]').textContent.includes('Busca indisponível')", timeout=5000)
  print(f'OK: Chromium mobile, 5 sugestões compactas, SVG, XSS, teclado, ausência/erro, altura {maxheight}px→{remaining}px')
  browser.close()
except ImportError:
 print('SKIP navegador: Playwright não instalado. Contratos estáticos aprovados.')
