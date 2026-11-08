'use strict';
(() => {
  const filter = document.getElementById('files-filter');
  if (!filter) return;
  filter.addEventListener('input', () => {
    const term = filter.value.toLocaleLowerCase('pt-BR').trim();
    document.querySelectorAll('[data-file-name]').forEach(row => {
      row.hidden = term !== '' && !(row.dataset.fileName || '').toLocaleLowerCase('pt-BR').includes(term);
    });
  });
})();
