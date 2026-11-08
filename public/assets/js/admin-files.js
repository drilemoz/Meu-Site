'use strict';
(() => {
  const rows = Array.from(document.querySelectorAll('[data-file-name]'));
  const filter = document.getElementById('files-filter');
  if (filter) filter.addEventListener('input', () => {
    const term = filter.value.toLocaleLowerCase('pt-BR').trim();
    rows.forEach(row => {
      row.hidden = term !== '' && !(row.dataset.fileName || '').toLocaleLowerCase('pt-BR').includes(term);
      if (row.hidden) row.querySelectorAll('.files-select').forEach(input => { input.checked = false; });
    });
    sync();
  });
  const selected = () => Array.from(document.querySelectorAll('.files-select:checked')).filter(input => !input.closest('.files-row')?.hidden);
  const all = document.getElementById('files-select-all');
  const counter = document.getElementById('files-selected-count');
  const buttons = Array.from(document.querySelectorAll('[data-files-needs-selection]'));
  const sync = () => {
    const count = selected().length;
    if (counter) counter.textContent = `${count} selecionado${count === 1 ? '' : 's'}`;
    buttons.forEach(button => { button.disabled = count < 1; });
    const visible = Array.from(document.querySelectorAll('.files-select')).filter(input => !input.closest('.files-row')?.hidden);
    if (all) { all.checked = visible.length > 0 && visible.every(input => input.checked); all.indeterminate = count > 0 && !all.checked; }
  };
  all?.addEventListener('change', () => {
    const visible = Array.from(document.querySelectorAll('.files-select')).filter(input => !input.closest('.files-row')?.hidden);
    const check = all.checked;
    visible.forEach((input, i) => { input.checked = check && i < 20; });
    if (check && visible.length > 20) alert('Foram selecionados os primeiros 20 itens. Divida operações grandes em lotes.');
    sync();
  });
  document.querySelectorAll('.files-select').forEach(input => input.addEventListener('change', () => {
    if (selected().length > 20) { input.checked = false; alert('Selecione até 20 itens por operação.'); }
    sync();
  }));
  const bulk = document.getElementById('files-bulk');
  bulk?.addEventListener('submit', e => {
    if (!selected().length) { e.preventDefault(); alert('Selecione pelo menos um item nesta página.'); }
  });
  const deleteForm = document.getElementById('files-bulk-delete');
  deleteForm?.addEventListener('submit', e => {
    deleteForm.querySelectorAll('input[name="selected[]"]').forEach(input => input.remove());
    const items = selected();
    if (!items.length) { e.preventDefault(); alert('Selecione pelo menos um item antes de excluir.'); return; }
    items.forEach(item => {
      const input = document.createElement('input');
      input.type = 'hidden'; input.name = 'selected[]'; input.value = item.value;
      deleteForm.append(input);
    });
  }, true);
  sync();
})();
