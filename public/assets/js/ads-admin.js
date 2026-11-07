/** Publicidade 1.0.59: janelas acessíveis; segurança/ocupação definitiva no PHP. */
(() => {
  const root = document.getElementById('ad-center');
  if (!root) return;
  const dataElement = document.getElementById('ad-dashboard-data');
  let state = { slots: {}, campaigns: [] };
  try { state = JSON.parse(dataElement?.textContent || '{}'); } catch (_) {}
  const dialogs = [...document.querySelectorAll('.ad-dialog')];
  const get = id => document.getElementById(id);
  const editor = get('ad-local-form');
  const warning = get('ad-overlap-warning');
  const overlapLine = get('ad-overlap-line');
  const slotLine = get('ad-slot-prepare-line');
  const active = editor?.elements.namedItem('active');
  const slotSelect = editor?.elements.namedItem('placement_slug');
  const start = editor?.elements.namedItem('start_date');
  const end = editor?.elements.namedItem('end_date');
  const id = editor?.elements.namedItem('id');
  const formValue = (name, value) => {
    const field = editor?.elements.namedItem(name);
    if (field) field.value = value == null ? '' : String(value);
  };
  const overlap = (aStart, aEnd, bStart, bEnd) => (!aEnd || aEnd >= bStart) && (!bEnd || bEnd >= aStart);
  const closeAll = () => dialogs.forEach(dialog => {
    if (dialog.open) {
      if (typeof dialog.close === 'function') dialog.close();
      else dialog.removeAttribute('open');
    }
  });
  const open = key => {
    const dialog = get(key);
    if (!dialog) return;
    closeAll();
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
    const focus = dialog.querySelector('input:not([type="hidden"]),select,button[data-ad-close]');
    focus?.focus({ preventScroll: true });
  };
  const availability = () => {
    if (!editor || !warning) return;
    const current = state.slots?.[slotSelect.value] || {};
    const isActive = !!active?.checked;
    const existing = (state.campaigns || []).filter(c => String(c.placement_slug) === slotSelect.value &&
      Number(c.id) !== Number(id.value) && Number(c.active) === 1 &&
      overlap(start.value, end.value || null, String(c.start_date), c.end_date || null));
    warning.hidden = true;
    overlapLine.hidden = true;
    slotLine.hidden = true;
    const slotPrepare = editor.elements.namedItem('prepare_local_slot');
    if (isActive && current.provider === 'adsense' && Number(current.enabled) === 1) {
      warning.textContent = 'Esta posição está dedicada ao AdSense. Abra a posição e altere o provedor antes de ativar uma campanha local.';
      warning.hidden = false;
      slotPrepare.checked = false;
    } else if (isActive && (current.provider !== 'local' || Number(current.enabled) !== 1)) {
      slotLine.hidden = false;
      warning.textContent = 'Posição desativada. Marque a opção para prepará-la como anúncios locais.';
      warning.hidden = false;
    } else {
      slotPrepare.checked = false;
    }
    if (isActive && existing.length) {
      overlapLine.hidden = false;
      warning.textContent = `Atenção: ${existing.length} campanha(s) ativa(s) usam esta posição em datas coincidentes. Mude o período/posição ou confirme o revezamento.`;
      warning.hidden = false;
    } else {
      editor.elements.namedItem('allow_overlap').checked = false;
    }
  };
  const newLocal = position => {
    if (!editor) return;
    editor.reset();
    formValue('id', '0');
    formValue('advertiser', '');formValue('title', '');formValue('description', '');formValue('target_url', '');
    formValue('end_date', '');formValue('priority', '0');
    formValue('start_date', start?.defaultValue || ''); // servidor valida a data
    formValue('library_campaign', '');
    if (position && state.slots?.[position]) slotSelect.value = position;
    active.checked = true;
    const extra = editor.querySelector('details');if (extra) extra.open = false;
    const picked = get('campaign-image-selected');if (picked) picked.textContent = '';
    const current = get('ad-current-image');if (current) current.textContent = '';
    get('ad-local-title').textContent = 'Novo anúncio local';
    availability();open('ad-local');
  };
  const edit = number => {
    const c = (state.campaigns || []).find(c => Number(c.id) === Number(number));
    if (!c || !editor) return;
    editor.reset();
    for (const field of ['id','advertiser','title','description','target_url','placement_slug','start_date','end_date','priority']) {
      formValue(field, c[field]);
    }
    active.checked = Number(c.active) === 1;
    formValue('library_campaign', '');
    get('ad-current-image').textContent = c.image ? `Imagem atual: ${c.image}` : '';
    const picked = get('campaign-image-selected');if (picked) picked.textContent = '';
    const extra = editor.querySelector('details');if (extra) extra.open = true;
    get('ad-local-title').textContent = 'Editar campanha #' + c.id;
    availability();open('ad-local');
  };
  const restoreDraft = draft => {
    if (!draft || !editor) return;
    newLocal(draft.placement_slug);
    for (const field of ['id','advertiser','title','description','target_url','placement_slug','start_date','end_date','priority']) {
      formValue(field, draft[field]);
    }
    active.checked = !!draft.active;
    editor.elements.namedItem('prepare_local_slot').checked = !!draft.prepare_local_slot;
    editor.elements.namedItem('allow_overlap').checked = !!draft.allow_overlap;
    get('ad-local-title').textContent = Number(draft.id) > 0 ? 'Editar campanha #' + draft.id : 'Novo anúncio local';
    availability();
    editor.elements.namedItem('prepare_local_slot').checked = !!draft.prepare_local_slot;
    editor.elements.namedItem('allow_overlap').checked = !!draft.allow_overlap;
  };
  const checkAdsense = () => {
    const form = get('ad-new-adsense')?.querySelector('form');
    const alert = get('ad-adsense-warning');
    if (!form || !alert) return;
    const slug = form.elements.namedItem('slot')?.value;
    const existing = (state.campaigns || []).filter(c => c.placement_slug === slug && Number(c.active) === 1);
    alert.hidden = existing.length === 0;
    if (existing.length) alert.textContent = `Há ${existing.length} campanha(s) local(is) ativa(s) nesta posição. Pause-as antes de ativar o AdSense.`;
  };
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-ad-open],[data-ad-close],[data-ad-new],[data-ad-edit]');
    if (!button) return;
    if (button.hasAttribute('data-ad-close')) { closeAll();return; }
    if (button.hasAttribute('data-ad-edit')) { edit(button.dataset.adEdit);return; }
    if (button.hasAttribute('data-ad-new')) {
      if (button.dataset.adNew === 'local') newLocal(button.dataset.adPosition || null);
      else { open('ad-new-adsense');checkAdsense(); }
      return;
    }
    if (button.dataset.adOpen) open(button.dataset.adOpen);
  });
  dialogs.forEach(dialog => dialog.addEventListener('click', event => {
    if (event.target === dialog) closeAll();
  }));
  [slotSelect,start,end,active].forEach(field=>field?.addEventListener('change',availability));
  get('ad-new-adsense')?.querySelector('select[name="slot"]')?.addEventListener('change',checkAdsense);
  if (state.draft) restoreDraft(state.draft);
})();
