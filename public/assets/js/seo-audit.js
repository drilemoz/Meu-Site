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
 const norm=str=>String(str||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,120).replace(/-+$/,'')||'noticia';
 const snapshot=()=>({slug:field('url_slug').value,title:field('seo_title').value,description:field('seo_description').value});
 const dispatch=el=>{el.dispatchEvent(new Event('input',{bubbles:true}));el.dispatchEvent(new Event('change',{bubbles:true}));};
 const apply=changes=>{
   const fields={slug:'url_slug',title:'seo_title',description:'seo_description'};
   for(const [key,value] of Object.entries(changes))if(Object.hasOwn(fields,key)){const input=field(fields[key]);input.value=String(value);dispatch(input);}
   refresh();
 };
 const modalOpen=(dialog,opener)=>{if(!window.IguaAdminModal?.open?.(dialog,opener))dialog.showModal();};
 const modalClose=dialog=>{if(window.IguaAdminModal?.close?.(dialog))return;if(dialog.open)dialog.close();};
 const tool=document.querySelector('#news-seo-tool-dialog'),diagnostic=document.querySelector('#news-seo-dialog');
 // Enter dentro dos inputs de um pop-up não pode submeter/publicar o formulário da notícia.
 for(const dialog of [tool,diagnostic])dialog.addEventListener('keydown',event=>{
   if(event.key==='Enter' && event.target.matches('input:not([type=checkbox]):not([type=radio])'))event.preventDefault();
 });
 const draftSlug=document.querySelector('#news-seo-draft-slug'),draftTitle=document.querySelector('#news-seo-draft-title'),draftDescription=document.querySelector('#news-seo-draft-description'),draftGenerated=document.querySelector('#news-seo-draft-generated');
 const diagSlug=document.querySelector('#news-diag-draft-slug'),diagTitle=document.querySelector('#news-diag-draft-title'),diagDescription=document.querySelector('#news-diag-draft-description');
 const toolTitle=document.querySelector('#news-seo-tool-title');let mode='edit';
 const seoPreview=document.querySelector('#news-url-preview');
 function slugPreview(value){const slug=value.trim()?value.trim():field('title').value.trim();const nice=norm(slug);seoPreview.textContent=seoPreview.dataset.pretty==='1'?'/noticia/'+nice:'/index.php?p=noticia&slug='+encodeURIComponent(nice);}
 function openTool(which,opener){
   mode=which;const data=snapshot();draftSlug.value=data.slug;draftTitle.value=data.title;draftDescription.value=data.description;
   draftGenerated.value=which==='generate'?(window.IguaNewsSEOProposal?.()||''):'';
   toolTitle.textContent=which==='slug'?'Endereço da notícia':which==='generate'?'Gerar descrição SEO':'Editar SEO';
   for(const element of tool.querySelectorAll('.news-seo-tool-panel'))element.hidden=element.id!=='news-seo-tool-'+which;
   if(which==='slug')slugPreview(draftSlug.value);
   if(which==='edit')updateMetaCount();
   modalOpen(tool,opener);
   (which==='slug'?draftSlug:which==='generate'?draftGenerated:draftTitle).focus({preventScroll:true});
 }
 function updateMetaCount(){document.querySelector('#news-meta-count').textContent=Array.from(draftDescription.value).length+'/160 recomendado';}
 draftDescription.addEventListener('input',updateMetaCount);
 draftSlug.addEventListener('input',()=>slugPreview(draftSlug.value));
 document.querySelector('#news-seo-suggest-slug').addEventListener('click',()=>{draftSlug.value=norm(field('title').value);slugPreview(draftSlug.value);draftSlug.focus();});
 document.querySelectorAll('[data-news-seo-tool]').forEach(button=>button.addEventListener('click',()=>openTool(button.dataset.newsSeoTool,button)));
 document.querySelector('#news-seo-tool-save').addEventListener('click',()=>{
   if(mode==='slug'){
     const slug=draftSlug.value.trim();if(slug && !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)){draftSlug.setCustomValidity('Use apenas letras minúsculas, números e hífens.');draftSlug.reportValidity();return;}
     draftSlug.setCustomValidity('');apply({slug});
   }else if(mode==='generate')apply({description:draftGenerated.value.trim()});
   else apply({title:draftTitle.value.trim(),description:draftDescription.value.trim()});
   modalClose(tool);
 });
 for(const id of ['news-seo-tool-x','news-seo-tool-cancel'])document.querySelector('#'+id).addEventListener('click',()=>modalClose(tool));
 draftSlug.addEventListener('input',()=>draftSlug.setCustomValidity(''));
 function createFix(item){
   const li=document.createElement('li');li.className=item.ok?'ok':'warn';
   const mark=document.createElement('span');mark.textContent=(item.ok?'✓ ':'! ')+item.label;li.appendChild(mark);
   const fix=document.createElement('button');fix.type='button';fix.className='news-seo-fix';fix.textContent='Editar';fix.dataset.seoFix=item.id;fix.setAttribute('aria-label','Corrigir: '+item.label);li.appendChild(fix);return li;
 }
 function refresh(){
   const blocks=(()=>{try{return JSON.parse(field('blocks_json')?.value||'[]');}catch{return [];}})();
   const rawSlug=field('url_slug')?.value.trim(),oldSlug=field('url_slug')?.defaultValue||'';
   const slug=rawSlug&&rawSlug===oldSlug?oldSlug:norm(rawSlug||field('title')?.value||'');
   const files=field('image')?.files;
   const hasCover=(form.dataset.hasCover==='1' && field('remove_cover')?.value!=='1')||(files&&files.length>0)||!!field('library_cover')?.value;
   const audit=seoAudit({title:field('title')?.value,seo_title:field('seo_title')?.value,summary:field('summary')?.value,
     seo_description:field('seo_description')?.value,slug,body:field('body')?.value,
     has_blocks:blocks.some(b=>String(b.content||'').trim()),image:hasCover,
     image_caption:field('image_caption')?.value||'',cover_alt:(files&&files.length)||field('library_cover')?.value?'':(form.dataset.coverAlt||'')});
   document.querySelector('#news-seo-score').textContent=`${audit.score}/100`;
   document.querySelector('#news-seo-progress').value=audit.score;
   document.querySelector('#news-diag-score').textContent=`${audit.score}/100`;
   document.querySelector('#news-diag-progress').value=audit.score;
   document.querySelector('#news-seo-summary').textContent=audit.score===100?'Itens editoriais conferidos.':`${audit.checks.filter(c=>!c.ok).length} ponto(s) para revisar · avaliação local`;
   const ul=document.querySelector('#news-seo-checklist');ul.replaceChildren(...audit.checks.map(createFix));
 }
 function openDiagnostic(){const data=snapshot();diagTitle.value=data.title;diagDescription.value=data.description;diagSlug.value=data.slug;document.querySelector('#news-diagnostic-edit').open=false;refresh();modalOpen(diagnostic,document.activeElement);}
 document.querySelector('#news-seo-dialog-open').addEventListener('click',openDiagnostic);
 document.querySelector('#news-seo-diag-save').addEventListener('click',()=>{
   const slug=diagSlug.value.trim();if(slug&&!/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug)){diagSlug.setCustomValidity('Endereço inválido. Use letras minúsculas, números e hífens.');diagSlug.reportValidity();return;}
   diagSlug.setCustomValidity('');apply({title:diagTitle.value.trim(),description:diagDescription.value.trim(),slug});modalClose(diagnostic);
 });
 diagSlug.addEventListener('input',()=>diagSlug.setCustomValidity(''));
 for(const id of ['news-seo-dialog-close','news-seo-dialog-done'])document.querySelector('#'+id).addEventListener('click',()=>modalClose(diagnostic));
 document.querySelector('#news-seo-checklist').addEventListener('click',event=>{
   const fix=event.target.closest('[data-seo-fix]');if(!fix)return;
   const name=fix.dataset.seoFix;
   if(['slug','description'].includes(name)){
     const panel=document.querySelector('#news-diagnostic-edit');panel.open=true;(name==='slug'?diagSlug:diagDescription).focus({preventScroll:true});return;
   }
   const to={title:0,summary:1,body:2,image:3,alt:4};
   if(Object.hasOwn(to,name)){modalClose(diagnostic);window.IguaNewsGoToStep?.(to[name]);}
 });
 form.addEventListener('input',refresh);form.addEventListener('change',refresh);
 form.addEventListener('click',event=>{if(event.target.closest('[data-library-cover],#news-library-clear]'))queueMicrotask(refresh);});
 window.addEventListener('igua:seo-refresh',refresh);refresh();
})(typeof globalThis!=='undefined'?globalThis:this);
