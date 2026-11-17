#!/usr/bin/env python3
"""Novo autor no editor, usando apenas uma resposta administrativa local simulada."""
from pathlib import Path
from urllib.parse import parse_qs, urlsplit

ROOT = Path(__file__).resolve().parents[1]
bundle = (ROOT / 'public/assets/js/news-editor.js').read_text()
start = bundle.index(" const taxonomyDialog=document.querySelector('#news-taxonomy-dialog');")
end = bundle.index(' render();loadVisual();preview();categorySuggestion();changeTab(0);', start)
code = "{const form=document.querySelector('form'),status=document.querySelector('#status'),csrf='test-token';" + bundle[start:end] + '}'
editor = (ROOT / 'app/news_admin_view/details.php').read_text()
dialog = editor[editor.index('<dialog id="news-taxonomy-dialog"'):]
dialog = dialog[:dialog.index('</dialog>') + len('</dialog>')]
markup = '''
<form><input name="title" value="Notícia que não deve ser alterada">
  <select name="author_profile_id"><option value="0">Conta / Redação</option></select>
  <select name="source_id"><option value="0">Fonte manual</option></select>
  <input name="source_name" value="Fonte anterior"><input name="source_url" value="https://fonte.test">
  <select name="category_id"><option value="5">Categoria anterior</option></select>
  <div class="news-category-chips"></div>
  <button type="button" data-news-taxonomy="author">+ Autor</button>
  <button type="button" data-news-taxonomy="source">+ Fonte</button>
  <button type="button" data-news-taxonomy="category">+ Categoria</button>
''' + dialog + '</form><p id="status"></p>'

try:
    from playwright.sync_api import sync_playwright
except ImportError:
    print('SKIP: Chromium via Playwright não disponível')
    raise SystemExit(0)

with sync_playwright() as p:
    browser = p.chromium.launch(headless=True, executable_path='/usr/bin/chromium', args=['--no-sandbox'])
    page = browser.new_page()
    requests = []

    def respond(route):
        url = urlsplit(route.request.url)
        if url.path != '/admin.php':
            return route.fulfill(status=200, content_type='text/html; charset=utf-8', body=markup)
        fields = parse_qs(route.request.post_data or '', keep_blank_values=True)
        action = parse_qs(url.query)['action'][0]
        requests.append((route.request.method, action, fields))
        if fields['name'] == ['Falha']:
            return route.fulfill(status=422, json={'saved':False,'message':'Nome inválido.'})
        return route.fulfill(status=200, json={'saved':True,'id':17+len(requests),'name':fields['name'][0]})

    page.route('http://portal.test/**', respond)
    page.goto('http://portal.test/editor')
    page.add_script_tag(content=code)
    page.get_by_role('button', name='+ Autor').click()
    assert page.get_by_role('dialog', name='Novo autor').is_visible()
    assert not page.locator('#news-taxonomy-url-wrap').is_visible()
    assert page.locator('#news-taxonomy-name').get_attribute('maxlength') == '120'
    page.locator('#news-taxonomy-name').fill('A')
    page.get_by_role('button', name='Salvar e selecionar').click()
    assert not requests
    page.locator('#news-taxonomy-name').fill('Falha')
    page.get_by_role('button', name='Salvar e selecionar').click()
    page.wait_for_function("document.querySelector('#news-taxonomy-error').textContent === 'Nome inválido.'")
    page.locator('#news-taxonomy-name').fill('Autora <Teste>')
    page.get_by_role('button', name='Salvar e selecionar').click()
    page.wait_for_function("document.querySelector('[name=author_profile_id]').value === '19'")
    assert requests[-1] == ('POST','news_create_author',{'csrf':['test-token'],'name':['Autora <Teste>'],'url':['']})
    assert page.locator('[name=author_profile_id] option:checked').inner_text() == 'Autora <Teste>'
    assert page.locator('[name=title]').input_value() == 'Notícia que não deve ser alterada'
    assert page.locator('[name=source_name]').input_value() == 'Fonte anterior'
    assert not page.get_by_role('dialog').is_visible()
    page.get_by_role('button', name='+ Fonte').click()
    assert page.locator('#news-taxonomy-url-wrap').is_visible()
    page.locator('#news-taxonomy-name').fill('Fonte teste')
    page.locator('#news-taxonomy-url').fill('https://fonte-nova.test')
    page.get_by_role('button', name='Salvar e selecionar').click()
    page.wait_for_function("document.querySelector('[name=source_id]').value === '20'")
    assert page.locator('[name=source_name]').input_value() == 'Fonte teste'
    page.get_by_role('button', name='+ Categoria').click()
    page.locator('#news-taxonomy-name').fill('Economia')
    page.get_by_role('button', name='Salvar e selecionar').click()
    page.wait_for_function("document.querySelector('[name=category_id]').value === '21'")
    assert page.locator('.news-category-chips').inner_text().strip() == 'Economia'
    assert page.locator('[name=author_profile_id]').input_value() == '19'
    browser.close()

print('OK: cadastro, validação AJAX e seleção do autor sem perder o formulário; fontes e categorias preservadas')
