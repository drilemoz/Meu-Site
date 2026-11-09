'use strict';
const fs=require('node:fs'),assert=require('node:assert/strict'),path=require('node:path');
const root=path.resolve(__dirname,'..');
const read=(p)=>fs.readFileSync(path.join(root,p),'utf8');
const editor=read('public/assets/js/news-gemini.js');
const header=read('app/news_admin_view/header.php');
const blocks=read('app/news_admin_view/blocks.php');
const details=read('app/news_admin_view/details.php');
const scripts=read('app/news_admin_view/scripts.php');
const footer=read('app/news_admin_view/footer.php');
for(const [task,view] of [['title',header],['summary',header],['body',blocks],['seo',details]]){
  assert.match(view,new RegExp('data-news-gemini="'+task+'"'));
  const button=view.match(new RegExp('<button[^>]*data-news-gemini="'+task+'"[^>]*>([\\s\\S]*?)<\\/button>'));
  assert.ok(button,'Atalho Gemini presente: '+task);
  if(task==='seo')assert.match(button[1],/Gemini IA/,'Ação de IA deve ser clara no celular');
  else assert.ok(!/Gemini IA/.test(button[1]),'Atalho compacto fora da barra SEO: '+task);
}
assert.match(editor,/fetch\(endpoint,\{method:'POST',credentials:'same-origin'/);
assert.match(editor,/if\(!selected\|\|!target\)return/);
assert.match(editor,/dialog\.showModal\(\)/);
assert.match(footer,/id="news-gemini-dialog"/);
assert.match(scripts,/news-gemini\.js/);
assert.doesNotMatch(editor,/api\.google|x-goog-api-key/i,'Segredo não pode ir ao browser');
console.log('Gemini 1.0.103: atalhos compactos, janela e consentimento para aplicar verificados.');
