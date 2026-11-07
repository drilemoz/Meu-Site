'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const code=fs.readFileSync(__dirname+'/../public/assets/js/integrations-admin.js','utf8');
const created={};
const make=(id,extra={})=>Object.assign({id,hidden:false,open:false,attrs:{},dataset:{},tagName:'DIV',listeners:{},
  addEventListener(name,cb){this.listeners[name]=cb;},setAttribute(k,v){this.attrs[k]=v;},
  classList:{toggle(){}},scrollIntoView(){this.scrolled=true;},getAttribute(k){return this.attrs[k];}},extra);
const tabs=['google','diagnostico','paginas','catalogo'].map(x=>make(x));
const detail=make('google-pagespeed',{tagName:'DETAILS',closest:()=>tabs[0]});
const buttons=tabs.map(x=>make('tab-'+x.id,{dataset:{intTarget:x.id}}));
const input=make('ga-input',{value:'G-ABCD12345',focus(){this.focused=true;},select(){this.selected=true;}});
const copy=make('copy',{attrs:{'data-int-copy':'ga-input'}});
const feedback=make('feedback',{hidden:true,textContent:''});
const button=make('pagespeed-submit',{type:'submit',disabled:false,textContent:'Medir'});
const wait=make('pagespeed-wait',{hidden:true});
const form=make('pagespeed-form',{querySelector(sel){return sel==='button[type=submit]'?button:sel==='[data-pagespeed-wait]'?wait:null;}});
const nav={querySelectorAll(){return buttons;}};
const hub={querySelector(sel){return ({'[data-integrations-nav]':nav,'[data-int-copy-feedback]':feedback,'[data-pagespeed-test-form]':form})[sel]??null;},
  querySelectorAll(sel){return sel==='[data-int-section]'?tabs:sel==='[data-int-copy]'?[copy]:[];},contains(){return true;}};
const loc={hash:'#google-pagespeed'};const events={};let clipboard='';
const nodes={'integrations-hub':hub,'google-pagespeed':detail,'ga-input':input};
const ctx={document:{getElementById:(x)=>nodes[x]??tabs.find(t=>t.id===x)},location:loc,
  window:{addEventListener(name,cb){events[name]=cb;}},navigator:{clipboard:{async writeText(s){clipboard=s;}}},
  history:{replaceState(a,b,hash){loc.hash=hash;}},decodeURIComponent,encodeURIComponent};
vm.runInNewContext(code,ctx);
assert.equal(detail.open,true,'Link direto abre detalhe');
assert.equal(tabs[0].hidden,false);
assert.equal(tabs[1].hidden,true);
form.listeners.submit();
assert.equal(button.disabled,true);
assert.equal(wait.hidden,false);
(async()=>{
  await copy.listeners.click();
  assert.equal(clipboard,'G-ABCD12345');
  assert.equal(feedback.hidden,false);
  input.value='';await copy.listeners.click();assert.match(feedback.textContent,/vazio/);
  loc.hash='#%invalid';assert.doesNotThrow(()=>events.hashchange());
  console.log('Integrações JS 181: deep link, PageSpeed pendente, cópia, vazio e hash inválido aprovados.');
})().catch(e=>{console.error(e);process.exitCode=1;});
