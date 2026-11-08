const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname,'../public/assets/js/ga4-consent.js'),'utf8');
function testGa4(consent,blocked) {
  const cookies = {'igua_ga4_choice':consent?'allow.G-TEST1234':'deny.G-TEST1234'};
  const scripts=[];
  const elems={};
  for (const id of ['ga4Consent','ga4Allow','ga4Deny','ga4ConsentChange','ga4ConsentStatus']) {
    elems[id]={hidden:true,disabled:false,getAttribute(name){return name==='data-ga4-id'?'G-TEST1234':null;},addEventListener(){}};
  }
  const document={
    getElementById(id){return elems[id]||null;},
    get cookie(){return Object.entries(cookies).map(([k,v])=>k+'='+v).join(';');},
    set cookie(raw){const [k,v]=raw.split(';')[0].split('=');cookies[k]=v;},
    head:{appendChild(node){scripts.push(node);}},
    createElement(name){return {tagName:name}},
    addEventListener(){},
  };
  const window={};
  const navigator={globalPrivacyControl:blocked};
  vm.runInNewContext(source,{document,window,navigator,location:{protocol:'https:'},encodeURIComponent,Date});
  return {scripts,window};
}
let sample=testGa4(true,false);
assert.equal(sample.scripts.length,1,'tag carregada após consentimento');
assert.equal(sample.window.dataLayer.some(args=>args[0]==='config' && args[1]==='G-TEST1234'),true,'config usa ID público');
sample=testGa4(false,false);
assert.equal(sample.scripts.length,0,'sem consentimento não envia evento');
sample=testGa4(true,true);
assert.equal(sample.scripts.length,0,'GPC bloqueia a coleta');
assert.equal(sample.window.dataLayer,undefined,'nenhuma chamada GA4 com GPC');
console.log('GA4 diagnóstico 1.0.119: consentimento, ID e bloqueio GPC aprovados.');
