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
