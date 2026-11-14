// Compartilhamento público, sob ação do visitante; nenhum rastreador ou serviço externo.
(() => {
  const root = document.querySelector('[data-public-share]');
  if (!root) return;
  const share = root.querySelector('[data-share-trigger]');
  const copy = root.querySelector('[data-share-copy]');
  const status = root.querySelector('[data-share-status]');
  const manual = root.querySelector('[data-share-manual]');
  if (!share || !copy || !status || !manual) return;

  let url;
  try {
    const target = new URL(root.dataset.shareUrl || '', window.location.origin);
    if (target.origin !== window.location.origin || !['https:', 'http:'].includes(target.protocol)) return;
    url = target.href;
  } catch (_) { return; }
  const title = root.dataset.shareTitle || document.title;

  const copyLink = async () => {
    manual.hidden = true;
    try {
      if (!navigator.clipboard || typeof navigator.clipboard.writeText !== 'function') throw new Error('clipboard unavailable');
      await navigator.clipboard.writeText(url);
      status.textContent = 'Link copiado.';
    } catch (_) {
      manual.value = url;
      manual.hidden = false;
      manual.focus();
      manual.select();
      status.textContent = 'Selecione e copie o link abaixo.';
    }
  };
  copy.addEventListener('click', () => { void copyLink(); });
  share.addEventListener('click', async () => {
    if (typeof navigator.share !== 'function') { await copyLink(); return; }
    try {
      await navigator.share({title, url});
      status.textContent = '';
    } catch (error) {
      if (error?.name === 'AbortError') return; // O visitante cancelou o compartilhamento.
      await copyLink();
    }
  });
})();
