'use strict';
// Verifica a montagem do editor e o contrato de execução sem novas dependências.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const crypto = require('node:crypto');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const sources = path.join(root, 'public/assets/js/news-editor-modules');
const output = fs.readFileSync(path.join(root, 'public/assets/js/news-editor.js'));
const order = [
  '01-blocks.js','02-steps.js','03-rich-text.js','04-cover-library.js',
  '05-seo-navigation.js','06-media-metadata.js','07-drafts-autosave.js','08-submit-init.js'
];
assert.deepEqual(fs.readdirSync(sources).sort(), [...order].sort(), 'apenas os oito módulos esperados');
const joined = Buffer.concat(order.map(name => fs.readFileSync(path.join(sources,name))));
assert.deepEqual(joined, output, 'módulos devem reconstruir exatamente o editor distribuído');
const js = output.toString('utf8');
assert.equal(crypto.createHash('sha256').update(output).digest('hex'),
  'd795445aefb6fe8866ac4ad0b44378d294759fab28142af4ffcbaca10cdd5ea5',
  'hash do bundle reconstruído para a versão 1.0.90');
assert.equal((js.match(/\(\(\) => \{/g)||[]).length, 1, 'uma única IIFE');
new vm.Script(js, {filename:'news-editor.js'});
const script=require('./support/news_editor_view_source.js')();
assert.equal((script.match(/\/assets\/js\/news-editor\.js/g)||[]).length, 1, 'apenas um carregamento do editor');
assert.equal((script.match(/data-tab="/g)||[]).length, 9, 'editor com nove etapas');
assert(js.includes('if(result.saved!==true)throw Error'), 'autosave confere resposta do servidor');
assert(js.includes("!['csrf','id','blocks_json'].includes(name)"), 'autosave não sobrescreve identificadores');
console.log('OK: oito módulos recompõem exatamente o editor 1.0.90; nove etapas, autosave e carregamento único preservados.');
