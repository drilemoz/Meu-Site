/* Otimização local de fotos. Sem envio externo; o servidor continua validando MIME e tamanho. */
(()=>{'use strict';
 const form=document.querySelector('#news-form');if(!form)return;
 const notice=message=>{if(typeof window!=='undefined'&&window.IguaAdminUI?.notify)return window.IguaAdminUI.notify(message);if(typeof alert==='function')return alert(message);};
 const inputs=[{el:form.querySelector('[name=image]'),label:document.querySelector('#news-image-upload-feedback')},{el:form.querySelector('[name="gallery_files[]"]'),label:document.querySelector('#news-gallery-upload-feedback')}];
 const target=1450*1024; // Abaixo do upload_max_filesize padrão do PHP (2M).
 const pending=new Map(),errors=new Map(),selectionTokens=new WeakMap();
 function status(text,label){if(label)label.textContent=text;}
 function canvasJpeg(canvas,quality){return new Promise(resolve=>canvas.toBlob(resolve,'image/jpeg',quality));}
 async function optimize(file){
   if(file.size<=target||!/^image\/(jpeg|png|webp)$/.test(file.type))return file;
   if(typeof createImageBitmap!=='function'||typeof DataTransfer==='undefined')throw Error('Este navegador não suporta otimização automática. Envie JPG menor que 1,5 MB.');
   const bitmap=await createImageBitmap(file);
   try{
     const maxEdge=Math.max(bitmap.width,bitmap.height);
     let scale=Math.min(1,1600/maxEdge);
     for(let step=0;step<3;step++){
       const canvas=document.createElement('canvas');
       canvas.width=Math.max(1,Math.round(bitmap.width*scale));canvas.height=Math.max(1,Math.round(bitmap.height*scale));
       const ctx=canvas.getContext('2d',{alpha:false});if(!ctx)throw Error('Não foi possível processar a fotografia neste aparelho.');
       ctx.fillStyle='#ffffff';ctx.fillRect(0,0,canvas.width,canvas.height);ctx.drawImage(bitmap,0,0,canvas.width,canvas.height);
       for(const q of [0.82,0.73,0.62]){
         const jpg=await canvasJpeg(canvas,q);
         if(!jpg)throw Error('Falha ao converter imagem.');
         if(jpg.size<=target)return new File([jpg],file.name.replace(/\.[^.]*$/, '')+'.jpg',{type:'image/jpeg',lastModified:Date.now()});
       }
       scale*=0.76;
     }
     throw Error('A foto continua muito grande. Reduza a resolução antes do envio.');
   }finally{bitmap.close();}
 }
 for(const {el,label} of inputs){if(!el)continue;
   el.addEventListener('change',()=>{
     const token=(selectionTokens.get(el)||0)+1;
     selectionTokens.set(el,token);
     errors.delete(el); // Erro anterior só desaparece quando ESTE campo é corrigido.
     const chosen=[...el.files];if(!chosen.length){pending.delete(el);status('',label);return;}
     const isCurrent=()=>selectionTokens.get(el)===token;
     const task=(async()=>{
       status('Preparando '+chosen.length+' foto(s) para envio…',label);
       const originals=chosen.map(f=>f.size);
       const optimized=await Promise.all(chosen.map(optimize));
       // No Android, selecionar novamente enquanto a foto anterior é processada
       // não pode sobrescrever a seleção mais recente quando a tarefa antiga termina.
       if(!isCurrent())return;
       if(optimized.some((file,i)=>file!==chosen[i])){
         if(typeof DataTransfer==='undefined')throw Error('Este navegador não suporta a troca de arquivos otimizados.');
         const dt=new DataTransfer();optimized.forEach(f=>dt.items.add(f));el.files=dt.files;
       }
       const saved=originals.reduce((a,b)=>a+b,0)-optimized.reduce((a,b)=>a+b.size,0);
       status(saved>0?'Foto(s) pronta(s). Economia de '+(saved/1048576).toFixed(1).replace('.',',')+' MB.':'Foto(s) pronta(s) para envio.',label);
       // Mantém as legendas já digitadas enquanto atualiza os nomes dos arquivos.
       if(el.hasAttribute('data-upload-input'))el.dispatchEvent(new Event('news:files-ready',{bubbles:false}));
     })().catch(err=>{
       if(!isCurrent())return;
       const message=err.message||'Não foi possível otimizar as fotos.';
       errors.set(el,message);status(message,label);
     });
     pending.set(el,task);task.finally(()=>{if(pending.get(el)===task)pending.delete(el);});
   });
 }
 form.addEventListener('submit',e=>{
   if(pending.size){
     e.preventDefault();e.stopImmediatePropagation();
     const button=e.submitter||form.querySelector('#news-submit');
     Promise.allSettled([...pending.values()]).then(()=>{if(errors.size===0)form.requestSubmit(button);else notice([...errors.values()][0]);});return;
   }
   if(errors.size){e.preventDefault();e.stopImmediatePropagation();notice([...errors.values()][0]+' Selecione uma imagem menor ou remova a foto para salvar.');return;}
   const total=[...form.querySelectorAll('input[type=file]')].reduce((n,input)=>n+[...input.files].reduce((sum,f)=>sum+f.size,0),0);
   const limit=Number(form.dataset.phpPostLimit)||0;
   if(limit>0&&total+300000>limit){
     e.preventDefault();e.stopImmediatePropagation();notice('O envio total ultrapassa o limite post_max_size do PHP. Envie menos fotos por vez ou aumente o limite do servidor.');
   }
 },true);
})();
