/* Exporta a vistoria autenticada e acrescenta o diagnóstico visível, mesmo se o servidor falhar. */
(() => {
  document.addEventListener('click', async event => {
    const link = event.target.closest?.('a[data-updater-report]');
    if (!link || !window.fetch || !window.Blob || !window.URL?.createObjectURL) return;
    event.preventDefault();
    if (link.dataset.reporting === '1') return;
    link.dataset.reporting = '1';
    const local = ['=== DIAGNÓSTICO NO NAVEGADOR ===', new Date().toISOString(), 'Conexão indicada pelo navegador: ' + (navigator.onLine ? 'online' : 'offline')];
    const dialog = link.closest('dialog');
    if (dialog) {
      const selectors = ['[data-updater-zip-diagnostic]','[data-github-diagnostic]','[data-github-final]','.updater-progress'];
      for (const selector of selectors) for (const panel of dialog.querySelectorAll(selector)) {
        if (panel.hidden || panel.closest('[hidden]')) continue;
        // Somente texto de diagnóstico controlado; nunca ler inputs, cookies ou HTML de respostas.
        for (const node of panel.querySelectorAll('h3,p,small,strong,.updater-progress-label,.updater-progress-value,.updater-progress-detail')) {
          const line = node.textContent.trim(); if (line) local.push(line.slice(0,1000));
        }
      }
      const file = dialog.querySelector('input[type="file"][name="update_zip"]')?.files?.[0];
      if (file) { local.push('Tamanho do ZIP selecionado: ' + file.size + ' bytes'); }
    }
    let server = 'Relatório do servidor indisponível.';
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), 15000);
    try {
      const response = await fetch(link.href,{credentials:'same-origin',cache:'no-store',signal:controller.signal});
      if (!response.ok || !response.headers.get('content-type')?.includes('text/plain') || response.redirected) {
        local.push('Download do servidor: HTTP ' + response.status + ' (sessão ou resposta inválida)');
      } else server = await response.text();
    } catch (_) {local.push('Download do servidor: conexão interrompida ou tempo limite.');}
    finally {clearTimeout(timeout);delete link.dataset.reporting;}
    const url = URL.createObjectURL(new Blob([server,'\n\n',local.join('\n'),'\n'],{type:'text/plain;charset=utf-8'}));
    const a = document.createElement('a');a.href = url;a.download = 'IguatamaOnline-relatorio-'+new Date().toISOString().replace(/[:.]/g,'-')+'.txt';
    document.body.append(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),30000);
  });
})();
