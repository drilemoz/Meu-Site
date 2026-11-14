'use strict';
const assert=require('node:assert/strict');
const fs=require('node:fs');
const vm=require('node:vm');
const path=require('node:path');
const root=path.resolve(__dirname,'..');
const read=p=>fs.readFileSync(path.join(root,p),'utf8');
const css=read('public/assets/css/style.css');
const view=read('app/news_admin_view/footer.php');
const header=read('app/news_admin_view/header.php');
const seo=read('app/news_admin_view/details.php');
const media=read('app/news_admin_view/media.php');
const source=read('public/assets/js/news-editor-modules/02-steps.js');
const submit=read('public/assets/js/news-editor-modules/08-submit-init.js');
assert.match(view,/<select[^>]+id="news-publication-choice"[^>]*name="publication_choice"[^>]*required/);
assert.match(view,/\$publicationDefault=!\$mayPublish\?'draft':\(\$scheduledOriginal\?'scheduled':'now'\)/);
assert.equal((view.match(/id="news-submit"/g)||[]).length,1);
assert(view.indexOf('id="news-publication-choice"')<view.indexOf('id="news-submit"'));
assert(view.indexOf('id="news-submit"')<view.indexOf('id="news-review-title"'));
assert(view.indexOf('id="news-schedule-control"')>view.indexOf('id="news-submit"'));
assert(!/type="radio" name="publication_choice"/.test(view));
assert.match(css,/\.news-wizard-page \.news-publish-row\{display:grid;grid-template-columns:minmax\(0,1fr\) minmax\(0,1\.05fr\)/);
assert.match(css,/@media\(max-width:420px\)\{\.news-wizard-page \.news-media-actions\{grid-template-columns:1fr\}/);
assert.match(css,/\.news-wizard-page \.news-seo-iconbar\{grid-template-columns:repeat\(3,minmax\(0,1fr\)\)/);
assert.equal((media.match(/data-media-pick="choose"/g)||[]).length,1);
for(const task of ['title','summary']){
  const button=header.match(new RegExp('<button[^>]+data-news-gemini="'+task+'"[^>]*>([\\s\\S]*?)<\\/button>'));
  assert(button,task);
  assert.match(button[1], /Gemini IA/);
  assert.match(button[1], /<span class="igua-gemini-mark"/);
  assert.doesNotMatch(button[0].split('>')[0], /igua-gemini-mark/);
}
assert.match(seo,/data-news-gemini="seo"/);
assert.match(seo,/class="igua-gemini-mark" aria-hidden="true">✦/);
assert.match(submit,/querySelector\('#news-publication-choice'\)\?\.value/);
// Exercise the actual updatePublication() function in isolation; no DOM/network needed.
const code=source.slice(source.indexOf(' function updatePublication(){'),source.indexOf(' const stepNav='));
assert(code.includes('function updatePublication()'));
const select={value:'now'},schedule={hidden:true},status={value:''},button={textContent:''};
const form={querySelector:q=>q==='#news-publication-choice'?select:q==='[name="status"]'?status:null};
const document={querySelector:q=>q==='#news-schedule-control'?schedule:q==='#news-submit'?button:null};
const update=vm.runInNewContext(code+';updatePublication',{form,document});
for(const [choice,hiddenStatus,visibleSchedule,caption] of [
  ['now','published',false,'Publicar notícia'],
  ['draft','draft',false,'Salvar rascunho'],
  ['scheduled','published',true,'Agendar notícia']
]){
  select.value=choice;update();
  assert.equal(status.value,hiddenStatus,choice);
  assert.equal(schedule.hidden,!visibleSchedule,choice);
  assert(button.textContent.includes(caption),choice);
}
console.log('OK editor_ui_243.js: publicação por seletor, botões Gemini, SEO e responsividade.');
