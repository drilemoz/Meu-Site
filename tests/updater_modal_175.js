'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');const vm=require('node:vm');const path=require('node:path');
const listeners={};const file={size:1048576,name:'Atualizacao-v1.0.75.zip'};
const fileInput={files:[file],reportValidity(){},focus(){}};
const submitBtn={disabled:false};const ack={checked:false,disabled:true,validCalls:0,reportValidity(){this.validCalls++}};
const fields={hidden:false};const confirmation={hidden:true};const fileLabel={textContent:''};
const accept={listeners:{},addEventListener(k,fn){this.listeners[k]=fn;},focus(){}};
const back={listeners:{},addEventListener(k,fn){this.listeners[k]=fn;}};
const dialog={dataset:{},attributes:{},setAttribute(k,v){this.attributes[k]=v;}};
const nodes={
 '.updater-progress-label':{textContent:''},'.updater-progress-value':{textContent:''},'.updater-progress-detail':{textContent:''},
 'progress':{value:0,removeAttribute(k){if(k==='value')delete this.value;}},
 '.updater-progress-result':{hidden:true,onclick:null},'.updater-progress-edit':{hidden:true,onclick:null}
};let progressUI=null;
const map={'input[name="operation"]':{value:'upload'},'input[type="file"][name="update_zip"]':fileInput,'button[type="submit"]':submitBtn,
 '[data-updater-fields]':fields,'[data-updater-confirm-step]':confirmation,'[data-updater-ack]':ack,'[data-updater-filename]':fileLabel,'[data-updater-accept]':accept,'[data-updater-back]':back};
const form={dataset:{updaterConfirmFlow:'manual'},action:'https://example.test/admin.php?action=updates',attributes:{},
 querySelector(q){return q==='.updater-progress'?progressUI:(map[q]??null);},
 closest(q){assert.equal(q,'dialog');return dialog;},checkValidity(){return !!fileInput.files[0]&&(ack.disabled||ack.checked);},
 reportValidity(){},setAttribute(k,v){this.attributes[k]=v;},addEventListener(k,fn){listeners[k]=fn;},append(node){progressUI=node;}};
const document={querySelectorAll:()=>[form],createElement(){return {hidden:true,attributes:{},querySelector(sel){return nodes[sel];},setAttribute(k,v){this.attributes[k]=v;},focus(){},innerHTML:''};}};
let sent=0,active=null,redirects=[];
class FakeXHR {constructor(){active=this;this.upload={listeners:{},addEventListener(k,fn){this.listeners[k]=fn;}};}
 open(method,url){assert.equal(method,'POST');assert.equal(url,form.action);}
 send(data){sent++;assert.equal(data.form,form);assert.equal(ack.checked,true);assert.equal(ack.disabled,false);}}
class FakeFormData{constructor(f){this.form=f;}}
const window={XMLHttpRequest:FakeXHR,FormData:FakeFormData,location:{set href(url){redirects.push(url);}}};
class DOMParser{parseFromString(html){return {querySelector(q){
  if(q.includes('alert.error')&&html.includes('ERRO'))return {textContent:'Senha incorreta'};
  if(q.includes('alert.success')&&html.includes('SUCESSO'))return {textContent:'Portal atualizado para 1.0.75. Backup confirmado.'};
  return null;
}};}}
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../public/assets/js/updater-progress.js'),'utf8'),{document,window,XMLHttpRequest:FakeXHR,FormData:FakeFormData,DOMParser});
function submit(){let e={prevented:false,preventDefault(){this.prevented=true;}};listeners.submit(e);return e;}
assert.equal(submit().prevented,true,'primeiro envio abre confirmação');
assert.equal(sent,0,'nenhum byte enviado antes da confirmação');
assert.equal(fields.hidden,true);assert.equal(confirmation.hidden,false);assert.equal(ack.disabled,false);
assert.equal(fileLabel.textContent,file.name);
accept.listeners.click();assert.equal(sent,0,'sem checkbox não transmite');assert.equal(ack.validCalls,1);
back.listeners.click();assert.equal(fields.hidden,false);assert.equal(confirmation.hidden,true);assert.equal(ack.disabled,true);
submit();ack.checked=true;accept.listeners.click();
assert.equal(sent,1,'transmite após consentimento e senha');
assert.equal(dialog.dataset.updaterBusy,'1','modal protegido durante upload');
assert.equal(fields.hidden,true);assert.equal(confirmation.hidden,true);
assert.equal(nodes['.updater-progress-value'].textContent,'0%');
submit();assert.equal(sent,1,'submissão duplicada bloqueada');
active.upload.listeners.progress({lengthComputable:true,loaded:524288,total:1048576});
assert.equal(nodes['.updater-progress-value'].textContent,'50%','percentual baseado em bytes');
active.upload.listeners.load();assert.equal(nodes['.updater-progress-value'].textContent,'Processando…');
assert.equal('value' in nodes.progress,false,'instalação sem porcentagem inventada');
active.status=200;active.responseText='ERRO';active.onload();
assert.equal(dialog.dataset.updaterBusy,undefined);assert.equal(nodes['.updater-progress-detail'].textContent,'Senha incorreta');
assert.equal(nodes['.updater-progress-edit'].hidden,false,'permite revisar erro confirmado');
assert.equal(redirects.length,0,'sem rolagem ou redirecionamento em erro');
nodes['.updater-progress-edit'].onclick();assert.equal(fields.hidden,false);assert.equal(ack.disabled,true);
submit();ack.checked=true;accept.listeners.click();
active.upload.listeners.load();active.status=200;active.responseText='SUCESSO';active.onload();
assert.equal(redirects.length,0,'sucesso mantém resultado no modal, sem navegar sozinho');
assert.equal(nodes['.updater-progress-value'].textContent,'Concluído');
assert.equal(nodes['.updater-progress-result'].hidden,false);
nodes['.updater-progress-result'].onclick();assert.equal(redirects.length,1,'botão abre a página de resultado');
console.log('OK: confirmação, progresso real, bloqueio de modal, erro, nova tentativa, sucesso e resultado sem rolagem');
