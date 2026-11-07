/* 1.0.75 — Modal de instalação, confirmação explícita e progresso verdadeiro do upload. */
(() => {
  const operations = new Set(['upload', 'preview', 'install_remote']);
  const copy = {
    upload: 'Enviando atualização', preview: 'Enviando ZIP para simulação',
    install_remote: 'Solicitando pacote ao servidor'
  };
  function progressUI(form) {
    let ui = form.querySelector('.updater-progress');
    if (ui) return ui;
    ui = document.createElement('div');
    ui.className = 'updater-progress';
    ui.setAttribute('role', 'status');
    ui.setAttribute('aria-live', 'polite');
    ui.setAttribute('tabindex', '-1');
    ui.innerHTML = '<div class="updater-progress-head"><strong class="updater-progress-label"></strong><span class="updater-progress-value"></span></div>' +
      '<progress max="100" aria-label="Progresso do envio"></progress>' +
      '<small class="updater-progress-detail"></small>' +
      '<div class="updater-progress-buttons"><button type="button" class="btn outline small updater-progress-edit" hidden>Rever dados</button>' +
      '<button type="button" class="btn primary small updater-progress-result" hidden>Ver resultado</button></div>';
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
    const manual = form.dataset.updaterConfirmFlow === 'manual';
    const fields = manual ? form.querySelector('[data-updater-fields]') : null;
    const confirmStep = manual ? form.querySelector('[data-updater-confirm-step]') : null;
    const ack = manual ? form.querySelector('[data-updater-ack]') : null;
    const uploadDialog = manual ? form.closest('dialog') : null;
    const fileInput = form.querySelector('input[type="file"][name="update_zip"]');
    function leaveBusy() {
      delete form.dataset.updaterSending;
      if (manual && uploadDialog) {
        delete uploadDialog.dataset.updaterBusy;
        uploadDialog.setAttribute('aria-busy', 'false');
      }
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.disabled = false;
    }
    function restoreFields() {
      leaveBusy();
      if (ack) { ack.checked = false; ack.disabled = true; }
      if (fields) fields.hidden = false;
      if (confirmStep) confirmStep.hidden = true;
      const ui = form.querySelector('.updater-progress');
      if (ui) ui.hidden = true;
      fileInput?.focus?.();
    }
    function showConfirmation() {
      if (!form.checkValidity()) { form.reportValidity?.(); return; }
      const selected = fileInput?.files?.[0];
      if (!selected) { fileInput?.reportValidity?.(); return; }
      const name = form.querySelector('[data-updater-filename]');
      if (name) name.textContent = selected.name || 'Arquivo ZIP selecionado';
      if (ack) { ack.disabled = false; ack.checked = false; }
      if (fields) fields.hidden = true;
      if (confirmStep) confirmStep.hidden = false;
      form.querySelector('[data-updater-accept]')?.focus?.();
    }
    function beginTransmission() {
      if (form.dataset.updaterSending === '1') return;
      if (!window.XMLHttpRequest || !window.FormData) { form.submit(); return; } // fallback HTML com confirmação
      const file = fileInput?.files?.[0];
      const isUpload = !!file;
      const ui = progressUI(form);
      const resultButton = ui.querySelector('.updater-progress-result');
      const editButton = ui.querySelector('.updater-progress-edit');
      resultButton.hidden = true;
      if (editButton) editButton.hidden = true;
      form.dataset.updaterSending = '1';
      if (manual) {
        if (fields) fields.hidden = true;
        if (confirmStep) confirmStep.hidden = true;
        if (uploadDialog) {
          uploadDialog.dataset.updaterBusy = '1';
          uploadDialog.setAttribute('aria-busy', 'true');
        }
      }
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.disabled = true;
      update(ui, copy[op], isUpload ? '0%' : 'Aguarde',
        isUpload ? 'Transferindo o ZIP para a hospedagem.' : 'O servidor consulta e baixa o pacote do GitHub.',
        isUpload ? 0 : null);
      ui.focus?.();
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
      function offerResult() {
        resultButton.hidden = false;
        resultButton.onclick = () => { window.location.href = form.action; };
      }
      function offerRevision() {
        if (manual && editButton) {
          editButton.hidden = false;
          editButton.onclick = restoreFields;
        }
      }
      xhr.onload = () => {
        leaveBusy();
        const html = xhr.responseText || '';
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const error = doc.querySelector('.admin-main > .alert.error');
        const success = doc.querySelector('.admin-main > .alert.success, .admin-main > .alert.ok, .admin-main > .alert.info');
        // Sucesso só é comprovado por resposta do PHP, nunca pelo evento 100% do upload.
        const successText = success?.textContent?.trim() || '';
        const validSuccess = success && (!manual || /Portal atualizado para/i.test(successText));
        if (xhr.status >= 200 && xhr.status < 300 && !error && validSuccess) {
          update(ui, 'Instalação concluída', 'Concluído', successText, 100);
          offerResult();
          if (!manual) window.location.href = form.action; // comportamento original de operações sem modal
        } else if (error) {
          update(ui, 'Operação não concluída', 'Erro', error.textContent.trim(), 0);
          offerRevision();
          offerResult();
        } else {
          update(ui, 'Resposta precisa de verificação', String(xhr.status || '—'),
            'Não foi possível confirmar o resultado automaticamente. Consulte a página de atualizações antes de tentar novamente.', null);
          offerResult();
        }
      };
      xhr.onerror = () => { leaveBusy(); update(ui, 'Conexão interrompida', 'Verificar', 'A instalação pode continuar no servidor. Confira o resultado antes de reenviar.', null); offerResult(); };
      xhr.ontimeout = () => { leaveBusy(); update(ui, 'Tempo de resposta excedido', 'Verificar', 'A hospedagem pode continuar processando. Confira o histórico antes de reenviar.', null); offerResult(); };
      xhr.onabort = () => { leaveBusy(); update(ui, 'Envio interrompido', 'Verificar', 'Confira o estado do sistema antes de reenviar.', null); offerResult(); };
      // Captura todos os campos antes de alterar botões: CSRF, senha e confirmação são obrigatórios no servidor.
      const data = new FormData(form);
      xhr.send(data);
    }
    if (manual) {
      form.querySelector('[data-updater-back]')?.addEventListener('click', restoreFields);
      form.querySelector('[data-updater-accept]')?.addEventListener('click', () => {
        if (!ack?.checked) { ack?.reportValidity?.(); return; }
        beginTransmission();
      });
    }
    form.addEventListener('submit', event => {
      if (form.dataset.updaterSending === '1') { event.preventDefault(); return; }
      if (!form.checkValidity()) return;
      if (manual) {
        event.preventDefault();
        showConfirmation();
        return;
      }
      if (!window.XMLHttpRequest || !window.FormData) return;
      event.preventDefault();
      beginTransmission();
    });
  });
})();
