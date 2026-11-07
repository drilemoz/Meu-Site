'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const code = fs.readFileSync(__dirname + '/../public/assets/js/setup-backup-upload.js','utf8');
const M = 1048576;
async function verify() {
  const handlers = {};
  const formFields = [];
  const file = {name:'meus-dados.json.gz',size:3*M, slice(a,b) {return {size:b-a};}};
  const field = {
    dataset:{serverLimit:String(2*M),chunkSize:String(M/2)},
    files:[file],
    setCustomValidity(msg){this.error=msg;},
    reportValidity(){throw new Error('Não deveria rejeitar upload em partes');},
    addEventListener(type,fn){handlers[type]=fn;},
  };
  const form = {
    querySelector(q){if(q==='button[type="submit"]')return button;
      if(q==='[name="csrf"]') return {value:'valid-token'}; return null;},
    addEventListener(type,fn){handlers['form-'+type]=fn;},
    appendChild(el){formFields.push(el);},
    submit(){this.submitted=true;}
  };
  field.form=form;
  const button={disabled:false};
  const hint={textContent:''};
  let fetchCount=0;
  let position=0;
  let sessionToken='';
  class FormDataMock {
    constructor(){this.data={};}
    append(key,value){this.data[key]=value;}
  }
  const document={
    querySelector(q){return q==='[data-backup-import]'?field:null;},
    getElementById(q){return q==='backup-upload-hint'?hint:null;},
    createElement(){return {};}
  };
  const fetch=async (url,options)=>{
    assert.equal(url,'/setup.php?step=3');
    assert.equal(options.credentials,'same-origin');
    const d=options.body.data;
    assert.equal(d.csrf,'valid-token');
    assert.equal(Number(d.upload_offset),position);
    assert.ok(d.chunk.size<=M/2);
    assert.equal(d.upload_token,sessionToken);
    fetchCount++;
    sessionToken='abcdef'.repeat(5)+'ab';
    position+=d.chunk.size;
    return {ok:true,json:async()=>({ok:true,upload_token:sessionToken,next_offset:position})};
  };
  vm.runInNewContext(code,{document,fetch,FormData:FormDataMock});
  let prevented=false;
  await handlers['form-submit']({preventDefault(){prevented=true;}});
  assert.equal(prevented,true);
  assert.equal(fetchCount,6);
  assert.equal(position,3*M);
  assert.equal(field.disabled,true);
  assert.equal(form.submitted,true);
  assert.equal(formFields[0].value,sessionToken);
  assert.match(hint.textContent,/Validando e restaurando/);
  console.log('OK: cliente divide 3 MiB em 6 blocos, usa CSRF/cookie e conclui POST final');
}
verify().catch(e=>{console.error(e);process.exitCode=1;});
