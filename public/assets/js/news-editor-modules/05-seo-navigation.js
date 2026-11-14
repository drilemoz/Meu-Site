 // Caso um campo obrigatório de outra etapa esteja vazio, mostrar a etapa correta.
 form.addEventListener('invalid', e=>{const step=e.target.closest('[data-step]');const i=steps.indexOf(step);if(i>=0)changeTab(i,{scroll:true});const advanced=e.target.closest('details');if(advanced)advanced.open=true;},true);
 tabs.forEach((b,i)=>b.addEventListener('click',()=>navigateTo(i)));
 document.querySelector('#news-next').addEventListener('click',()=>navigateTo(active+1));
 document.querySelector('#news-prev').addEventListener('click',()=>navigateTo(active-1));
 // Foco em frases inteiras: não inserir reticências artificiais nem cortar palavras.
 function proposeSeoDescription(){
   syncInputs();
   const title=form.querySelector('[name=title]').value.trim();
   const summary=form.querySelector('[name=summary]').value.trim();
   const body=blocks.map(b=>b.content).join(' ')||form.querySelector('[name=body]').value;
   const clean=s=>String(s).replace(/<[^>]*>/g,'').replace(/\s+/g,' ').trim();
   const sentence=clean(summary||body||title);
   let proposed=sentence;
   if([...proposed].length>155){
     const sentences=proposed.match(/[^.!?]+[.!?]+(?=\s|$)/gu)||[];
     const first=sentences.map(clean).find(s=>[...s].length<=155&&[...s].length>=35);
     proposed=first||clean(title);
   }
   if([...proposed].length>320)proposed=''; // exige revisão manual, nunca uma frase truncada.
   return proposed;
 }
 window.IguaNewsSEOProposal=proposeSeoDescription;
 function seo(){
   const field=form.querySelector('[name=seo_description]');
   field.value=proposeSeoDescription();
   const title=form.querySelector('[name=title]').value.trim();
   const seoTitle=form.querySelector('[name=seo_title]');if(!seoTitle.value.trim())seoTitle.value=title.slice(0,220);
   preview();form.dispatchEvent(new Event('input',{bubbles:true}));
 }
 function categorySuggestion(){
   const title=form.querySelector('[name=title]').value.toLocaleLowerCase('pt-BR').normalize('NFD').replace(/[\u0300-\u036f]/g,'');
   const rules=[['Segurança',/acidente|colisao|roubo|furto|incendio|policia|bombeiro|crime/],['Empregos',/emprego|vaga|contratacao|concurso|processo seletivo/],['Esportes',/futebol|campeonato|jogo|time|atleta|gol/],['Política',/prefeitura|vereador|camara|prefeito|eleicao|projeto de lei/],['Eventos',/festival|show|festa|evento|feira cultural/],['Saúde',/hospital|vacinacao|saude|medico|posto de saude/],['Educação',/escola|aluno|educacao|professor|universidade/],['Cultura',/cultura|teatro|exposicao|artista/],['Cidade',/obra|bairro|transito|rodovia|agua|energia/]];
   const parent=document.querySelector('#news-category-suggest');if(!parent)return;
   const category=form.querySelector('[name=category_id]');const match=rules.find(([name,pattern])=>pattern.test(title)&&[...category.options].some(o=>o.textContent.trim().toLocaleLowerCase('pt-BR')===name.toLocaleLowerCase('pt-BR')));
   parent.innerHTML='';if(!match)return;
   const opt=[...category.options].find(o=>o.textContent.trim().toLocaleLowerCase('pt-BR')===match[0].toLocaleLowerCase('pt-BR'));
   const span=document.createElement('span');span.textContent='Sugestão local: '+match[0]+' · ';parent.appendChild(span);
   const button=document.createElement('button');button.type='button';button.textContent='Usar categoria';button.className='news-category-apply';button.addEventListener('click',()=>{category.value=opt.value;parent.textContent='Categoria sugerida aplicada. Confirme antes de publicar.';});parent.appendChild(button);
 }
 function preview(){const t=form.querySelector('[name=seo_title]').value||form.querySelector('[name=title]').value;const d=form.querySelector('[name=seo_description]').value||form.querySelector('[name=summary]').value;document.querySelector('#news-preview-title').textContent=t||'Título da notícia';document.querySelector('#news-preview-desc').textContent=d||'Descrição resumida';document.querySelector('#news-meta-count').textContent=[...form.querySelector('[name=seo_description]').value].length+'/160 recomendado';}
 // A manchete não altera automaticamente o endereço de uma notícia já salva.
 const slugInput=form.querySelector('[name=url_slug]'),slugPreview=document.querySelector('#news-url-preview');
 const makeSlug=value=>value.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'').slice(0,120).replace(/-+$/,'')||'noticia';
 function updateSlugPreview(){
   if(!slugInput||!slugPreview)return;
   const originalSlug=slugInput.defaultValue||'';
   const value=slugInput.value===originalSlug && originalSlug.length>120?originalSlug:makeSlug(slugInput.value.trim()||form.querySelector('[name=title]').value);
   slugPreview.textContent=slugPreview.dataset.pretty==='1'?'/noticia/'+value:'/index.php?p=noticia&slug='+encodeURIComponent(value);
 }
 slugInput?.addEventListener('input',updateSlugPreview);
 form.querySelector('[name=title]').addEventListener('input',updateSlugPreview);
 document.querySelector('#news-generate-slug')?.addEventListener('click',()=>{
    slugInput.value=makeSlug(form.querySelector('[name=title]').value);
    updateSlugPreview();slugInput.dispatchEvent(new Event('change',{bubbles:true}));
 });
 updateSlugPreview();
 document.querySelector('#news-generate-seo')?.addEventListener('click',seo);
 ['title','summary','seo_title','seo_description'].forEach(k=>form.querySelector(`[name=${k}]`).addEventListener('input',preview));form.querySelector('[name=title]').addEventListener('input',categorySuggestion);
