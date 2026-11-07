'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict'),vm=require('node:vm');
const root=require('node:path').join(__dirname,'..');
const js=fs.readFileSync(root+'/public/assets/js/news-editor.js','utf8');
const match=js.match(/const makeSlug=value=>([^;]+);/);
assert.ok(match,'função real de normalização presente');
const normalize=vm.runInNewContext('value=>'+match[1],{});
for(const [value,expected] of [
 ['Trânsito na MG 050!','transito-na-mg-050'],
 ['Iguatama: chuva e acidentes','iguatama-chuva-e-acidentes'],
 ['  Teste --- de SEO  ','teste-de-seo'],
 ['!!!','noticia'],
])assert.equal(normalize(value),expected,'slug para: '+value);
assert.ok(normalize('a'.repeat(150)).length<=120,'limite de slug JS');
assert.match(js,/slugInput\.defaultValue/,'URLs legadas longas preservadas na prévia');
const view=require('./support/news_editor_view_source.js')();
assert.match(view,/name="url_slug"/,'campo editorial disponível');
assert.match(view,/id="news-url-preview"/,'prévia do endereço disponível');
assert.match(view,/id="news-seo-suggest-slug"/,'geração manual do slug disponível');
assert.equal((view.match(/data-tab="/g)||[]).length,9,'nove etapas do editor');
console.log('OK: normalização real do JavaScript, prévia do slug e editor 3 etapas.');
