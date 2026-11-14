'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

function node(attrs = {}) {
  const events = {};
  const attributes = new Set(Object.keys(attrs));
  return {
    hidden: false, checked: false, disabled: false, value: '', textContent: '',
    dataset: {}, attrs: {},
    addEventListener(type, callback) { events[type] = callback; },
    dispatch(type, event = {}) { if (events[type]) events[type](event); },
    hasAttribute(name) { return attributes.has(name); },
    setAttribute(name, value) { this.attrs[name] = value; },
    closest() { return this.row || null; },
    querySelectorAll() { return []; },
    classList: {
      toggle() {},
      add() {},
    },
  };
}

const rowA = node(); rowA.dataset.fileName = 'app';
const rowB = node(); rowB.dataset.fileName = 'public';
const checkA = node(); checkA.row = rowA;
const checkB = node(); checkB.row = rowB;
rowA.querySelectorAll = () => [checkA];
rowB.querySelectorAll = () => [checkB];
const root = node();
const classes = new Set();
root.classList = { add: x => classes.add(x), toggle: (x, yes) => yes ? classes.add(x) : classes.delete(x) };
root.querySelectorAll = selector => {
  if (selector === '[data-file-name]') return [rowA, rowB];
  if (selector === '.files-select') return [checkA, checkB];
  if (selector === '[data-files-needs-selection]') return [copy, zip];
  return [];
};
const filter = node();
const filterCount = node();
const filterEmpty = node();
const toggle = node();
const selectAll = node();
const count = node();
const copy = node();
const zip = node({'data-files-unavailable': true});
const bulk = node();
const byId = {
  'files-filter': filter,
  'files-filter-count': filterCount,
  'files-filter-empty': filterEmpty,
  'files-select-toggle': toggle,
  'files-select-all': selectAll,
  'files-selected-count': count,
  'files-bulk': bulk,
};
const document = {querySelector: s => s === '.files-manager' ? root : null,
  getElementById: id => byId[id] || null};
const source = fs.readFileSync(require('node:path').join(__dirname, '../public/assets/js/admin-files.js'), 'utf8');
vm.runInNewContext(source, {document, alert() {}, String});
assert.equal(classes.has('files-enhanced'), true, 'modo progressivo ativado');
assert.equal(classes.has('files-selecting'), false, 'seleção escondida inicialmente');
assert.equal(copy.disabled, true, 'comando não permite ação sem seleção');

toggle.dispatch('click');
assert.equal(classes.has('files-selecting'), true, 'seleção abre com o botão');
checkA.checked = true; checkA.dispatch('change');
assert.equal(copy.disabled, false, 'comando de cópia habilita após seleção');
assert.equal(zip.disabled, true, 'ZIP não deve reativar sem ZipArchive');
assert.equal(count.textContent, '1 selecionado');
filter.value = 'public'; filter.dispatch('input');
assert.equal(rowA.hidden, true);
assert.equal(checkA.checked, false, 'busca limpa seleção oculta');
assert.equal(filterCount.textContent, '1 de 2 itens nesta página');
assert.equal(copy.disabled, true);
filter.value = 'não-existe'; filter.dispatch('input');
assert.equal(filterEmpty.hidden, false, 'estado vazio exibido quando filtro não retorna nada');
filter.value = ''; filter.dispatch('input');
checkB.checked = true; checkB.dispatch('change');
toggle.dispatch('click');
assert.equal(classes.has('files-selecting'), false, 'modo seleção fecha');
assert.equal(checkB.checked, false, 'cancelar limpa a seleção');
console.log('OK file_manager_225.js: seleção móvel, filtro e ZIP indisponível');
