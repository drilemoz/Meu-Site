'use strict';
const assert=require('node:assert/strict'), fs=require('node:fs'),vm=require('node:vm'),path=require('node:path');
const listeners={};
const file={size:1048576,name:'Atualizacao-v1.0.75.zip'};
const fileInput={files:[file],listeners:{},reportValidity(){},focus(){},addEventListener(k,fn){this.listeners[k]=fn;}};
const submitBtn={disabled:false}; const ack={checked:false,disabled:true,validCalls:0,reportValidity(){this.validCalls++}};
const fields={hidden:false}, confirmation={hidden:true},fileLabel={textContent:''};
const accept={listeners:{},addEventListener(k,fn){this.listeners[k]=fn;},focus(){}};
const back={listeners:{},addEventListener(k,fn){this.listeners[k]=fn;}};
const diagButton={listeners:{},addEventListener(k,fn){this.listeners[k]=fn;},disabled:false};
const diagFields={'[data-updater-zip-title]':{textContent:''},'[data-updater-zip-detail]':{textContent:''},'[data-updater-zip-action]':{textContent:''},'[data-updater-zip-code]':{textContent:''}};
const diag={hidden:true,dataset:{},querySelector(k){return diagFields[k];}};
const dialog={dataset:{},attributes:{},setAttribute(k,v){this.attributes[k]=v;}};
const nodes={'.updater-progress-label':{textContent:''},'.updater-progress-value':{textContent:''},'.updater-progress-detail':{textContent:''},
 'progress':{value:0,removeAttribute(k){if(k==='value')delete this.value;}},'.updater-progress-result':{hidden:true,onclick:null},'.updater-progress-edit':{hidden:true,onclick:null}};
let progressUI=null;
const map={'input[name="operation"]':{value:'upload'},'input[type="file"][name="update_zip"]':fileInput,'input[name="csrf"]':{value:'csrf-test'},'button[type="submit"]':submitBtn,
 '[data-updater-fields]':fields,'[data-updater-confirm-step]':confirmation,'[data-updater-ack]':ack,'[data-updater-filename]':fileLabel,'[data-updater-accept]':accept,'[data-updater-back]':back,
 '[data-updater-zip-diagnostic]':diag,'[data-updater-diagnose-zip]':diagButton};
const form={dataset:{updaterConfirmFlow:'manual'},action:'https://example.test/admin.php?action=updates',querySelector(q){return q==='.updater-progress'?progressUI:(map[q]??null);},
 closest(q){assert.equal(q,'dialog');return dialog;},checkValidity(){return !!fileInput.files[0]&&(ack.disabled||ack.checked);},reportValidity(){},addEventListener(k,fn){listeners[k]=fn;},append(node){progressUI=node;}};
const document={querySelectorAll:()=>[form],createElement(){return {hidden:true,attributes:{},querySelector(sel){return nodes[sel];},setAttribute(k,v){this.attributes[k]=v;},focus(){},innerHTML:''};}};
let sent=0,active=null,redirects=[],probes=0;
class FakeXHR{constructor(){active=this;this.upload={listeners:{},addEventListener(k,fn){this.listeners[k]=fn;}};}open(method,url){assert.equal(method,'POST');assert.equal(url,form.action);}send(data){sent++;assert.equal(data.form,form);assert.equal(ack.checked,true);}}
class FakeFormData{constructor(f){this.form=f;this.fields={};}append(name,value){this.fields[name]=value;}}
const window={XMLHttpRequest:FakeXHR,FormData:FakeFormData,location:{set href(url){redirects.push(url);}}};
class DOMParser{parseFromString(html){return {querySelector(q){if(q.includes('alert.error')&&html.includes('ERRO'))return {textContent:'Senha incorreta'};
 if(q.includes('alert.success')&&html.includes('SUCESSO'))return {textContent:'Portal atualizado para 1.0.75. Backup confirmado.'};return null;}};}}
async function fetchStub(url,options){probes++;assert.equal(options.body.fields.operation,'diagnose_zip');assert.equal(options.body.fields.csrf,'csrf-test');
 assert.equal(options.body.fields.confirm_password,undefined);return {ok:true,headers:{get:()=> 'application/json'},json:async()=>({ok:true,diagnostic:{code:'compatible',title:'Compatível',detail:'Manifesto válido',action:'Faça backup'}})};}
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../public/assets/js/updater-progress.js'),'utf8'),{document,window,XMLHttpRequest:FakeXHR,FormData:FakeFormData,DOMParser,fetch:fetchStub});
const tick=()=>new Promise(resolve=>setImmediate(resolve));
function submit(){const e={prevented:false,preventDefault(){this.prevented=true;}};listeners.submit(e);return e;}
(async()=>{
 assert.equal(submit().prevented,true);assert.equal(sent,0,'nenhum byte de instalação antes do diagnóstico');
 await tick();await tick();assert.equal(probes,1);assert.equal(confirmation.hidden,false);assert.equal(fields.hidden,true);
 assert.equal(fileLabel.textContent,file.name);accept.listeners.click();assert.equal(sent,0);assert.equal(ack.validCalls,1);
 back.listeners.click();assert.equal(fields.hidden,false);assert.equal(confirmation.hidden,true);
 submit();await tick();await tick();ack.checked=true;accept.listeners.click();assert.equal(sent,1,'instala após análise e consentimento');
 assert.equal(dialog.dataset.updaterBusy,'1');submit();assert.equal(sent,1,'não duplica');
 active.upload.listeners.progress({lengthComputable:true,loaded:524288,total:1048576});assert.equal(nodes['.updater-progress-value'].textContent,'50%');
 active.upload.listeners.load();assert.equal('value' in nodes.progress,false);
 active.status=200;active.responseText='ERRO';active.onload();assert.equal(nodes['.updater-progress-detail'].textContent,'Senha incorreta');
 assert.equal(nodes['.updater-progress-edit'].hidden,false);nodes['.updater-progress-edit'].onclick();
 submit();await tick();await tick();ack.checked=true;accept.listeners.click();active.upload.listeners.load();active.status=200;active.responseText='SUCESSO';active.onload();
 assert.equal(redirects.length,0);assert.equal(nodes['.updater-progress-value'].textContent,'Concluído');
 nodes['.updater-progress-result'].onclick();assert.equal(redirects.length,1);
 console.log('OK: diagnóstico sem senha, confirmação, progresso real, erro, revisão e sucesso');
})().catch(e=>{console.error(e);process.exitCode=1;});
