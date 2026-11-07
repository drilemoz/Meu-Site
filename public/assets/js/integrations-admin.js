/* Central de Integrações: abas progressivas, sem alterar formulários ou credenciais. */
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
    const hash=decodeURIComponent(location.hash.slice(1));
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
  const dnsTest=hub.querySelector('[data-dns-test-form]');
  if(dnsTest)dnsTest.addEventListener('submit',()=>{
    const button=dnsTest.querySelector('button[type=submit]');
    const wait=dnsTest.querySelector('[data-dns-wait]');
    if(button){button.disabled=true;button.textContent='Consultando…';}
    if(wait)wait.hidden=false;
  });
  const test=hub.querySelector('[data-ga4-test-form]');
  if(test)test.addEventListener('submit',()=>{
    const button=test.querySelector('button[type=submit]');
    const wait=test.querySelector('[data-ga4-wait]');
    if(button){button.disabled=true;button.textContent='Consultando…';}
    if(wait)wait.hidden=false;
  });
})();
