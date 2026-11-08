/* v1.0.89 — Abas e janelas nativas. Formulários reais são movidos, nunca clonados. */
(()=>{
  'use strict';
  const hub=document.getElementById('integrations-hub');
  if(!hub)return;
  const nav=hub.querySelector('[data-integrations-nav]');
  const buttons=nav?Array.from(nav.querySelectorAll('[data-int-target]')):[];
  const sections=Array.from(hub.querySelectorAll('[data-int-section]'));
  const dialog=hub.querySelector('#int-dialog');
  const modalAvailable=!!(dialog && typeof dialog.showModal==='function' && typeof dialog.close==='function');
  const panel=modalAvailable?dialog.querySelector('[data-int-dialog-panel]'):null;
  const title=modalAvailable?dialog.querySelector('[data-int-dialog-title]'):null;
  const back=modalAvailable?dialog.querySelector('[data-int-dialog-back]'):null;
  const scroller=modalAvailable?dialog.querySelector('[data-int-dialog-scroll]'):null;
  const stack=[];
  let initialFocus=null;
  const modalSelector='details.int-service,details.int-credentials,details.int-method-secondary,details.int-sc-list,details.int-speed-recommendations,details.int-health-history,details.int-index-tips,details.compact-help,.int-index-api';
  const activate=(id)=>{
    if(!sections.some(section=>section.id===id))return;
    sections.forEach(section=>{section.hidden=section.id!==id;});
    buttons.forEach(button=>{
      const active=button.dataset.intTarget===id;
      button.classList.toggle('is-active',active);
      button.setAttribute('aria-pressed',String(active));
    });
  };
  const updateModalTitle=()=>{
    const item=stack[stack.length-1];
    if(!item)return;
    title.textContent=item.title;
    back.hidden=stack.length<2;
    panel.className='int-dialog-panel '+(item.node.className||'');
    scroller.scrollTop=0;
  };
  const restoreCurrent=()=>{
    const current=stack[stack.length-1];
    if(!current)return;
    for(const node of current.moved)current.node.appendChild(node);
    current.moved=[];
  };
  const moveIntoDialog=(current)=>{
    const directSummary=current.node.querySelector(':scope > summary');
    current.moved=Array.from(current.node.childNodes).filter(node=>node!==directSummary);
    for(const node of current.moved)panel.appendChild(node);
    updateModalTitle();
  };
  const modalTitle=(node)=>{
    const summary=node.querySelector(':scope > summary');
    if(summary){
      const full=summary.querySelector('.int-service-copy strong');
      return (summary.getAttribute('aria-label') || full?.textContent || summary.textContent || 'Ajuda e configuração').trim();
    }
    return node.querySelector('h3, strong')?.textContent?.trim() || 'Inspeção de indexação';
  };
  const openModal=(node)=>{
    if(!modalAvailable)return false;
    if(!node || !node.matches(modalSelector))return false;
    if(stack.length && stack[stack.length-1].node===node)return true;
    if(stack.some(item=>item.node===node))return true;
    if(!stack.length){
      initialFocus=document.activeElement;
    }else{
      restoreCurrent();
      panel.replaceChildren();
    }
    stack.push({node,title:modalTitle(node),moved:[]});
    moveIntoDialog(stack[stack.length-1]);
    if(!dialog.open){if(!window.IguaAdminModal?.open?.(dialog,initialFocus))dialog.showModal();document.body?.classList.add('int-modal-open');}
    dialog.querySelector('[data-int-dialog-close]')?.focus({preventScroll:true});
    return true;
  };
  const backModal=()=>{
    if(stack.length<=1){dialog.close();return;}
    restoreCurrent();
    panel.replaceChildren();
    stack.pop();
    moveIntoDialog(stack[stack.length-1]);
    back.focus({preventScroll:true});
  };
  if(modalAvailable){
    hub.classList.add('int-modal-ready');
    // Um único dialog na camada superior, com navegação interna, evita overlays empilhados.
    dialog.addEventListener('cancel',event=>{if(stack.length>1){event.preventDefault();backModal();}});
    dialog.addEventListener('close',()=>{
      if(stack.length){restoreCurrent();panel.replaceChildren();stack.length=0;}
      document.body?.classList.remove('int-modal-open');
      if(initialFocus?.isConnected)initialFocus.focus({preventScroll:true});
      initialFocus=null;
    });
    back?.addEventListener('click',backModal);
    dialog.querySelectorAll('[data-int-dialog-close]').forEach(button=>button.addEventListener('click',()=>dialog.close()));
    hub.addEventListener('click',event=>{
      const trigger=event.target.closest('[data-int-open-panel]');
      if(trigger){
        const target=document.getElementById(trigger.getAttribute('data-int-open-panel'));
        if(target && openModal(target))event.preventDefault();
        return;
      }
      const summary=event.target.closest('summary');
      if(summary){
        const node=summary.parentElement;
        if(node?.matches(modalSelector) && openModal(node))event.preventDefault();
      }
    });
  }
  // PageSpeed: selecionar dispositivos só altera o painel visível; nunca faz chamadas à API.
  const speedBar=hub.querySelector('[data-int-speed-tabs]');
  if(speedBar){
    const speedTabs=Array.from(speedBar.querySelectorAll('[data-int-speed-tab]'));
    const speedGrid=hub.querySelector('[data-int-speed-grid]');
    const speedPanels=speedGrid?Array.from(speedGrid.querySelectorAll('[data-int-speed-panel]')):[];
    if(speedTabs.length===2 && speedPanels.length===2){
      const showSpeed=(device,moveFocus=false)=>{
        speedTabs.forEach(tab=>{
          const active=tab.dataset.intSpeedTab===device;
          tab.setAttribute('aria-selected',String(active));
          tab.tabIndex=active?0:-1;
          if(active && moveFocus)tab.focus({preventScroll:true});
        });
        speedPanels.forEach(item=>{item.hidden=item.dataset.intSpeedPanel!==device;});
      };
      speedTabs.forEach(tab=>{
        tab.addEventListener('click',()=>showSpeed(tab.dataset.intSpeedTab));
        tab.addEventListener('keydown',event=>{
          if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;
          event.preventDefault();
          const index=speedTabs.indexOf(tab);
          const next=event.key==='Home'?0:event.key==='End'?speedTabs.length-1:(index+(event.key==='ArrowRight'?1:-1)+speedTabs.length)%speedTabs.length;
          showSpeed(speedTabs[next].dataset.intSpeedTab,true);
        });
      });
      speedGrid.classList.add('int-speed-tabs-ready');
      showSpeed('mobile');
    }
  }
  const fromHash=()=>{
    let hash;
    try{hash=decodeURIComponent(location.hash.slice(1));}catch{return;}
    if(!hash)return;
    const target=document.getElementById(hash);
    if(!target || !hub.contains(target))return;
    const section=target.closest('[data-int-section]');
    if(section)activate(section.id);
    if(modalAvailable){
      if(target.matches(modalSelector))openModal(target);
      // Um link profundo não força rolagem da página. O diálogo tem rolagem própria.
    }else{
      if(target.tagName==='DETAILS')target.open=true;
      target.scrollIntoView?.({block:'nearest',behavior:'auto'});
    }
  };
  buttons.forEach(button=>button.addEventListener('click',()=>{
    if(modalAvailable && dialog.open)dialog.close();
    activate(button.dataset.intTarget);
    const newHash='#'+encodeURIComponent(button.dataset.intTarget);
    if(location.hash!==newHash)history.replaceState(null,'',newHash);
  }));
  if(modalAvailable){
    hub.querySelectorAll('a[href^="#"]').forEach(anchor=>anchor.addEventListener('click',event=>{
      const id=anchor.getAttribute('href').slice(1);
      const target=document.getElementById(id);
      if(!target || !hub.contains(target))return;
      event.preventDefault();
      const section=target.closest('[data-int-section]');
      if(section)activate(section.id);
      if(target.matches(modalSelector))openModal(target);
      history.replaceState(null,'','#'+encodeURIComponent(id));
    }));
  }
  window.addEventListener('hashchange',fromHash);
  activate('google');
  if(location.hash)fromHash();
  // Diagnóstico local da tag pública, sob clique e na mesma origem; nunca envia evento ao Google.
  const ga4PublicCheck=hub.querySelector('[data-ga4-public-test]');
  if(ga4PublicCheck){
    ga4PublicCheck.addEventListener('click',async()=>{
      const result=hub.querySelector('[data-ga4-public-result]');
      if(!result)return;
      const id=(hub.querySelector('#int-ga-id')?.value||'').trim().toUpperCase();
      result.hidden=false;
      result.classList.remove('is-error','is-ok');
      if(!/^G-[A-Z0-9]{4,20}$/.test(id)){
        result.textContent='Preencha e salve um ID G- válido antes de verificar a instalação pública.';
        result.classList.add('is-error');return;
      }
      ga4PublicCheck.disabled=true;
      result.textContent='Verificando o HTML público do portal…';
      try{
        const response=await fetch('/index.php',{method:'GET',credentials:'same-origin',cache:'no-store',headers:{Accept:'text/html'}});
        if(!response.ok)throw new Error('A página inicial retornou HTTP '+response.status+'.');
        if(!(response.headers.get('content-type')||'').toLowerCase().includes('text/html'))throw new Error('A página inicial não retornou HTML.');
        const html=await response.text();
        const doc=new DOMParser().parseFromString(html,'text/html');
        const publicId=(doc.querySelector('#ga4Consent[data-ga4-id]')?.getAttribute('data-ga4-id')||'').toUpperCase();
        const hasScript=Array.from(doc.querySelectorAll('script[src]')).some(node=>{
          try{return new URL(node.getAttribute('src'),location.origin).pathname==='/assets/js/ga4-consent.js';}catch{return false;}
        });
        if(publicId===id && hasScript){
          result.textContent='Tag GA4 e ID correspondente encontrados no HTML público. A instalação parece correta, mas o envio e o recebimento de eventos ainda precisam ser confirmados no Tempo real do Google após consentimento.';
          result.classList.add('is-ok');
        }else if(publicId && publicId!==id){
          result.textContent='O ID presente no site público difere do campo mostrado aqui. Salve a configuração e verifique cache, versão instalada e ID do fluxo Web.';
          result.classList.add('is-error');
        }else{
          result.textContent='Não encontrei a tag GA4 completa no HTML público. Verifique se a configuração foi salva, se a versão está instalada e se há cache de página.';
          result.classList.add('is-error');
        }
      }catch(error){
        result.textContent='Não foi possível verificar a página pública: '+(error instanceof Error?error.message:'falha de leitura')+' Abra a página inicial e confira a configuração.';
        result.classList.add('is-error');
      }finally{ga4PublicCheck.disabled=false;}
    });
  }
  // Todas as consultas externas permanecem POST com CSRF, sem disparo automático.
  for(const [selector,waiting] of [
    ['[data-dns-test-form]','[data-dns-wait]'],
    ['[data-search-console-test-form]','[data-search-console-wait]'],
    ['[data-url-inspection-form]','[data-url-inspection-wait]'],
    ['[data-ga4-test-form]','[data-ga4-wait]'],
    ['[data-pagespeed-test-form]','[data-pagespeed-wait]']
  ]){
    const all=Array.from(hub.querySelectorAll(selector));
    const forms=all.length?all:(hub.querySelector(selector)?[hub.querySelector(selector)]:[]);
    forms.forEach(form=>form.addEventListener('submit',()=>{
      const button=form.querySelector('button[type=submit]');
      const wait=form.querySelector(waiting);
      if(button){button.disabled=true;button.textContent='Consultando…';}
      if(wait)wait.hidden=false;
    }));
  }
  hub.querySelectorAll('[data-gemini-visibility]').forEach(button=>button.addEventListener('click',()=>{
    const input=document.getElementById(button.getAttribute('aria-controls'));
    if(input)input.type=input.type==='password'?'text':'password';
  }));
  // Teste da API Gemini num segundo diálogo compacto, sem recarregar a página nem expor credencial.
  const geminiTestDialog=hub.querySelector('#gemini-test-dialog');
  const geminiTestForm=hub.querySelector('[data-gemini-test-form]');
  const geminiTestResult=geminiTestDialog?.querySelector('[data-gemini-test-result]');
  const geminiTestRun=geminiTestDialog?.querySelector('[data-gemini-test-run]');
  let geminiTestBusy=false;
  let geminiTestOpener=null;
  if(geminiTestDialog && geminiTestForm && geminiTestResult && geminiTestRun && typeof geminiTestDialog.showModal==='function'){
    const report=(message,kind='')=>{
      geminiTestResult.classList.remove('is-error','is-success');
      if(kind)geminiTestResult.classList.add(kind);
      geminiTestResult.textContent=message;
    };
    hub.querySelectorAll('[data-gemini-test-open]').forEach(button=>button.addEventListener('click',()=>{
      if(geminiTestBusy)return;
      geminiTestOpener=button;
      report('O modelo configurado será consultado agora, somente se você iniciar o teste.');
      geminiTestRun.disabled=false;
      if(!geminiTestDialog.open)geminiTestDialog.showModal();
    }));
    geminiTestDialog.querySelectorAll('[data-gemini-test-close]').forEach(button=>button.addEventListener('click',()=>{
      if(!geminiTestBusy)geminiTestDialog.close();
    }));
    geminiTestDialog.addEventListener('cancel',event=>{if(geminiTestBusy)event.preventDefault();});
    geminiTestDialog.addEventListener('close',()=>{if(geminiTestOpener?.isConnected)geminiTestOpener.focus({preventScroll:true});});
    geminiTestRun.addEventListener('click',async()=>{
      if(geminiTestBusy)return;
      geminiTestBusy=true;geminiTestRun.disabled=true;
      geminiTestDialog.querySelectorAll('[data-gemini-test-close]').forEach(button=>{button.disabled=true;});
      report('Conectando ao Google Gemini…\nVerificando o modelo e a resposta real da API.');
      try{
        const response=await fetch(geminiTestForm.action,{method:'POST',credentials:'same-origin',headers:{Accept:'application/json'},body:new FormData(geminiTestForm)});
        const type=response.headers.get('content-type')||'';
        if(!type.includes('application/json'))throw new Error('O servidor não retornou um diagnóstico JSON. Verifique a sessão administrativa ou o PHP da hospedagem.');
        const data=await response.json();
        if(response.ok && data.ok){report('Conexão confirmada.\nModelo: '+String(data.model||'configurado')+'\n'+String(data.message||''),'is-success');}
        else{
          const http=data.http_status || response.status;
          const code=http?'HTTP '+http+' · ':'';
          report(code+String(data.message||'Não foi possível completar o teste.')+'\n'+String(data.advice||'Consulte a Central de Saúde.'),'is-error');
        }
      }catch(error){report('Falha ao consultar o servidor do portal. '+(error instanceof Error?error.message:'Verifique conexão e sessão.'),'is-error');}
      finally{geminiTestBusy=false;geminiTestRun.disabled=false;geminiTestDialog.querySelectorAll('[data-gemini-test-close]').forEach(button=>{button.disabled=false;});}
    });
  }
  const feedback=hub.querySelector('[data-int-copy-feedback]');
  const showFeedback=(text)=>{
    const place=modalAvailable && dialog.open?dialog.querySelector('[data-int-dialog-feedback]'):feedback;
    if(place){place.hidden=false;place.textContent=text;}
  };
  hub.querySelectorAll('[data-int-copy]').forEach(button=>button.addEventListener('click',async()=>{
    const field=document.getElementById(button.getAttribute('data-int-copy'));
    if(!field || typeof field.value!=='string')return;
    const value=field.value.trim();
    if(!value){showFeedback('O campo ainda está vazio.');return;}
    try{
      if(!navigator.clipboard?.writeText)throw new Error('clipboard_unavailable');
      await navigator.clipboard.writeText(value);
      showFeedback('Copiado. Confirme o valor antes de compartilhar.');
    }catch{
      field.focus();field.select();
      showFeedback('Selecionei o valor. Use Copiar no teclado do aparelho.');
    }
  }));
})();
