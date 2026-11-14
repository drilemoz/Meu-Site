/* Assistente progressivo de instalação; o PHP continua validando cada POST. */
(() => {
  'use strict';
  const by = (tag, cls, text) => {
    const el = document.createElement(tag);
    if (cls) el.className = cls;
    if (text !== undefined) el.textContent = text;
    return el;
  };
  const help = {
    host: 'Use o host MySQL fornecido pela hospedagem. Em muitos servidores é localhost.',
    admin_host: 'Servidor que aceita a conexão administrativa. Para criar um usuário exclusivo, o MySQL deve ser local.',
    port: 'A porta usual do MySQL é 3306. Mantenha-a se o provedor não indicar outra.',
    database: 'Nome da base de dados. Na criação automática, ela não pode existir previamente, exceto no modo Termux quando permitido.',
    username: 'Conta MySQL autorizada. Para criar banco, ela precisa da permissão CREATE DATABASE.',
    admin_username: 'Conta usada temporariamente para administrar MySQL. A senha dessa conta não é salva pelo instalador.',
    app_username: 'Conta destinada ao uso diário do portal. Prefira uma conta de aplicação com permissões só no banco do site.',
    password: 'Senha atual do usuário MySQL, fornecida pelo provedor ou já configurada por você.',
    app_password: 'Senha atual da conta MySQL do portal. Não é uma senha nova criada por este formulário.',
    admin_password_db: 'Senha administrativa usada somente durante esta operação. Não será salva.',
    portal_password: 'Crie uma senha exclusiva para entrar no painel do portal. Mínimo de 10 caracteres.',
    portal_password_confirm: 'Repita exatamente a senha definida para o painel. Uma diferença impedirá a instalação.',
    migration_password: 'Use a senha da conta administrativa que existia no portal antigo.',
    migration_site_url: 'Só informe uma URL HTTPS quando o domínio do site for alterado. Se permanecer igual, deixe vazio.',
    mysql_tls: 'Ative se o servidor MySQL remoto oferece TLS verificado por uma CA confiável.',
    mysql_tls_private_ack: 'Não use esta opção em redes públicas. Apenas para uma rede privada já confiável.',
    backup: 'Envie o backup JSON ou GZIP compatível. Para migrar também as imagens, use o pacote portátil.',
    demo: 'Conteúdo de demonstração não substitui notícias reais e pode ser deixado desativado.'
  };
  const labelTitle = (label) => {
    const prefix = [...label.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent.trim()).join(' ').trim();
    return (prefix || label.querySelector('span')?.textContent?.trim() || label.textContent.trim()).replace(/\s+/g,' ').slice(0,130);
  };
  const addVisibility = root => {
    root.querySelectorAll('input[type="password"]').forEach(input => {
      if (input.dataset.visibilityReady) return;
      input.dataset.visibilityReady = '1';
      const wrapper = by('span','setup-password-wrap');
      input.parentNode.insertBefore(wrapper,input);
      wrapper.append(input);
      const toggle = by('button','setup-show-password','Mostrar');
      toggle.type = 'button';
      toggle.setAttribute('aria-label','Mostrar senha');
      toggle.setAttribute('aria-pressed','false');
      toggle.addEventListener('click',() => {
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        toggle.textContent = visible ? 'Ocultar' : 'Mostrar';
        toggle.setAttribute('aria-pressed',String(visible));
        toggle.setAttribute('aria-label',visible ? 'Ocultar senha' : 'Mostrar senha');
      });
      wrapper.append(toggle);
    });
  };
  const configureNewPassword = form => {
    const primary = form.querySelector('[name="portal_password"]');
    const confirm = form.querySelector('[name="portal_password_confirm"]');
    if (!(primary && confirm)) return;
    const gauge = by('div','setup-password-feedback');
    gauge.setAttribute('aria-live','polite');
    const score = by('div','setup-password-meter');
    const fill = by('span',''); score.append(fill);
    const desc = by('p','setup-password-grade','');
    const match = by('p','setup-password-match','');
    gauge.append(score,desc,match);
    const label = primary.closest('label');
    label?.append(gauge);
    const update = () => {
      const value = primary.value;
      const length = [...value].length;
      const missing = Math.max(0,10-length);
      const variety = [/[a-z]/.test(value),/[A-Z]/.test(value),/\d/.test(value),/[^\p{L}\p{N}\s]/u.test(value)].filter(Boolean).length;
      const strength = (length >= 10 ? 1 : 0) + (length >= 14 ? 1 : 0) + (variety >= 3 ? 1 : 0) + (variety === 4 ? 1 : 0);
      const level = !value ? 'Aguardando senha' : strength < 2 ? 'Fraca' : strength < 4 ? 'Média' : 'Forte';
      fill.style.width = !value ? '0%' : `${Math.max(15,strength*25)}%`;
      fill.dataset.level = level;
      desc.textContent = missing ? `Faltam ${missing} ${missing === 1 ? 'caractere' : 'caracteres'} para o mínimo de 10. Força: ${level}.` : `Mínimo de caracteres atingido. Força estimada: ${level}.`;
      if (confirm.value) {
        const agrees = confirm.value === primary.value;
        match.textContent = agrees ? '✓ As senhas coincidem.' : 'As senhas não conferem.';
        match.dataset.match = String(agrees);
        confirm.setCustomValidity(agrees ? '' : 'As senhas não conferem.');
      } else {
        match.textContent = 'Digite novamente a senha para confirmar.';
        match.dataset.match = 'pending';
        confirm.setCustomValidity('');
      }
    };
    primary.addEventListener('input',update);
    confirm.addEventListener('input',update);
    update();
  };
  const shouldGuide = new Set([
    'test_db','create_db','provision_db','auto_db',
    'install_fresh','install_migration','install_restore',
    'recover_config','reset_existing'
  ]);
  const displayValue = input => {
    if (input instanceof HTMLInputElement && input.dataset.visibilityReady === '1') return input.value ? '•••••••• (informada)' : '(não informada)';
    if (input instanceof HTMLInputElement && input.type === 'file') return input.files?.[0]?.name || '(nenhum arquivo)';
    if (input instanceof HTMLInputElement && (input.type === 'checkbox' || input.type === 'radio')) return input.checked ? 'Sim' : 'Não';
    return input.value?.trim() || '(não informado)';
  };
  function guide(form) {
    const action = form.querySelector('input[name="action"]')?.value;
    if (!shouldGuide.has(action)) return;
    const labels = [...form.querySelectorAll('label')].filter(label => label.querySelector('input:not([type="hidden"]),select,textarea'));
    if (!labels.length) return;
    const submit = form.querySelector('button[type="submit"]');
    if (!submit) return;
    const existingActions = submit.closest('.wizard-actions');
    const groups = [];
    labels.forEach(label => {
      const input = label.querySelector('input,select,textarea');
      if (!input) return;
      if (input.name === 'portal_password_confirm' && groups.at(-1)?.some(x => x.querySelector('[name="portal_password"]'))) {
        groups.at(-1).push(label);
      } else if (['mysql_tls','mysql_tls_private_ack'].includes(input.name) && groups.at(-1)?.some(x => x.querySelector('[name="mysql_tls"]'))) {
        groups.at(-1).push(label);
      } else {
        groups.push([label]);
      }
    });
    if (!groups.length) return;
    // Estrutura original continua utilizável quando não existe JavaScript.
    form.noValidate = true;
    const shell = by('section','setup-guided');
    shell.setAttribute('aria-label','Assistente passo a passo');
    const top = by('div','setup-guided-top');
    const counter = by('span','setup-guided-count');
    const progress = by('div','setup-guided-progress');
    progress.setAttribute('role','progressbar');
    progress.setAttribute('aria-valuemin','1');
    progress.setAttribute('aria-valuemax',String(groups.length+1));
    const bar = by('span'); progress.append(bar);
    const heading = by('h2','setup-guided-title');
    const guideText = by('div','setup-guided-tip');
    const panels = groups.map((group,i) => {
      const panel = by('div','setup-guided-panel');
      panel.dataset.step = String(i);
      const title = labelTitle(group[0]);
      const ctl = group[0].querySelector('input,select,textarea');
      const details = by('details','setup-guided-help');
      details.append(by('summary','','?'),by('p','',help[ctl?.name] || 'Preencha a informação solicitada e avance. Os dados serão verificados novamente pelo servidor antes da instalação.'));
      panel.append(details);
      group.forEach(label => panel.append(label));
      return panel;
    });
    const review = by('div','setup-guided-panel setup-guided-review');
    review.dataset.step = String(groups.length);
    review.append(by('p','setup-guided-review-note','Revise os dados antes de continuar. Senhas não aparecem neste resumo.'));
    const summary = by('dl','setup-guided-summary'); review.append(summary);
    submit.classList.add('setup-guided-submit');
    submit.textContent = action.startsWith('install_') ? (action === 'install_migration' ? 'Confirmar e migrar portal' : action === 'install_fresh' ? 'Confirmar e instalar portal' : 'Confirmar importação') : (action === 'reset_existing' ? 'Confirmar reinicialização' : 'Confirmar e continuar');
    review.append(submit);
    const controls = by('div','setup-guided-controls');
    const back = by('button','btn outline','← Voltar'); back.type = 'button';
    const next = by('button','btn primary','Avançar →'); next.type = 'button';
    controls.append(back,next);
    top.append(counter,progress);
    shell.append(top,heading,guideText,...panels,review,controls);
    form.append(shell);
    // Reduz textos redundantes sem retirar avisos de segurança.
    if (existingActions && !existingActions.textContent.trim()) existingActions.remove();
    form.classList.add('is-guided');
    let current = 0;
    const controlsFor = index => [...groups[index]].flatMap(label => [...label.querySelectorAll('input,select,textarea')]);
    const validStep = index => {
      for (const input of controlsFor(index)) {
        if (input.disabled) continue;
        if (input.name === 'portal_password_confirm') {
          const primary = form.querySelector('[name="portal_password"]');
          input.setCustomValidity(input.value === primary.value ? '' : 'As senhas não conferem.');
        }
        if (!input.checkValidity()) { input.reportValidity(); input.focus(); return false; }
      }
      return true;
    };
    const renderReview = () => {
      summary.replaceChildren();
      groups.forEach(group => group.forEach(label => {
        const input = label.querySelector('input,select,textarea');
        if (!input || input.name === 'portal_password_confirm') return;
        const dt = by('dt','',labelTitle(label));
        const dd = by('dd','',displayValue(input));
        summary.append(dt,dd);
      }));
    };
    const render = () => {
      panels.forEach((p,i) => {p.hidden = current !== i;});
      review.hidden = current !== groups.length;
      const finalStep = current === groups.length;
      counter.textContent = `Etapa ${current+1} de ${groups.length+1}`;
      progress.setAttribute('aria-valuenow',String(current+1));
      bar.style.width = `${100*(current+1)/(groups.length+1)}%`;
      heading.textContent = finalStep ? 'Revise e confirme' : labelTitle(groups[current][0]);
      guideText.textContent = finalStep ? 'A ação só será executada após sua confirmação.' : 'Preencha este campo para continuar.';
      if (finalStep) renderReview();
      back.disabled = current === 0;
      next.hidden = finalStep;
    };
    back.addEventListener('click',() => {if(current > 0) {current--;render();shell.scrollIntoView({block:'start'});}});
    next.addEventListener('click',() => {
      if (!validStep(current)) return;
      if(current < groups.length) {current++;render();shell.scrollIntoView({block:'start'});}
    });
    form.addEventListener('submit',e => {
      for(let i=0;i<groups.length;i++) {
        // A etapa precisa estar visível antes de mostrar um campo inválido.
        current=i; render();
        if(!validStep(i)) {e.preventDefault();e.stopImmediatePropagation();return;}
      }
      current=groups.length; render();
      // Upload segmentado e ações POST originais continuam responsáveis pela submissão.
    },true);
    render();
  }
  addVisibility(document);
  document.querySelectorAll('form.setup-form').forEach(form => {
    configureNewPassword(form);
    guide(form);
  });
})();
