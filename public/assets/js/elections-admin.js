'use strict';
// Adapta os cargos à abrangência, sem substituir a validação PHP.
(() => {
  const scope = document.getElementById('election-scope');
  const office = document.getElementById('election-office');
  if (!scope || !office) return;
  const offices = {
    municipal: [['mayor', 'Prefeito(a)'], ['councilor', 'Vereador(a)']],
    general: [['president', 'Presidente'], ['governor', 'Governador(a)'], ['senator', 'Senador(a)'], ['federal_deputy', 'Deputado(a) federal'], ['state_deputy', 'Deputado(a) estadual/distrital']]
  };
  scope.addEventListener('change', () => {
    const oldValue = office.value;
    office.replaceChildren(...(offices[scope.value] || []).map(([value, label]) => new Option(label, value)));
    if ([...office.options].some(opt => opt.value === oldValue)) office.value = oldValue;
  });
})();
