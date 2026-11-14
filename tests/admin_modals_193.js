'use strict';
const assert=require('node:assert/strict');
const vm=require('node:vm');
const fs=require('node:fs');
const src=fs.readFileSync(__dirname+'/../public/assets/js/admin-dialogs.js','utf8');
class MockDialog{
 constructor(){this.open=false;this.listeners={};this.dataset={};this.buttons={};}
 showModal(){this.open=true;}
 close(){this.open=false;this.emit('close');}
 addEventListener(name,fn){(this.listeners[name]??=[]).push(fn);}
 emit(name,evt={}){for(const fn of (this.listeners[name]||[]))fn(evt);}
 querySelector(){return this.buttons.focus||null;}
 getBoundingClientRect(){return {left:20,right:360,top:30,bottom:600};}
}
const a=new MockDialog(),b=new MockDialog();let locked=false,restored=false,focusInside=false;
a.buttons.focus={focus(){focusInside=true;}};
const opener={isConnected:true,focus(){restored=true;}};
const document={activeElement:opener,body:{classList:{toggle(n,on){assert.equal(n,'igua-modal-open');locked=on;}}},querySelectorAll(s){assert.equal(s,'dialog.igua-modal');return [a,b];}};
const window={};vm.runInNewContext(src,{HTMLDialogElement:MockDialog,document,window,WeakMap,Object});
assert.equal(window.IguaAdminModal.open(a),true);
assert.equal(a.open,true);assert.equal(locked,true);assert.equal(focusInside,true);
assert.equal(window.IguaAdminModal.open(a),true,'reabrir não duplica');
a.dataset.updaterBusy='1';assert.equal(window.IguaAdminModal.close(a),false,'ocupado não fecha');
let cancelled=false;a.emit('cancel',{preventDefault(){cancelled=true;}});assert.equal(cancelled,true);
a.dataset.updaterBusy='0';assert.equal(window.IguaAdminModal.close(a),true);assert.equal(a.open,false);
assert.equal(restored,true,'foco devolvido');assert.equal(locked,false,'rolagem liberada');
window.IguaAdminModal.open(b,opener);
b.emit('click',{target:b,clientX:100,clientY:200});assert.equal(b.open,true,'toque dentro não fecha');
b.emit('click',{target:b,clientX:5,clientY:5});assert.equal(b.open,false,'toque fora fecha');
assert.equal(locked,false);
console.log('Modal compartilhado 193: abertura, foco, rolagem, bloqueio, Escape e backdrop OK');
