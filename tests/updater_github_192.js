'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const elements = Object.create(null);
function el(){return {hidden:false,value:'',textContent:'',disabled:false,checked:false, dataset:{},listeners:{},attributes:{},addEventListener(name,fn){this.listeners[name]=fn;},setAttribute(name,v){this.attributes[name]=v;},removeAttribute(name){delete this.attributes[name];}};}
for(const x of ['#updater-github-status','[data-github-label]','[data-github-message]','[data-github-release]','[data-github-version]','[data-github-notes]','#updater-github-form','[data-github-process]','[data-github-stage]','[data-github-detail]','[data-github-progress]','[data-github-final]','[data-github-final-title]','[data-github-final-message]','[data-github-refresh]','[data-github-retry]','.updater-github-actions','[data-github-diagnostic]','[data-github-diagnostic-title]','[data-github-diagnostic-detail]','[data-github-diagnostic-action]','[data-github-diagnostic-code]'])elements[x]=el();
const password=el(), token=el(), ack=el(), submit=el(), csrf=el();token.value='a'.repeat(32);
const form=elements['#updater-github-form'];form.action='/admin.php?action=updates';form.checkValidity=()=>ack.checked&&!!password.value;
form.querySelector=x=>({'input[name="confirm_password"]':password,'input[name="update_token"]':token,'input[name="confirm_install"]':ack,'button[type="submit"]':submit,'input[name="csrf_token"]':csrf}[x]??null);
const dialog=el();dialog.querySelector=x=>elements[x]??null;
const trigger=el();
const document={getElementById:x=>({'updater-github-dialog':dialog,'updater-github-trigger':trigger}[x]??null)};
let fetchCalls=0, ajax=null, redirects=[], intervalCallback=null;
async function fetchStub(url){fetchCalls++;if(url.includes('updater_info'))return {ok:true,json:async()=>({ok:true,installed:'1.0.91',configured:true,release:{newer:true,version:'1.0.92',downloadable:true,notes:'Nota <script> não executável'}})};
return {ok:true,json:async()=>({ok:true,progress:{stage:'backup',message:'Backup confirmado em andamento.'}})};}
class XHR{constructor(){ajax=this;}open(method,url){assert.equal(method,'POST');assert.equal(url,form.action);}send(data){assert.equal(data.form,form);}}
class FormDataStub{constructor(f){this.form=f;}}
const window={XMLHttpRequest:XHR,FormData:FormDataStub,location:{set href(url){redirects.push(url);}}};
class DOMParserStub{parseFromString(html){return {querySelector(sel){if(sel.includes('alert.error')&&html.includes('ERRO'))return {textContent:'Falha confirmada'};if(sel.includes('alert.success')&&html.includes('SUCESSO'))return {textContent:'Portal atualizado para 1.0.92. Backup concluído.'};return null;}};}}
const context={document,window,fetch:fetchStub,XMLHttpRequest:XHR,FormData:FormDataStub,DOMParser:DOMParserStub,setInterval(fn){intervalCallback=fn;return 1;},clearInterval(){intervalCallback=null;},encodeURIComponent};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../public/assets/js/updater-github.js'),'utf8'),context);
const flush=()=>new Promise(resolve=>setImmediate(resolve));
(async()=>{
assert.equal(ajax,null,'sem POST ao abrir modal');
trigger.listeners.click();await flush();
assert.equal(fetchCalls,1,'consulta somente após Visualizar');
assert.equal(elements['[data-github-version]'].textContent,'1.0.92');
assert.equal(elements['[data-github-notes]'].textContent,'Nota <script> não executável');
assert.equal(elements['[data-github-release]'].hidden,false);
password.value='correta';ack.checked=true;
const e={preventDefault(){this.prevented=true;}};form.listeners.submit(e);
assert.equal(e.prevented,true,'POST interceptado após validação');
assert.equal(dialog.dataset.updaterBusy,'1','janela bloqueada durante instalação');
assert.equal(elements['[data-github-progress]'].attributes.value,undefined,'porcentagem não inventada');
intervalCallback();await flush();
assert.equal(elements['[data-github-stage]'].textContent,'Criando backup','etapa realmente reportada pelo PHP');
ajax.status=200;ajax.responseText='SUCESSO';ajax.onload();
assert.equal(elements['[data-github-final-title]'].textContent,'Atualização concluída!');
assert.equal(elements['[data-github-refresh]'].textContent,'Atualizar página');
assert.equal(redirects.length,0,'sem redirecionamento automático');
elements['[data-github-refresh]'].listeners.click();assert.equal(redirects.length,1);
console.log('OK: consulta ao abrir, novidades sem HTML, estado real, senha/CSRF, sucesso confirmado e atualizar página');
})().catch(e=>{console.error(e);process.exitCode=1;});
