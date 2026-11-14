// Pesquisa pública progressiva: o envio GET tradicional continua funcional sem JavaScript.
(() => {
  const form = document.querySelector('[data-public-search]');
  if (!form) return;
  const input = form.querySelector('[name="q"]');
  const panel = form.querySelector('[data-search-suggestions]');
  const optionsList = form.querySelector('#public-search-options');
  const caption = form.querySelector('[data-search-caption]');
  const allLink = form.querySelector('[data-search-all]');
  if (!input || !panel || !optionsList || !caption || !allLink) return;
  let timer = null, controller = null, sequence = 0, active = -1;
  const choices = () => [...optionsList.querySelectorAll('[role="option"]')];
  const close = () => {
    panel.hidden = true;
    optionsList.replaceChildren();
    caption.textContent = '';
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
    input.removeAttribute('aria-busy');
    active = -1;
  };
  const updateAvailableHeight = () => {
    if (panel.hidden) return;
    const viewport = window.visualViewport;
    const bottom = viewport ? viewport.offsetTop + viewport.height : window.innerHeight;
    const free = Math.max(0, Math.floor(bottom - form.getBoundingClientRect().bottom - 14));
    panel.style.setProperty('--search-room', `${free}px`);
  };
  const open = () => {
    panel.hidden = false;
    input.setAttribute('aria-expanded', 'true');
    updateAvailableHeight();
  };
  const highlight = index => {
    const options = choices();
    if (!options.length) return;
    active = index;
    options.forEach((node, i) => node.setAttribute('aria-selected', String(i === index)));
    if (index >= 0 && options[index]) {
      input.setAttribute('aria-activedescendant', options[index].id);
      options[index].scrollIntoView({block: 'nearest'});
    } else input.removeAttribute('aria-activedescendant');
  };
  const buildIcon = kind => {
    const holder = document.createElement('span');
    holder.className = 'header-search-suggestion-icon header-search-icon-' + kind;
    holder.setAttribute('aria-hidden', 'true');
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '1.8');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    const paths = {
      news: ['M4 4h13v16H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2', 'M17 8h3v10a2 2 0 0 1-2 2', 'M7 8h7', 'M7 12h7', 'M7 16h4'],
      bus: ['M5 17h14V7a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v10Z', 'M5 12h14', 'M7 21v-4', 'M17 21v-4', 'M8 16h1', 'M15 16h1'],
      category: ['M4 5h16v14H4z', 'M8 9h8', 'M8 13h8']
    };
    paths[kind].forEach(d => {
      const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
      path.setAttribute('d', d);
      svg.append(path);
    });
    holder.append(svg);
    return holder;
  };
  const markMatch = (element, label, query) => {
    // Use somente nós de texto: títulos devolvidos pela API nunca viram HTML.
    const start = label.toLocaleLowerCase('pt-BR').indexOf(query.toLocaleLowerCase('pt-BR'));
    if (start < 0) {element.textContent = label; return;}
    element.append(document.createTextNode(label.slice(0, start)));
    const mark = document.createElement('mark');
    mark.textContent = label.slice(start, start + query.length);
    element.append(mark, document.createTextNode(label.slice(start + query.length)));
  };
  const showMessage = message => {
    optionsList.replaceChildren();
    active = -1;
    input.removeAttribute('aria-activedescendant');
    caption.textContent = message;
    allLink.hidden = true;
    open();
  };
  // Aceitar os dois formatos oficiais da API: URLs amigáveis quando SEO está
  // ativo e /index.php?... nas instalações que ainda usam as rotas antigas.
  // Não aceitar links externos, caminhos arbitrários ou javascript:.
  const isSafeSuggestionUrl = url => {
    if (typeof url !== 'string') return false;
    if (/^\/(?:noticia|categoria)\/[a-z0-9]+(?:-[a-z0-9]+)*$/.test(url)) return true;
    if (!url.startsWith('/index.php?')) return false;
    // O prefixo fixo já garante caminho relativo local; analisar só os parâmetros
    // permite testar também em páginas sem origem HTTP (ex.: testes isolados).
    const queryString = url.slice('/index.php?'.length);
    if (!queryString || queryString.includes('#') || queryString.includes('\\')) return false;
    const params = new URLSearchParams(queryString);
    return ['noticia', 'categoria', 'onibus'].includes(params.get('p'));
  };
  const show = (rows, query) => {
    optionsList.replaceChildren();
    active = -1;
    input.removeAttribute('aria-activedescendant');
    const visible = rows.filter(item => item && typeof item.label === 'string' && typeof item.type === 'string' && typeof item.url === 'string' && isSafeSuggestionUrl(item.url)).slice(0, 6);
    if (!visible.length) {showMessage('Nenhum resultado encontrado'); return;}
    visible.forEach((item, index) => {
      const kind = item.type === 'Ônibus' ? 'bus' : item.type === 'Editoria' ? 'category' : 'news';
      const link = document.createElement('a');
      link.className = 'header-search-suggestion';
      link.id = `search-result-${index}`;
      link.href = item.url;
      link.setAttribute('role', 'option');
      link.setAttribute('aria-selected', 'false');
      const body = document.createElement('span');
      body.className = 'header-search-suggestion-body';
      const title = document.createElement('span');
      title.className = 'header-search-suggestion-title';
      markMatch(title, item.label, query);
      const type = document.createElement('small');
      type.textContent = kind === 'bus' ? 'Horários de ônibus' : item.type;
      body.append(title, type);
      const arrow = document.createElement('span');
      arrow.className = 'header-search-suggestion-arrow';
      arrow.setAttribute('aria-hidden', 'true');
      arrow.textContent = '›';
      link.append(buildIcon(kind), body, arrow);
      optionsList.append(link);
    });
    caption.textContent = `Sugestões para “${query.slice(0, 40)}” · ${visible.length}`;
    allLink.href = `/index.php?p=buscar&q=${encodeURIComponent(query)}`;
    allLink.hidden = false;
    open();
  };
  const dismiss = () => {sequence++; clearTimeout(timer); controller?.abort(); close();};
  const search = () => {
    clearTimeout(timer);
    controller?.abort();
    const id = ++sequence;
    const query = input.value.trim();
    if ([...query].length < 2 || [...query].length > 100) {close(); return;}
    timer = window.setTimeout(async () => {
      controller = new AbortController();
      input.setAttribute('aria-busy', 'true');
      showMessage('Buscando sugestões…');
      try {
        const response = await fetch(`/api_search.php?q=${encodeURIComponent(query)}`, {signal: controller.signal, credentials: 'same-origin', headers: {'Accept': 'application/json'}});
        if (!response.ok) throw new Error('search_unavailable');
        const results = await response.json();
        if (id !== sequence || input.value.trim() !== query || document.activeElement !== input) return;
        if (!Array.isArray(results)) throw new Error('invalid_response');
        show(results, query);
      } catch (error) {
        if (id === sequence && error.name !== 'AbortError' && document.activeElement === input) showMessage('Busca indisponível. Use Enter para pesquisar.');
      } finally {
        if (id === sequence) input.removeAttribute('aria-busy');
      }
    }, 300);
  };
  input.addEventListener('input', search);
  input.addEventListener('focus', () => {if ([...input.value.trim()].length >= 2 && panel.hidden) search();});
  input.addEventListener('keydown', event => {
    if (event.key === 'Escape') {dismiss(); return;}
    if (panel.hidden) return;
    const options = choices();
    if (options.length && (event.key === 'ArrowDown' || event.key === 'ArrowUp')) {
      event.preventDefault();
      highlight(event.key === 'ArrowDown' ? (active + 1) % options.length : (active - 1 + options.length) % options.length);
    } else if (event.key === 'Enter' && active >= 0 && options[active]) {
      event.preventDefault();
      window.location.assign(options[active].href);
    }
  });
  document.addEventListener('pointerdown', event => {if (!form.contains(event.target)) dismiss();});
  form.addEventListener('submit', () => {clearTimeout(timer); controller?.abort();});
  input.addEventListener('blur', () => {window.setTimeout(() => {if (!form.contains(document.activeElement)) dismiss();}, 150);});
  window.visualViewport?.addEventListener('resize', updateAvailableHeight);
  window.visualViewport?.addEventListener('scroll', updateAvailableHeight);
  window.addEventListener('resize', updateAvailableHeight);
})();
