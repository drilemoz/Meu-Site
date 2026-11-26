#!/usr/bin/env python3
"""Nova fonte no editor, usando apenas uma resposta administrativa local simulada."""
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

    def respond(url, method, body):
        action = parse_qs(urlsplit(url).query)['action'][0]
        fields = parse_qs(body, keep_blank_values=True)
        requests.append((method, action, fields))
        if fields['name'] == ['Falha']:
            return {'status':422,'json':{'saved':False,'message':'Nome inválido.'}}
        return {'status':200,'json':{'saved':True,'id':17+len(requests),'name':fields['name'][0]}}

    page.set_content(markup)
    page.expose_function('testTaxonomyResponse', respond)
    page.evaluate("""() => { window.fetch = async (url, opts) => {
        const result = await window.testTaxonomyResponse(url, opts.method, opts.body.toString());
        return new Response(JSON.stringify(result.json), {status: result.status, headers:{'Content-Type':'application/json'}});
    }; }""")
    page.add_script_tag(content=code)
    page.get_by_role('button', name='+ Fonte').click()
    assert page.locator('#news-taxonomy-url-wrap').is_visible()
    page.locator('#news-taxonomy-name').fill('A')
    page.get_by_role('button', name='Salvar e selecionar').click()
    assert not requests
    page.locator('#news-taxonomy-name').fill('Falha')
    page.get_by_role('button', name='Salvar e selecionar').click()
    page.wait_for_function("document.querySelector('#news-taxonomy-error').textContent === 'Nome inválido.'")
    page.locator('#news-taxonomy-name').fill('Fonte teste')
    page.locator('#news-taxonomy-url').fill('https://fonte-nova.test')
    page.get_by_role('button', name='Salvar e selecionar').click()
    page.wait_for_function("document.querySelector('[name=source_id]').value === '19'")
    assert page.locator('[name=source_name]').input_value() == 'Fonte anterior'
    assert page.locator('[name=source_id] option:checked').inner_text() == 'Fonte teste'
    assert requests[-1] == ('POST','news_create_source',{'csrf':['test-token'],'name':['Fonte teste'],'url':['https://fonte-nova.test']})
    assert page.locator('[name=title]').input_value() == 'Notícia que não deve ser alterada'
    page.get_by_role('button', name='+ Categoria').click()
    page.locator('#news-taxonomy-name').fill('Economia')
    page.get_by_role('button', name='Salvar e selecionar').click()
    page.wait_for_function("document.querySelector('[name=category_id]').value === '20'")
    assert page.locator('.news-category-chips').inner_text().strip() == 'Economia'
    assert page.locator('[name=author_profile_id]').input_value() == '0'
    browser.close()

print('OK: cadastro, validação AJAX e seleção da fonte sem perder o formulário; fontes e categorias preservadas')
