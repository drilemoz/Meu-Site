/* GA4 opcional: sem aviso fixo; escolha exclusiva na Política de Privacidade. */
(function () {
  'use strict';
  var config = document.getElementById('ga4ConsentConfig');
  if (!config) return;
  var id = config.getAttribute('data-ga4-id') || '';
  if (!/^G-[A-Z0-9]{4,20}$/.test(id)) return;
  var allow = document.getElementById('ga4Allow');
  var deny = document.getElementById('ga4Deny');
  var status = document.getElementById('ga4ConsentStatus');
  var key = 'igua_ga4_choice';
  var accepted = 'allow.' + id;
  var rejected = 'deny.' + id;
  var loaded = false;
  function cookie(name) {
    var match = document.cookie.split(';').map(function (part) { return part.trim(); }).find(function (part) { return part.indexOf(name + '=') === 0; });
    return match ? match.slice(name.length + 1) : '';
  }
  function save(choice) {
    document.cookie = key + '=' + (choice === 'allow' ? accepted : rejected) + '; Max-Age=15552000; Path=/; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
  }
  function disabledByBrowser() {
    return cookie('igua_analytics_optout') === '1' || navigator.globalPrivacyControl === true ||
      navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.msDoNotTrack === '1';
  }
  function updateControls() {
    if (allow) allow.disabled = disabledByBrowser();
    if (!status) return;
    var choice = cookie(key);
    if (disabledByBrowser()) status.textContent = 'O navegador impede o envio de métricas externas por sua configuração de privacidade.';
    else if (choice === accepted) status.textContent = 'Google Analytics autorizado neste navegador.';
    else if (choice === rejected) status.textContent = 'Google Analytics não autorizado neste navegador.';
    else status.textContent = 'Google Analytics desativado. Nenhum dado externo é enviado sem autorização.';
  }
  function start() {
    if (loaded || disabledByBrowser() || cookie(key) !== accepted) return;
    loaded = true;
    // Nenhuma tag externa antes do aceite; consentimento de anúncios continua negado.
    window.dataLayer = window.dataLayer || [];
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag('consent', 'default', { ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied', analytics_storage: 'denied' });
    window.gtag('js', new Date());
    window.gtag('consent', 'update', { analytics_storage: 'granted' });
    window.gtag('config', id);
    var script = document.createElement('script');
    script.async = true;
    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
    document.head.appendChild(script);
  }
  function revoke() {
    save('deny');
    if (loaded) {
      // Após carregar, recarrega para interromper o envio nesta aba.
      if (typeof window.gtag === 'function') window.gtag('consent', 'update', { analytics_storage: 'denied' });
      location.reload();
      return;
    }
    updateControls();
  }
  if (allow) allow.addEventListener('click', function () {
    if (disabledByBrowser()) { updateControls(); return; }
    save('allow');
    updateControls();
    start();
  });
  if (deny) deny.addEventListener('click', revoke);
  document.addEventListener('igua:analytics-optout', function (event) {
    if (event.detail && event.detail.optedOut) revoke();
    else updateControls();
  });
  updateControls();
  // Mantém consentimentos antigos sem abrir nenhuma interface ou nova solicitação.
  if (!disabledByBrowser() && cookie(key) === accepted) start();
})();
