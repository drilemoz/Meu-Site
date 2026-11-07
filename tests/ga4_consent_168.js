const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const code = fs.readFileSync(path.join(__dirname, '../public/assets/js/ga4-consent.js'), 'utf8');
function browser({choice='', gpc=false, dnt='', optout=false, id='G-A1B2C3D4', bannerExists=true, priorChoice=''}={}) {
  const cookies = {};
  if (choice) cookies.igua_ga4_choice = choice + '.' + id;
  if (priorChoice) cookies.igua_ga4_choice = priorChoice;
  if (optout) cookies.igua_analytics_optout = '1';
  const listeners = {};
  let reloads = 0;
  const scripts = [];
  const elements = Object.fromEntries(['ga4Consent','ga4Allow','ga4Deny','ga4ConsentChange','ga4ConsentStatus'].map(id => [id,{hidden:true,disabled:false,textContent:'',getAttribute(key) {return key === 'data-ga4-id' ? this.gaId : null;},addEventListener(type,callback) {this.listeners[type]=callback;},listeners:{},click() {this.listeners.click?.({ preventDefault() {} });}}]));
  elements.ga4Consent.gaId = id;
  if (!bannerExists) delete elements.ga4Consent;
  const document = {
    getElementById(id) {return elements[id] || null;},
    head:{appendChild(script) {scripts.push(script);}},
    createElement(tag) {return {tagName:tag};},
    addEventListener(type,cb) {listeners[type]=cb;},
    get cookie() {return Object.entries(cookies).map(([k,v])=>`${k}=${v}`).join('; ');},
    set cookie(value) {const [k,v] = value.split(';')[0].split('=');cookies[k] = v;},
  };
  const location = { protocol:'https:', reload() {reloads++;} };
  const window = { doNotTrack:dnt };
  const navigator = { globalPrivacyControl:gpc, doNotTrack:dnt };
  vm.runInNewContext(code,{document,window,location,navigator,encodeURIComponent,Date}, {filename:'ga4-consent.js'});
  return {elements,document,window,location,navigator,scripts,cookies,fire(name,detail) {listeners[name]?.({detail});},get reloads() {return reloads;}};
}
let b=browser();
assert.equal(b.scripts.length,0, 'sem tag antes do aceite');
assert.equal(b.elements.ga4Consent.hidden,false, 'solicita consentimento');
b.elements.ga4Allow.click();
assert.equal(b.cookies.igua_ga4_choice,'allow.G-A1B2C3D4');
assert.equal(b.scripts.length,1,'carrega tag somente após aceite');
assert.match(b.scripts[0].src,/^https:\/\/www\.googletagmanager\.com\/gtag\/js\?id=G-A1B2C3D4$/);
assert.equal(b.window.dataLayer[0][0],'consent');
assert.equal(b.window.dataLayer[0][2].analytics_storage,'denied','padrão negado antes de config');
assert.equal(b.window.dataLayer[0][2].ad_storage,'denied','publicidade não ganha consentimento GA4');
assert.equal(b.window.dataLayer[2][2].analytics_storage,'granted');
b.elements.ga4Allow.click();
assert.equal(b.scripts.length,1,'sem duplicação');
b.elements.ga4Deny.click();
assert.equal(b.cookies.igua_ga4_choice,'deny.G-A1B2C3D4');
assert.equal(b.reloads,1,'recarrega ao revogar tag já carregada');
b=browser({choice:'allow'});
assert.equal(b.scripts.length,1,'consentimento prévio explícito respeitado');
b=browser({choice:'deny'});
assert.equal(b.scripts.length,0,'negação armazenada impede tags');
assert.equal(b.elements.ga4Consent.hidden,true);
b.elements.ga4ConsentChange.click();
assert.equal(b.elements.ga4Consent.hidden,false,'pode revisar preferência');
b=browser({choice:'allow',gpc:true});
assert.equal(b.scripts.length,0,'GPC bloqueia mesmo com aceite anterior');
assert.equal(b.elements.ga4Allow.disabled,true);
b=browser({choice:'allow',dnt:'1'});
assert.equal(b.scripts.length,0,'DNT bloqueia');
b=browser({choice:'allow',optout:true});
assert.equal(b.scripts.length,0,'opt-out próprio bloqueia GA4');
b=browser({priorChoice:'allow.G-OLD12345'});
assert.equal(b.scripts.length,0,'ID alterado exige novo consentimento');
assert.equal(b.elements.ga4Consent.hidden,false);
b=browser({id:'G-ABC<script>'});
assert.equal(b.scripts.length,0,'ID inválido não injeta script');
b=browser({bannerExists:false});
assert.equal(b.scripts.length,0,'sem configuração GA4 não inicia');
b=browser(); b.elements.ga4Deny.click();
assert.equal(b.cookies.igua_ga4_choice,'deny.G-A1B2C3D4','negação persiste');
assert.equal(b.reloads,0,'negação antes da tag não recarrega');
b=browser(); b.elements.ga4Allow.click(); b.fire('igua:analytics-optout',{optedOut:true});
assert.equal(b.cookies.igua_ga4_choice,'deny.G-A1B2C3D4','opt-out revoga GA4');
assert.equal(b.reloads,1);
console.log('GA4: 14 cenários de consentimento aprovados.');
