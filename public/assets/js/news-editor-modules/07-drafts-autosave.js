 // Um rascunho automático só guarda campos recuperáveis; arquivos locais nunca são enviados.
 function recoverableDraftName(name){
   return !['csrf','id','blocks_json'].includes(name)&&
     !['new_image_caption[]','new_image_credit[]','new_attachment_caption[]','new_attachment_credit[]'].includes(name);
 }
 function snapshot(){
   updateSource();syncInputs();const fields={};
   new FormData(form).forEach((value,name)=>{
     if(value instanceof File||!recoverableDraftName(name))return;
     if(name.endsWith('[]')){fields[name]??=[];fields[name].push(value);}
     else fields[name]=value;
   });
   return {fields,blocks};
 }
 const status=document.querySelector('#news-save-status');
 function restoreDraft(data){
   if(!data||!data.fields||typeof data.fields!=='object'||Array.isArray(data.fields))return;
   const groups=new Map();
   form.querySelectorAll('[name]').forEach(el=>{
     if(!recoverableDraftName(el.name)||el.type==='file')return;
     if(!groups.has(el.name))groups.set(el.name,[]);
     groups.get(el.name).push(el);
   });
   for(const [name,els] of groups){
     const exists=Object.hasOwn(data.fields,name),stored=exists?data.fields[name]:undefined;
     const values=Array.isArray(stored)?stored.filter(v=>typeof v==='string'||typeof v==='number').map(String):
       (typeof stored==='string'||typeof stored==='number'?[String(stored)]:[]);
     if(els[0].type==='checkbox'||els[0].type==='radio'){
       // A ausência de um checkbox no FormData significa desmarcado.
       els.forEach(el=>{el.checked=values.includes(String(el.value));});
     }else if(els[0].multiple&&els[0].options){
       if(exists)Array.from(els[0].options).forEach(opt=>{opt.selected=values.includes(String(opt.value));});
     }else if(exists){
       els.forEach((el,i)=>{
         if(Array.isArray(stored))el.value=values[i]??'';
         else if(i===0&&values.length)el.value=values[0];
       });
     }
   }
   if(Array.isArray(data.blocks))blocks=data.blocks;
   render();loadVisual();preview();categorySuggestion();if(typeof window!=='undefined')window.dispatchEvent(new Event('igua:seo-refresh'));
   status.textContent='Rascunho recuperado. Confira fotos, anexos e campos antes de publicar.';
 }
 document.querySelector('#recover-news-draft')?.addEventListener('click',()=>{
   const el=document.querySelector('#news-draft-initial');
   if(el)try{restoreDraft(JSON.parse(el.textContent));}catch(_){status.textContent='Não foi possível recuperar este rascunho.';}
 });
 async function autosave(){
   if(submitting||saving)return;
   const value=JSON.stringify(snapshot());if(value===lastJson)return;
   saving=true;status.textContent='Salvando rascunho…';
   try{
     const body=new URLSearchParams({csrf,id:articleId,payload:value});
     const r=await fetch('/admin.php?action=autosave_news',{method:'POST',body,credentials:'same-origin',headers:{'Accept':'application/json'}});
     if(!r.ok)throw Error('HTTP '+r.status);
     const result=await r.json();if(result.saved!==true)throw Error('Resposta inválida');
     lastJson=value;status.textContent='Rascunho salvo automaticamente';
   }catch(e){status.textContent='Sem conexão: tentaremos salvar de novo. Você também pode salvar manualmente.';}
   finally{saving=false;}
 }
 let saveTimer=setInterval(autosave,18000);window.addEventListener('beforeunload',()=>clearInterval(saveTimer));
