/* GA4 opcional: modo básico, sem carregar/consultar servidores Google antes de consentimento. */
(function () {
  'use strict';
  var banner = document.getElementById('ga4Consent');
  if (!banner) return;
  var id = banner.getAttribute('data-ga4-id') || '';
  if (!/^G-[A-Z0-9]{4,20}$/.test(id)) return;
  var allow = document.getElementById('ga4Allow');
  var deny = document.getElementById('ga4Deny');
  var change = document.getElementById('ga4ConsentChange');
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
  function setStatus(message) {
    if (!status) return;
    status.textContent = message;
    status.hidden = !message;
  }
  function updateBanner(force) {
    var blocked = disabledByBrowser();
    if (allow) allow.disabled = blocked;
    if (blocked) setStatus('Medição externa bloqueada pela preferência de privacidade deste navegador.');
    else setStatus('');
    banner.hidden = !force && (blocked || cookie(key) === accepted || cookie(key) === rejected);
  }
  function start() {
    if (loaded || disabledByBrowser() || cookie(key) !== accepted) return;
    loaded = true;
    // Nenhuma requisição ou tag externa antes do aceite. Publicidade segue negada.
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
      // A tag já carregada não pode ser descarregada; reload garante modo básico após revogação.
      if (typeof window.gtag === 'function') window.gtag('consent', 'update', { analytics_storage: 'denied' });
      location.reload();
      return;
    }
    updateBanner(false);
  }
  allow && allow.addEventListener('click', function () {
    if (disabledByBrowser()) { updateBanner(true); return; }
    save('allow'); updateBanner(false); start();
  });
  deny && deny.addEventListener('click', revoke);
  change && change.addEventListener('click', function (event) { event.preventDefault(); updateBanner(true); });
  document.addEventListener('igua:analytics-optout', function (event) {
    if (event.detail && event.detail.optedOut) revoke();
    else updateBanner(false);
  });
  updateBanner(false);
  if (!disabledByBrowser() && cookie(key) === accepted) start();
})();
