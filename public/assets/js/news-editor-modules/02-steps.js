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
   if(scroll){document.querySelector('.news-editor-steps').scrollIntoView({block:'start',behavior:'auto'});tabs[active]?.scrollIntoView({inline:'nearest',block:'nearest'});}
 }
 function hasText(){updateSource();syncInputs();return !!(form.querySelector('[name=body]').value.trim()||blocks.some(b=>b.content.trim()));}
 function canLeave(index){
   if(index===0){const field=form.querySelector('[name=title]');if(!field.checkValidity()){changeTab(0);field.reportValidity();return false;}}
   if(index===1){const field=form.querySelector('[name=summary]');if(field.value.trim().length<20){changeTab(1);status.textContent='Escreva uma descrição com ao menos 20 caracteres.';field.focus();return false;}}
   if(index===2&&!hasText()){
     changeTab(2);status.textContent='Escreva a matéria ou adicione um cartão antes de avançar.';
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
