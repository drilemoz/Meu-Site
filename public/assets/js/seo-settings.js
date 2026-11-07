/* 1.0.102 — prévias locais e modais acessíveis de SEO. Sem requisições externas. */
(() => {
  'use strict';
  const form = document.getElementById('site-settings-form');
  const center = document.getElementById('seo-portal-center');
  if (!form || !center) return;
  const field = name => form.elements.namedItem(name);
  const get = name => String(field(name)?.value ?? '').trim();
  const checked = name => !!field(name)?.checked;
  const el = id => document.getElementById(id);
  const desc = field('seo_default_description');
  const titleTemplateField = field('seo_title_template');
  const validateTitleTemplate = () => titleTemplateField?.setCustomValidity(
    titleTemplateField.value.includes('%title%') ? '' : 'O modelo de título precisa conter %title%.'
  );
  titleTemplateField?.addEventListener('input',validateTitleTemplate);
  validateTitleTemplate();
  const social = field('seo_social_image');
  const initialIndex = checked('seo_index');
  let hasSubmitted = false;
  const serialized = () => JSON.stringify([...new FormData(form).entries()]);
  const originalPayload = serialized();
  const updateDirty = () => { el('seo-pending-changes').textContent = serialized() === originalPayload ? 'Nenhuma alteração nesta sessão' : 'Alterações pendentes de salvamento'; };
  const snapshots = new WeakMap();
  const snapshotFields = {
    'seo-content-dialog':['seo_default_description','seo_title_template'],
    'seo-advanced-dialog':['seo_site_url','seo_social_image','seo_pretty_urls','seo_index']
  };
  const saveSnapshot = dialog => {
    const names = snapshotFields[dialog.id] || [];
    snapshots.set(dialog,names.map(name=>[name,field(name).type === 'checkbox' ? field(name).checked : field(name).value]));
    dialog.dataset.seoApplied='0';
  };
  const dialogs = ['seo-content-dialog','seo-advanced-dialog','seo-diagnostic-dialog'].map(el).filter(Boolean);
  const imageUrl = url => {
    try {
      const parsed = new URL(url, window.location.origin);
      if (parsed.protocol !== 'https:' && parsed.origin !== window.location.origin) return '';
      if (url.startsWith('/') && !url.startsWith('/uploads/')) return '';
      if (!/\.(png|jpe?g|webp|gif)(?:$|[?#])/i.test(parsed.pathname)) return '';
      return parsed.href;
    } catch { return ''; }
  };
  const summary = () => {
    const title = get('seo_title_template').replaceAll('%title%', 'Últimas notícias').replaceAll('%site%', get('site_name'));
    const site = get('seo_site_url');
    const description = get('seo_default_description');
    el('seo-preview-title').textContent = title || 'Título não configurado';
    el('seo-social-preview-title').textContent = title || 'Título não configurado';
    el('seo-preview-description').textContent = description || 'Descrição não configurada';
    el('seo-social-preview-description').textContent = description || 'Descrição não configurada';
    el('seo-preview-url').textContent = site || 'Endereço HTTPS não configurado';
    el('seo-social-domain').textContent = site || 'Domínio não configurado';
    el('seo-description-count').textContent = `${Array.from(desc.value).length}/320 caracteres`;
    const valid = imageUrl(get('seo_social_image'));
    const img = el('seo-social-preview-image');
    const fallback = el('seo-social-preview-empty');
    if (valid) {
      img.hidden = false; fallback.hidden = true;
      if (img.src !== valid) img.src = valid;
    } else {
      img.hidden = true; img.removeAttribute('src'); fallback.hidden = false;
    }
    el('seo-image-status').textContent = valid ? 'Imagem selecionada. Confira a prévia social.' :
      (get('seo_social_image') ? 'Endereço inválido ou sem arquivo de imagem reconhecido.' : 'Nenhuma imagem social selecionada.');
  };
  const checks = () => {
    const descLength = Array.from(get('seo_default_description')).length;
    const titleTemplate = get('seo_title_template');
    const site = get('seo_site_url');
    let isHttps = false;
    try { const u = new URL(site); isHttps = u.protocol === 'https:' && !!u.hostname && !u.username && !u.password; } catch {}
    return [
      ['Modelo de título com %title%', titleTemplate.includes('%title%')],
      ['Descrição completa (preferência editorial: 50 a 160 caracteres)', descLength >= 50 && descLength <= 160],
      ['Endereço público HTTPS configurado', isHttps],
      ['Imagem social com URL de arquivo reconhecido', !!imageUrl(get('seo_social_image'))],
      ['Indexação habilitada', checked('seo_index')]
    ];
  };
  const runChecks = (openDialog = false) => {
    const results = checks();
    const done = results.filter(x => x[1]).length;
    const list = el('seo-diagnostic-list');
    list.replaceChildren(...results.map(([name,ok]) => {
      const li=document.createElement('li'); li.className=ok?'is-ok':'is-warning';
      li.textContent=(ok?'✓ ':'! ')+name;return li;
    }));
    const status=el('seo-portal-status');
    status.textContent = `${done}/${results.length} verificações`;
    status.classList.toggle('is-ok',done===results.length);
    status.classList.toggle('is-warning',done!==results.length);
    el('seo-portal-summary').textContent = done===results.length ?
      'Todos os critérios locais conferidos. Isto não garante indexação.' :
      `${results.length-done} ponto(s) para conferir. Abra o diagnóstico para ver os detalhes.`;
    if (openDialog) show(el('seo-diagnostic-dialog'));
  };
  const show = dialog => {
    if (!dialog || typeof dialog.showModal !== 'function') return;
    if (dialog.open) return;
    saveSnapshot(dialog);
    if (!window.IguaAdminModal?.open?.(dialog)) dialog.showModal();
  };
  const rollback = dialog => {
    if (dialog.dataset.seoApplied === '1') return;
    (snapshots.get(dialog)||[]).forEach(([name,value]) => {
      const input=field(name);if(input.type==='checkbox')input.checked=value;else input.value=value;
    });
    summary();updateDirty();
  };
  const close = (dialog,apply=false) => {
    if (!dialog?.open) return;
    if (apply) dialog.dataset.seoApplied='1';
    else rollback(dialog); // O botão Cancelar restaura imediatamente; Escape também usa o evento close.
    if (!window.IguaAdminModal?.close?.(dialog)) dialog.close();
  };
  dialogs.forEach(dialog => dialog.addEventListener('close',() => {
    rollback(dialog);
    summary();updateDirty();
    snapshots.delete(dialog);
  }));
  const tabs=[el('seo-tab-google'),el('seo-tab-social')];
  const selectTab = (index,focus=false) => {
    tabs.forEach((tab,i)=>{const selected=i===index;tab.setAttribute('aria-selected',String(selected));tab.tabIndex=selected?0:-1;});
    // Explicitly update both panels: tab and content must always agree.
    el('seo-preview-google').hidden = index!==0;
    el('seo-preview-social').hidden = index!==1;
    if(focus)tabs[index].focus();
  };
  tabs.forEach((tab,i)=>{
    tab.addEventListener('click',()=>selectTab(i));
    tab.addEventListener('keydown',event=>{
      if(!['ArrowRight','ArrowLeft','Home','End'].includes(event.key))return;
      event.preventDefault();const next=event.key==='Home'?0:event.key==='End'?1:event.key==='ArrowRight'?(i+1)%2:(i+1)%2;
      selectTab(next,true);
    });
  });
  center.querySelectorAll('[data-seo-dialog-open]').forEach(button=>button.addEventListener('click',()=>{
    const dialog=el(button.dataset.seoDialogOpen);
    if(dialog?.id==='seo-diagnostic-dialog')runChecks(true);
    else show(dialog);
  }));
  form.querySelectorAll('[data-seo-dialog-close]').forEach(button=>button.addEventListener('click',()=>close(button.closest('dialog'),button.dataset.seoDialogClose==='apply')));
  form.addEventListener('input',()=>{summary();updateDirty();});
  form.addEventListener('change',()=>{summary();updateDirty();});
  el('seo-portal-check').addEventListener('click',()=>runChecks(true));
  el('seo-social-preview-image').addEventListener('error',()=>{
    el('seo-social-preview-image').hidden=true;
    el('seo-social-preview-empty').hidden=false;
    el('seo-image-status').textContent='A imagem não carregou. Confira se o endereço aponta para um arquivo público.';
  });
  // A imagem pode ser selecionada na biblioteca por outro controlador.
  social?.addEventListener('input',summary);
  form.addEventListener('invalid',event=>{
    const dialog=event.target.closest('dialog');
    if(dialog && !dialog.open) show(dialog);
  },true);
  form.addEventListener('submit',async event=>{
    if (hasSubmitted || !initialIndex || checked('seo_index')) return;
    event.preventDefault();
    const confirm = window.IguaAdminUI?.confirm ? await window.IguaAdminUI.confirm('Desativar a indexação do portal? Isso pode afetar a presença das páginas nos buscadores.',true)
      : window.confirm('Desativar a indexação do portal?');
    if(!confirm)return;
    hasSubmitted=true;
    form.requestSubmit(event.submitter || undefined);
  });
  summary();
})();
