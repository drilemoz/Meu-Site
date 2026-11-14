'use strict';
/* Simula apenas a camada de confirmação no DOM, não operações HTTP. */
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const events = {};
function dialog() {
  const listeners = {};
  return {
    returnValue: 'confirm', open: false,
    showModal() { this.open = true; },
    close(value = this.returnValue) {
      this.returnValue = value; this.open = false;
      for (const fn of listeners.close || []) fn(); listeners.close = [];
    },
    addEventListener(type, cb) { (listeners[type] ||= []).push(cb); },
    querySelector() { return { focus() {} }; }
  };
}
const confirmation = dialog(), message = dialog();
const nodes = {
  'admin-confirm-dialog': confirmation,
  'admin-message-dialog': message,
  'admin-confirm-message': { textContent: '' },
  'admin-confirm-accept': { classList: { toggle() {} } },
  'admin-message-text': { textContent: '' }
};
const document = {
  body: { classList: { contains: key => key === 'admin-body' } },
  getElementById: id => nodes[id] || null,
  querySelectorAll: () => [],
  addEventListener: (type, cb, capture) => { if (type === 'click') { if (capture === true) events[type] = cb; } else events[type] = cb; }
};
const context = { document, location: {hash:''}, window: {addEventListener() {}, confirm() { throw Error('Não deve chamar confirm nativo'); }, alert() {}}, WeakSet, Promise };
vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../public/assets/js/admin-ui.js'),'utf8'),context);
assert.equal(typeof context.window.IguaAdminUI.confirm,'function');
async function flush() { await Promise.resolve(); await Promise.resolve(); }
function button() {
  const b = { disabled:false, count:0, getAttribute:()=>'Excluir esta notícia?', click() { this.count++; } };
  return b;
}
function event(btn) { return { target:{closest:() => btn}, prevented:false, stopped:false, preventDefault(){this.prevented=true;},stopImmediatePropagation(){this.stopped=true;} }; }
(async()=>{
  const b=button(), e=event(b);
  const cancelled=events.click(e);
  assert(e.prevented && e.stopped && confirmation.open,'a confirmação intercepta o clique');
  confirmation.close('cancel'); await cancelled; assert.equal(b.count,0,'cancelar não executa ação');
  const accepted=events.click(event(b));assert.equal(confirmation.returnValue,'cancel','retorno anterior não pode autorizar por Escape');
  confirmation.close('confirm');await accepted;assert.equal(b.count,1,'aceitar executa uma vez');
  const escape=events.click(event(b));confirmation.close();await escape;assert.equal(b.count,1,'Escape não herda confirmação antiga');
  const msg=context.window.IguaAdminUI.notify('<exemplo>');assert.equal(nodes['admin-message-text'].textContent,'<exemplo>','mensagem usa textContent');message.close('confirm');await msg;
  console.log('OK: cancelamento, aprovação única, Escape, bloqueio antecipado e avisos seguros');
})().catch(e=>{console.error(e);process.exitCode=1;});
