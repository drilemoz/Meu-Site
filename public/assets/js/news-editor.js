(() => {
 'use strict';
 const form=document.querySelector('#news-form'); if(!form)return;
 const tabs=[...document.querySelectorAll('[data-tab]')],steps=[...document.querySelectorAll('[data-step]')];
 const content=document.querySelector('#news-block-list'),serialized=document.querySelector('#news-blocks-json');
 const initial=JSON.parse(document.querySelector('#news-blocks-initial')?.textContent||'[]');
 let blocks=initial.map(x=>({kind:x.kind,heading:x.heading||'',content:x.content||'',collapsed:!!x.collapsed}));
 const csrf=form.querySelector('[name=csrf]').value,articleId=form.querySelector('[name=id]').value;
 const names={fact:'Fato rápido',section:'Seção de detalhes',paragraph:'Parágrafo',heading:'Subtítulo',quote:'Citação',list:'Lista'};
 let active=0;let submitting=false;let lastJson='';let saving=false;
 const esc=x=>String(x).replace(/[&<>"']/g,ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
 function syncInputs(){content.querySelectorAll('[data-idx]').forEach(el=>{
   const i=Number(el.dataset.idx);if(!blocks[i])return;
   blocks[i][el.dataset.field]=el.type==='checkbox'?el.checked:el.value;
 });serialized.value=JSON.stringify(blocks.filter(x=>x.heading.trim()||x.content.trim()));}
 function render(){
   content.innerHTML=blocks.map((x,i)=>`<div class="news-block" data-block="${i}"><div class="news-block-head"><strong>${esc(names[x.kind]||x.kind)} ${i+1}</strong><span><button type="button" data-move="up" data-i="${i}" aria-label="Mover para cima">↑</button><button type="button" data-move="down" data-i="${i}" aria-label="Mover para baixo">↓</button><button type="button" data-move="remove" data-i="${i}" aria-label="Excluir bloco">×</button></span></div>${x.kind==='paragraph'?'':`<label>${x.kind==='fact'?'Campo (ex.: Veículos)':x.kind==='section'?'Pergunta / seção':'Título'}<input data-idx="${i}" data-field="heading" maxlength="180" value="${esc(x.heading)}"></label>`}<label>${x.kind==='fact'?'Resposta curta':x.kind==='list'?'Um item por linha':'Conteúdo'}<textarea data-idx="${i}" data-field="content" rows="${x.kind==='fact'?2:4}" maxlength="6500">${esc(x.content)}</textarea></label>${x.kind==='section'?`<label class="news-mini-checkbox"><input type="checkbox" data-idx="${i}" data-field="collapsed" ${x.collapsed?'checked':''}> Recolher inicialmente no portal</label>`:''}</div>`).join('');
   syncInputs();
 }
 content.addEventListener('input',syncInputs);content.addEventListener('change',syncInputs);
 content.addEventListener('click',e=>{let button=e.target.closest('[data-move]');if(!button)return;syncInputs();const i=Number(button.dataset.i),what=button.dataset.move;
   if(what==='remove')blocks.splice(i,1);
   if(what==='up'&&i>0)[blocks[i-1],blocks[i]]=[blocks[i],blocks[i-1]];
   if(what==='down'&&i<blocks.length-1)[blocks[i+1],blocks[i]]=[blocks[i],blocks[i+1]];
   render();
 });
 document.querySelectorAll('[data-add-block]').forEach(b=>b.addEventListener('click',()=>{syncInputs();blocks.push({kind:b.dataset.addBlock,heading:'',content:'',collapsed:b.dataset.addBlock==='section'});render();}));
 const templates={
   acidente:[['fact','Local',''],['fact','Quando',''],['fact','Veículos',''],['fact','Vítimas','A confirmar'],['section','O que aconteceu?',''],['section','Estado das vítimas',''],['section','Causa do acidente','Ainda não confirmada']],
   politica:[['fact','Decisão',''],['fact','Quem decidiu',''],['section','O que muda?',''],['section','Próximos passos','']],
   empregos:[['fact','Quantidade de vagas',''],['fact','Prazo de inscrição',''],['section','Requisitos',''],['section','Como participar','']],
   evento:[['fact','Onde',''],['fact','Quando',''],['fact','Entrada',''],['section','Programação','']],
   esporte:[['fact','Equipes',''],['fact','Placar',''],['section','Destaques do jogo',''],['section','Próxima partida','']],
   obra:[['fact','Local',''],['fact','Prazo',''],['fact','Investimento',''],['section','O que será feito?',''],['section','Impactos aos moradores','']]
 };
 document.querySelector('#news-apply-template')?.addEventListener('click',()=>{syncInputs();let key=document.querySelector('#news-template').value;if(!templates[key])return;blocks.push(...templates[key].map(([kind,heading,content])=>({kind,heading,content,collapsed:kind==='section'})));render();});
 // Assistente de nove passos: texto e validação ficam no formulário original.
 const labels=['Título','Descrição','Matéria','Fotos','Créditos','Anexos','Classificação','SEO','Revisão'];
 const reviewTitle=document.querySelector('#news-review-title');
 function updateReview(){
   updateSource();syncInputs();
   const get=name=>form.querySelector(`[name="${name}"]`)?.value||'';
   reviewTitle.textContent=get('title').trim()||'Título pendente';
   document.querySelector('#news-review-summary').textContent=get('summary').trim()||'Descrição pendente';
   const cat=form.querySelector('[name="category_id"]');
   document.querySelector('#news-review-category').textContent=cat?.selectedOptions?.[0]?.textContent||'Categoria não definida';
   const body=(get('body').trim()||blocks.map(x=>x.heading+' '+x.content).join(' ').trim());
   document.querySelector('#news-review-body').textContent=body.slice(0,1600)+(body.length>1600?'…':'');
   const files=[...form.querySelectorAll('input[type=file]')].reduce((n,input)=>n+input.files.length,0);
   const gallery=form.querySelectorAll('[name="library_gallery[]"]').length;
   document.querySelector('#news-review-counts').textContent=`${files} arquivo(s) local(is) selecionado(s) · ${gallery} foto(s) da biblioteca · SEO revisável na etapa anterior`;
   // Escolha de publicação é persistida e nunca presume autorização.
   updatePublication();
 }
 function updatePublication(){
   const choice=form.querySelector('[name="publication_choice"]:checked')?.value||'draft';
   const schedule=document.querySelector('#news-schedule-control');
   schedule.hidden=choice!=='scheduled';
   form.querySelector('[name="status"]').value=choice==='draft'?'draft':'published';
   document.querySelector('#news-submit').textContent=choice==='now'?'Publicar notícia ✓':choice==='scheduled'?'Agendar notícia ✓':'Salvar rascunho ✓';
 }
 const stepNav=document.querySelector('#news-steps'),stepsToggle=document.querySelector('#news-steps-toggle');
 stepsToggle.addEventListener('click',()=>{stepNav.hidden=!stepNav.hidden;stepsToggle.setAttribute('aria-expanded',String(!stepNav.hidden));});
 function changeTab(index,{scroll=false}={}){
   active=Math.max(0,Math.min(steps.length-1,index));
   steps.forEach((step,i)=>{step.hidden=i!==active;step.classList.toggle('active',i===active);});
   tabs.forEach((button,i)=>{
     button.classList.toggle('active',i===active);
     if(i===active)button.setAttribute('aria-current','step');else button.removeAttribute('aria-current');
   });
   document.querySelector('#news-step-progress').textContent=`Etapa ${active+1} de ${steps.length} · ${labels[active]}`;
   document.querySelector('#news-prev').hidden=active===0;
   document.querySelector('#news-next').hidden=active===steps.length-1;
   document.querySelector('#news-submit').hidden=active!==steps.length-1;
   if(active===steps.length-1)updateReview();
   if(scroll){document.querySelector('.news-step-nav').scrollIntoView({block:'start',behavior:'auto'});stepNav.hidden=true;stepsToggle.setAttribute('aria-expanded','false');}
 }
 window.IguaNewsGoToStep=(index)=>changeTab(index,{scroll:true});
 function hasText(){updateSource();syncInputs();return !!(form.querySelector('[name=body]').value.trim()||blocks.some(b=>b.content.trim()));}
 function canLeave(index){
   if(index===0){const field=form.querySelector('[name=title]');if(!field.checkValidity()){changeTab(0);field.reportValidity();return false;}}
   if(index===1){const field=form.querySelector('[name=summary]');if(field.value.trim().length<20){changeTab(1);status.textContent='Escreva uma descrição com ao menos 20 caracteres.';field.focus();return false;}}
   if(index===2&&!hasText()){
     changeTab(2);status.textContent='Escreva o texto da matéria antes de avançar.';
     const visual=document.querySelector('#news-visual-editor');visual?.focus();return false;
   }
   if(index===6){const field=form.querySelector('[name=category_id]');if(!field.checkValidity()){changeTab(6);field.reportValidity();return false;}}
   return true;
 }
 function navigateTo(index){if(index>active){for(let i=active;i<index;i++)if(!canLeave(i))return;}changeTab(index,{scroll:true});}
 for(const radio of form.querySelectorAll('[name="publication_choice"]'))radio.addEventListener('change',updatePublication);
 const titleCount=document.querySelector('#news-title-counter'),summaryCount=document.querySelector('#news-summary-counter');
 const titleField=form.querySelector('[name=title]'),summaryField=form.querySelector('[name=summary]');
 function updateCounters(){
   const titleLen=[...titleField.value.trim()].length,summaryLen=[...summaryField.value.trim()].length;
   titleCount.textContent=`${titleLen}/220 caracteres`;
   summaryCount.textContent=`${summaryLen}/155 caracteres recomendados`;
   document.querySelector('#news-summary-help').textContent=summaryLen>155?'A descrição é maior que o recomendado; revise a frase inteira na etapa SEO. O Google pode alterá-la.':summaryLen<20?'Use uma ou duas frases completas com ao menos 20 caracteres.':'Descrição pronta para revisão editorial.';
 }
 titleField.addEventListener('input',updateCounters);summaryField.addEventListener('input',updateCounters);updateCounters();updatePublication();
 const bodyField=form.querySelector('[name=body]');
 const visual=document.querySelector('#news-visual-editor');
 const raw=form.querySelector('.news-markdown-source');
 let editingSource=false;
 const sourceButton=document.querySelector('#news-source-toggle');
 const moreButton=document.querySelector('#news-more-format'),moreTools=document.querySelector('#news-more-tools');
 moreButton.addEventListener('click',()=>{moreTools.hidden=!moreTools.hidden;moreButton.setAttribute('aria-expanded',String(!moreTools.hidden));});
 function markdownFromVisual(){
   function walk(node){
     if(node.nodeType===3)return node.nodeValue||'';
     if(node.nodeType!==1)return '';
     const tag=node.tagName;
     let value=[...node.childNodes].map(walk).join('');
     if(tag==='B'||tag==='STRONG')return '**'+value+'**';
     if(tag==='I'||tag==='EM')return '*'+value+'*';
     if(tag==='BR')return '\n';
     if(/^H[1-6]$/.test(tag))return '\n## '+value.trim()+'\n';
     if(tag==='LI')return '\n- '+value.trim();
     if(tag==='UL'||tag==='OL')return '\n'+value+'\n';
     if(tag==='P'||tag==='DIV')return '\n'+value+'\n';
     return value;
   }
   return [...visual.childNodes].map(walk).join('').replace(/\u00a0/g,' ').replace(/\n{3,}/g,'\n\n').trim();
 }
 function loadVisual(){if(!visual||editingSource)return;visual.innerHTML=formattedPreview(bodyField.value);if(!bodyField.value.trim())visual.innerHTML='';}
 function updateSource(){if(editingSource||!visual)return;bodyField.value=markdownFromVisual();bodyField.dispatchEvent(new Event('input',{bubbles:true}));}
 function toggleSource(){editingSource=!editingSource;raw.classList.toggle('source-visible',editingSource);visual.hidden=editingSource;sourceButton.textContent=editingSource?'Editor visual':'Texto simples';if(editingSource)bodyField.focus();else loadVisual();}
 sourceButton.addEventListener('click',toggleSource);
 visual.addEventListener('input',updateSource);
 visual.addEventListener('paste',e=>{if(!e.clipboardData)return;e.preventDefault();const text=e.clipboardData.getData('text/plain')||'';document.execCommand('insertText',false,text);updateSource();});
 document.querySelectorAll('[data-format]').forEach(button=>button.addEventListener('click',()=>{
   const type=button.dataset.format;
   if(editingSource){
     const a=bodyField.selectionStart,b=bodyField.selectionEnd,chosen=bodyField.value.slice(a,b)||'texto';
     const text=type==='bold'?'**'+chosen+'**':type==='italic'?'*'+chosen+'*':type==='heading'?'\n## '+chosen:'\n- '+chosen;
     bodyField.setRangeText(text,a,b,'end');bodyField.dispatchEvent(new Event('input',{bubbles:true}));bodyField.focus();return;
   }
   visual.focus();
   if(type==='bold'||type==='italic')document.execCommand(type,false,null);
   if(type==='heading')document.execCommand('formatBlock',false,'h3');
   if(type==='list')document.execCommand('insertUnorderedList',false,null);
   updateSource();
 }));
 function formattedPreview(value){
   const escaped=esc(value),inline=t=>t.replace(/\*\*([^*\n]{1,400})\*\*/g,'<strong>$1</strong>').replace(/(^|[^*])\*([^*\n]{1,400})\*(?!\*)/g,'$1<em>$2</em>');
   let html='',paragraph=[],list=[];
   const flush=()=>{if(paragraph.length){html+='<p>'+paragraph.map(inline).join('<br>')+'</p>';paragraph=[];}if(list.length){html+='<ul>'+list.join('')+'</ul>';list=[];}};
   for(let line of escaped.trim().split(/\r?\n/)){line=line.trim();if(!line){flush();continue;}
     if(/^##\s+/.test(line)){flush();html+='<h3>'+inline(line.slice(3))+'</h3>';}
     else if(/^-\s+/.test(line)){if(paragraph.length)flush();list.push('<li>'+inline(line.slice(2))+'</li>');}
     else {if(list.length)flush();paragraph.push(line);}
   }flush();return html||'<p class="muted">Digite o texto para ver a prévia.</p>';
 }
 const previewBody=document.querySelector('#news-body-preview');
 const previewButton=document.querySelector('#news-toggle-preview');
 previewButton?.addEventListener('click',()=>{previewBody.hidden=!previewBody.hidden;if(!previewBody.hidden)previewBody.innerHTML=formattedPreview(bodyField.value);previewButton.textContent=previewBody.hidden?'◉':'×';previewButton.setAttribute('aria-label',previewBody.hidden?'Prévia':'Fechar prévia');});
 bodyField.addEventListener('input',()=>{if(!previewBody.hidden)previewBody.innerHTML=formattedPreview(bodyField.value);});
 const picked=form.querySelector('#news-library-cover');const pickedStatus=document.querySelector('#news-library-selected');
 document.querySelectorAll('[data-library-cover]').forEach(button=>button.addEventListener('click',()=>{
   const fileInput=form.querySelector('[name=image]');if(fileInput.files.length){pickedStatus.textContent='Remova o novo upload antes de escolher uma capa da biblioteca.';return;}
   picked.value=button.dataset.libraryCover;form.querySelector('#news-remove-cover').value='0';pickedStatus.textContent='Capa selecionada da biblioteca: '+(button.dataset.caption||button.dataset.libraryCover);
   if(!form.querySelector('[name=image_caption]').value)form.querySelector('[name=image_caption]').value=button.dataset.caption||'';
   if(!form.querySelector('[name=image_credit]').value)form.querySelector('[name=image_credit]').value=button.dataset.credit||'';
 }));
 document.querySelector('#news-library-clear')?.addEventListener('click',()=>{picked.value='';form.querySelector('#news-remove-cover').value='1';form.querySelector('[name=image]').value='';pickedStatus.textContent='Capa removida. Se a opção automática estiver ativa, a primeira foto da galeria poderá virar capa.';});
 form.querySelector('[name=image]')?.addEventListener('change',()=>{if(form.querySelector('[name=image]').files.length){picked.value='';form.querySelector('#news-remove-cover').value='0';pickedStatus.textContent='Upload novo selecionado; escolha de capa da biblioteca desfeita.';}});
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
 // Preserva legendas/créditos de um grupo ao selecionar arquivos no outro.
 // Uma seleção nova limpa apenas os metadados do grupo substituído; a otimização
 // assíncrona (news:files-ready) mantém os campos já digitados do mesmo grupo.
 const newMediaPane=document.querySelector('#news-new-media-preview');
 const newMediaDetails={gallery:[],attachment:[]};
 function captureNewMediaDetails(){
   newMediaPane.querySelectorAll('[data-new-media-kind]').forEach(box=>{
     const kind=box.dataset.newMediaKind,index=Number(box.dataset.newMediaIndex);
     if(!Object.hasOwn(newMediaDetails,kind)||!Number.isInteger(index)||index<0)return;
     newMediaDetails[kind][index]={
       caption:box.querySelector('[data-new-caption]').value,
       credit:box.querySelector('[data-new-credit]').value
     };
   });
 }
 function previewFiles(replacedKind=''){
   captureNewMediaDetails();
   if(Object.hasOwn(newMediaDetails,replacedKind))newMediaDetails[replacedKind]=[];
   newMediaPane.innerHTML='';
   document.querySelectorAll('[data-upload-input]').forEach(inp=>{
     const kind=inp.dataset.uploadInput;
     if(!Object.hasOwn(newMediaDetails,kind))return;
     [...inp.files].forEach((file,i)=>{
       const details=newMediaDetails[kind][i]||{caption:'',credit:''};
       const box=document.createElement('div');box.className='news-new-file';
       box.dataset.newMediaKind=kind;box.dataset.newMediaIndex=String(i);
       box.innerHTML=`<strong>${esc(file.name)} (${(file.size/1048576).toFixed(1)} MB)</strong><label>Legenda <input data-new-caption name="${kind==='gallery'?'new_image_caption':'new_attachment_caption'}[]" maxlength="360" value="${esc(details.caption)}"></label><label>Crédito <input data-new-credit name="${kind==='gallery'?'new_image_credit':'new_attachment_credit'}[]" maxlength="180" value="${esc(details.credit)}"></label>`;
       newMediaPane.appendChild(box);
     });
   });
 }
 document.querySelectorAll('[data-upload-input]').forEach(el=>{
   el.addEventListener('change',()=>previewFiles(el.dataset.uploadInput));
   el.addEventListener('news:files-ready',()=>previewFiles());
 });

 // Miniaturas locais, sem enviar arquivos antes da confirmação final.
 const localPhotoPreview=document.querySelector('#news-local-photo-preview');
 let photoObjectUrls=[];
 function refreshLocalPhotos(){
   photoObjectUrls.forEach(url=>URL.revokeObjectURL(url));photoObjectUrls=[];
   localPhotoPreview.replaceChildren();
   const input=form.querySelector('[name="gallery_files[]"]');
   const cover=form.querySelector('[name="image"]');
   const files=[...cover.files,...input.files];
   files.slice(0,10).forEach((file,index)=>{
     if(!/^image\/(jpeg|png|webp)$/.test(file.type))return;
     const url=URL.createObjectURL(file);photoObjectUrls.push(url);
     const figure=document.createElement('figure');const img=document.createElement('img');
     img.src=url;img.alt=`Prévia da foto ${index+1}`;img.loading='lazy';
     const caption=document.createElement('figcaption');caption.textContent=file.name+(index===0?' · sugerida para capa':'');
     figure.append(img,caption);localPhotoPreview.append(figure);
   });
   const archive=form.querySelector('[name="photo_zip"]');
   if(archive.files.length){const hint=document.createElement('p');hint.className='muted tiny';hint.textContent='ZIP selecionado: '+archive.files[0].name+'. As fotos serão verificadas e adicionadas após salvar.';localPhotoPreview.append(hint);}
 }
 for(const input of form.querySelectorAll('[name="gallery_files[]"],[name="image"],[name="photo_zip"]')){
   input.addEventListener('change',refreshLocalPhotos);
   input.addEventListener('news:files-ready',refreshLocalPhotos);
 }
 window.addEventListener('pagehide',()=>photoObjectUrls.forEach(url=>URL.revokeObjectURL(url)));
 // Um rascunho automático só guarda campos recuperáveis; arquivos locais nunca são enviados.
 function recoverableDraftName(name){
   return !['csrf','id','blocks_json'].includes(name)&&
     !['new_image_caption[]','new_image_credit[]','new_attachment_caption[]','new_attachment_credit[]'].includes(name);
 }
 function snapshot(){
   updateSource();syncInputs();const fields={};
   new FormData(form).forEach((value,name)=>{
     if(value instanceof File||!recoverableDraftName(name))return;
     if(name.endsWith('[]')){fields[name]??=[];fields[name].push(value);}
     else fields[name]=value;
   });
   return {fields,blocks};
 }
 const status=document.querySelector('#news-save-status');
 function restoreDraft(data){
   if(!data||!data.fields||typeof data.fields!=='object'||Array.isArray(data.fields))return;
   const groups=new Map();
   form.querySelectorAll('[name]').forEach(el=>{
     if(!recoverableDraftName(el.name)||el.type==='file')return;
     if(!groups.has(el.name))groups.set(el.name,[]);
     groups.get(el.name).push(el);
   });
   for(const [name,els] of groups){
     const exists=Object.hasOwn(data.fields,name),stored=exists?data.fields[name]:undefined;
     const values=Array.isArray(stored)?stored.filter(v=>typeof v==='string'||typeof v==='number').map(String):
       (typeof stored==='string'||typeof stored==='number'?[String(stored)]:[]);
     if(els[0].type==='checkbox'||els[0].type==='radio'){
       // A ausência de um checkbox no FormData significa desmarcado.
       els.forEach(el=>{el.checked=values.includes(String(el.value));});
     }else if(els[0].multiple&&els[0].options){
       if(exists)Array.from(els[0].options).forEach(opt=>{opt.selected=values.includes(String(opt.value));});
     }else if(exists){
       els.forEach((el,i)=>{
         if(Array.isArray(stored))el.value=values[i]??'';
         else if(i===0&&values.length)el.value=values[0];
       });
     }
   }
   if(Array.isArray(data.blocks))blocks=data.blocks;
   render();loadVisual();preview();categorySuggestion();updateCounters();updatePublication();if(typeof window!=='undefined')window.dispatchEvent(new Event('igua:seo-refresh'));
   status.textContent='Rascunho recuperado. Confira fotos, anexos e campos antes de publicar.';
 }
 const draftNotice=document.querySelector('#news-draft-notice');
 const draftSeenKey=draftNotice?'igua:news-draft-prompt:'+draftNotice.dataset.draftKey:'';
 const dismissDraft=()=>{if(!draftNotice)return;draftNotice.hidden=true;try{sessionStorage.setItem(draftSeenKey,'1');}catch(_){/* Navegação privada sem storage */}};
 if(draftNotice){try{if(sessionStorage.getItem(draftSeenKey)==='1')draftNotice.hidden=true;}catch(_){}}
 document.querySelector('#ignore-news-draft')?.addEventListener('click',dismissDraft);
 document.querySelector('#recover-news-draft')?.addEventListener('click',()=>{
   const el=document.querySelector('#news-draft-initial');
   if(el)try{restoreDraft(JSON.parse(el.textContent));dismissDraft();}catch(_){status.textContent='Não foi possível recuperar este rascunho.';}
 });
 async function autosave(){
   if(submitting||saving)return;
   const value=JSON.stringify(snapshot());if(value===lastJson)return;
   saving=true;status.textContent='Salvando rascunho…';
   try{
     const body=new URLSearchParams({csrf,id:articleId,payload:value});
     const r=await fetch('/admin.php?action=autosave_news',{method:'POST',body,credentials:'same-origin',headers:{'Accept':'application/json'}});
     if(!r.ok)throw Error('HTTP '+r.status);
     const result=await r.json();if(result.saved!==true)throw Error('Resposta inválida');
     lastJson=value;status.textContent='Rascunho salvo automaticamente';
   }catch(e){status.textContent='Sem conexão: tentaremos salvar de novo. Você também pode salvar manualmente.';}
   finally{saving=false;}
 }
 let saveTimer=setInterval(autosave,18000);window.addEventListener('beforeunload',()=>clearInterval(saveTimer));
 form.addEventListener('submit',e=>{
   updateSource();syncInputs();
   for(const i of [0,1,2,6])if(!canLeave(i)){e.preventDefault();return;}
   const choice=form.querySelector('[name="publication_choice"]:checked')?.value;
   if(!choice){changeTab(8,{scroll:true});status.textContent='Escolha como salvar ou publicar.';e.preventDefault();return;}
   if(choice==='scheduled'){
     const field=form.querySelector('[name=publish_at]');
     const when=field.value ? new Date(field.value) : null;
     if(!when||Number.isNaN(when.getTime())||when.getTime()<=Date.now()){
       changeTab(8,{scroll:true});status.textContent='Informe uma data e horário futuros para o agendamento.';field.focus();e.preventDefault();return;
     }
   }
   if(!form.querySelector('[name=seo_description]').value.trim())seo();
   updatePublication();submitting=true;
 });
 // Cadastrar categorias e fontes sem deixar o rascunho (ACL + CSRF no servidor).
 const taxonomyDialog=document.querySelector('#news-taxonomy-dialog');
 if(taxonomyDialog){
   const taxName=document.querySelector('#news-taxonomy-name'),taxUrl=document.querySelector('#news-taxonomy-url');
   const taxError=document.querySelector('#news-taxonomy-error'),urlWrap=document.querySelector('#news-taxonomy-url-wrap');
   const saveTax=document.querySelector('#news-taxonomy-save');let taxonomyKind='category';
   for(const trigger of document.querySelectorAll('[data-news-taxonomy]'))trigger.addEventListener('click',()=>{
     taxonomyKind=trigger.dataset.newsTaxonomy;
     document.querySelector('#news-taxonomy-title').textContent=taxonomyKind==='category'?'Nova categoria':'Nova fonte';
     urlWrap.hidden=taxonomyKind!=='source';taxName.maxLength=taxonomyKind==='category'?75:160;
     taxName.value='';taxUrl.value='';taxError.textContent='';saveTax.disabled=false;
     taxonomyDialog.showModal();taxName.focus();
   });
   document.querySelector('#news-taxonomy-close').addEventListener('click',()=>taxonomyDialog.close());
   taxonomyDialog.addEventListener('click',e=>{if(e.target===taxonomyDialog)taxonomyDialog.close();});
   saveTax.addEventListener('click',async()=>{
     const name=taxName.value.trim(),url=taxUrl.value.trim();
     if(name.length<2||name.length>(taxonomyKind==='category'?75:160)){taxError.textContent='Informe um nome válido.';taxName.focus();return;}
     if(taxonomyKind==='source'&&url&&(!/^https:\/\/[^\s]+$/i.test(url)||!taxUrl.checkValidity())){taxError.textContent='Use um endereço HTTPS válido.';taxUrl.focus();return;}
     saveTax.disabled=true;taxError.textContent='Salvando…';
     try{
       const endpoint=taxonomyKind==='category'?'news_create_category':'news_create_source';
       const data=new URLSearchParams({csrf,name,url});
       const response=await fetch('/admin.php?action='+endpoint,{method:'POST',body:data,credentials:'same-origin',headers:{'Accept':'application/json'}});
       if(!response.ok){
         const failure=await response.json().catch(()=>null);
         throw Error(typeof failure?.message==='string' && failure.message.length<=200
           ? failure.message : 'O servidor recusou o cadastro (HTTP '+response.status+').');
       }
       const result=await response.json();if(result.saved!==true||!Number.isInteger(result.id)||result.id<1)throw Error('Resposta inválida.');
       const select=form.querySelector(taxonomyKind==='category'?'[name=category_id]':'[name=source_id]');
       select.add(new Option(result.name,String(result.id),true,true));select.dispatchEvent(new Event('change',{bubbles:true}));
       if(taxonomyKind==='category'){
         const label=document.createElement('label'),checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.name='extra_categories[]';checkbox.value=String(result.id);
         label.append(checkbox,document.createTextNode(' '+result.name));document.querySelector('.news-category-chips').append(label);
       }else{
         form.querySelector('[name=source_name]').value=result.name;
         form.querySelector('[name=source_url]').value=url;
       }
       taxonomyDialog.close();status.textContent='Cadastro criado e selecionado. Continue a notícia.';
     }catch(e){taxError.textContent=e.message||'Não foi possível salvar agora.';}
     finally{saveTax.disabled=false;}
   });
 }
 render();loadVisual();preview();categorySuggestion();changeTab(0);
})();
