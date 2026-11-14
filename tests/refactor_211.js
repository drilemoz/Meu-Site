'use strict';
const assert = require('assert');
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const src = fs.readFileSync(path.join(__dirname,'../public/assets/js/seo-settings.js'),'utf8');

const listeners = {};
const elements = new Map();
const fields = new Map();
function makeElement(id = '') {
  return {
    id, hidden:false, src:'', value:'', textContent:'', type:'text', checked:false,
    dataset:{}, className:'', tabIndex:0, open:false,
    listeners:{}, attributes:{},
    classList:{toggle(){}},
    addEventListener(name, callback) {this.listeners[name] = callback;},
    setCustomValidity(message) {this.validationMessage = message;},
    removeAttribute(key) {if (key === 'src') this.src = ''; else delete this.attributes[key];},
    setAttribute(name,value) {this.attributes[name]=value;},
    replaceChildren(...children) {this.children=children;},
    focus() {this.focused=true;},
    querySelectorAll() {return [];}
  };
}
const get = id => {if (!elements.has(id)) elements.set(id,makeElement(id));return elements.get(id);};
const setField = (name,value,type='text') => {const el=makeElement(name);el.type=type;if(type==='checkbox')el.checked=!!value;else el.value=value;fields.set(name,el);return el;};
setField('site_name','Iguatama Online');
setField('seo_home_title','Iguatama Online');
setField('seo_title_template','%title% | %site%');
setField('seo_default_description','Notícias de Iguatama e região. Acompanhe a cobertura local e serviços do município.');
setField('seo_site_url','https://iguatama.online');
setField('seo_social_image','/uploads/2026/11/capa.webp');
setField('seo_favicon_image','/uploads/2026/11/icone.png');
setField('seo_pretty_urls',true,'checkbox');
setField('seo_index',true,'checkbox');
const form = get('site-settings-form');
form.elements={namedItem:name=>fields.get(name)??null};
const center=get('seo-portal-center');
for (const id of ['seo-content-dialog','seo-pages-dialog','seo-advanced-dialog','seo-diagnostic-dialog']) {
  const el=get(id);el.showModal=function(){this.open=true;};el.close=function(){this.open=false;};
}
function FakeFormData() {return {entries:()=>[...fields.entries()].filter(([name,f])=>f.type!=='checkbox'||f.checked).map(([name,f])=>[name,f.value])};}
const context={
  document:{getElementById:get,createElement:()=>makeElement()},
  window:{location:{origin:'https://iguatama.online'}},
  FormData:FakeFormData,URL,Event:class Event {constructor(type,opts){this.type=type;this.bubbles=!!opts?.bubbles;}},
  console
};
vm.runInNewContext(src,context,{filename:'seo-settings.js'});
assert.strictEqual(get('seo-preview-title').textContent,'Iguatama Online');
assert.strictEqual(get('seo-social-preview-title').textContent,'Iguatama Online');
assert.match(get('seo-preview-description').textContent,/Notícias de Iguatama/);
assert.strictEqual(get('seo-social-preview-image').src,'https://iguatama.online/uploads/2026/11/capa.webp');
assert.strictEqual(get('seo-social-selected-image').hidden,false);
assert.strictEqual(get('seo-favicon-preview').src,'https://iguatama.online/uploads/2026/11/icone.png');
fields.get('seo_social_image').value='https://example.com/nao-e-imagem';
form.listeners.input();
assert.strictEqual(get('seo-social-preview-image').hidden,true);
assert.strictEqual(get('seo-social-preview-empty').hidden,false);
assert.strictEqual(get('seo-social-selected-image').hidden,true);
fields.get('seo_favicon_image').value='';
form.listeners.input();
assert.strictEqual(get('seo-favicon-preview').src,'/assets/img/favicon.svg');
get('seo-portal-check').listeners.click();
assert.match(get('seo-portal-status').textContent,/\d\/5 verificações/);
assert.strictEqual(get('seo-diagnostic-dialog').open,true);
console.log('Refatoração JavaScript 1.0.111 aprovada: título, descrições, imagens, favicon, diagnóstico e janela.');
