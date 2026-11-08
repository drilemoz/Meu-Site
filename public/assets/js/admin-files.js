'use strict';
(() => {
  const root = document.querySelector('.files-manager');
  if (!root) return;
  const rows = Array.from(root.querySelectorAll('[data-file-name]'));
  const filter = document.getElementById('files-filter');
  const filterCount = document.getElementById('files-filter-count');
  const filterEmpty = document.getElementById('files-filter-empty');
  const toggle = document.getElementById('files-select-toggle');
  const checkboxes = Array.from(root.querySelectorAll('.files-select'));
  const all = document.getElementById('files-select-all');
  const counter = document.getElementById('files-selected-count');
  const buttons = Array.from(root.querySelectorAll('[data-files-needs-selection]'));
  const selected = () => checkboxes.filter(input => input.checked && !input.closest('.files-row')?.hidden);
  const visible = () => checkboxes.filter(input => !input.closest('.files-row')?.hidden);
  let selecting = false;

  const sync = () => {
    const count = selected().length;
    if (counter) counter.textContent = `${count} selecionado${count === 1 ? '' : 's'}`;
    buttons.forEach(button => { button.disabled = count === 0 || button.hasAttribute('data-files-unavailable'); });
    if (all) {
      const eligible = visible();
      all.checked = eligible.length > 0 && eligible.every(input => input.checked);
      all.indeterminate = count > 0 && !all.checked;
    }
  };

  const changeMode = (next, clear = false) => {
    selecting = next;
    if (clear) checkboxes.forEach(input => { input.checked = false; });
    root.classList.toggle('files-selecting', selecting);
    if (toggle) {
      toggle.setAttribute('aria-expanded', String(selecting));
      toggle.setAttribute('aria-pressed', String(selecting));
      toggle.textContent = selecting ? '✕ Cancelar seleção' : '☑ Selecionar';
    }
    sync();
  };

  toggle?.addEventListener('click', () => changeMode(!selecting, selecting));
  filter?.addEventListener('input', () => {
    const term = filter.value.toLocaleLowerCase('pt-BR').trim();
    let matches = 0;
    rows.forEach(row => {
      row.hidden = term !== '' && !(row.dataset.fileName || '').toLocaleLowerCase('pt-BR').includes(term);
      if (row.hidden) row.querySelectorAll('.files-select').forEach(input => { input.checked = false; });
      else matches++;
    });
    if (filterCount) filterCount.textContent = `${matches} de ${rows.length} itens nesta página`;
    if (filterEmpty) filterEmpty.hidden = matches !== 0;
    sync();
  });

  all?.addEventListener('change', () => {
    const eligible = visible();
    const checked = all.checked;
    eligible.forEach((input, i) => { input.checked = checked && i < 20; });
    if (checked && eligible.length > 20) alert('Foram selecionados os primeiros 20 itens. Divida operações grandes em lotes.');
    sync();
  });
  checkboxes.forEach(input => input.addEventListener('change', () => {
    if (selected().length > 20) { input.checked = false; alert('Selecione até 20 itens por operação.'); }
    sync();
  }));
  document.getElementById('files-bulk')?.addEventListener('submit', e => {
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
  // Progressive enhancement: sem JavaScript, os checkboxes e os comandos continuam visíveis.
  root.classList.add('files-enhanced');
  changeMode(false);
})();
