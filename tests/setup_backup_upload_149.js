const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const source = fs.readFileSync(__dirname + '/../public/assets/js/setup-backup-upload.js','utf8');

function simulate(phpLimitBytes, fileSize) {
  const callbacks = {};
  const field = {
    dataset: { serverLimit: String(phpLimitBytes) },
    files: [{ size: fileSize }],
    setCustomValidity(message) { this.message = message; },
    reportValidity() { this.shown = true; },
    addEventListener(type, cb) { callbacks[type] = cb; },
    form: { addEventListener(type, cb) { callbacks[`form_${type}`] = cb; } },
  };
  const hint = { textContent: '' };
  const document = {
    querySelector(selector) { return selector === '[data-backup-import]' ? field : null; },
    getElementById(id) { return id === 'backup-upload-hint' ? hint : null; },
  };
  vm.runInNewContext(source, { document });
  let prevented = false;
  callbacks.form_submit({ preventDefault() { prevented = true; } });
  return { field, hint, prevented };
}
const M = 1024 * 1024;
assert.equal(simulate(2*M, M).prevented,false,'backup pequeno continua importavel em PHP 2M/8M');
assert.equal(simulate(2*M, 3*M).prevented,true,'backup de 3M bloqueado antes de POST de 2M');
assert.match(simulate(2*M, 3*M).field.message,/servidor não permite/);
assert.equal(simulate(15*M, 15*M).prevented,false,'backup de 15MiB liberado com 20M/24M');
assert.equal(simulate(15*M, 15*M+1).prevented,true,'limite logico de 15MiB respeitado');
console.log('Testes de pré-validação do backup v1.0.49 aprovados.');
