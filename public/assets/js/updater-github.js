/* 1.0.92 — consulta sob demanda e instalação GitHub em modal com etapas confirmadas pelo PHP. */
(() => {
  const dialog = document.getElementById('updater-github-dialog');
  const trigger = document.getElementById('updater-github-trigger');
  if (!dialog || !trigger) return;
  const get = (selector) => dialog.querySelector(selector);
  const status = get('#updater-github-status');
  const label = get('[data-github-label]');
  const message = get('[data-github-message]');
  const releaseArea = get('[data-github-release]');
  const version = get('[data-github-version]');
  const notes = get('[data-github-notes]');
  const form = get('#updater-github-form');
  const process = get('[data-github-process]');
  const stage = get('[data-github-stage]');
  const detail = get('[data-github-detail]');
  const progress = get('[data-github-progress]');
  const final = get('[data-github-final]');
  const finalTitle = get('[data-github-final-title]');
  const finalMessage = get('[data-github-final-message]');
  const refresh = get('[data-github-refresh]');
  const retry = get('[data-github-retry]');
  const diagnostic = get('[data-github-diagnostic]');
  const diagnosticTitle = get('[data-github-diagnostic-title]');
  const diagnosticDetail = get('[data-github-diagnostic-detail]');
  const diagnosticAction = get('[data-github-diagnostic-action]');
  const diagnosticCode = get('[data-github-diagnostic-code]');
  function showDiagnostic(info) {
    if (!diagnostic) return;
    if (!info) { diagnostic.hidden = true; return; }
    diagnostic.hidden = false;
    diagnosticTitle.textContent = info.title || 'Diagnóstico disponível';
    diagnosticDetail.textContent = info.detail || 'Não foi possível determinar a causa.';
    diagnosticAction.textContent = info.action || 'Confira a vistoria do servidor.';
    diagnosticCode.textContent = 'Código: ' + (info.code || 'unknown');
  }
  const actions = get('.updater-github-actions');
  const password = form.querySelector('input[name="confirm_password"]');
  const tokenInput = form.querySelector('input[name="update_token"]');
  const submit = form.querySelector('button[type="submit"]');
  const csrf = form.querySelector('input[name="csrf_token"]'); // preservado pelo próprio form
  let installedVersion = '';
  let targetVersion = '';
  let busy = false;
  let generation = 0;
  let pollTimer = null;
  let previousStage = '';
  let queuedVps = false;
  const messages = {
    connecting: 'Conectando ao GitHub',
    download: 'Baixando atualização',
    validating: 'Validando pacote',
    preflight: 'Verificando compatibilidade',
    backup: 'Criando backup',
    applying: 'Atualizando arquivos',
    complete: 'Instalação confirmada',
    failed: 'Falha registrada no servidor'
  };
  function showStatus(title, description, state = '') {
    status.hidden = false;
    status.dataset.state = state;
    label.textContent = title;
    message.textContent = description;
  }
  function resetView() {
    releaseArea.hidden = true;
    process.hidden = true;
    final.hidden = true;
    actions.hidden = false;
    retry.hidden = true;
    showDiagnostic(null);
    previousStage = '';
    targetVersion = '';
    password.value = '';
    form.querySelector('input[name="confirm_install"]').checked = false;
    submit.disabled = false;
  }
  async function consult() {
    if (busy) return;
    const seq = ++generation;
    resetView();
    showStatus('Conectando…', 'Consultando a versão mais recente e as novidades no GitHub.');
    try {
      const response = await fetch('/admin.php?action=updates&updater_info=1', {
        credentials: 'same-origin', cache: 'no-store', headers: {'Accept': 'application/json'}
      });
      const type = response.headers?.get?.('content-type') || '';
      if (type && !type.includes('application/json')) throw new Error('O servidor retornou uma página em vez de JSON. Confira a sessão administrativa e o endpoint.');
      const info = await response.json();
      if (seq !== generation) return;
      if (!response.ok || !info.ok) {
        showDiagnostic(info.diagnostic || {code: 'http_'+response.status,
          title: 'Falha de resposta do servidor', detail: 'O painel recebeu HTTP '+response.status+'.',
          action: 'Confira a Central de Saúde e os registros do servidor antes de tentar novamente.'});
        showStatus('Não foi possível consultar', info.message || 'Consulta indisponível.', 'error');
        retry.hidden = false;
        return;
      }
      installedVersion = info.installed || '';
      if (!info.configured) {
        showStatus('Origem não configurada', 'Configure o repositório nas opções avançadas.', 'error');
        showDiagnostic({code:'source_missing',title:'Origem não configurada',detail:'A instalação não tem repositório definido.',action:'Abra Configurações e diagnóstico > Configurar origem.'});
        return;
      }
      const remote = info.release;
      if (!remote) {
        showStatus('Nenhuma atualização publicada', 'O repositório não informou uma release disponível.', 'done');
        showDiagnostic({code:'release_missing',title:'Release não encontrada',detail:'Não foi localizada uma release pública marcada como versão mais recente.',action:'Verifique a aba Releases do repositório oficial.'});
        return;
      }
      if (!remote.newer) {
        showStatus('Sistema atualizado', 'Versão instalada: ' + installedVersion + '. Última versão publicada: ' + remote.version + '.', 'done');
        return;
      }
      targetVersion = remote.version;
      version.textContent = remote.version;
      notes.textContent = (remote.notes || '').trim() || 'Sem notas de versão fornecidas pelo repositório.';
      releaseArea.hidden = false;
      if (!remote.downloadable) {
        form.hidden = true;
        showStatus('Versão encontrada: ' + remote.version, 'Ainda não há um ZIP compatível na release. A instalação pelo GitHub está indisponível.', 'error');
        showDiagnostic({code:'asset_missing',title:'ZIP de atualização não encontrado',detail:'A release foi encontrada, mas não contém o nome esperado do pacote.',action:'Publique Atualizacao-v'+remote.version+'.zip como asset da release.'});
        return;
      }
      form.hidden = false;
      showStatus('Atualização disponível: ' + remote.version, 'Confira as novidades, confirme a origem e informe sua senha para instalar.', 'done');
    } catch (error) {
      if (seq !== generation) return;
      showStatus('Não foi possível consultar', error && error.message ? error.message : 'Verifique a conexão com o GitHub.', 'error');
      showDiagnostic({code:'client_connection',title:'Falha ao consultar o painel',detail:'O navegador não conseguiu obter um diagnóstico estruturado do servidor.',action:'Confira login, conexão, console do navegador e logs privados. A consulta externa é feita pela hospedagem, não pelo celular.'});
      retry.hidden = false;
    }
  }
  function stopPolling() {
    if (pollTimer !== null) { clearInterval(pollTimer); pollTimer = null; }
  }
  async function poll(token) {
    if (!busy) return;
    try {
      const url = '/admin.php?action=updates&updater_status=' + encodeURIComponent(token);
      const response = await fetch(url, {credentials:'same-origin',cache:'no-store',headers:{'Accept':'application/json'}});
      if (!response.ok || !busy) return;
      const data = await response.json();
      if (!busy || !data.ok || !data.progress || !Object.hasOwn(messages, data.progress.stage)) return;
      if (queuedVps && data.progress.stage === 'complete') {
        showFinal(true, 'Atualização concluída!', data.progress.message || 'Deploy confirmado pelo serviço externo.');
        return;
      }
      if (queuedVps && data.progress.stage === 'failed') {
        showFinal(false, 'Atualização não concluída', data.progress.message || 'Confira o journal do serviço e o histórico.');
        return;
      }
      if (data.progress.stage === previousStage) return;
      previousStage = data.progress.stage;
      stage.textContent = messages[previousStage];
      detail.textContent = data.progress.message || 'Etapa registrada pelo servidor.';
    } catch (_) {
      // No modo manutenção, o endpoint pode ficar temporariamente indisponível.
      // Conservar a última etapa confirmada, nunca inventar um percentual.
    }
  }
  function showFinal(ok, title, description) {
    stopPolling();
    busy = false;
    delete dialog.dataset.updaterBusy;
    dialog.setAttribute('aria-busy', 'false');
    process.hidden = true;
    releaseArea.hidden = true;
    status.hidden = true;
    actions.hidden = false;
    final.hidden = false;
    final.dataset.result = ok ? 'ok' : 'error';
    finalTitle.textContent = title;
    finalMessage.textContent = description;
    refresh.textContent = ok ? 'Atualizar página' : 'Conferir versão e histórico';
  }
  function install(event) {
    if (busy) {event.preventDefault();return;}
    if (!form.checkValidity()) return;
    if (!window.XMLHttpRequest || !window.FormData) return; // fallback HTML sem inventar progresso
    event.preventDefault();
    if (!/^[a-f0-9]{32}$/.test(tokenInput.value)) {
      showStatus('Token inválido', 'Atualize a página antes de tentar a instalação.', 'error');
      return;
    }
    busy = true;
    queuedVps = false;
    dialog.dataset.updaterBusy = '1';
    dialog.setAttribute('aria-busy','true');
    status.hidden = true;
    releaseArea.hidden = true;
    actions.hidden = true;
    final.hidden = true;
    process.hidden = false;
    progress.removeAttribute('value');
    stage.textContent = 'Conectando ao GitHub';
    detail.textContent = 'Autorização enviada ao servidor. Aguardando as primeiras etapas confirmadas.';
    const xhr = new XMLHttpRequest();
    xhr.open('POST', form.action, true);
    xhr.withCredentials = true;
    xhr.timeout = 180000;
    xhr.onload = () => {
      const doc = new DOMParser().parseFromString(xhr.responseText || '', 'text/html');
      const error = doc.querySelector('.admin-main > .alert.error');
      const success = doc.querySelector('.admin-main > .alert.success, .admin-main > .alert.ok');
      const text = success?.textContent?.trim() || '';
      if (xhr.status >= 200 && xhr.status < 300 && !error && text.includes('Solicitação de implantação registrada.')) {
        queuedVps = true;
        stage.textContent = 'Aguardando serviço externo';
        detail.textContent = 'Solicitação confirmada. O deploy continua fora do PHP-FPM; aguarde a conclusão e não reenvie.';
      } else if (xhr.status >= 200 && xhr.status < 300 && !error && text.includes('Portal atualizado para ' + targetVersion + '.')) {
        showFinal(true, 'Atualização concluída!', 'Portal atualizado para ' + targetVersion + '. O servidor confirmou a instalação e o backup.');
      } else if (error) {
        showFinal(false, 'Atualização não concluída', error.textContent.trim());
      } else {
        showFinal(false, 'Resultado não confirmado', 'Não foi possível confirmar o resultado da instalação. Consulte a versão e o histórico antes de tentar novamente.');
      }
    };
    xhr.onerror = () => showFinal(false, 'Conexão interrompida', 'O servidor ainda pode estar atualizando. Confira o histórico antes de tentar novamente.');
    xhr.ontimeout = () => showFinal(false, 'Tempo de resposta excedido', 'A instalação pode continuar no servidor. Confira a versão e o histórico antes de reenviar.');
    xhr.onabort = () => showFinal(false, 'Solicitação interrompida', 'Confira o estado do sistema antes de tentar novamente.');
    poll(tokenInput.value);
    pollTimer = setInterval(() => poll(tokenInput.value), 1400);
    // O POST só é criado após ação explícita do usuário, com CSRF, senha e confirmação.
    xhr.send(new FormData(form));
  }
  trigger.addEventListener('click', consult);
  retry.addEventListener('click', consult);
  form.addEventListener('submit', install);
  refresh.addEventListener('click', () => { window.location.href = form.action; });
})();
