'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const root=path.join(__dirname,'..');
const read=p=>fs.readFileSync(path.join(root,p),'utf8');
const sourceCode=read('public/assets/js/news-editor-modules/08-submit-init.js');
const bundle=read('public/assets/js/news-editor.js');
for(const [label,body] of [['módulo',sourceCode],['bundle',bundle]]){
  assert(body.includes("'news_create_category'")&&body.includes("'news_create_source'"),label+' mantém ações AJAX');
  assert(body.includes("response.json().catch(()=>null)"),label+' interpreta erro JSON do servidor');
  assert(body.includes('O servidor recusou o cadastro (HTTP '+"'"),label+' mantém diagnóstico genérico para respostas não JSON');
  assert(body.includes('csrf,name,url'),label+' envia token CSRF');
}
console.log('1.0.101: wizard AJAX e tratamento de falhas aprovados.');
