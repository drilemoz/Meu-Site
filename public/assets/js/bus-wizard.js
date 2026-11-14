/* IguaNews 1.0.7 - wizard responsivo, sem salvar dados até o último cartão. */
(() => {
  const form = document.querySelector('[data-bus-wizard]');
  if (!form) return;
  const $ = id => document.getElementById(id);
  const data = JSON.parse($('busWizardData').textContent);
  const initialClock = data.time || '';
  const normalize = value => String(value || '').trim();
  const timeToMin = time => { const m = /^(\d{2}):(\d{2})$/.exec(time); return m ? (+m[1] * 60 + +m[2]) : NaN; };
  const minToTime = minutes => String(Math.floor(minutes / 60) % 24).padStart(2,'0') + ':' + String(minutes % 60).padStart(2,'0');
  let stops = (data.stops || []).map(stop => {
    const offset = stop.minutes_from_start === null ? null : +stop.minutes_from_start;
    const minutes = timeToMin(initialClock);
    const abs = offset === null || !Number.isFinite(minutes) ? null : minutes + offset;
    return {city:stop.city, label:stop.location_label || '', time:abs===null?'':minToTime(abs), day:abs===null?0:Math.floor(abs/1440), unknown:offset===null};
  });
  if (!stops.length) stops = [{city:'', label:'',time:'',day:0,unknown:false}];
  let index = 1;
  let step = 'origin';
  $('wizardOriginCity').value = stops[0].city;
  $('wizardOriginLabel').value = stops[0].label;
  $('wizardStart').value = initialClock;
  $('wizardCompany').value = data.company;
  $('wizardFare').value = data.fare;
  $('wizardNotes').value = data.notes;
  $('wizardActive').checked = data.active;
  $('wizardWeekdays').querySelectorAll('input[type="checkbox"]').forEach(input => { input.checked = Boolean(data.daysMask & (1 << +input.value)); });
  const showError = message => { const node = $('wizardError'); node.hidden = !message; node.textContent = message; };
  function setStep(next) {
    step = next;
    form.querySelectorAll('[data-wizard-step]').forEach(el => el.hidden = el.dataset.wizardStep !== step);
    document.querySelectorAll('[data-wizard-dot]').forEach(dot => {
      const order = ['origin','stop','finish'];
      dot.classList.toggle('current',dot.dataset.wizardDot === step);
      dot.classList.toggle('done',order.indexOf(dot.dataset.wizardDot) < order.indexOf(step));
    });
    showError('');
    if(step === 'stop') loadStop();
    if(step === 'finish') updateSummary();
  }
  function saveOrigin() {
    const city = normalize($('wizardOriginCity').value), label = normalize($('wizardOriginLabel').value), time = $('wizardStart').value;
    if (!city || city.length > 100 || label.length > 150 || !Number.isFinite(timeToMin(time))) { showError('Preencha a cidade de origem e um horário de saída válido.'); return false; }
    stops[0] = {city,label,time,day:0,unknown:false};
    return true;
  }
  function loadStop() {
    const stop = stops[index] || {city:'',label:'',time:'',day:0,unknown:false};
    $('wizardStopCity').value = stop.city;
    $('wizardStopLabel').value = stop.label;
    $('wizardStopClock').value = stop.time;
    $('wizardStopDay').value = String(stop.day || 0);
    $('wizardStopUnknown').checked = !!stop.unknown;
    toggleClock();
    $('wizardStopOverline').textContent = `ETAPA 2 · CIDADE ${index+1} DO TRAJETO`;
    $('wizardTrail').textContent = stops.slice(0,index).map(s => s.city).join(' → ');
  }
  function toggleClock() { const disabled = $('wizardStopUnknown').checked; $('wizardStopClock').disabled = disabled; $('wizardStopDay').disabled = disabled; }
  $('wizardStopUnknown').addEventListener('change',toggleClock);
  function readStop(strict) {
    const city=normalize($('wizardStopCity').value),label=normalize($('wizardStopLabel').value);
    const unknown=$('wizardStopUnknown').checked;
    const time=unknown?'':$('wizardStopClock').value;
    const day=unknown?0:+$('wizardStopDay').value;
    const candidate={city,label,time,day,unknown};
    if(!strict) { if(city || time) stops[index] = candidate; return true; }
    if(!city || city.length>100 || label.length>150) { showError('Informe a cidade desta parada.'); return false; }
    const key=s=>normalize(s.city).toLocaleLowerCase('pt-BR');
    if(stops.some((s,i)=>i!==index && key(s)===key(candidate))) { showError('Uma cidade já aparece neste percurso. Confira a sequência.'); return false; }
    if(!unknown) {
      const current=timeToMin(time),first=timeToMin(stops[0].time);
      if(!Number.isFinite(current)) { showError('Informe o horário previsto ou marque que ele ainda não foi confirmado.'); return false; }
      const offset=day*1440+current-first;
      if(offset<=0 || offset>10080) { showError('Horário anterior à saída. Se for depois da meia-noite, escolha “Dia seguinte”.'); return false; }
      for(let i=1;i<index;i++) {
        const prev=stops[i];
        if(prev.unknown) { showError('Não é possível informar horário posterior a uma parada com hora desconhecida.'); return false; }
        const previous=prev.day*1440+timeToMin(prev.time)-first;
        if(offset<=previous) { showError('O horário precisa ser posterior ao da parada anterior. Confira o dia da viagem.'); return false; }
      }
    }else if(stops.slice(1,index).some(s=>!s.unknown) && index>1) { /* permitido começar desconhecimento depois de paradas conhecidas */ }
    stops[index]=candidate;
    return true;
  }
  function updateSummary() {
    const route=stops.map(s=>s.city).join(' → ');
    const times=stops.map((s,i)=>`<div>${i+1}. ${escapeHtml(s.city)} <small>${escapeHtml(s.unknown?'Horário a confirmar':(s.time+(s.day>0?' · +'+s.day+' dia(s)':'')))}</small></div>`).join('');
    $('wizardSummary').innerHTML='<strong>Confira o percurso</strong><div class="bus-wizard-summary-list">'+times+'</div><small>Tarifa para o trajeto completo; confirme valores e circulação com a empresa.</small>';
    // Não se injeta HTML não escapado vindo do formulário.
  }
  function escapeHtml(s) { return String(s).replace(/[&<>"']/g, ch=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch])); }
  function hidden(name,value) {
    const input=document.createElement('input'); input.type='hidden';input.name=name;input.value=String(value ?? '');$('busWizardHidden').append(input);
  }
  function saveFinal() {
    const company=normalize($('wizardCompany').value),fare=normalize($('wizardFare').value);
    if(!company||company.length>160) { showError('Informe a empresa responsável pela linha.'); return; }
    if(fare && !/^\d{1,6}([,.]\d{1,2})?$/.test(fare)) { showError('Informe a tarifa em reais. Exemplo: 15,50.'); return; }
    const days=[...$('wizardWeekdays').querySelectorAll('input:checked')].map(el=>el.value);
    if(!days.length) { showError('Selecione ao menos um dia da semana em que o ônibus circula.');return; }
    const start=stops[0].time, first=timeToMin(start);
    let previous=-1, encounteredUnknown=false;
    for(let i=0;i<stops.length;i++) {
      const s=stops[i];
      if(s.unknown) {encounteredUnknown=true;continue;}
      if(encounteredUnknown) {showError('Uma cidade depois de uma parada sem horário também precisa ficar a confirmar.');return;}
      const offset=s.day*1440+timeToMin(s.time)-first;
      if(!Number.isFinite(offset)||offset<=previous) {showError('Confira a ordem e as horas das paradas.');return;}
      previous=offset;
    }
    const box=$('busWizardHidden');box.replaceChildren();
    hidden('start_time',start);hidden('company',company);hidden('fare',fare);hidden('notes',$('wizardNotes').value);
    if($('wizardActive').checked)hidden('active',1);
    for(const day of days)hidden('days[]',day);
    for(const stop of stops){hidden('stop_city[]',stop.city);hidden('stop_label[]',stop.label);hidden('stop_clock[]',stop.unknown?'':stop.time);hidden('stop_day[]',stop.unknown?'':stop.day);}
    // Salvar só acontece após confirmação explícita do usuário.
    form.submit();
  }
  form.addEventListener('click',ev=>{
    const button=ev.target.closest('[data-wizard-action]');if(!button)return;
    const action=button.dataset.wizardAction;showError('');
    if(action==='origin-next') {if(!saveOrigin())return;index=1;setStep('stop');}
    if(action==='stop-next') {if(!readStop(true))return;if(stops.length>=25){showError('Máximo de 25 cidades por linha. Conclua o trajeto.');return;}index++;setStep('stop');}
    if(action==='stop-finish') {if(!readStop(true))return;stops=stops.slice(0,index+1);setStep('finish');}
    if(action==='stop-back') {readStop(false); if(index===1)setStep('origin');else {index--;setStep('stop');}}
    if(action==='finish-back') {index=stops.length-1;setStep('stop');}
    if(action==='save') saveFinal();
  });
  form.addEventListener('submit',ev=>ev.preventDefault());
  setStep('origin');
})();
