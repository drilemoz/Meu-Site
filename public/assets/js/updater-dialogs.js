/* Atualizações 1.0.56 — janelas compactas acessíveis e independentes. */
(() => {
  const dialogs = [...document.querySelectorAll('.updater-dialog')];
  const openDialog = (dialog) => {
    if (!dialog) return;
    if (typeof dialog.showModal === 'function') dialog.showModal();
    else dialog.setAttribute('open', '');
  };
  const closeDialog = (dialog) => {
    if (typeof dialog.close === 'function') dialog.close();
    else dialog.removeAttribute('open');
  };
  document.querySelectorAll('[data-updater-open]').forEach(button => {
    button.addEventListener('click', () => openDialog(document.getElementById(button.dataset.updaterOpen)));
  });
  dialogs.forEach(dialog => {
    dialog.querySelectorAll('[data-updater-close]').forEach(button => button.addEventListener('click', () => closeDialog(dialog)));
    dialog.addEventListener('click', (event) => {
      if (event.target !== dialog) return;
      const rect = dialog.getBoundingClientRect();
      if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) closeDialog(dialog);
    });
  });
})();
