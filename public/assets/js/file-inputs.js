/* IguaNews 1.0.19: seleção de arquivos consistente e acessível em formulários. */
(()=>{'use strict';
 const enhance=(root=document)=>root.querySelectorAll('input[type="file"]:not([data-file-enhanced])').forEach(input=>{
  input.dataset.fileEnhanced='1';
  if(input.closest('.news-upload-tile')){input.dataset.fileEnhanced='custom';input.classList.add('igua-native-file');return;}
  const wrapper=document.createElement('span');wrapper.className='igua-file-picker';
  const button=document.createElement('button');button.type='button';button.className='igua-file-button';button.textContent='📎 Escolher arquivo'+(input.multiple?'s':'');
  const status=document.createElement('span');status.className='igua-file-name';status.setAttribute('aria-live','polite');status.textContent='Nenhum arquivo';
  input.parentNode.insertBefore(wrapper,input);wrapper.append(button,status,input);
  input.classList.add('igua-native-file');input.setAttribute('aria-label',input.getAttribute('aria-label')||'Selecionar arquivo');
  button.addEventListener('click',e=>{e.preventDefault();e.stopPropagation();input.click();});
  const update=()=>{const files=[...input.files];status.textContent=!files.length?'Nenhum arquivo':files.length===1?files[0].name:files.length+' arquivos selecionados';status.title=status.textContent;};
  input.addEventListener('change',update);input.addEventListener('news:files-ready',update);update();
 });
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',()=>enhance());else enhance();
})();
