/* IguaNews Media Manager 1.0.29. Todas as operações são autenticadas no admin.php. */
(()=>{'use strict';
 const modal=document.querySelector('#media-manager');if(!modal)return;
 const embedded=modal.classList.contains('media-inline');
 const $=selector=>modal.querySelector(selector);
 const grid=$('.media-grid'),path=$('.media-path'),search=$('[data-media-search]'),upload=$('[data-media-upload]');
 const searchBox=$('[data-media-search-box]'),searchToggle=$('[data-media-search-toggle]'),uploadName=$('[data-media-upload-name]');
 const requests=window.IguaMediaState.createRequests(),uploads=window.IguaMediaState.createUploadQueue();
 const csrf=modal.dataset.csrf;let level=0,year='',month='',mode='cover',page=1,query='',trash=false;
 const esc=v=>String(v??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const months=['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
 const notify=text=>{$('.media-status').textContent=text;};

 function mobileMedia(){return window.matchMedia('(max-width: 720px)').matches;}
 function refreshSearchBox(force=false){if(!searchBox||!searchToggle)return;const opened=force||query!==''||document.activeElement===search;searchBox.classList.toggle('is-open', !mobileMedia() || opened);searchToggle.setAttribute('aria-expanded', String(searchBox.classList.contains('is-open')));}
 function updateUploadName(files){if(!uploadName)return;const chosen=[...(files||[])];uploadName.textContent=chosen.length?chosen.map(file=>file.name).join(', '):'Nenhum arquivo';}
 const safeFetch=async(url,options)=>{const r=await fetch(url,{credentials:'same-origin',...options});const data=await r.json();if(!r.ok||!data.ok)throw Error(data.error||'Falha ao comunicar com o servidor.');return data;};
 async function load(){
  const ticket=requests.next();
  notify('Carregando…');
  try{
   const params=new URLSearchParams({action:'media_list',page,year,month,q:query,trash:trash?'1':'0'});
   const data=await safeFetch('/admin.php?'+params,{signal:ticket.signal});
   if(!requests.isCurrent(ticket)||modal.hidden)return;
   path.textContent='Biblioteca'+(level>0?' / '+year:'')+(level>1?' / '+months[Number(month)]:'')+(trash?' / Lixeira':'');
   const back=$('[data-media-back]');back.disabled=level===0;
   let html='';
   if(!query&&!trash&&level===0){const years=[...new Set(data.folders.map(x=>x.folder_year))];html=years.map(y=>`<button type="button" class="media-folder" data-year="${Number(y)}">📁 ${Number(y)}</button>`).join('');}
   else if(!query&&!trash&&level===1){html=data.folders.filter(x=>String(x.folder_year)===String(year)).map(x=>`<button type="button" class="media-folder" data-month="${Number(x.folder_month)}">📁 ${months[Number(x.folder_month)]} <small>(${Number(x.total)})</small></button>`).join('');}
   if(level===2||query||trash){html+=data.items.map(item=>`<article class="media-tile" data-id="${Number(item.id)}"><button type="button" data-${mode==='standalone'?'details':'select'}="${Number(item.id)}" title="${mode==='standalone'?'Ver detalhes':'Selecionar foto'}"><img loading="lazy" src="${esc(item.missing?'/assets/img/cidade.jpg':item.thumb)}" data-media-source="${esc(item.url)}" data-image-fallback="/assets/img/cidade.jpg" alt="${esc(item.alt_text||item.title)}"></button><small>${esc(item.title||item.original_name)}</small>${item.missing?'<span class="media-missing" role="status">Arquivo ausente</span>':''}<div class="media-tile-actions"><button type="button" data-details="${Number(item.id)}">Detalhes</button><button type="button" data-download="${Number(item.id)}">↓</button></div></article>`).join('');}
   grid.innerHTML=html||'<p class="muted">Nenhuma imagem nesta pasta.</p>';
   modal._items=data.items;
   $('.media-paging').hidden=!(level===2||query||trash);
   $('.media-pages').textContent=`${data.page} de ${data.pages} · ${data.total} imagens`;
   $('[data-media-prev]').disabled=data.page<=1;$('[data-media-next]').disabled=data.page>=data.pages;
   notify('');
  }catch(err){if(requests.isCurrent(ticket) && !modal.hidden && err.name!=='AbortError')notify(err.message);}
 }
 function open(which){if(embedded)return;clearTimeout(search._t);mode=which;modal.hidden=false;document.body.classList.add('media-modal-open');level=0;year='';month='';page=1;trash=false;query='';search.value='';refreshSearchBox(false);load();$('.media-close')?.focus();}
 function close(){if(embedded)return;clearTimeout(search._t);requests.invalidate();modal.hidden=true;document.body.classList.remove('media-modal-open');}
 document.querySelectorAll('[data-media-pick]').forEach(b=>b.addEventListener('click',()=>open(b.dataset.mediaPick)));
 // Ações de seleção são criadas nas próprias telas, sem botões duplicados.
 // Os uploads diretos opcionais continuam disponíveis e acessíveis.
 $('.media-close')?.addEventListener('click',close);
 searchToggle?.addEventListener('click',()=>{if(!searchBox)return;const willOpen=!searchBox.classList.contains('is-open');if(willOpen){refreshSearchBox(true);search.focus();}else{search.value='';query='';search.blur();refreshSearchBox(false);page=1;load();}});
 window.addEventListener('resize',()=>refreshSearchBox(false));
 if(!embedded)modal.addEventListener('click',e=>{if(e.target===modal)close();});
 if(!embedded)document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.hidden&&!document.getElementById('admin-confirm-dialog')?.open&&!document.getElementById('admin-message-dialog')?.open)close();});
 $('[data-media-back]').addEventListener('click',()=>{if(level===2){level=1;month='';}else if(level===1){level=0;year='';}page=1;load();});
 grid.addEventListener('click',async e=>{
  const y=e.target.closest('[data-year]'),m=e.target.closest('[data-month]');
  if(y){year=y.dataset.year;level=1;load();return;}if(m){month=m.dataset.month;level=2;load();return;}
  const btn=e.target.closest('[data-select],[data-download],[data-details]');if(!btn)return;
  const id=Number(btn.dataset.select||btn.dataset.download||btn.dataset.details);
  const item=modal._items?.find(x=>Number(x.id)===id);if(!item)return;
  if(btn.dataset.select&&trash){notify('Restaure a imagem antes de selecioná-la.');return;}
  if(btn.dataset.select&&item.missing){notify('Arquivo ausente no servidor. Envie a imagem novamente antes de selecioná-la.');return;}
  if(btn.dataset.download){window.location.href='/admin.php?action=media_download&id='+id;return;}
  if(btn.dataset.details){showDetails(item);return;}
  if(mode==='cover'){
   const target=document.querySelector('[name=library_cover]');if(target){target.value=item.filename;
    const cap=document.querySelector('[name=image_caption]'),credit=document.querySelector('[name=image_credit]');if(cap&&!cap.value)cap.value=item.caption;if(credit&&!credit.value)credit.value=item.credit;
    const status=document.querySelector('#news-library-selected');if(status)status.textContent='Capa escolhida: '+(item.title||item.filename);}
  }else if(mode==='gallery'){
   const form=document.querySelector('#news-form'),list=document.querySelector('#media-gallery-selected');if(form){
    if(form.querySelectorAll('input[name="library_gallery[]"]').length>=10){notify('Máximo de 10 fotos na galeria.');return;}
    if(![...form.querySelectorAll('input[name="library_gallery[]"]')].some(x=>x.value===item.filename)){
     const input=document.createElement('input');input.type='hidden';input.name='library_gallery[]';input.value=item.filename;form.appendChild(input);
     if(list){const pill=document.createElement('span');pill.textContent='✓ '+(item.title||item.filename)+' ';pill.className='media-pill';const remove=document.createElement('button');remove.type='button';remove.textContent='×';remove.onclick=()=>{input.remove();pill.remove();};pill.append(remove);list.append(pill);}
    }
   }
  }else if(mode==='campaign'){const target=document.querySelector('#library-campaign');if(target)target.value=item.filename;const status=document.querySelector('#campaign-image-selected');if(status)status.textContent='Selecionado: '+item.title;}
  else if(mode==='social'){const input=document.querySelector('[name=seo_social_image]');if(input){input.value=item.url;input.dispatchEvent(new Event('input',{bubbles:true}));}}
  close();
 });
 function showDetails(item){const box=$('.media-details');box.hidden=false;box.querySelector('[name=id]').value=item.id;
  for(const name of ['title','caption','credit','alt_text'])box.querySelector('[name='+name+']').value=item[name]||'';
  $('.media-details-preview').src=item.missing?'/assets/img/cidade.jpg':item.thumb;$('.media-details-preview').setAttribute('data-image-fallback','/assets/img/cidade.jpg');$('.media-details-name').textContent=item.original_name+' · '+item.width+'×'+item.height+' · '+(item.bytes/1024).toFixed(0)+' KB';
  $('.media-details-delete').textContent=item.deleted_at?'Restaurar':'Mover para lixeira';
 }
 $('.media-details-close').onclick=()=>{$('.media-details').hidden=true;};
 async function post(action,form){const body=new FormData(form);body.set('csrf',csrf);notify('Salvando…');const data=await safeFetch('/admin.php?action='+action,{method:'POST',body});return data;}
 $('.media-details-form').addEventListener('submit',async e=>{e.preventDefault();try{await post('media_update',e.currentTarget);$('.media-details').hidden=true;await load();notify('Informações salvas.');}catch(err){notify(err.message);}});
 $('.media-details-delete').addEventListener('click',async()=>{const form=$('.media-details-form'),id=form.querySelector('[name=id]').value;const isDeleted=$('.media-details-delete').textContent==='Restaurar';if(!isDeleted&&!(await window.IguaAdminUI.confirm('Enviar esta imagem para a lixeira? Só é permitido quando não há notícias ou anúncios vinculados.', true)))return;
  try{const fd=new FormData();fd.set('id',id);fd.set('csrf',csrf);await safeFetch('/admin.php?action='+(isDeleted?'media_restore':'media_trash'),{method:'POST',body:fd});$('.media-details').hidden=true;load();}catch(err){notify(err.message);}
 });
 // Processamento no dispositivo para evitar uploads acima do limite PHP; JPEG fotográfico,
 // WebP/Png transparente permanece intacto quando o navegador não oferece conversão segura.
 async function optimize(file){if(file.size<1300000)return file;
  if(!/^image\/(jpeg|png|webp)$/.test(file.type)||!('createImageBitmap' in window)||!('DataTransfer' in window))return file;
  const bitmap=await createImageBitmap(file);try{const scale=Math.min(1,1920/Math.max(bitmap.width,bitmap.height));
   const canvas=document.createElement('canvas');canvas.width=Math.round(bitmap.width*scale);canvas.height=Math.round(bitmap.height*scale);
   const ctx=canvas.getContext('2d');if(!ctx)return file;ctx.drawImage(bitmap,0,0,canvas.width,canvas.height);
   const mime=file.type==='image/png'?'image/webp':'image/jpeg';const converted=await new Promise(resolve=>canvas.toBlob(resolve,mime,.85));
   if(converted && converted.type===mime && converted.size<file.size && converted.size<1500000)return new File([converted],file.name.replace(/\.[^.]+$/,'')+(mime==='image/webp'?'.webp':'.jpg'),{type:mime});
   return file;
  }finally{bitmap.close();}
 }
 const uploadButton=$('.media-upload-form button[type=submit]');
 function showPending(){
  updateUploadName(uploads.remaining());
  uploadButton.textContent=uploads.remaining().length?'Reenviar pendentes':'Enviar fotos';
 }
 async function performUpload(){
  if(uploads.isSending())return;
  const pending=uploads.remaining();if(!pending.length)return;
  if(pending.length>10){notify('Escolha no máximo 10 imagens por envio.');return;}
  uploadButton.disabled=true;upload.disabled=true;
  const result=await uploads.send(async(original)=>{
   notify(`Preparando imagem ${pending.length-uploads.remaining().length+1} de ${pending.length}…`);
   const file=await optimize(original);
   const body=new FormData();body.set('media_file',file);body.set('csrf',csrf);
   await safeFetch('/admin.php?action=media_upload',{method:'POST',body});
  },(sent,left)=>{notify(`${sent} de ${pending.length} imagens confirmadas. ${left} pendente(s).`);});
  uploadButton.disabled=false;upload.disabled=false;showPending();
  if(result.uploaded>0){
   // Mostra os arquivos já recebidos, mesmo quando o lote restante falha.
   year=new Date().getFullYear().toString();month=String(new Date().getMonth()+1);level=2;trash=false;query='';search.value='';page=1;refreshSearchBox(false);
   await load();
  }
  if(result.error){
   notify(`${result.uploaded} confirmada(s) neste envio, ${result.remaining} pendente(s): ${result.error.message}. Reenvie apenas as pendentes. Se houve perda de conexão após o servidor gravar uma foto, confira a biblioteca antes de tentar novamente.`);
  }else{notify(`${result.uploaded} imagem(ns) adicionada(s).`);}
 }
 $('.media-upload-form').addEventListener('submit',async e=>{e.preventDefault();await performUpload();});
 upload.addEventListener('change',async()=>{
  if(uploads.isSending())return;
  const selected=[...upload.files];
  if(selected.length>10){notify('Escolha no máximo 10 imagens por envio.');upload.value='';return;}
  if(selected.length && uploads.remaining().length && !(await window.IguaAdminUI.confirm('Existem fotos ainda não enviadas. Substituir a fila de fotos pendentes?', true))){upload.value='';showPending();return;}
  uploads.select(selected);
  // A fila conserva os File reais, permitindo reenviar os pendentes sem reenviar os concluídos.
  upload.value='';showPending();
  if(selected.length)await performUpload();
 });
 search.addEventListener('input',()=>{requests.invalidate();refreshSearchBox(true);clearTimeout(search._t);search._t=setTimeout(()=>{query=search.value.trim();page=1;load();},300);});
 $('[data-media-prev]').onclick=()=>{page=Math.max(1,page-1);load();};$('[data-media-next]').onclick=()=>{page++;load();};
 $('[data-media-trash]').onclick=()=>{trash=!trash;query='';search.value='';page=1;refreshSearchBox(false);load();};
 document.querySelector('#news-library-clear')?.addEventListener('click',()=>{const field=document.querySelector('#news-library-cover');if(field)field.value='';const el=document.querySelector('#news-library-selected');if(el)el.textContent='Nenhuma foto selecionada.';});
 if(embedded){mode='standalone';refreshSearchBox(false);load();}
})();
