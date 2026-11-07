'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const code=fs.readFileSync(__dirname+'/../public/assets/js/integrations-admin.js','utf8');
const make=(id,extra={})=>Object.assign({id,hidden:false,open:false,attrs:{},dataset:{},tagName:'DIV',listeners:{},
  addEventListener(name,cb){this.listeners[name]=cb;},setAttribute(k,v){this.attrs[k]=v;},
  classList:{toggle(){}},scrollIntoView(){},getAttribute(k){return this.attrs[k];}},extra);
const tabs=['google','diagnostico','paginas','catalogo'].map(x=>make(x));
const buttons=tabs.map(x=>make('btn-'+x.id,{dataset:{intTarget:x.id}}));
const wrap=(name)=>{
  const submit=make('submit-'+name,{type:'submit',disabled:false,textContent:'Medir '+name});
  const wait=make('wait-'+name,{hidden:true});
  const form=make('form-'+name,{querySelector(sel){return sel==='button[type=submit]'?submit:sel==='[data-pagespeed-wait]'?wait:null;}});
  return {submit,wait,form};
};
const mobile=wrap('mobile'),desktop=wrap('desktop');
const nav={querySelectorAll(){return buttons;}};
const hub={querySelector(sel){return sel==='[data-integrations-nav]'?nav:null;},
  querySelectorAll(sel){if(sel==='[data-int-section]')return tabs;if(sel==='[data-pagespeed-test-form]')return [mobile.form,desktop.form];return [];},
  contains(){return true;}};
const events={};
const ctx={document:{getElementById(id){return id==='integrations-hub'?hub:tabs.find(x=>x.id===id);}},location:{hash:''},
  window:{addEventListener(name,cb){events[name]=cb;}},navigator:{},history:{replaceState(){}},decodeURIComponent,encodeURIComponent};
vm.runInNewContext(code,ctx);
assert.equal(typeof mobile.form.listeners.submit,'function');
assert.equal(typeof desktop.form.listeners.submit,'function');
mobile.form.listeners.submit();
assert.equal(mobile.submit.disabled,true);
assert.equal(mobile.wait.hidden,false);
assert.equal(desktop.submit.disabled,false,'não bloqueia desktop ao iniciar mobile');
assert.equal(desktop.wait.hidden,true);
desktop.form.listeners.submit();
assert.equal(desktop.submit.disabled,true);
assert.equal(desktop.wait.hidden,false);
console.log('Integrações 1.0.82: progresso isolado para celular/computador aprovado.');
