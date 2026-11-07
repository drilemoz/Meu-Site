/* 1.0.93 — Controlador compartilhado dos diálogos administrativos. */
(() => {
  'use strict';
  const focusOrigin = new WeakMap();
  const dialogs = [...document.querySelectorAll('dialog.igua-modal')];
  const visible = () => dialogs.some(dialog => dialog.open);
  const lock = () => document.body?.classList.toggle('igua-modal-open', visible());
  const busy = dialog => dialog?.dataset?.updaterBusy === '1' || dialog?.dataset?.iguaBusy === '1';
  const open = (dialog, opener = document.activeElement) => {
    if (typeof HTMLDialogElement === 'undefined' || !(dialog instanceof HTMLDialogElement) || typeof dialog.showModal !== 'function') return false;
    if (dialog.open) return true;
    if (opener?.isConnected) focusOrigin.set(dialog, opener);
    dialog.showModal();
    lock();
    const focus = dialog.querySelector('[data-igua-initial-focus], [data-int-dialog-close], [data-updater-close], [data-ad-close], .admin-ui-close');
    focus?.focus({preventScroll:true});
    return true;
  };
  const close = dialog => {
    if (!dialog?.open || busy(dialog)) return false;
    dialog.close();
    return true;
  };
  dialogs.forEach(dialog => {
    dialog.addEventListener('close', () => {
      lock();
      const origin = focusOrigin.get(dialog);
      if (origin?.isConnected) origin.focus({preventScroll:true});
      focusOrigin.delete(dialog);
    });
    dialog.addEventListener('cancel', event => {if (busy(dialog)) event.preventDefault();});
    dialog.addEventListener('click', event => {
      if (event.target !== dialog || busy(dialog)) return;
      const r = dialog.getBoundingClientRect();
      if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) close(dialog);
    });
  });
  window.IguaAdminModal = Object.freeze({open, close});
})();
