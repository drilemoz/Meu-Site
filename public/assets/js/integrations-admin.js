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
    if(!dialog.open){dialog.showModal();document.body?.classList.add('int-modal-open');}
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
