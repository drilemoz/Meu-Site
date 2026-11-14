/* Galeria de fotografias: <dialog> nativo, navegação acessível e links funcionais sem JS. */
(() => {
  'use strict';
  const gallery = document.querySelector('.news-gallery');
  const dialog = document.querySelector('[data-news-gallery-dialog]');
  if (!gallery || !dialog || typeof dialog.showModal !== 'function') return;

  const photos = Array.from(gallery.querySelectorAll('[data-gallery-photo]'));
  if (!photos.length) return;
  const image = dialog.querySelector('[data-gallery-large]');
  const caption = dialog.querySelector('[data-gallery-caption]');
  const credit = dialog.querySelector('[data-gallery-credit]');
  const counter = dialog.querySelector('[data-gallery-counter]');
  const original = dialog.querySelector('[data-gallery-original]');
  const loadStatus = dialog.querySelector('[data-gallery-image-status]');
  const previous = dialog.querySelector('[data-gallery-prev]');
  const next = dialog.querySelector('[data-gallery-next]');
  const close = dialog.querySelector('[data-gallery-close]');
  if (![image, caption, credit, counter, original, loadStatus, previous, next, close].every(Boolean)) return;

  let current = 0;
  let opener = null;
  let touchStart = null;

  function show(index) {
    current = (index + photos.length) % photos.length;
    const link = photos[current];
    const description = link.dataset.galleryCaption || `Foto ${current + 1} da reportagem`;
    const photographer = link.dataset.galleryCredit || '';
    loadStatus.textContent = 'Carregando foto…';
    image.alt = description;
    image.src = link.href;
    caption.textContent = description;
    credit.textContent = photographer ? `Foto: ${photographer}` : '';
    credit.hidden = !photographer;
    counter.textContent = `Foto ${current + 1} de ${photos.length}`;
    original.href = link.href;
    previous.hidden = photos.length < 2;
    next.hidden = photos.length < 2;
  }

  image.addEventListener('load', () => { loadStatus.textContent = ''; });
  image.addEventListener('error', () => { loadStatus.textContent = 'Não foi possível carregar esta foto.'; });

  photos.forEach((link, index) => link.addEventListener('click', event => {
    // Abrir em nova aba continua disponível com Ctrl/Cmd, Shift ou botão central.
    if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    opener = link;
    try {
      show(index);
      if (!dialog.open) dialog.showModal();
      document.body.classList.add('news-gallery-modal-open');
      close.focus();
      event.preventDefault();
    } catch (_) {
      // Se o browser não suportar a janela modal, o link original continua funcionando.
      document.body.classList.remove('news-gallery-modal-open');
    }
  }));

  previous.addEventListener('click', () => show(current - 1));
  next.addEventListener('click', () => show(current + 1));
  close.addEventListener('click', () => dialog.close());

  dialog.addEventListener('keydown', event => {
    if (event.key === 'ArrowLeft' && photos.length > 1) { event.preventDefault(); show(current - 1); }
    if (event.key === 'ArrowRight' && photos.length > 1) { event.preventDefault(); show(current + 1); }
    // Escape é gerenciado nativamente pelo <dialog>.
  });

  dialog.addEventListener('close', () => {
    document.body.classList.remove('news-gallery-modal-open');
    image.removeAttribute('src');
    loadStatus.textContent = '';
    if (opener && opener.isConnected) opener.focus();
    opener = null;
    touchStart = null;
  });

  dialog.addEventListener('click', event => {
    if (event.target === dialog) dialog.close();
  });

  const stage = dialog.querySelector('.news-gallery-stage');
  if (stage) {
    stage.addEventListener('touchstart', event => {
      if (event.touches.length !== 1) { touchStart = null; return; }
      touchStart = { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }, { passive: true });
    stage.addEventListener('touchend', event => {
      if (!touchStart || event.changedTouches.length !== 1 || photos.length < 2) { touchStart = null; return; }
      const dx = event.changedTouches[0].clientX - touchStart.x;
      const dy = event.changedTouches[0].clientY - touchStart.y;
      touchStart = null;
      if (Math.abs(dx) < 55 || Math.abs(dx) < Math.abs(dy) * 1.3) return;
      show(current + (dx < 0 ? 1 : -1));
    }, { passive: true });
    stage.addEventListener('touchcancel', () => { touchStart = null; }, { passive: true });
  }
})();
