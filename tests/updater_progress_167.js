'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const fields={'input[name="operation"]':{value:'upload'},'input[type="file"][name="update_zip"]':{files:[{size:1048576}]},'button[type="submit"]':{disabled:false}};
const nodes={
 '.updater-progress-label':{textContent:''},'.updater-progress-value':{textContent:''},'.updater-progress-detail':{textContent:''},
 'progress':{value:0,removeAttribute(key){if(key==='value')delete this.value;}},
 '.updater-progress-result':{hidden:true,onclick:null}
};
let progressUI=null;
const form={dataset:{},action:'http://local/admin.php?action=updates',listeners:{},
  checkValidity:()=>true,querySelector:(sel)=>sel==='.updater-progress'?progressUI:(fields[sel]||null),
  append:(el)=>{progressUI=el;},addEventListener(type,fn){this.listeners[type]=fn;}};
const document={querySelectorAll:()=>[form],createElement:()=>({hidden:true,querySelector:(sel)=>nodes[sel],setAttribute(){},innerHTML:''})};
let activeRequest=null;
class FakeXHR{
  constructor(){this.upload={listeners:{},addEventListener:(key,fn)=>{this.upload.listeners[key]=fn;}};activeRequest=this;}
  open(method,url){assert.equal(method,'POST');assert.equal(url,form.action);}
  send(data){assert(data.form===form);}
}
class FakeFormData{constructor(f){this.form=f;}}
let redirects=[];
const window={XMLHttpRequest:FakeXHR,FormData:FakeFormData,location:{set href(x){redirects.push(x);}}};
vm.runInNewContext(fs.readFileSync(path.join(__dirname,'../public/assets/js/updater-progress.js'),'utf8'),{document,window,XMLHttpRequest:FakeXHR,FormData:FakeFormData,DOMParser:class{parseFromString(html){return {querySelector:(q)=>q.includes('alert.error')&&html.includes('ERRO')?{textContent:'Falha detectada'}:q.includes('alert.success')&&html.includes('SUCESSO')?{textContent:'Portal atualizado'}:null};}}});
function submit(){const e={prevented:false,preventDefault(){this.prevented=true;}};form.listeners.submit(e);return e;}
assert.equal(submit().prevented,true,'intercepta envio sem remover validação nativa');
assert.equal(fields['button[type="submit"]'].disabled,true,'bloqueia duplicação');
assert.equal(nodes['.updater-progress-value'].textContent,'0%','começa com zero enviado');
assert.equal(submit().prevented,true,'impede segundo envio simultâneo');
activeRequest.upload.listeners.progress({lengthComputable:true,loaded:524288,total:1048576});
assert.equal(nodes['.updater-progress-value'].textContent,'50%','progresso de bytes real');
activeRequest.upload.listeners.load();
assert.equal(nodes['.updater-progress-value'].textContent,'Processando…','não inventa progresso de instalação');
assert.equal(Object.hasOwn(nodes.progress,'value'),false,'barra indeterminada');
activeRequest.status=200;activeRequest.responseText='SUCESSO';activeRequest.onload();
assert.equal(redirects.length,1,'redireciona somente após sucesso confirmado');
assert.equal(fields['button[type="submit"]'].disabled,false,'desbloqueia botão');
// Falhas não podem resultar em 'instalado' nem reenviar automaticamente.
submit();activeRequest.upload.listeners.progress({lengthComputable:true,loaded:1,total:4});
activeRequest.status=200;activeRequest.responseText='ERRO';activeRequest.onload();
assert.equal(redirects.length,1,'erro de instalação não causa redirecionamento de sucesso');
assert.equal(nodes['.updater-progress-detail'].textContent,'Falha detectada','mensagem real do servidor é visível');
submit();activeRequest.ontimeout();
assert.equal(redirects.length,1,'timeout não gera reenvio');
assert.match(nodes['.updater-progress-detail'].textContent,/Confira o histórico/);
console.log('OK: upload 50%, servidor indeterminado, duplicação, sucesso, erro e timeout');
