/* 1.0.67 — Progresso fiel do upload; processamento PHP sem percentual inventado. */
(() => {
  const operations = new Set(['upload', 'preview', 'install_remote']);
  const copy = {
    upload: 'Enviando pacote', preview: 'Enviando ZIP para simulação',
    install_remote: 'Solicitando pacote ao servidor'
  };
  function progressUI(form) {
    let ui = form.querySelector('.updater-progress');
    if (ui) return ui;
    ui = document.createElement('div');
    ui.className = 'updater-progress';
    ui.setAttribute('role', 'status');
    ui.setAttribute('aria-live', 'polite');
    ui.innerHTML = '<div class="updater-progress-head"><strong class="updater-progress-label"></strong><span class="updater-progress-value"></span></div>' +
      '<progress max="100" aria-label="Progresso do envio"></progress>' +
      '<small class="updater-progress-detail"></small>' +
      '<button type="button" class="btn outline small updater-progress-result" hidden>Ver resultado</button>';
    form.append(ui);
    return ui;
  }
  function update(ui, label, value, detail, completed) {
    ui.hidden = false;
    ui.querySelector('.updater-progress-label').textContent = label;
    ui.querySelector('.updater-progress-value').textContent = value;
    ui.querySelector('.updater-progress-detail').textContent = detail;
    const bar = ui.querySelector('progress');
    if (completed === null) bar.removeAttribute('value');
    else bar.value = completed;
  }
  document.querySelectorAll('form[data-updater-progress-form]').forEach(form => {
    const op = form.querySelector('input[name="operation"]')?.value;
    if (!operations.has(op)) return;
    form.addEventListener('submit', event => {
      if (form.dataset.updaterSending === '1') { event.preventDefault(); return; }
      if (!form.checkValidity()) return;
      if (!window.XMLHttpRequest || !window.FormData) return; // fallback: envio comum
      event.preventDefault();
      const file = form.querySelector('input[type="file"][name="update_zip"]')?.files?.[0];
      const isUpload = !!file;
      const ui = progressUI(form);
      const bar = ui.querySelector('progress');
      const valueLabel = ui.querySelector('.updater-progress-value');
      const resultButton = ui.querySelector('.updater-progress-result');
      resultButton.hidden = true;
      form.dataset.updaterSending = '1';
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.disabled = true;
      update(ui, copy[op], isUpload ? '0%' : 'Aguarde',
        isUpload ? 'Transferência do navegador para a hospedagem.' : 'O servidor consulta e baixa o pacote do GitHub.',
        isUpload ? 0 : null);
      const xhr = new XMLHttpRequest();
      xhr.open('POST', form.action, true);
      xhr.withCredentials = true;
      xhr.timeout = 180000;
      if (isUpload) xhr.upload.addEventListener('progress', progress => {
        if (!progress.lengthComputable) {
          update(ui, copy[op], 'Enviando…', 'O navegador não informou o tamanho total do envio.', null);
          return;
        }
        const pct = Math.max(0, Math.min(100, Math.floor((progress.loaded / progress.total) * 100)));
        update(ui, copy[op], pct + '%', 'Enviado: ' + (progress.loaded / 1048576).toFixed(1) + ' de ' + (progress.total / 1048576).toFixed(1) + ' MiB.', pct);
      });
      xhr.upload.addEventListener('load', () => {
        update(ui, op === 'preview' ? 'Validando ZIP no servidor' : 'Validando e instalando no servidor',
          'Processando…', 'Upload concluído. A hospedagem ainda está processando; não feche esta página.', null);
      });
      function allowAgain() {
        delete form.dataset.updaterSending;
        if (submit) submit.disabled = false;
      }
      function offerResult() {
        resultButton.hidden = false;
        resultButton.onclick = () => { window.location.href = form.action; };
      }
      xhr.onload = () => {
        allowAgain();
        const html = xhr.responseText || '';
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const error = doc.querySelector('.admin-main > .alert.error');
        const success = doc.querySelector('.admin-main > .alert.success, .admin-main > .alert.ok, .admin-main > .alert.info');
        // redirects e erros podem ser HTML completo de outra tela; nunca presumir instalação.
        if (xhr.status >= 200 && xhr.status < 300 && !error && success) {
          update(ui, 'Resposta do servidor', 'Finalizado', success.textContent.trim(), 100);
          offerResult();
          window.location.href = form.action; // atualiza a versão no cabeçalho e exibe mensagem oficial
        } else if (error) {
          update(ui, 'Operação não concluída', 'Erro', error.textContent.trim(), 0);
          offerResult();
        } else {
          update(ui, 'Resposta precisa de verificação', String(xhr.status || '—'),
            'Não foi possível confirmar o resultado automaticamente. Consulte a página de atualizações antes de tentar novamente.', null);
          offerResult();
        }
      };
      xhr.onerror = () => { allowAgain(); update(ui, 'Conexão interrompida', 'Verificar', 'A instalação pode continuar no servidor. Confira o resultado antes de reenviar.', null); offerResult(); };
      xhr.ontimeout = () => { allowAgain(); update(ui, 'Tempo de resposta excedido', 'Verificar', 'A hospedagem pode continuar processando. Confira o histórico antes de reenviar.', null); offerResult(); };
      xhr.onabort = () => { allowAgain(); update(ui, 'Envio interrompido', 'Verificar', 'Confira o estado do sistema antes de reenviar.', null); offerResult(); };
      // Montar FormData antes de bloquear os controles; mantém CSRF, senha e consentimento.
      const data = new FormData(form);
      xhr.send(data);
    });
  });
})();
