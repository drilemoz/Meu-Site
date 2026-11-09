/* Importa backups de ate 15 MiB sem exigir alteracao do php.ini quando upload em blocos e possivel. */
(() => {
  'use strict';
  const field = document.querySelector('[data-backup-import]');
  if (!field) return;
  const form = field.form;
  const hint = document.getElementById('backup-upload-hint');
  const single = Number(field.dataset.serverLimit) || 0;
  const chunkSize = Number(field.dataset.chunkSize) || 0;
  const logicalLimit = 15 * 1024 * 1024;
  let busy = false;
  const format = bytes => (bytes / (1024 * 1024)).toFixed(2).replace('.', ',') + ' MiB';
  const validate = () => {
    const file = field.files && field.files[0];
    let message = '';
    if (file && (file.size > logicalLimit || file.size < 1)) message = 'O backup precisa ter entre 1 byte e 15 MiB.';
    else if (file && file.size > single && !chunkSize) message = 'O servidor não permite receber este arquivo. Verifique os limites de upload do PHP.';
    field.setCustomValidity(message);
    if (hint) hint.textContent = message || (file
      ? 'Backup selecionado: ' + format(file.size) + (file.size > single ? '. Envio automático em partes menores.' : '. Envio direto disponível.')
      : 'Máximo: 15 MiB. ' + (chunkSize ? 'Envio em partes disponível automaticamente.' : 'Limite direto: ' + format(single) + '.'));
    return !message;
  };
  field.addEventListener('change', validate);
  form?.addEventListener('submit', async event => {
    if (busy) { event.preventDefault(); return; }
    if (!validate()) { event.preventDefault(); field.reportValidity(); return; }
    const file = field.files && field.files[0];
    if (!file || file.size <= single || !chunkSize) return; // envio HTML tradicional
    event.preventDefault();
    busy = true;
    const button = form.querySelector('button[type="submit"]');
    if (button) button.disabled = true;
    const csrf = form.querySelector('[name="csrf"]')?.value || '';
    try {
      let offset = 0;
      let token = '';
      while (offset < file.size) {
        const end = Math.min(offset + chunkSize, file.size);
        const part = new FormData();
        part.append('action', 'backup_chunk');
        part.append('csrf', csrf);
        part.append('backup_name', file.name);
        part.append('upload_total', String(file.size));
        part.append('upload_offset', String(offset));
        part.append('upload_token', token);
        part.append('chunk', file.slice(offset, end), 'parte.bin');
        const response = await fetch('/setup.php?step=4', {method:'POST', body:part, credentials:'same-origin', cache:'no-store'});
        const body = await response.json().catch(() => null);
        if (!response.ok || !body?.ok || typeof body.upload_token !== 'string' || body.next_offset !== end) {
          throw new Error(body?.error || 'Servidor rejeitou uma das partes do backup.');
        }
        token = body.upload_token;
        offset = body.next_offset;
        if (hint) hint.textContent = 'Enviando backup em partes: ' + Math.floor(100 * offset / file.size) + '% (' + format(offset) + ' de ' + format(file.size) + ').';
      }
      const hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = 'upload_token';
      hidden.value = token;
      form.appendChild(hidden);
      field.disabled = true; // o arquivo já está em staging; não reenviar no POST final
      if (hint) hint.textContent = 'Upload concluído. Validando e restaurando o backup…';
      form.submit();
    } catch (err) {
      if (hint) hint.textContent = 'Envio interrompido: ' + (err?.message || 'tente novamente.');
      busy = false;
      if (button) button.disabled = false;
    }
  });
})();
