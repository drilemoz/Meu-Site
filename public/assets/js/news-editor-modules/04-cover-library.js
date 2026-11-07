 const picked=form.querySelector('#news-library-cover');const pickedStatus=document.querySelector('#news-library-selected');
 document.querySelectorAll('[data-library-cover]').forEach(button=>button.addEventListener('click',()=>{
   const fileInput=form.querySelector('[name=image]');if(fileInput.files.length){pickedStatus.textContent='Remova o novo upload antes de escolher uma capa da biblioteca.';return;}
   picked.value=button.dataset.libraryCover;form.querySelector('#news-remove-cover').value='0';pickedStatus.textContent='Capa selecionada da biblioteca: '+(button.dataset.caption||button.dataset.libraryCover);
   if(!form.querySelector('[name=image_caption]').value)form.querySelector('[name=image_caption]').value=button.dataset.caption||'';
   if(!form.querySelector('[name=image_credit]').value)form.querySelector('[name=image_credit]').value=button.dataset.credit||'';
 }));
 document.querySelector('#news-library-clear')?.addEventListener('click',()=>{picked.value='';form.querySelector('#news-remove-cover').value='1';form.querySelector('[name=image]').value='';pickedStatus.textContent='Capa removida. Se a opção automática estiver ativa, a primeira foto da galeria poderá virar capa.';});
 form.querySelector('[name=image]')?.addEventListener('change',()=>{if(form.querySelector('[name=image]').files.length){picked.value='';form.querySelector('#news-remove-cover').value='0';pickedStatus.textContent='Upload novo selecionado; escolha de capa da biblioteca desfeita.';}});
