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
