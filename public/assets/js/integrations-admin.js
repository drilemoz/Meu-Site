/* Central de Integrações: abas acessíveis, estados sob demanda e cópia explícita de IDs públicos. */
(()=>{
  'use strict';
  const hub=document.getElementById('integrations-hub');
  if(!hub)return;
  const nav=hub.querySelector('[data-integrations-nav]');
  const buttons=Array.from(nav.querySelectorAll('[data-int-target]'));
  const sections=Array.from(hub.querySelectorAll('[data-int-section]'));
  const activate=(id)=>{
    if(!sections.some(section=>section.id===id))return;
    sections.forEach(section=>{section.hidden=section.id!==id;});
    buttons.forEach(button=>{
      const active=button.dataset.intTarget===id;
      button.classList.toggle('is-active',active);
      button.setAttribute('aria-pressed',String(active));
    });
  };
  const fromHash=()=>{
    let hash;
    try{hash=decodeURIComponent(location.hash.slice(1));}catch{return;}
    const target=document.getElementById(hash);
    if(!target || !hub.contains(target))return;
    const section=target.closest('[data-int-section]');
    if(section)activate(section.id);
    if(target.tagName==='DETAILS')target.open=true;
    target.scrollIntoView({block:'nearest',behavior:'auto'});
  };
  buttons.forEach(button=>button.addEventListener('click',()=>{
    activate(button.dataset.intTarget);
    const newHash='#'+encodeURIComponent(button.dataset.intTarget);
    if(location.hash!==newHash)history.replaceState(null,'',newHash);
  }));
  window.addEventListener('hashchange',fromHash);
  activate('google');
  if(location.hash)fromHash();
  // Consultas externas somente após POST protegido por CSRF.
  for(const [selector,waiting] of [
    ['[data-dns-test-form]','[data-dns-wait]'],
    ['[data-search-console-test-form]','[data-search-console-wait]'],
    ['[data-url-inspection-form]','[data-url-inspection-wait]'],
    ['[data-ga4-test-form]','[data-ga4-wait]'],
    ['[data-pagespeed-test-form]','[data-pagespeed-wait]']
  ]){
    // PageSpeed possui dois formulários; ambos mostram seu próprio andamento.
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
  hub.querySelectorAll('[data-int-copy]').forEach(button=>button.addEventListener('click',async()=>{
    const field=document.getElementById(button.getAttribute('data-int-copy'));
    if(!field || typeof field.value!=='string')return;
    const value=field.value.trim();
    if(feedback)feedback.hidden=false;
    if(!value){if(feedback)feedback.textContent='O campo ainda está vazio.';return;}
    try{
      if(!navigator.clipboard?.writeText)throw new Error('clipboard_unavailable');
      await navigator.clipboard.writeText(value);
      if(feedback)feedback.textContent='Copiado. Confirme o valor antes de compartilhar.';
    }catch{
      field.focus();field.select();
      if(feedback)feedback.textContent='Selecionei o valor. Use Copiar no teclado do aparelho.';
    }
  }));
})();
