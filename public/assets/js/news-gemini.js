/* Gemini editorial: somente botão de ícone, chamada sob demanda, revisão humana antes de aplicar. */
(()=>{
 'use strict';
 const form=document.getElementById('news-form');
 const dialog=document.getElementById('news-gemini-dialog');
 if(!form||!dialog||typeof dialog.showModal!=='function')return;
 const feedback=document.getElementById('news-gemini-feedback');
 const suggestion=document.getElementById('news-gemini-suggestion');
 const apply=document.getElementById('news-gemini-apply');
 const title=document.getElementById('news-gemini-heading');
 const field=name=>form.querySelector('[name="'+name+'"]');
 const csrf=field('csrf');
 let current=null;
 let pending=false;
 let originButton=null;
 const names={title:'Manchete',summary:'Descrição',body:'Matéria',seo:'Descrição SEO'};
 const close=()=>{if(dialog.open)dialog.close();};
 dialog.querySelectorAll('[data-news-gemini-close]').forEach(btn=>btn.addEventListener('click',close));
 dialog.addEventListener('close',()=>{if(originButton?.isConnected)originButton.focus({preventScroll:true});originButton=null;});
 suggestion.addEventListener('input',()=>{apply.disabled=pending||!suggestion.value.trim()||!current;});
 const modalOpen=button=>{
   if(dialog.open)return;
   if(!window.IguaAdminModal?.open?.(dialog,button))dialog.showModal();
 };
 async function request(button){
   if(pending)return;
   const task=button.dataset.newsGemini;
   if(!Object.hasOwn(names,task))return;
   originButton=button;current=null;apply.disabled=true;pending=true;
   title.textContent='Gemini · '+names[task];
   suggestion.value='';feedback.textContent='Consultando o Gemini…';
   modalOpen(button);
   const endpoint='/admin.php?action=news_gemini_suggest';
   // O acesso é restrito ao mesmo domínio; não inclua chaves ou tokens de API.
   const body=new URLSearchParams({csrf:csrf.value,id:field('id').value,task,
     title:field('title').value,summary:field('summary').value,body:field('body').value});
   try{
     const response=await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json'},body});
     const data=await response.json().catch(()=>null);
     if(!response.ok || data?.ok!==true || typeof data?.suggestion!=='string'){
       throw Error(typeof data?.message==='string'&&data.message.length<350?data.message:
         'Não foi possível consultar o Gemini (HTTP '+response.status+').');
     }
     if(!['title','summary','body','seo_description'].includes(data.field))throw Error('Resposta inesperada.');
     current=data.field;
     suggestion.value=data.suggestion;
     feedback.textContent='Sugestão recebida. Confira os fatos antes de aplicar.';
   }catch(error){
     feedback.textContent=error.message||'Erro ao consultar a API.';
   }finally{pending=false;apply.disabled=!current||!suggestion.value.trim();}
 }
 form.querySelectorAll('[data-news-gemini]').forEach(button=>button.addEventListener('click',()=>request(button)));
 function applyTextToVisual(value){
   const visual=document.getElementById('news-visual-editor');
   if(!visual||visual.hidden)return;
   const frag=document.createDocumentFragment();
   for(const para of value.replace(/\r\n/g,'\n').split(/\n{2,}/)){
     const block=document.createElement('p');
     const lines=para.split('\n');
     lines.forEach((line,index)=>{if(index)block.appendChild(document.createElement('br'));block.appendChild(document.createTextNode(line));});
     frag.appendChild(block);
   }
   visual.replaceChildren(frag);
   visual.dispatchEvent(new Event('input',{bubbles:true}));
 }
 apply.addEventListener('click',()=>{
   if(!current||pending)return;
   const selected=suggestion.value.trim();
   const target=field(current);
   if(!selected||!target)return;
   const maxLength=target.maxLength>0?target.maxLength:12000;
   if([...selected].length>maxLength){
     feedback.textContent='A sugestão está acima do limite deste campo. Reduza-a antes de aplicar.';return;
   }
   target.value=selected;
   if(current==='body')applyTextToVisual(selected);
   target.dispatchEvent(new Event('input',{bubbles:true}));
   if(current==='seo_description')window.dispatchEvent(new Event('igua:seo-refresh'));
   feedback.textContent='Sugestão aplicada apenas ao editor. A notícia não foi salva nem publicada.';
   close();
 });
})();
