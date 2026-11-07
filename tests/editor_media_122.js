'use strict';
// Regressões executáveis sem dependências de navegador ou npm: simulam eventos
// de seleção, otimização assíncrona e reconstrução dos campos de mídia.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.resolve(__dirname, '..');
const editor = fs.readFileSync(path.join(root, 'public/assets/js/news-editor.js'), 'utf8');
const upload = fs.readFileSync(path.join(root, 'public/assets/js/news-upload.js'), 'utf8');
const flush = () => new Promise(resolve => setImmediate(resolve));

class Input {
  constructor(kind) { this.kind = kind; this.dataset = {uploadInput: kind}; this.files = []; this.listeners = new Map(); }
  addEventListener(name, fn) { this.listeners.set(name, fn); }
  dispatchEvent(event) { this.emit(event.type); }
  emit(name) { this.listeners.get(name)?.({type:name}); }
  hasAttribute(name) { return name === 'data-upload-input'; }
}
function htmlUnescape(text) {
  return text.replace(/&#39;/g, "'").replace(/&quot;/g, '"').replace(/&lt;/g,'<').replace(/&gt;/g,'>').replace(/&amp;/g,'&');
}
function makeEditorHarness() {
  const gallery = new Input('gallery'), attachment = new Input('attachment');
  const inputs = [gallery, attachment];
  const pane = {
    boxes: [],
    set innerHTML(_) { this.boxes = []; },
    appendChild(box) { this.boxes.push(box); },
    querySelectorAll(selector) { assert.equal(selector, '[data-new-media-kind]'); return this.boxes; }
  };
  const document = {
    querySelector(selector) { assert.equal(selector, '#news-new-media-preview'); return pane; },
    querySelectorAll(selector) { assert.equal(selector, '[data-upload-input]'); return inputs; },
    createElement(name) {
      assert.equal(name, 'div');
      return {
        dataset: {}, className: '',
        set innerHTML(html) {
          this.html = html;
          const caption = html.match(/data-new-caption[^>]*value="([^"]*)"/);
          const credit = html.match(/data-new-credit[^>]*value="([^"]*)"/);
          this.caption = {value: htmlUnescape(caption?.[1] || '')};
          this.credit = {value: htmlUnescape(credit?.[1] || '')};
        },
        querySelector(selector) { return selector === '[data-new-caption]' ? this.caption : this.credit; }
      };
    }
  };
  const start = editor.indexOf(" const newMediaPane=document.querySelector('#news-new-media-preview');");
  const end = editor.indexOf(" function snapshot()", start);
  assert(start !== -1 && end > start, 'Trecho de prévia do editor deve existir');
  const code = editor.slice(start, end);
  const esc = value => String(value).replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
  vm.runInNewContext(`(()=>{${code}})()`, {document, esc});
  return {gallery, attachment, pane};
}
function file(name, size = 900000) { return {name, size, type: 'image/jpeg', lastModified: 1}; }
function testPreviewRetention() {
  const {gallery, attachment, pane} = makeEditorHarness();
  gallery.files = [file('foto.jpg')]; gallery.emit('change');
  assert.equal(pane.boxes.length, 1);
  pane.boxes[0].caption.value = 'Local: Centro';
  pane.boxes[0].credit.value = 'Repórter & arquivo';
  attachment.files = [file('documento.pdf')]; attachment.emit('change');
  assert.equal(pane.boxes.length, 2);
  assert.equal(pane.boxes[0].caption.value, 'Local: Centro', 'Anexar PDF não deve apagar legenda da foto');
  assert.equal(pane.boxes[0].credit.value, 'Repórter & arquivo', 'Anexar PDF não deve apagar crédito da foto');
  pane.boxes[1].caption.value = 'PDF oficial';
  gallery.files = [file('foto-otimizada.jpg', 500000)]; gallery.emit('news:files-ready');
  assert.equal(pane.boxes[0].caption.value, 'Local: Centro', 'Otimizar foto não deve apagar legenda');
  assert.equal(pane.boxes[1].caption.value, 'PDF oficial', 'Otimizar foto não deve apagar metadados de anexo');
  gallery.files = [file('outra-foto.jpg')]; gallery.emit('change');
  assert.equal(pane.boxes[0].caption.value, '', 'Seleção substituída não deve herdar legenda antiga');
  assert.equal(pane.boxes[1].caption.value, 'PDF oficial', 'Trocar foto não deve apagar metadados de anexo');
  gallery.files = [file('<script>alert(1)</script>.jpg')]; gallery.emit('change');
  assert(pane.boxes[0].html.includes('&lt;script&gt;'), 'Nome de arquivo precisa ser escapado');
  console.log('OK: legendas/créditos preservados e novas seleções isoladas');
}

