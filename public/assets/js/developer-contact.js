/* Modal acessível do desenvolvedor, sem bibliotecas externas. */
(() => {
  const modal = document.getElementById('igDeveloperDialog');
  if (!modal) return;
  const openers = document.querySelectorAll('[data-developer-contact]');
  const closers = modal.querySelectorAll('[data-developer-close]');
  for (const button of openers) button.addEventListener('click', () => {
    if (typeof modal.showModal === 'function') modal.showModal();
    else modal.setAttribute('open', '');
  });
  for (const button of closers) button.addEventListener('click', () => {
    if (typeof modal.close === 'function') modal.close();
    else modal.removeAttribute('open');
  });
  modal.addEventListener('click', event => {
    if (event.target !== modal) return;
    const rect = modal.getBoundingClientRect();
    if (event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom) {
      if (typeof modal.close === 'function') modal.close(); else modal.removeAttribute('open');
    }
  });
})();
