(()=>{
  'use strict';
  const root=document.querySelector('.sitemap-center');
  if(!root)return;
  // Diálogos reutilizáveis: abertura sob ação, âncoras legadas e fechamento acessível.
  const openTool = (id, trigger) => {
    const dialog=document.getElementById(id);
    if(!dialog)return false;
    return window.IguaAdminModal?.open(dialog, trigger) || false;
  };
  document.querySelectorAll('[data-sitemap-open]').forEach(button=>{
    button.addEventListener('click',()=>openTool(button.getAttribute('data-sitemap-open'),button));
  });
  document.querySelectorAll('[data-sitemap-close]').forEach(button=>{
    button.addEventListener('click',()=>{
      const dialog=button.closest('dialog');
      if(dialog)window.IguaAdminModal?.close(dialog);
    });
  });
  const deepLinks={'#indexnow':'sitemap-indexnow-dialog','#bing-webmaster':'sitemap-bing-dialog'};
  if(deepLinks[window.location.hash]){
    const source=document.querySelector('[data-sitemap-open="'+deepLinks[window.location.hash]+'"]');
    if(source)openTool(deepLinks[window.location.hash],source);
  }
  const status=document.getElementById('sitemap-online-results');
  const fileButton=document.getElementById('sitemap-check-files');
  const paths=['/sitemap.php','/robots.txt','/ads.txt'];
  // Nenhuma entrada arbitrária é usada no teste de recursos: apenas URLs da mesma origem.
  async function probe(path){
    if(typeof path!=='string'||!path.startsWith('/')||path.startsWith('//')||path.includes('\\')||/[\x00-\x1f]/.test(path))
      return {ok:false,label:'URL inválida'};
    const url=new URL(path,window.location.origin);
    if(url.origin!==window.location.origin)return {ok:false,label:'Origem não permitida'};
    try{
      const controller=new AbortController();
      const timeout=setTimeout(()=>controller.abort(),8000);
      try{
        // GET, não HEAD: hosts PHP compartilhados nem sempre respondem HEAD corretamente.
        const response=await fetch(url.href,{method:'GET',credentials:'same-origin',cache:'no-store',signal:controller.signal});
        if(new URL(response.url).origin!==window.location.origin)return {ok:false,label:'Redirecionamento para outro domínio'};
        if(response.body)await response.body.cancel();
        return {ok:response.ok,label:'HTTP '+response.status};
      }finally{clearTimeout(timeout);}
    }catch{return {ok:false,label:'Não foi possível acessar (rede ou timeout)'};}
  }
  if(fileButton&&status){
    fileButton.addEventListener('click',async()=>{
      fileButton.disabled=true;status.textContent='Verificando os arquivos no domínio...';
      try{
        const checks=await Promise.all(paths.map(probe));
        status.replaceChildren();
        for(let i=0;i<paths.length;i++){
          const p=document.createElement('p');
          p.textContent=paths[i]+': '+checks[i].label+(checks[i].ok?' ✓':' · requer atenção');
          status.appendChild(p);
        }
        const note=document.createElement('small');note.textContent='HTTP 200 não comprova conteúdo correto nem indexação pelo Google.';status.appendChild(note);
      }finally{fileButton.disabled=false;}
    });
  }
  document.querySelectorAll('[data-sitemap-copy]').forEach(button=>{
    button.addEventListener('click',async()=>{
      const value=button.getAttribute('data-sitemap-copy')||'';
      try{
        if(!navigator.clipboard||!navigator.clipboard.writeText)throw new Error('clipboard');
        await navigator.clipboard.writeText(value);button.textContent='Copiado ✓';
      }catch{button.textContent='Copie pelo link';}
    });
  });
  document.querySelectorAll('[data-sitemap-probe]').forEach(button=>{
    button.addEventListener('click',async()=>{
      const info=button.closest('.sitemap-url-card')?.querySelector('.sitemap-probe-result');
      button.disabled=true;if(info)info.textContent='Consultando o link público...';
      const result=await probe(button.getAttribute('data-sitemap-probe')||'');
      if(info)info.textContent=result.label+(result.ok?' · acessível':' · confira o endereço');
      button.disabled=false;
    });
  });
})();
