 const bodyField=form.querySelector('[name=body]');
 const visual=document.querySelector('#news-visual-editor');
 const raw=form.querySelector('.news-markdown-source');
 let editingSource=false;
 const sourceButton=document.querySelector('#news-source-toggle');
 const moreButton=document.querySelector('#news-more-format'),moreTools=document.querySelector('#news-more-tools');
 moreButton.addEventListener('click',()=>{moreTools.hidden=!moreTools.hidden;moreButton.setAttribute('aria-expanded',String(!moreTools.hidden));});
 function markdownFromVisual(){
   function walk(node){
     if(node.nodeType===3)return node.nodeValue||'';
     if(node.nodeType!==1)return '';
     const tag=node.tagName;
     let value=[...node.childNodes].map(walk).join('');
     if(tag==='B'||tag==='STRONG')return '**'+value+'**';
     if(tag==='I'||tag==='EM')return '*'+value+'*';
     if(tag==='BR')return '\n';
     if(/^H[1-6]$/.test(tag))return '\n## '+value.trim()+'\n';
     if(tag==='LI')return '\n- '+value.trim();
     if(tag==='UL'||tag==='OL')return '\n'+value+'\n';
     if(tag==='P'||tag==='DIV')return '\n'+value+'\n';
     return value;
   }
   return [...visual.childNodes].map(walk).join('').replace(/\u00a0/g,' ').replace(/\n{3,}/g,'\n\n').trim();
 }
 function loadVisual(){if(!visual||editingSource)return;visual.innerHTML=formattedPreview(bodyField.value);if(!bodyField.value.trim())visual.innerHTML='';}
 function updateSource(){if(editingSource||!visual)return;bodyField.value=markdownFromVisual();bodyField.dispatchEvent(new Event('input',{bubbles:true}));}
 function toggleSource(){editingSource=!editingSource;raw.classList.toggle('source-visible',editingSource);visual.hidden=editingSource;sourceButton.textContent=editingSource?'Editor visual':'Texto simples';if(editingSource)bodyField.focus();else loadVisual();}
 sourceButton.addEventListener('click',toggleSource);
 visual.addEventListener('input',updateSource);
 visual.addEventListener('paste',e=>{if(!e.clipboardData)return;e.preventDefault();const text=e.clipboardData.getData('text/plain')||'';document.execCommand('insertText',false,text);updateSource();});
 document.querySelectorAll('[data-format]').forEach(button=>button.addEventListener('click',()=>{
   const type=button.dataset.format;
   if(editingSource){
     const a=bodyField.selectionStart,b=bodyField.selectionEnd,chosen=bodyField.value.slice(a,b)||'texto';
     const text=type==='bold'?'**'+chosen+'**':type==='italic'?'*'+chosen+'*':type==='heading'?'\n## '+chosen:'\n- '+chosen;
     bodyField.setRangeText(text,a,b,'end');bodyField.dispatchEvent(new Event('input',{bubbles:true}));bodyField.focus();return;
   }
   visual.focus();
   if(type==='bold'||type==='italic')document.execCommand(type,false,null);
   if(type==='heading')document.execCommand('formatBlock',false,'h3');
   if(type==='list')document.execCommand('insertUnorderedList',false,null);
   updateSource();
 }));
 function formattedPreview(value){
   const escaped=esc(value),inline=t=>t.replace(/\*\*([^*\n]{1,400})\*\*/g,'<strong>$1</strong>').replace(/(^|[^*])\*([^*\n]{1,400})\*(?!\*)/g,'$1<em>$2</em>');
   let html='',paragraph=[],list=[];
   const flush=()=>{if(paragraph.length){html+='<p>'+paragraph.map(inline).join('<br>')+'</p>';paragraph=[];}if(list.length){html+='<ul>'+list.join('')+'</ul>';list=[];}};
   for(let line of escaped.trim().split(/\r?\n/)){line=line.trim();if(!line){flush();continue;}
     if(/^##\s+/.test(line)){flush();html+='<h3>'+inline(line.slice(3))+'</h3>';}
     else if(/^-\s+/.test(line)){if(paragraph.length)flush();list.push('<li>'+inline(line.slice(2))+'</li>');}
     else {if(list.length)flush();paragraph.push(line);}
   }flush();return html||'<p class="muted">Digite o texto para ver a prévia.</p>';
 }
 const previewBody=document.querySelector('#news-body-preview');
 const previewButton=document.querySelector('#news-toggle-preview');
 previewButton?.addEventListener('click',()=>{previewBody.hidden=!previewBody.hidden;if(!previewBody.hidden)previewBody.innerHTML=formattedPreview(bodyField.value);previewButton.textContent=previewBody.hidden?'◉':'×';previewButton.setAttribute('aria-label',previewBody.hidden?'Prévia':'Fechar prévia');});
 bodyField.addEventListener('input',()=>{if(!previewBody.hidden)previewBody.innerHTML=formattedPreview(bodyField.value);});
