const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const code=fs.readFileSync(path.join(__dirname,'../public/assets/js/integrations-admin.js'),'utf8');
async function verify({formId='G-TEST1234', publicId='G-TEST1234', script=true, fail=false}={}) {
  let requests=0;
  const classes=new Set();
  const output={hidden:true,textContent:'',classList:{remove(...names){names.forEach(n=>classes.delete(n));},add(name){classes.add(name);}}};
  const button={disabled:false,handlers:{},addEventListener(name,cb){this.handlers[name]=cb;}};
  const input={value:formId};
  const hub={
    querySelector(selector){return {'[data-ga4-public-test]':button,'[data-ga4-public-result]':output,'#int-ga-id':input}[selector]||null;},
    querySelectorAll(){return [];},
    contains(){return false;}
  };
  const window={addEventListener(){}};
  const document={getElementById(id){return id==='integrations-hub'?hub:null;}};
  const context={window,document,history:{replaceState(){}},location:{hash:'',origin:'https://iguatama.online'},
    URL,fetch:async(url,args)=>{
      requests++;assert.equal(url,'/index.php');assert.equal(args.method,'GET');assert.equal(args.credentials,'same-origin');
      if(fail)throw new Error('Falha de rede');
      return {ok:true,headers:{get:()=> 'text/html; charset=utf-8'},text:async()=> '<html>fixture</html>'};
    },
    DOMParser:class{parseFromString(){return {
      querySelector(selector){return selector==='#ga4Consent[data-ga4-id]' && publicId ? {getAttribute:()=>publicId}:null;},
      querySelectorAll(selector){return selector==='script[src]'&&script?[{getAttribute:()=>'/assets/js/ga4-consent.js?v=1.0.119'}]:[];}
    };}}
  };
  vm.runInNewContext(code,context);
  assert.equal(requests,0,'sem consulta ao abrir o admin');
  await button.handlers.click();
  return {requests,text:output.textContent,classes,button};
}
(async()=>{
  const correct=await verify();
  assert.equal(correct.requests,1);
  assert.match(correct.text,/Tag GA4 e ID correspondente encontrados/);
  assert.match(correct.text,/envio e o recebimento de eventos ainda precisam ser confirmados/);
  assert(correct.classes.has('is-ok'));
  const mismatch=await verify({publicId:'G-OUTRO1234'});
  assert.match(mismatch.text,/ID presente no site público difere/);
  assert(mismatch.classes.has('is-error'));
  const absent=await verify({publicId:'',script:false});
  assert.match(absent.text,/Não encontrei a tag GA4 completa/);
  const invalid=await verify({formId:'javascript:1'});
  assert.equal(invalid.requests,0);
  assert.match(invalid.text,/ID G- válido/);
  const offline=await verify({fail:true});
  assert.match(offline.text,/Não foi possível verificar/);
  assert.equal(offline.button.disabled,false,'botão reabilitado após falha');
  console.log('GA4 público 1.0.119: clique explícito, ID correto/divergente, ausência, entrada inválida e erro de rede aprovados.');
})().catch(err=>{console.error(err);process.exitCode=1;});
