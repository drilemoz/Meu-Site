/* Meu Site 1.0.60: navegação à direita e confirmações próprias, com fallback seguro. */
(() => {
  'use strict';
  const nav = document.getElementById('admin-nav-dialog');
  const toggle = document.getElementById('admin-nav-toggle');
  const closeNav = document.getElementById('admin-nav-close');
  const resetToggle = () => { if (toggle) toggle.setAttribute('aria-expanded', 'false'); };
  const openNav = () => {
    if (!nav || !toggle) return;
    if (typeof nav.showModal === 'function') {
      nav.showModal(); toggle.setAttribute('aria-expanded', 'true'); closeNav?.focus();
    }
  };
  toggle?.addEventListener('click', openNav);
  closeNav?.addEventListener('click', () => nav?.close());
  nav?.addEventListener('close', () => { resetToggle(); toggle?.focus(); });
  nav?.addEventListener('click', e => {
    if (e.target === nav) nav.close();
  });

  // Formulários e busca aparecem somente sob demanda; deep links são respeitados.
  document.querySelectorAll('[data-admin-reveal]').forEach(btn => btn.addEventListener('click', e => {
    const details = document.getElementById(btn.dataset.adminReveal);
    if (!details || details.tagName !== 'DETAILS') return;
    if (btn.tagName === 'A') return;
    e.preventDefault(); details.open = true;
    details.scrollIntoView({ block: 'start', behavior: 'smooth' });
    details.querySelector('input:not([type=hidden]),select')?.focus({ preventScroll: true });
  }));
  const openHashSection = () => {
    if (!location.hash) return;
    const target = document.getElementById(location.hash.substring(1));
    // A Central de Integrações usa janelas próprias; não expandir o cartão no fundo.
    if (target?.closest?.('#integrations-hub')) return;
    if (target?.tagName === 'DETAILS') target.open = true;
  };
  openHashSection();
  window.addEventListener('hashchange', openHashSection);
  const filter = document.getElementById('news-filter-dialog');
  document.getElementById('news-filter-open')?.addEventListener('click', () => {
    if (filter && typeof filter.showModal === 'function') { filter.showModal(); filter.querySelector('[name=q]')?.focus(); }
  });
  document.getElementById('news-filter-close')?.addEventListener('click', () => filter?.close());
  filter?.addEventListener('click', e => { if(e.target === filter) filter.close(); });

  const confirmDialog = document.getElementById('admin-confirm-dialog');
  const messageDialog = document.getElementById('admin-message-dialog');
  const approved = new WeakSet();
  let pending = false;
  function ask(message, danger = false) {
    if (!confirmDialog || typeof confirmDialog.showModal !== 'function') return Promise.resolve(window.confirm(message));
    if (pending) return Promise.resolve(false);
    pending = true;
    confirmDialog.returnValue = 'cancel'; // Escape nunca herda uma confirmação anterior.
    document.getElementById('admin-confirm-message').textContent = message;
    document.getElementById('admin-confirm-accept').classList.toggle('admin-danger-confirm', danger);
    return new Promise(resolve => {
      const finished = () => { pending = false; resolve(confirmDialog.returnValue === 'confirm'); };
      confirmDialog.addEventListener('close', finished, { once: true });
      if (!window.IguaAdminModal?.open?.(confirmDialog)) confirmDialog.showModal();
    });
  }
  function notify(message) {
    if (!messageDialog || typeof messageDialog.showModal !== 'function') { window.alert(message); return Promise.resolve(); }
    document.getElementById('admin-message-text').textContent = String(message);
    if (messageDialog.open) messageDialog.close();
    if (!window.IguaAdminModal?.open?.(messageDialog)) messageDialog.showModal();
    return new Promise(resolve => messageDialog.addEventListener('close', () => resolve(), { once: true }));
  }
  // Campos obrigatórios dentro de seções recolhidas precisam ficar visíveis antes da validação nativa.
  document.addEventListener('invalid', e => {
    const fold = e.target?.closest?.('.admin-settings-fold');
    if (fold && !fold.open) fold.open = true;
  }, true);
  // Mantém apenas uma ajuda aberta e permite fechá-la fora do balão ou com Escape.
  document.addEventListener('click', e => {
    const activeHelp = e.target?.closest?.('.compact-help');
    document.querySelectorAll('.admin-body .compact-help[open]').forEach(help => {
      if (help !== activeHelp) help.open = false;
    });
  });
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.admin-body .compact-help[open]').forEach(help => { help.open = false; });
  });
  document.querySelectorAll('[data-admin-message-close]').forEach(el => el.addEventListener('click', () => messageDialog?.close()));
  window.IguaAdminUI = { confirm: ask, notify };
  // Captura antes de ações de outros scripts para nunca enviar um POST sem confirmação.
  document.addEventListener('click', async e => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn || !document.body.classList.contains('admin-body') || approved.has(btn)) return;
    if (btn.disabled) return;
    e.preventDefault(); e.stopImmediatePropagation();
    const question = btn.getAttribute('data-confirm') || 'Confirmar esta ação?';
    const danger = /excluir|apagar|remover|desativar|limpar|substituir|restaurar/i.test(question);
    if (!(await ask(question, danger))) return;
    approved.add(btn);
    try { btn.click(); } finally { approved.delete(btn); }
  }, true);
})();
