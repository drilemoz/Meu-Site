/* Verifica que a prévia usa somente textContent (nunca interpreta HTML). */
const fs=require('fs'), assert=require('assert');
const src=fs.readFileSync('public/assets/js/setup-identity.js','utf8');
assert(src.includes('preview.textContent') && src.includes('previewDesc.textContent'));
assert(!src.includes('innerHTML'));
const modal=fs.readFileSync('public/assets/js/developer-contact.js','utf8');
assert(modal.includes('showModal') && modal.includes('getBoundingClientRect'));
console.log('Prévia segura e modal JavaScript aprovados.');
