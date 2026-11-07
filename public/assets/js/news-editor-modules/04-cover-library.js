 const picked=form.querySelector('#news-library-cover');const pickedStatus=document.querySelector('#news-library-selected');
 document.querySelectorAll('[data-library-cover]').forEach(button=>button.addEventListener('click',()=>{
   const fileInput=form.querySelector('[name=image]');if(fileInput.files.length){pickedStatus.textContent='Remova o novo upload antes de escolher uma capa da biblioteca.';return;}
   picked.value=button.dataset.libraryCover;pickedStatus.textContent='Capa selecionada da biblioteca: '+(button.dataset.caption||button.dataset.libraryCover);
   if(!form.querySelector('[name=image_caption]').value)form.querySelector('[name=image_caption]').value=button.dataset.caption||'';
   if(!form.querySelector('[name=image_credit]').value)form.querySelector('[name=image_credit]').value=button.dataset.credit||'';
 }));
 document.querySelector('#news-library-clear')?.addEventListener('click',()=>{picked.value='';pickedStatus.textContent='Nenhuma capa escolhida na biblioteca.';});
 form.querySelector('[name=image]')?.addEventListener('change',()=>{if(form.querySelector('[name=image]').files.length){picked.value='';pickedStatus.textContent='Upload novo selecionado; escolha de capa da biblioteca desfeita.';}});
