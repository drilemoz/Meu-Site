'use strict';
const assert = require('node:assert/strict');
const {createRequests,createUploadQueue} = require('../public/assets/js/media-state.js');
const fs = require('node:fs');
const path = require('node:path');

const deferred = () => {let resolve,reject;const promise=new Promise((res,rej)=>{resolve=res;reject=rej;});return {promise,resolve,reject};};

async function testStaleQueries() {
  const requests=createRequests();
  const first=requests.next(),old=deferred();
  const second=requests.next(),latest=deferred();
  assert.equal(requests.isCurrent(first),false,'Consulta antiga invalidada');
  if(first.signal)assert.equal(first.signal.aborted,true,'Consulta anterior abortada');
  assert.equal(requests.isCurrent(second),true);
  let displayed='';
  const display=async(ticket,promise)=>{const result=await promise;if(requests.isCurrent(ticket))displayed=result;};
  const p1=display(first,old.promise),p2=display(second,latest.promise);
  latest.resolve('Outubro');await p2;
  old.resolve('Setembro');await p1;
  assert.equal(displayed,'Outubro','Resposta tardia não substitui pasta mais recente');
  requests.invalidate();assert.equal(requests.isCurrent(second),false,'Fechar modal invalida consulta ativa');
  console.log('OK: resultados de buscas antigas não substituem a tela atual');
}
async function testPartialUpload() {
  const queue=createUploadQueue();
  const a={name:'foto-a.jpg'},b={name:'foto-b.jpg'},c={name:'foto-c.jpg'};
  assert(queue.select([a,b,c]));
  const sent=[];let attempts=0;
  let r=await queue.send(async file=>{sent.push(file.name);if(file===b&&++attempts===1)throw new Error('Falha de conexão');});
  assert.equal(r.uploaded,1);assert.equal(r.remaining,2);assert.equal(r.error.message,'Falha de conexão');
  assert.deepEqual(queue.remaining(),[b,c],'Só arquivos ainda não confirmados ficam na fila');
  assert.equal(queue.isSending(),false);
  r=await queue.send(async file=>{sent.push(file.name);});
  assert.equal(r.uploaded,2);assert.equal(r.remaining,0);assert.equal(r.error,null);
  assert.deepEqual(sent,['foto-a.jpg','foto-b.jpg','foto-b.jpg','foto-c.jpg'],'Foto confirmada não é reenviada');
  console.log('OK: retry após falha parcial não reenvia fotos confirmadas');
}
async function testUploadConcurrency() {
  const queue=createUploadQueue(),slow=deferred();
  const selected=[{name:'capa.jpg'}, {name:'nova.jpg'}];queue.select(selected);
  const run=queue.send(async file=>{if(file===selected[0])await slow.promise;});
  assert.equal(queue.isSending(),true);
  assert.equal(queue.select([{name:'intrusa.jpg'}]),false,'Seleção durante upload não substitui fila');
  const concurrent=await queue.send(async()=>{throw new Error('Upload duplo');});
  assert.equal(concurrent.busy,true,'Submit concorrente não inicia segundo envio');
  slow.resolve();const result=await run;
  assert.equal(result.uploaded,2);assert.deepEqual(queue.remaining(),[]);
  assert.equal(queue.isSending(),false);
  console.log('OK: evita uploads concorrentes e seleção no meio de lote');
}
function testWiring() {
  const js=fs.readFileSync(path.join(__dirname,'../public/assets/js/media-library.js'),'utf8');
  const admin=['../public/admin.php','../app/admin_views/layout.php'].map(p=>fs.readFileSync(path.join(__dirname,p),'utf8')).join('\n');
  const media=fs.readFileSync(path.join(__dirname,'../app/media_admin.php'),'utf8');
  const statePosition=admin.indexOf('src="/assets/js/media-state.js"');
  const managerPosition=admin.indexOf('src="/assets/js/media-library.js');
  assert(statePosition>0 && statePosition<managerPosition,'Dependência carregada antes do gerenciador');
  assert(js.includes("searchBox=$('[data-media-search-box]')") && js.includes("uploadName=$('[data-media-upload-name]')"),'Referências antes ausentes são inicializadas');
  assert(js.includes('requests.isCurrent(ticket)') && js.includes('requests.invalidate()'),'Atualização de galeria protegida por geração');
  assert(js.includes('uploads.remaining()') && js.includes('Reenviar pendentes'),'Retry usa somente pendentes');
  assert(media.includes('data-file-enhanced="1"'),'Input já estilizado não recebe botão duplicado');
  console.log('OK: integração PHP/JS e controles de pesquisa/upload');
}
(async()=>{await testStaleQueries();await testPartialUpload();await testUploadConcurrency();testWiring();})().catch(err=>{console.error(err);process.exitCode=1;});
