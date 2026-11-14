'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const source=fs.readFileSync(__dirname+'/../public/assets/js/integrations-admin.js','utf8');

class El {
  constructor(id='',tag='DIV',cl='') {this.id=id;this.tagName=tag;this.className=cl;this.childNodes=[];this.parentElement=null;this.listeners={};this.dataset={};this.attrs={};this.hidden=false;this.open=false;this.textContent='';this.value='';this.scrolled=false;
    const flags=new Set(cl.split(' ').filter(Boolean));this.classList={add:x=>flags.add(x),remove:x=>flags.delete(x),toggle:(x,b)=>b?flags.add(x):flags.delete(x),contains:x=>flags.has(x)};
  }
  appendChild(node){if(node.parentElement)node.parentElement.childNodes=node.parentElement.childNodes.filter(x=>x!==node);this.childNodes.push(node);node.parentElement=this;return node;}
  replaceChildren(...nodes){for(const node of this.childNodes)node.parentElement=null;this.childNodes=[];for(const node of nodes)this.appendChild(node);}
  addEventListener(event,fn){(this.listeners[event]??=[]).push(fn);}
  fire(event,obj={}){for(const fn of this.listeners[event]??[])fn(obj);}
  matches(selector){if(selector==='summary')return this.tagName==='SUMMARY';return selector.split(',').some(s=>{const cls=s.trim().match(/\.[\w-]+/g)||[];return cls.length>0&&cls.every(v=>this.className.split(' ').includes(v.slice(1)));});}
  closest(selector){let node=this;while(node){if(selector==='[data-int-section]'?node.dataset.intSection===true:selector==='summary'?node.tagName==='SUMMARY':selector==='#integrations-hub'?node.id==='integrations-hub':node.matches(selector))return node;node=node.parentElement;}return null;}
  setAttribute(k,v){this.attrs[k]=v;}
  getAttribute(k){return this.attrs[k]||null;}
  focus(){this.focused=true;}
  scrollIntoView(){this.scrolled=true;}
  contains(node){for(let n=node;n;n=n.parentElement)if(n===this)return true;return false;}
  querySelector(selector){if(selector===':scope > summary')return this.childNodes.find(x=>x.tagName==='SUMMARY')??null;if(selector==='.int-service-copy strong')return this._strong??null;if(selector==='h3, strong')return this._strong??null;return this._query?.[selector]??null;}
  querySelectorAll(selector){return this._queryAll?.[selector]??[];}
}
const hub=new El('integrations-hub');const nav=new El('nav','NAV');const dialog=new El('int-dialog','DIALOG','int-dialog');const panel=new El('panel','DIV','int-dialog-panel');const title=new El('title','H2');const back=new El('back','BUTTON');const scroller=new El('scroll','DIV');const close=new El('close','BUTTON');const foot=new El('foot','BUTTON');
dialog._query={'[data-int-dialog-panel]':panel,'[data-int-dialog-title]':title,'[data-int-dialog-back]':back,'[data-int-dialog-scroll]':scroller,'[data-int-dialog-close]':close};dialog._queryAll={'[data-int-dialog-close]':[close,foot]};dialog.open=false;
dialog.showModal=()=>{dialog.open=true;};dialog.close=()=>{dialog.open=false;dialog.fire('close');};hub.appendChild(dialog);dialog.appendChild(panel);
const sections=['google','diagnostico','paginas','catalogo'].map(id=>{const el=new El(id,'SECTION');el.dataset.intSection=true;hub.appendChild(el);return el;});
const buttons=sections.map(el=>{const b=new El('tab-'+el.id,'BUTTON');b.dataset.intTarget=el.id;return b;});nav._queryAll={'[data-int-target]':buttons};
const service=new El('google-ga4','DETAILS','int-service int-ga');sections[0].appendChild(service);
const serviceSummary=new El('summary','SUMMARY');serviceSummary._strong=new El('strong','STRONG');serviceSummary._strong.textContent='Google Analytics 4';service.appendChild(serviceSummary);
const serviceBody=new El('body','DIV','int-service-body');service.appendChild(serviceBody);
const help=new El('help','DETAILS','compact-help');serviceBody.appendChild(help);
const helpSummary=new El('help-summary','SUMMARY');helpSummary.attrs['aria-label']='Ajuda do GA4';help.appendChild(helpSummary);const helpText=new El('help-content','DIV','compact-help-content');helpText.textContent='Encontre o ID em Fluxos de dados';help.appendChild(helpText);
hub._query={'[data-integrations-nav]':nav,'#int-dialog':dialog};hub._queryAll={'[data-int-section]':sections,'a[href^="#"]':[]};
const getById=id=>({'integrations-hub':hub,'int-dialog':dialog,'google-ga4':service,...Object.fromEntries(sections.map(x=>[x.id,x]))})[id];
const events={};const location={hash:''};const doc={getElementById:getById,activeElement:serviceSummary,body:{classList:{add(){},remove(){}}}};
vm.runInNewContext(source,{document:doc,location,window:{addEventListener(k,fn){events[k]=fn;}},history:{replaceState(a,b,v){location.hash=v;}},navigator:{},decodeURIComponent,encodeURIComponent});
assert.equal(hub.classList.contains('int-modal-ready'),true);
assert.equal(sections[1].hidden,true,'abas são escondidas');
const click=target=>{let stopped=false;hub.fire('click',{target,preventDefault(){stopped=true;}});return stopped;};
assert.equal(click(serviceSummary),true,'abrir serviço bloqueia details padrão');
assert.equal(dialog.open,true,'janela nativa abre');
assert.equal(service.open,false,'cartão não expande na página');
assert.equal(service.childNodes.includes(serviceBody),false,'formulário é movido sem clonagem');
assert.equal(panel.childNodes.includes(serviceBody),true,'formulário está no diálogo');
assert.equal(title.textContent,'Google Analytics 4');
assert.equal(click(helpSummary),true,'ajuda abre como subjanela');
assert.equal(panel.childNodes.includes(helpText),true,'conteúdo da ajuda visível');
assert.equal(back.hidden,false,'subjanela mostra voltar');
back.fire('click');
assert.equal(panel.childNodes.includes(serviceBody),true,'voltar restaura serviço');
assert.equal(help.childNodes.includes(helpText),true,'ajuda devolvida ao local original');
close.fire('click');
assert.equal(dialog.open,false,'fechar janela');
assert.equal(service.childNodes.includes(serviceBody),true,'formulário devolvido ao cartão');
assert.equal(serviceSummary.scrolled,false,'não força rolagem na página');
location.hash='#google-ga4';events.hashchange();
assert.equal(dialog.open,true,'deep link abre janela automaticamente');
assert.equal(sections[0].hidden,false);
dialog.close();
buttons[1].fire('click');assert.equal(sections[1].hidden,false,'aba diagnóstico abre');
assert.equal(sections[0].hidden,true);
console.log('Integrações v1.0.89: janelas nativas, formulário movido, ajuda, voltar, fechar e deep link OK.');
