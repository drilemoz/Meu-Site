/* IguaNews métricas próprias, sem cookies de identificação ou SDKs externos. */
(function () {
  'use strict';
  document.addEventListener('click', function (event) {
    if (document.cookie.split(';').some(function (s) { return s.trim() === 'igua_analytics_optout=1'; })) return;
    var element = event.target && event.target.closest ? event.target.closest('a[href]') : null;
    if (!element || !navigator.sendBeacon) return;
    var href = element.getAttribute('href');
    if (!href || href.charAt(0) === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
    var destination;
    try {
      var u = new URL(href, location.href);
      if (!/^https?:$/.test(u.protocol)) return;
      destination = u.origin === location.origin ? u.pathname + u.search : u.origin + '/';
    } catch (e) { return; }
    var payload = JSON.stringify({ type:'click', page: location.pathname + location.search, target:destination });
    navigator.sendBeacon('/analytics_event.php', new Blob([payload], { type:'application/json' }));
  }, { passive: true });
})();

(function(){
  var button=document.getElementById('analyticsPrivacyToggle');
  if(!button)return;
  var status=document.getElementById('analyticsPrivacyStatus');
  function off(){return document.cookie.split(';').some(function(x){return x.trim()==='igua_analytics_optout=1';});}
  function update(){var disabled=off();button.textContent=disabled?'Voltar a permitir métricas':'Não participar das métricas';if(status)status.textContent=disabled?'Novos acessos não serão registrados pelo módulo próprio neste navegador.':'Métricas próprias permitidas, respeitando sinais do navegador.';}
  button.addEventListener('click',function(){
    var disabled=off();
    document.cookie='igua_analytics_optout='+(disabled?'0':'1')+'; Max-Age=31536000; Path=/; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');
    document.dispatchEvent(new CustomEvent('igua:analytics-optout', { detail: { optedOut: !disabled } }));
    update();
  });update();
})();
