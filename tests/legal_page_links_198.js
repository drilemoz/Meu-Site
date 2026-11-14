'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const script=fs.readFileSync(path.join(__dirname,'../public/assets/js/legal-apps-admin.js'),'utf8');
const open=(query)=>{
  let opened=0,heading='';
  const nodes=[];
  const mk=(tag='div')=>({tagName:tag.toUpperCase(),append(...children){this.children??=[];this.children.push(...children);},set textContent(v){this._text=v;},get textContent(){return this._text||'';}});
  const dlg={showModal(){opened++},close(){},addEventListener(){}};
  const panel={replaceChildren(...children){nodes.splice(0,nodes.length,...children)}};
  const editor={value:'## Título\n\n<script>não executar</script>'};
  const preview={listeners:{},addEventListener(type,fn){this.listeners[type]=fn;}};
  const title={set textContent(v){heading=v;}};
  const document={
    querySelector(x){return ({'[data-legal-preview-open]':preview,'#legal-page-edit-form [name=title]':{value:'Privacidade'}})[x]||null;},
    querySelectorAll(){return []},
    getElementById(id){return ({'legal-body':editor,'legal-preview-dialog':dlg,'legal-preview-heading':title,'legal-preview-content':panel})[id]||null;},
    createDocumentFragment(){return mk('fragment')},
    createElement:mk,
    createTextNode(s){return {textContent:s}}
  };
  const window={location:{search:query},IguaAdminModal:{open(d,t){assert.equal(d,dlg);assert.equal(t,preview);opened++;return true;}}};
  vm.runInNewContext(script,{document,window,URLSearchParams});
  return {count:()=>opened,title:()=>heading,nodes,preview};
};
const draft=open('?action=legal_page_edit&id=5&legal_preview=1');
assert.equal(draft.count(),1,'Link abre automaticamente modal privado');
assert.equal(draft.title(),'Privacidade','Cabeçalho do rascunho');
assert.ok(draft.nodes.length,'Prévia renderizada');
const ordinary=open('?action=legal_page_edit&id=5');
assert.equal(ordinary.count(),0,'Edição normal não abre modal automaticamente');
ordinary.preview.listeners.click({currentTarget:ordinary.preview});
assert.equal(ordinary.count(),1,'Botão de prévia continua funcionando');
console.log('OK: prévia profunda autenticada, sem abertura automática na edição comum');
