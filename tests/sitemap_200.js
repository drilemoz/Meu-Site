'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const src=fs.readFileSync(__dirname+'/../public/assets/js/sitemap-admin.js','utf8');
function button(attrs={}){
  return {disabled:false,attrs,textContent:'',handlers:{},addEventListener(n,fn){this.handlers[n]=fn;},getAttribute(k){return attrs[k]||'';},closest(){return {querySelector(){return probeResult;}}}};
}
const status={textContent:'',children:[],replaceChildren(){this.children=[];},appendChild(v){this.children.push(v)}};
const file=button(),copy=button({'data-sitemap-copy':'https://iguatama.online/uaicortes'}),test=button({'data-sitemap-probe':'/uaicortes'});
const probeResult={textContent:''};let calls=[];let copied='';
const document={querySelector(s){return s==='.sitemap-center'?{}:null;},getElementById(s){return s==='sitemap-online-results'?status:s==='sitemap-check-files'?file:null;},querySelectorAll(s){return s==='[data-sitemap-copy]'?[copy]:s==='[data-sitemap-probe]'?[test]:[];},createElement(n){return {nodeName:n,textContent:''};}};
const fetch=async (url,options)=>{calls.push({url,options});return {ok:url.endsWith('/ads.txt')?false:true,status:url.endsWith('/ads.txt')?404:200,url,body:{async cancel(){}}};};
vm.runInNewContext(src,{document,window:{location:{origin:'https://iguatama.online'}},navigator:{clipboard:{async writeText(v){copied=v;}}},URL,AbortController,setTimeout,clearTimeout,fetch});
(async()=>{
  assert.equal(calls.length,0,'nenhuma consulta ocorre sem toque');
  await file.handlers.click();
  assert.deepEqual(calls.map(x=>x.url),['https://iguatama.online/sitemap.php','https://iguatama.online/robots.txt','https://iguatama.online/ads.txt']);
  assert(status.children.some(x=>x.textContent.includes('/ads.txt: HTTP 404')),'mostra ausência do ads.txt');
  assert(status.children.some(x=>x.textContent.includes('não comprova')),'distingue HTTP de indexação');
  assert(calls.every(x=>x.options.credentials==='same-origin' && x.options.cache==='no-store'),'requisições same-origin e sob demanda');
  await copy.handlers.click();assert.equal(copied,'https://iguatama.online/uaicortes');assert.equal(copy.textContent,'Copiado ✓');
  await test.handlers.click();assert.equal(probeResult.textContent,'HTTP 200 · acessível','verifica link individual');
  const previous=calls.length;test.attrs['data-sitemap-probe']='//exemplo.org/admin';await test.handlers.click();
  assert.equal(calls.length,previous,'não consulta URL de outra origem');
  console.log('Sitemap 200 JS: sem chamadas automáticas, HTTP, cópia, URL segura, erro 404 e same-origin OK');
})().catch(e=>{console.error(e);process.exitCode=1;});
