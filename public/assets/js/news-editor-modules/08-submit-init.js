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
 render();loadVisual();preview();categorySuggestion();changeTab(0);
})();
