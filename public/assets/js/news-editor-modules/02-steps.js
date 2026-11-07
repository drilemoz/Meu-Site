 // Fluxo de três etapas. Não há uma quarta página de revisão/SEO.
 function changeTab(index,{scroll=false}={}){
   active=Math.max(0,Math.min(steps.length-1,index));
   steps.forEach((step,i)=>{step.hidden=i!==active;step.classList.toggle('active',i===active);});
   tabs.forEach((button,i)=>{
     button.classList.toggle('active',i===active);
     if(i===active)button.setAttribute('aria-current','step');else button.removeAttribute('aria-current');
   });
   const labels=['Básico','Texto','Foto'];
   document.querySelector('#news-step-progress').textContent=`Etapa ${active+1} de 3 · ${labels[active]}`;
   document.querySelector('#news-prev').hidden=active===0;
   document.querySelector('#news-next').hidden=active===steps.length-1;
   document.querySelector('#news-submit').hidden=active!==steps.length-1;
   if(scroll)document.querySelector('.news-editor-steps').scrollIntoView({block:'start',behavior:'auto'});
 }
 function hasText(){
   updateSource();syncInputs();
   return !!(form.querySelector('[name=body]').value.trim()||blocks.some(b=>b.content.trim()));
 }
 function canLeave(index){
   if(index===0){
     for(const field of [form.querySelector('[name=title]'),form.querySelector('[name=category_id]')]){
       if(!field.checkValidity()){changeTab(0);field.reportValidity();return false;}
     }
   }
   if(index===1&&!hasText()){
     status.textContent='Escreva o texto da notícia ou adicione um cartão antes de avançar.';
     changeTab(1);document.querySelector('#news-visual-editor').focus();return false;
   }
   return true;
 }
 function navigateTo(index){
   if(index>active){for(let i=active;i<index;i++)if(!canLeave(i))return;}
   changeTab(index,{scroll:true});
 }
