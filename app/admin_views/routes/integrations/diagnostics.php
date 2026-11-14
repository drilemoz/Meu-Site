  <section class="int-section" id="diagnostico" data-int-section aria-labelledby="int-diag-title">
    <div class="int-section-head"><div><span class="int-eyebrow">ESTADO E ÚLTIMAS CONSULTAS</span><h3 id="int-diag-title">Diagnóstico central</h3></div><details class="compact-help"><summary aria-label="Ajuda do diagnóstico">?</summary><div class="compact-help-content">O painel combina configurações locais e registros resumidos das consultas manuais feitas nesta sessão. Nenhuma API é chamada ao abrir esta aba. Um serviço preparado não significa conexão validada; consulta concluída não prova aprovação do provedor. O histórico some ao encerrar a sessão.</div></details></div>
    <div class="int-diag-overview"><div><strong><?= $pass ?> de <?= $total ?></strong><span>checagens locais atendidas</span></div><a href="/admin.php?action=integrations&amp;check=1#diagnostico" class="btn outline">↻ Conferir configurações</a></div>
    <div class="int-health-intro"><strong>Estado das APIs</strong><span>Preparação local e última operação registrada nesta sessão, sem consultas automáticas.</span></div>
    <div class="int-health-grid" role="list" aria-label="Diagnóstico por integração">
    <?php foreach($integrationDiag as $diag): ?>
      <article class="int-health-card" role="listitem">
        <div class="int-health-card-head"><strong><?=h($diag['name'])?></strong><span class="int-state <?=$diag['ready']?'int-ready':'int-off'?>"><?=$diag['ready']?'Pré-requisitos locais':'Configuração pendente'?></span></div>
        <div class="int-health-last <?=h($diag['status'])?>"><span aria-hidden="true"><?= $diag['status']==='success'?'✓':($diag['status']==='error'?'!':'·') ?></span><?=h(igua_int_diag_status_label($diag['status'],$diag['reason']))?></div>
        <small>Última operação: <?=$diag['checked_at']!==null?date('d/m/Y H:i',(int)$diag['checked_at']):'não registrada nesta sessão'?></small>
        <?php if($diag['status']==='error'): ?><p class="int-health-advice"><?=h(igua_int_diag_error_tip($diag['reason']))?></p>
        <?php elseif(!$diag['ready']): ?><p class="int-health-advice"><?=h($diag['hint'])?></p><?php endif; ?>
        <a class="int-health-link" href="#<?=h($diag['anchor'])?>">Abrir integração ↗</a>
      </article>
    <?php endforeach; ?>
    </div>
    <details class="int-health-history"><summary>Histórico recente · <?=count($integrationHistory)?> operações</summary>
      <?php if(!$integrationHistory): ?><p>Nenhuma consulta registrada nesta sessão desde a instalação desta versão.</p><?php else: ?>
      <div class="int-health-events" role="list">
      <?php foreach($integrationHistory as $event): ?>
        <?php if(!is_array($event) || !isset($integrationDiag[$event['service']??'']) || !in_array($event['status']??'', ['success','error'],true) || !is_int($event['at']??null))continue; ?>
        <div role="listitem"><strong><?=h($integrationDiag[$event['service']]['name'])?></strong><span><?=h(igua_int_diag_status_label((string)$event['status']))?></span><time><?=date('d/m/Y H:i',(int)$event['at'])?></time></div>
      <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <small>Até 12 operações, somente nesta sessão administrativa. Sem tokens, respostas completas ou dados pessoais da conta Google.</small>
    </details>
    <div class="int-checks" role="list">
      <?php foreach($rows as $row): ?><div class="int-check" role="listitem"><span class="int-check-mark <?= $row['ok']?'good':'attention' ?>" aria-hidden="true"><?= $row['ok']?'✓':'!' ?></span><div><strong><?=h($row['name'])?></strong><p><?=h($row['detail'])?></p></div><span class="int-check-label"><?= $row['ok']?'OK local':'Atenção' ?></span></div><?php endforeach; ?>
    </div>
    <?php if($dnsLast!==null): ?><div class="int-dns-diag"><strong>DNS Search Console</strong><span><?=h(['matched'=>'TXT esperado encontrado','found'=>'TXT Google encontrado','missing'=>'Não encontrado','mismatch'=>'Divergente','unavailable'=>'Indisponível','no_domain'=>'Sem domínio'][''.$dnsStatus]??'Não confirmado')?></span><small>Consulta sob demanda, não confirmação de propriedade Google.</small></div><?php endif; ?>
    <?php foreach(['mobile'=>'celular','desktop'=>'computador'] as $strategy=>$deviceLabel): ?>
      <?php if(($pageSpeedResults[$strategy]??null)!==null): ?><div class="int-dns-diag"><strong>PageSpeed <?=h($deviceLabel)?></strong><span><?= (int)$pageSpeedResults[$strategy]['score'] ?>/100</span><small>Última consulta Lighthouse nesta sessão, não uma métrica de usuários reais.</small></div><?php endif; ?>
    <?php endforeach; ?>
    <div class="int-section-note"><span class="int-note-icon" aria-hidden="true">ⓘ</span><p>O painel não testa conexões automaticamente. Datas e erros são relativos às operações desta sessão; abra cada serviço e execute a consulta manual quando necessário. Alguns dados podem estar desatualizados após mudanças de permissão no Google.</p></div>
  </section>