function makeUploadHarness() {
  const imageInput = new Input('image'), galleryInput = new Input('gallery');
  const inputs = [imageInput, galleryInput];
  const listeners = {};
  const form = {
    dataset: {},
    querySelector(name) { return name === '[name=image]' ? imageInput : name === '[name="gallery_files[]"]' ? galleryInput : null; },
    querySelectorAll() { return inputs; },
    addEventListener(name, fn) { listeners[name] = fn; },
    requestSubmit() { this.resubmitted = true; }
  };
  class FakeFile {
    constructor(parts, name, options) { this.name = name; this.size = parts.reduce((n,p)=>n+p.size,0); this.type=options.type; }
  }
  class FakeDataTransfer {
    constructor() { const list=[]; this.items={add:val=>list.push(val)}; this.files=list; }
  }
  const blobs = [], alerts = [], status1={textContent:''}, status2={textContent:''};
  const document = {
    querySelector(selector) {
      return {'#news-form':form,'#news-image-upload-feedback':status1,'#news-gallery-upload-feedback':status2}[selector] ?? null;
    },
    createElement(type) {
      assert.equal(type, 'canvas');
      const canvas = {
        width:0,height:0,id:'',
        getContext() { return {fillRect(){},drawImage(bitmap){canvas.id=bitmap.id;}}; },
        toBlob(cb) { blobs.push({id:canvas.id,cb}); }
      }; return canvas;
    }
  };
  const createImageBitmap = async f => {
    if(f.fail) throw new Error('falha de imagem');
    return {width:1800,height:1200,id:f.id,close(){}};
  };
  const sandbox = {document, File:FakeFile, DataTransfer:FakeDataTransfer, createImageBitmap,
    Event:class {constructor(type){this.type=type;}}, alert:message=>alerts.push(message), console};
  vm.runInNewContext(upload,sandbox);
  function submit() {
    const event={prevented:false,preventDefault(){this.prevented=true;},stopImmediatePropagation(){this.stopped=true;}};
    listeners.submit(event);return event;
  }
  return {imageInput,galleryInput,blobs,alerts,submit,status1,status2};
}
async function testAsyncStaleSelection() {
  const h=makeUploadHarness();
  const big = id => ({id,name:id+'.png',size:2500000,type:'image/png'});
  h.imageInput.files=[big('antiga')];h.imageInput.emit('change');await flush();
  h.imageInput.files=[big('nova')];h.imageInput.emit('change');await flush();
  assert.equal(h.blobs.length,2,'Duas otimizações iniciadas');
  h.blobs.find(b=>b.id==='nova').cb({size:600000}); await flush();
  assert.equal(h.imageInput.files[0].name,'nova.jpg','Arquivo novo otimizado primeiro');
  assert.equal(h.submit().prevented,false,'Tarefa antiga não deve bloquear envio de seleção pronta');
  h.blobs.find(b=>b.id==='antiga').cb({size:600000}); await flush();
  assert.equal(h.imageInput.files[0].name,'nova.jpg','Tarefa antiga não pode substituir foto nova');
  console.log('OK: seleção mais recente prevalece sobre otimização assíncrona antiga');
}
async function testErrorIsolation() {
  const h=makeUploadHarness();
  h.imageInput.files=[{id:'erro',name:'quebrada.png',size:2500000,type:'image/png',fail:true}];
  h.imageInput.emit('change'); await flush();
  h.galleryInput.files=[file('galeria.jpg')]; h.galleryInput.emit('change'); await flush();
  const event=h.submit();
  assert(event.prevented,'Erro da capa não pode ser apagado por alteração na galeria');
  assert(h.alerts.some(x=>x.includes('falha de imagem')),'Erro continua visível');
  h.imageInput.files=[];h.imageInput.emit('change');
  assert.equal(h.submit().prevented,false,'Remover campo com erro permite salvar');
  console.log('OK: erro de um upload não é ocultado ao alterar outro campo');
}
(async()=>{testPreviewRetention();await testAsyncStaleSelection();await testErrorIsolation();})().catch(e=>{console.error(e);process.exitCode=1;});
