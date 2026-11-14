// Imagens com erro devem mostrar um substituto local sem repetir requisições.
// Delegação em captura cobre elementos inseridos dinamicamente na biblioteca.
(() => {
  document.addEventListener('error', event => {
    const img = event.target;
    if (!(img instanceof HTMLImageElement) || !img.hasAttribute('data-image-fallback')) return;
    if (img.dataset.mediaSource && img.dataset.mediaTriedSource !== '1') {
      img.dataset.mediaTriedSource = '1';
      if (img.dataset.mediaSource !== img.getAttribute('src')) {
        img.src = img.dataset.mediaSource;
        return;
      }
    }
    if (img.dataset.fallbackApplied === '1') return;
    img.dataset.fallbackApplied = '1';
    img.closest('.media-tile')?.classList.add('media-image-unavailable');
    const fallback = img.getAttribute('data-image-fallback') || '/assets/img/cidade.jpg';
    if (img.getAttribute('src') !== fallback) img.src = fallback;
  }, true);
})();

(() => {
  const toggle = document.querySelector('#menuToggle');
  const menu = document.querySelector('#mainNav');
  if (toggle && menu) {
    const setOpen = open => {
      menu.classList.toggle('open', open);
      toggle.setAttribute('aria-expanded', String(open));
      toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
      toggle.textContent = open ? '✕' : '☰';
    };
    toggle.addEventListener('click', () => setOpen(!menu.classList.contains('open')));
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape' && menu.classList.contains('open')) {
        setOpen(false);
        toggle.focus();
      }
    });
    document.addEventListener('click', event => {
      if (menu.classList.contains('open') && !menu.contains(event.target) && !toggle.contains(event.target)) {
        setOpen(false);
      }
    });
  }
  // Confirmações no painel são tratadas pelo admin-ui.js, em fase de captura.
  if (!document.body.classList.contains('admin-body')) {
    document.querySelectorAll('[data-confirm]').forEach(el => {
      el.addEventListener('click', e => { if (!confirm(el.getAttribute('data-confirm'))) e.preventDefault(); });
    });
  }
})();

// Transporte: contagem regressiva local a partir da hora absoluta gerada pelo PHP.
(() => {
  const labels = document.querySelectorAll('[data-bus-time]');
  const format = target => {
    const milliseconds = new Date(target).getTime() - Date.now();
    if (!Number.isFinite(milliseconds)) return 'Horário indisponível';
    if (milliseconds < 0) return 'Saída programada encerrada';
    const mins = Math.ceil(milliseconds / 60000);
    if (mins < 1) return 'Sai em menos de 1 min';
    const days = Math.floor(mins / 1440), hrs = Math.floor((mins % 1440) / 60), rem = mins % 60;
    if (days) return `Sai em ${days} dia${days === 1 ? '' : 's'}${hrs ? ` e ${hrs} h` : ''}`;
    return hrs ? `Sai em ${hrs} h${rem ? ` e ${rem} min` : ''}` : `Sai em ${rem} min`;
  };
  if (labels.length) {
    const update = () => labels.forEach(label => label.textContent = format(label.dataset.busTime));
    update();
    window.setInterval(update, 30000);
  }
  document.querySelectorAll('[data-city-search]').forEach(field => {
    const datalist = document.getElementById(field.getAttribute('list'));
    if (!datalist) return;
    let controller, timer;
    field.addEventListener('input', () => {
      clearTimeout(timer);
      const q = field.value.trim(); if (q.length < 2) return;
      timer = setTimeout(async () => {
        controller?.abort(); controller = new AbortController();
        try {
          const resp = await fetch(`/api_cities.php?q=${encodeURIComponent(q)}`, { signal: controller.signal });
          if (!resp.ok) return;
          const cities = await resp.json(); if (!Array.isArray(cities)) return;
          datalist.replaceChildren(...cities.map(({name, uf}) => {
            const opt = document.createElement('option'); opt.value = name;
            opt.label = uf ? `${name} · ${uf}` : name;
            return opt;
          }));
        } catch (err) { if (err.name !== 'AbortError') console.debug('Catálogo de cidades indisponível'); }
      }, 280);
    });
  });
  const editor = document.querySelector('#busStopsEditor');
  const add = document.querySelector('#busAddStop');
  if (!editor || !add) return;
  const updateIndexes = () => editor.querySelectorAll('.bus-stop-edit-row').forEach((row,index) => {
    row.querySelector('.bus-stop-index').textContent = String(index+1);
    row.querySelector('.bus-remove-stop').disabled = editor.children.length<=2;
  });
  add.addEventListener('click', () => {
    if(editor.children.length>=25)return;
    const row=editor.lastElementChild.cloneNode(true);
    row.querySelectorAll('input').forEach(input => input.value='');
    editor.append(row); updateIndexes();
    row.querySelector('input[name="stop_city[]"]')?.focus();
  });
  editor.addEventListener('click', e=>{
    const remove=e.target.closest('.bus-remove-stop');
    if (remove && editor.children.length>2) { remove.closest('.bus-stop-edit-row').remove();updateIndexes(); }
  });
  updateIndexes();
})();

// Google AdSense: nenhum script externo até aceite explícito; sem rastrear a escolha no servidor.
(() => {
  const slots = [...document.querySelectorAll('[data-ad-publisher][data-ad-unit]')];
  const banner = document.querySelector('#adConsent');
  if (!slots.length && !banner) return;
  const configKey = 'iguanews_ads_choice_v1';
  const save = v => { try { window.localStorage.setItem(configKey, v); } catch (_) {} };
  const get = () => { try { return window.localStorage.getItem(configKey); } catch (_) { return null; } };
  const hide = () => { if (banner) banner.hidden = true; };
  let loaded = false;
  const accept = () => {
    hide();
    if (loaded) return;
    loaded = true;
    if (!slots.length) return;
    const publisher = slots[0].dataset.adPublisher;
    if (!/^ca-pub-\d{16}$/.test(publisher)) return;
    const script = document.createElement('script');
    script.async = true;
    script.crossOrigin = 'anonymous';
    script.src = `https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=${publisher}`;
    script.addEventListener('load', () => {
      slots.forEach(slot => {
        if (slot.dataset.adPublisher !== publisher || !/^\d{6,32}$/.test(slot.dataset.adUnit)) return;
        const ins = document.createElement('ins');
        ins.className = 'adsbygoogle';
        ins.style.display = 'block';
        ins.dataset.adClient = publisher;
        ins.dataset.adSlot = slot.dataset.adUnit;
        ins.dataset.adFormat = 'auto';
        ins.dataset.fullWidthResponsive = 'true';
        slot.querySelector('.ad-google-mount')?.appendChild(ins);
        slot.classList.add('ad-google-ready');
        try { (window.adsbygoogle = window.adsbygoogle || []).push({}); } catch (_) { /* Ad blocker / rede */ }
      });
    });
    document.head.appendChild(script);
  };
  const choice = get();
  if (choice === 'allow') accept();
  else if (choice === 'deny') hide();
  else if (banner) banner.hidden = false;
  document.querySelector('#adConsentAllow')?.addEventListener('click', () => { save('allow'); accept(); });
  document.querySelector('#adConsentDeny')?.addEventListener('click', () => { save('deny'); hide(); });
  document.querySelector('#adConsentChange')?.addEventListener('click', e => { e.preventDefault(); if (banner) banner.hidden = false; });
})();
