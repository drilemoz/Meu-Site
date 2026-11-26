 form.addEventListener('submit',e=>{
   updateSource();syncInputs();
   for(const i of [0,1,2,6])if(!canLeave(i)){e.preventDefault();return;}
   const choice=form.querySelector('#news-publication-choice')?.value;
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
 // Cadastrar categorias, fontes e autores sem deixar o rascunho (ACL + CSRF).
 const taxonomyDialog=document.querySelector('#news-taxonomy-dialog');
 if(taxonomyDialog){
   const taxName=document.querySelector('#news-taxonomy-name'),taxUrl=document.querySelector('#news-taxonomy-url');
   const taxError=document.querySelector('#news-taxonomy-error'),urlWrap=document.querySelector('#news-taxonomy-url-wrap');
   const saveTax=document.querySelector('#news-taxonomy-save');
   const kinds=new Map([
     ['category',{title:'Nova categoria',max:75,action:'news_create_category',field:'category_id'}],
     ['source',{title:'Nova fonte',max:160,action:'news_create_source',field:'source_id'}],
   ]);
   let taxonomyKind='category';
   for(const trigger of document.querySelectorAll('[data-news-taxonomy]'))trigger.addEventListener('click',()=>{
     const config=kinds.get(trigger.dataset.newsTaxonomy);
     if(!config||saveTax.disabled)return;
     taxonomyKind=trigger.dataset.newsTaxonomy;
     document.querySelector('#news-taxonomy-title').textContent=config.title;
     urlWrap.hidden=taxonomyKind!=='source';taxName.maxLength=config.max;
     taxName.value='';taxUrl.value='';taxError.textContent='';
     taxonomyDialog.showModal();taxName.focus();
   });
   document.querySelector('#news-taxonomy-close').addEventListener('click',()=>taxonomyDialog.close());
   taxonomyDialog.addEventListener('click',e=>{if(e.target===taxonomyDialog)taxonomyDialog.close();});
   saveTax.addEventListener('click',async()=>{
     const kind=taxonomyKind,config=kinds.get(kind);
     const name=taxName.value.trim(),url=taxUrl.value.trim();
     if(name.length<2||name.length>config.max){taxError.textContent='Informe um nome válido.';taxName.focus();return;}
     if(kind==='source'&&url&&(!/^https:\/\/[^\s]+$/i.test(url)||!taxUrl.checkValidity())){taxError.textContent='Use um endereço HTTPS válido.';taxUrl.focus();return;}
     saveTax.disabled=true;taxError.textContent='Salvando…';
     try{
       const endpoint=config.action;
       const data=new URLSearchParams({csrf,name,url});
       const response=await fetch('/admin.php?action='+endpoint,{method:'POST',body:data,credentials:'same-origin',headers:{'Accept':'application/json'}});
       if(!response.ok){
         const failure=await response.json().catch(()=>null);
         throw Error(typeof failure?.message==='string' && failure.message.length<=200
           ? failure.message : 'O servidor recusou o cadastro (HTTP '+response.status+').');
       }
       const result=await response.json();if(result.saved!==true||!Number.isInteger(result.id)||result.id<1)throw Error('Resposta inválida.');
       const select=form.elements[config.field];
       select.add(new Option(result.name,String(result.id),true,true));select.dispatchEvent(new Event('change',{bubbles:true}));
       if(kind==='category'){
         const label=document.createElement('label'),checkbox=document.createElement('input');checkbox.type='checkbox';checkbox.name='extra_categories[]';checkbox.value=String(result.id);
         label.append(checkbox,document.createTextNode(' '+result.name));document.querySelector('.news-category-chips').append(label);
       }
       if(taxonomyDialog.open)taxonomyDialog.close();
       status.textContent='Cadastro criado e selecionado. Continue a notícia.';
     }catch(e){
       const message=e.message||'Não foi possível salvar agora.';
       if(taxonomyDialog.open)taxError.textContent=message;
       else status.textContent=message;
     }
     finally{saveTax.disabled=false;}
   });
 }
 render();loadVisual();preview();categorySuggestion();changeTab(0);
})();
