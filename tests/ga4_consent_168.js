const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const code = fs.readFileSync(path.join(__dirname, '../public/assets/js/ga4-consent.js'), 'utf8');
function browser({choice='', gpc=false, dnt='', optout=false, id='G-A1B2C3D4', configExists=true, privacyPage=false, priorChoice=''}={}) {
  const cookies = {};
  if (choice) cookies.igua_ga4_choice = choice + '.' + id;
  if (priorChoice) cookies.igua_ga4_choice = priorChoice;
  if (optout) cookies.igua_analytics_optout = '1';
  const listeners = {};
  let reloads = 0;
  const scripts = [];
  const elements = Object.fromEntries(['ga4ConsentConfig','ga4Allow','ga4Deny','ga4ConsentStatus'].map(id => [id,{
    disabled:false,textContent:'',getAttribute(key) {return key === 'data-ga4-id' ? this.gaId : null;},
    addEventListener(type,callback) {this.listeners[type]=callback;},listeners:{},
    click() {if (!this.disabled) this.listeners.click?.({preventDefault() {}});}
  }]));
  elements.ga4ConsentConfig.gaId = id;
  if (!configExists) delete elements.ga4ConsentConfig;
  if (!privacyPage) for (const name of ['ga4Allow','ga4Deny','ga4ConsentStatus']) delete elements[name];
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
  vm.runInNewContext(code,{document,window,location,navigator,encodeURIComponent,Date},{filename:'ga4-consent.js'});
  return {elements,document,window,location,navigator,scripts,cookies,fire(name,detail) {listeners[name]?.({detail});},get reloads() {return reloads;}};
}
let b=browser();
assert.equal(b.scripts.length,0,'sem aceite não carrega GA4');
assert.equal(b.elements.ga4Allow,undefined,'navegação pública não oferece janela de autorização');
b=browser({privacyPage:true});
assert.match(b.elements.ga4ConsentStatus.textContent,/desativado/,'controle opcional só na página de privacidade');
b.elements.ga4Allow.click();
assert.equal(b.cookies.igua_ga4_choice,'allow.G-A1B2C3D4');
assert.equal(b.scripts.length,1,'carrega somente após ação voluntária');
assert.match(b.scripts[0].src,/^https:\/\/www\.googletagmanager\.com\/gtag\/js\?id=G-A1B2C3D4$/);
assert.equal(b.window.dataLayer[0][0],'consent');
assert.equal(b.window.dataLayer[0][2].analytics_storage,'denied','padrão negado antes da configuração');
assert.equal(b.window.dataLayer[0][2].ad_storage,'denied','consentimento GA4 não habilita anúncios');
assert.equal(b.window.dataLayer[2][2].analytics_storage,'granted');
assert.match(b.elements.ga4ConsentStatus.textContent,/autorizado/);
b.elements.ga4Allow.click();
assert.equal(b.scripts.length,1,'não duplica script');
b.elements.ga4Deny.click();
assert.equal(b.cookies.igua_ga4_choice,'deny.G-A1B2C3D4');
assert.equal(b.reloads,1,'recarrega ao revogar tag carregada');
b=browser({choice:'allow'});
assert.equal(b.scripts.length,1,'consentimento anterior permanece funcional sem banner');
b=browser({choice:'deny'});
assert.equal(b.scripts.length,0,'negação anterior continua sem tag');
b=browser({choice:'allow',gpc:true,privacyPage:true});
assert.equal(b.scripts.length,0,'GPC bloqueia');
assert.equal(b.elements.ga4Allow.disabled,true);
b=browser({choice:'allow',dnt:'1'});
assert.equal(b.scripts.length,0,'DNT bloqueia');
b=browser({choice:'allow',optout:true});
assert.equal(b.scripts.length,0,'opt-out do portal bloqueia');
b=browser({priorChoice:'allow.G-OLD12345',privacyPage:true});
assert.equal(b.scripts.length,0,'outro ID exige nova autorização');
assert.match(b.elements.ga4ConsentStatus.textContent,/desativado/);
b=browser({id:'G-ABC<script>'});
assert.equal(b.scripts.length,0,'ID inválido não injeta tag');
b=browser({configExists:false});
assert.equal(b.scripts.length,0,'GA4 desligado não inicia');
b=browser({privacyPage:true});b.elements.ga4Deny.click();
assert.equal(b.cookies.igua_ga4_choice,'deny.G-A1B2C3D4','negação salva sem recarregar');
assert.equal(b.reloads,0);
b=browser({privacyPage:true});b.elements.ga4Allow.click();b.fire('igua:analytics-optout',{optedOut:true});
assert.equal(b.cookies.igua_ga4_choice,'deny.G-A1B2C3D4','opt-out também revoga GA4');
assert.equal(b.reloads,1);
console.log('GA4: 17 verificações de consentimento sem aviso fixo aprovadas.');
