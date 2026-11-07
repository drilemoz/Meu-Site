'use strict';
// Teste de restauração real das rotinas do editor, sem DOM externo ou npm.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const editor = fs.readFileSync(path.join(__dirname, '../public/assets/js/news-editor.js'),'utf8');
const start = editor.indexOf(' function recoverableDraftName(name){');
const end = editor.indexOf(' async function autosave(){', start);
assert(start >= 0 && end > start, 'Rotinas de rascunho localizadas no editor real');

class FakeFile { constructor(name) {this.name = name;} }
const field = (name, value, type = 'text', checked = false) => ({name, value, type, checked});
const elements = [
  field('csrf', 'csrf-atual','hidden'),field('id','91','hidden'),field('blocks_json','[]','hidden'),
  field('title','Manchete original'),field('summary','Resumo novo'),
  field('extra_categories[]','1','checkbox',true),field('extra_categories[]','2','checkbox',false),field('extra_categories[]','3','checkbox',true),
  field('featured','1','checkbox',true),field('remove_media[]','18','checkbox',false),
  field('repeated[]','Texto A'),field('repeated[]','Texto B'),
  field('new_image_caption[]','Legenda temporária'),
  field('gallery_files[]',new FakeFile('foto.jpg'),'file'),
];
const form = { querySelectorAll(selector) {assert.equal(selector,'[name]'); return elements;}};
class FakeFormData {
  constructor(ignored) {assert.equal(ignored,form);this.entries=[];
    for (const el of elements) {
      if ((el.type==='checkbox'||el.type==='radio')&&!el.checked) continue;
      this.entries.push([el.name,el.value]);
    }
  }
  forEach(fn) {this.entries.forEach(([v,k])=>fn(k,v));}
}
const messages=[];
const status = {set textContent(text) {messages.push(text);}, get textContent() {return messages.at(-1)||'';}};
const document = {querySelector(s) {if(s==='#news-save-status')return status; if(['#recover-news-draft','#ignore-news-draft','#news-draft-notice'].includes(s))return null;throw Error(s);}};
let blocks=[{kind:'paragraph',heading:'',content:'Texto salvo',collapsed:false}];
const script = `(()=>{${editor.slice(start,end)};return {snapshot,restoreDraft};})()`;
const api=vm.runInNewContext(script,{form,document,FormData:FakeFormData,File:FakeFile,
  updateSource(){},syncInputs(){},render(){},loadVisual(){},preview(){},categorySuggestion(){},updateCounters(){},updatePublication(){},
  get blocks(){return blocks;},set blocks(v){blocks=v;}});
const saved=api.snapshot();
assert.equal(saved.fields.title,'Manchete original');
assert.equal(saved.fields.featured,'1');
assert.deepEqual(Array.from(saved.fields['extra_categories[]']),['1','3']);
assert.deepEqual(Array.from(saved.fields['repeated[]']),['Texto A','Texto B']);
for(const forbidden of ['csrf','id','blocks_json','new_image_caption[]','gallery_files[]'])assert.equal(forbidden in saved.fields,false,forbidden);
// Simula reabrir notícia com opções do banco diferentes das gravadas no rascunho.
elements.find(x=>x.name==='title').value='Versão atual no banco';
for (const el of elements.filter(x=>x.name==='extra_categories[]'))el.checked=el.value==='2';
elements.find(x=>x.name==='featured').checked=false;
elements.find(x=>x.name==='remove_media[]').checked=true;
elements.filter(x=>x.name==='repeated[]').forEach(x=>x.value='Substituído');
api.restoreDraft(saved);
assert.equal(elements.find(x=>x.name==='title').value,'Manchete original');
assert.deepEqual(elements.filter(x=>x.name==='extra_categories[]').map(x=>x.checked),[true,false,true]);
assert.equal(elements.find(x=>x.name==='featured').checked,true);
assert.equal(elements.find(x=>x.name==='remove_media[]').checked,false,'Campo ausente deve desmarcar checkbox');
assert.deepEqual(elements.filter(x=>x.name==='repeated[]').map(x=>x.value),['Texto A','Texto B']);
assert.equal(elements.find(x=>x.name==='id').value,'91','Não restaurar ID do artigo');
assert.equal(elements.find(x=>x.name==='csrf').value,'csrf-atual','Não restaurar token CSRF');
assert.equal(elements.find(x=>x.name==='new_image_caption[]').value,'Legenda temporária','Metadados de arquivo local não são recuperáveis');
// Testa rascunho com categorias/featured desmarcadas.
api.restoreDraft({fields:{title:'Rascunho sem destaques','extra_categories[]':[]},blocks:[]});
assert.deepEqual(elements.filter(x=>x.name==='extra_categories[]').map(x=>x.checked),[false,false,false]);
assert.equal(elements.find(x=>x.name==='featured').checked,false);
assert.equal(elements.find(x=>x.name==='remove_media[]').checked,false);
assert.equal(elements.find(x=>x.name==='title').value,'Rascunho sem destaques');
assert.equal(elements.find(x=>x.name==='id').value,'91');
assert.match(status.textContent,/fotos, anexos/);
console.log('OK: autosave exclui arquivos/segredos e mantém arrays, campos escalares, permissões e blocos');
console.log('OK: recuperação restaura checkboxes marcados/desmarcados, arrays por índice e ignora uploads');
