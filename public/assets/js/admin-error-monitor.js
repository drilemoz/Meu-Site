/* Monitoramento conservador do painel: códigos fixos, sem URLs, mensagens, corpos, IP ou credenciais. */
(()=>{'use strict';
 const allowed=new Set(['MEDIA_HTTP','MEDIA_TIMEOUT','GEMINI_HTTP','GEMINI_TIMEOUT','JS_RUNTIME','JS_PROMISE']);
 const csrf=document.querySelector('#media-manager')?.dataset.csrf;
 if(!csrf)return;
 let sent=0;
 const once=new Set();
 const report=code=>{
   if(!allowed.has(code)||sent>=8||once.has(code))return;
   sent++;once.add(code);
   const body=new URLSearchParams({csrf,code});
   fetch('/admin.php?action=admin_error_event',{method:'POST',credentials:'same-origin',body,keepalive:true}).catch(()=>{});
 };
 window.IguaAdminErrors=Object.freeze({report});
 window.addEventListener('error',e=>{if(e instanceof ErrorEvent)report('JS_RUNTIME');});
 window.addEventListener('unhandledrejection',()=>report('JS_PROMISE'));
})();
