'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const code = fs.readFileSync(__dirname + '/../public/assets/js/article-gallery.js', 'utf8');

class Element {
  constructor() { this.listeners = {}; this.dataset = {}; this.hidden = false; this.textContent = ''; this.src = ''; this.isConnected = true; this.focused = false; }
  addEventListener(type, handler) { (this.listeners[type] ||= []).push(handler); }
  dispatch(type, data = {}) { for (const handler of this.listeners[type] || []) handler(data); }
  focus() { this.focused = true; }
  removeAttribute(name) { if (name === 'src') this.src = ''; }
}
function harness({ supported = true, amount = 3 } = {}) {
  const links = Array.from({ length: amount }, (_, i) => {
    const el = new Element();
    el.href = '/uploads/images/foto' + i + '.jpg';
    el.dataset = { galleryCaption: i === 1 ? '<script>alert(1)</script>' : `Legenda ${i+1}`, galleryCredit: i === 2 ? 'Fotógrafo' : '' };
    return el;
  });
  const gallery = { querySelectorAll: () => links };
  const selectors = ['[data-gallery-large]','[data-gallery-caption]','[data-gallery-credit]','[data-gallery-counter]','[data-gallery-original]','[data-gallery-image-status]','[data-gallery-prev]','[data-gallery-next]','[data-gallery-close]','.news-gallery-stage'];
  const nodes = Object.fromEntries(selectors.map(s => [s, new Element()]));
  const dialog = new Element();
  dialog.open = false;
  dialog.querySelector = (s) => nodes[s] || null;
  dialog.showModal = supported ? () => { dialog.open = true; } : undefined;
  dialog.close = () => { dialog.open = false; dialog.dispatch('close'); };
  const cssClasses = new Set();
  const document = {
    querySelector: (s) => s === '.news-gallery' ? gallery : s === '[data-news-gallery-dialog]' ? dialog : null,
    body: { classList: { add: cls => cssClasses.add(cls), remove: cls => cssClasses.delete(cls) } }
  };
  vm.runInNewContext(code, { document });
  return { links, dialog, nodes, cssClasses };
}
const click = (link, options = {}) => {
  const event = { button:0, defaultPrevented:false, metaKey:false, ctrlKey:false, shiftKey:false, altKey:false, ...options, prevented:false, preventDefault() { this.prevented = true; } };
  link.dispatch('click', event); return event;
};
{
  const h = harness();
  const event = click(h.links[0]);
  assert.equal(event.prevented, true);
  assert.equal(h.dialog.open, true);
  assert.equal(h.nodes['[data-gallery-counter]'].textContent, 'Foto 1 de 3');
  assert.equal(h.nodes['[data-gallery-large]'].src, '/uploads/images/foto0.jpg');
  assert.equal(h.nodes['[data-gallery-close]'].focused, true);
  assert.equal(h.cssClasses.has('news-gallery-modal-open'), true);
  h.nodes['[data-gallery-next]'].dispatch('click');
  assert.equal(h.nodes['[data-gallery-counter]'].textContent, 'Foto 2 de 3');
  assert.equal(h.nodes['[data-gallery-caption]'].textContent, '<script>alert(1)</script>');
  h.nodes['[data-gallery-next]'].dispatch('click');
  assert.equal(h.nodes['[data-gallery-credit]'].textContent, 'Foto: Fotógrafo');
  h.nodes['[data-gallery-next]'].dispatch('click');
  assert.equal(h.nodes['[data-gallery-counter]'].textContent, 'Foto 1 de 3');
  h.dialog.dispatch('keydown', { key:'ArrowLeft', preventDefault() {} });
  assert.equal(h.nodes['[data-gallery-counter]'].textContent, 'Foto 3 de 3');
  h.nodes['[data-gallery-large]'].dispatch('error');
  assert.equal(h.nodes['[data-gallery-image-status]'].textContent, 'Não foi possível carregar esta foto.');
  h.nodes['[data-gallery-large]'].dispatch('load');
  assert.equal(h.nodes['[data-gallery-image-status]'].textContent, '');
  h.nodes['.news-gallery-stage'].dispatch('touchstart', { touches:[{clientX:200,clientY:50}] });
  h.nodes['.news-gallery-stage'].dispatch('touchend', { changedTouches:[{clientX:70,clientY:55}] });
  assert.equal(h.nodes['[data-gallery-counter]'].textContent, 'Foto 1 de 3');
  h.nodes['[data-gallery-close]'].dispatch('click');
  assert.equal(h.dialog.open, false);
  assert.equal(h.cssClasses.has('news-gallery-modal-open'), false);
  assert.equal(h.links[0].focused, true);
  assert.equal(h.nodes['[data-gallery-large]'].src, '');
}
{
  const h = harness();
  assert.equal(click(h.links[0], { metaKey:true }).prevented, false, 'link modificador sem interceptação');
  assert.equal(h.dialog.open, false);
  assert.equal(click(h.links[0], { button:1 }).prevented, false, 'botão do meio sem interceptação');
  click(h.links[1]);
  h.dialog.dispatch('click', { target:h.dialog });
  assert.equal(h.dialog.open, false, 'clique no backdrop fecha');
}
{
  const h = harness({supported:false});
  assert.equal(click(h.links[0]).prevented, false, 'fallback sem dialog mantém link funcional');
}
{
  const h = harness({amount:1});
  click(h.links[0]);
  assert.equal(h.nodes['[data-gallery-prev]'].hidden, true);
  assert.equal(h.nodes['[data-gallery-next]'].hidden, true);
}
console.log('OK: galeria pública (modal, navegação, swipe, foco, fallback e textos seguros).');
