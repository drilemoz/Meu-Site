// Sugestões públicas: não altera o envio do formulário sem JavaScript.
(() => {
  const form = document.querySelector('[data-public-search]');
  if (!form) return;
  const input = form.querySelector('[name="q"]');
  const list = form.querySelector('[data-search-suggestions]');
  if (!input || !list) return;
  let timer = null, controller = null, requestNumber = 0, active = -1;
  const close = () => {
    list.hidden = true;
    list.replaceChildren();
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    active = -1;
  };
  const choices = () => [...list.querySelectorAll('[role="option"]')];
  const highlight = index => {
    const options = choices();
    active = index;
    options.forEach((node, i) => node.setAttribute('aria-selected', String(i === index)));
    if (index >= 0 && options[index]) {
      input.setAttribute('aria-activedescendant', options[index].id);
      options[index].scrollIntoView({block: 'nearest'});
    } else input.removeAttribute('aria-activedescendant');
  };
  const show = rows => {
    list.replaceChildren();
    active = -1;
    rows.forEach((item, index) => {
      if (!item || typeof item.label !== 'string' || typeof item.type !== 'string' || typeof item.url !== 'string' || !item.url.startsWith('/')) return;
      const link = document.createElement('a');
      link.className = 'header-search-suggestion';
      link.id = `search-result-${index}`;
      link.href = item.url;
      link.setAttribute('role', 'option');
      link.setAttribute('aria-selected', 'false');
      const kind = document.createElement('small'); kind.textContent = item.type;
      const title = document.createElement('span'); title.textContent = item.label;
      link.append(kind, title);
      list.append(link);
    });
    list.hidden = !list.children.length;
    input.setAttribute('aria-expanded', String(!list.hidden));
  };
  const search = () => {
    clearTimeout(timer);
    controller?.abort();
    requestNumber++;
    const text = input.value.trim();
    if ([...text].length < 2 || [...text].length > 100) { close(); return; }
    const current = requestNumber;
    timer = window.setTimeout(async () => {
      controller = new AbortController();
      try {
        const response = await fetch(`/api_search.php?q=${encodeURIComponent(text)}`, {signal: controller.signal, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
        if (!response.ok) { close(); return; }
        const results = await response.json();
        if (current !== requestNumber || input.value.trim() !== text || document.activeElement !== input) return;
        if (!Array.isArray(results)) {close();return;}
        show(results.slice(0,6));
      } catch (error) {
        if (current === requestNumber && error.name !== 'AbortError') close();
      }
    }, 300);
  };
  const dismiss = () => { requestNumber++; clearTimeout(timer); controller?.abort(); close(); };
  input.addEventListener('input', search);
  input.addEventListener('keydown', event => {
    if (event.key === 'Escape') {dismiss();return;}
    if (list.hidden) return;
    const options = choices();
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      event.preventDefault();
      highlight(event.key === 'ArrowDown' ? (active+1) % options.length : (active-1+options.length)%options.length);
    } else if (event.key === 'Enter' && active>=0 && options[active]) {
      event.preventDefault();
      window.location.assign(options[active].href);
    }
  });
  document.addEventListener('pointerdown', event => {if (!form.contains(event.target)) dismiss();});
  form.addEventListener('submit', () => {clearTimeout(timer);controller?.abort();});
  input.addEventListener('blur', () => {window.setTimeout(() => {if (!form.contains(document.activeElement)) dismiss();}, 150);});
})();
