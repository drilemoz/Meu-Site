/* IguaNews 1.0.25: verificação editorial local, sem chamada remota. */
(function (root) {
 'use strict';
 const len=value=>Array.from(String(value??'').trim()).length;
 function seoAudit(data) {
   const title=String(data.seo_title||data.title||'').trim();
   const summary=String(data.summary||'').trim();
   let description=String(data.seo_description||summary||data.body||data.title||'').trim().replace(/\s+/g,' ');
   if(!data.seo_description&&!summary&&len(description)>155)description=Array.from(description).slice(0,152).join('').replace(/\s+\S*$/,'').trim()+'…';
   const slug=String(data.slug||'');
   const body=String(data.body||'').replace(/<[^>]*>/g,'').trim();
   const image=!!data.image;
   const alt=String(data.cover_alt||data.image_caption||'').trim();
   const checks=[
     {id:'title',label:'Título claro e informativo (20 a 90 caracteres)',ok:len(title)>=20&&len(title)<=90,weight:20},
     {id:'description',label:'Descrição de busca objetiva (50 a 160 caracteres)',ok:len(description)>=50&&len(description)<=160,weight:15},
     {id:'slug',label:'Endereço legível e válido (3 a 120 caracteres)',ok:slug.length>=3&&slug.length<=120&&/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug),weight:15},
     {id:'summary',label:'Resumo para o leitor (40 a 240 caracteres)',ok:len(summary)>=40&&len(summary)<=240,weight:15},
     {id:'body',label:'Texto informativo ou blocos editoriais',ok:len(body)>=100||!!data.has_blocks,weight:15},
     {id:'image',label:'Foto de capa selecionada',ok:image,weight:10},
     {id:'alt',label:'Legenda ou texto alternativo descritivo da capa',ok:image&&len(alt)>=10,weight:10},
   ];
   return {score:checks.reduce((n,c)=>n+(c.ok?c.weight:0),0),checks};
 }
 if(typeof module==='object'&&module.exports)module.exports={seoAudit};
 if(typeof document==='undefined')return;
 const form=document.querySelector('#news-form'),panel=document.querySelector('#news-seo-audit');
 if(!form||!panel)return;
 const field=name=>form.querySelector(`[name="${name}"]`);
 function refresh() {
   const blocks=(()=>{try{return JSON.parse(field('blocks_json')?.value||'[]');}catch{return [];}})();
   const rawSlug=field('url_slug')?.value.trim();
   const oldSlug=field('url_slug')?.defaultValue||'';
   const normalized=str=>String(str||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,120).replace(/-+$/,'')||'noticia';
   const slug=rawSlug && rawSlug===oldSlug ? oldSlug : normalized(rawSlug||field('title')?.value||'');
   const files=field('image')?.files;
   const hasCover=form.dataset.hasCover==='1'||(files&&files.length>0)||!!field('library_cover')?.value;
   const imageCaption=field('image_caption')?.value||'';
   const audit=seoAudit({
     title:field('title')?.value,seo_title:field('seo_title')?.value,summary:field('summary')?.value,
     seo_description:field('seo_description')?.value,slug,body:field('body')?.value,
     has_blocks:blocks.some(b=>String(b.content||'').trim()),image:hasCover,
     image_caption:imageCaption,cover_alt:(files&&files.length)||field('library_cover')?.value?'':(form.dataset.coverAlt||'')
   });
   document.querySelector('#news-seo-score').textContent=`${audit.score}/100`;
   document.querySelector('#news-seo-progress').value=audit.score;
   document.querySelector('#news-seo-summary').textContent=audit.score===100?'Itens editoriais conferidos.':`${audit.checks.filter(c=>!c.ok).length} ponto(s) para revisar · avaliação local`;
   const ul=document.querySelector('#news-seo-checklist');ul.replaceChildren();
   for(const item of audit.checks){
     const li=document.createElement('li');li.className=item.ok?'ok':'warn';
     li.textContent=(item.ok?'✓ ':'! ')+item.label;ul.appendChild(li);
   }
 }
 const dialog=document.querySelector('#news-seo-dialog');
 document.querySelector('#news-seo-dialog-open').addEventListener('click',()=>dialog.showModal());
 const close=()=>dialog.close();
 document.querySelector('#news-seo-dialog-close').addEventListener('click',close);
 document.querySelector('#news-seo-dialog-done').addEventListener('click',close);
 dialog.addEventListener('click',e=>{if(e.target===dialog)close();});
 form.addEventListener('input',refresh);
 form.addEventListener('change',refresh);
 form.addEventListener('click',e=>{
   if(e.target.closest('[data-library-cover],#news-library-clear,#news-generate-slug,#news-generate-seo]'))queueMicrotask(refresh);
 });
 window.addEventListener('igua:seo-refresh',refresh);
 refresh();
})(typeof globalThis!=='undefined'?globalThis:this);
