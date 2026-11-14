<?php
declare(strict_types=1);
function igua_health_admin_view(PDO $db): void {
    $checks=igua_health_checks($db);
    $counts=['ok'=>0,'warning'=>0,'error'=>0,'info'=>0];
    $attention=[];
    $normal=[];
    foreach ($checks as $check) {
        $status=(string)($check['status']??'info');
        if (!array_key_exists($status,$counts)) $status='info';
        $counts[$status]++;
        if (in_array($status,['warning','error'],true)) $attention[]=$check;
        else $normal[]=$check;
    }
    $latest=list_backups()[0]??null;
    ?>
    <section class="panel health-intro health-intro-compact">
      <div class="panel-heading"><div class="compact-heading"><h2>Estado do ambiente</h2><details class="compact-help"><summary aria-label="Ajuda sobre a verificação de saúde">?</summary><div class="compact-help-content">Diagnóstico sob demanda do PHP, banco e armazenamento. Não testa entrega real de e-mails, cron, servidores externos ou uma restauração completa.</div></details></div><a class="btn outline small" href="/admin.php?action=health">↻ Verificar</a></div>
      <div class="health-summary" aria-label="Resumo dos diagnósticos"><span class="health-mark good">✓ <?= (int)$counts['ok'] ?> OK</span><span class="health-mark warn">! <?= (int)$counts['warning'] ?> atenção</span><span class="health-mark bad">× <?= (int)$counts['error'] ?> erros</span><span class="health-mark neutral">i <?= (int)$counts['info'] ?> informações</span></div>
      <p class="muted tiny health-updated"><?= h(date('d/m/Y H:i')) ?> · PHP <?= h(PHP_VERSION) ?> · v<?= h(igua_brand_version()) ?></p>
    </section>
    <section class="panel health-priority" aria-labelledby="health-priority-title">
      <div class="panel-heading"><h2 id="health-priority-title">Pontos de atenção</h2><span class="muted tiny"><?= count($attention) ?> registro(s)</span></div>
      <?php if (!$attention): ?><p class="health-quiet">✓ Nenhum problema identificado nas verificações disponíveis.</p><?php endif; ?>
      <?php foreach ($attention as $check): ?>
        <article class="health-row health-row-<?= h($check['status']) ?>"><div class="health-row-title"><strong><?= h($check['name']) ?></strong><span class="health-mark <?= $check['status']==='error'?'bad':'warn' ?>"><?= $check['status']==='error'?'Erro':'Atenção' ?></span></div><p><?= h($check['detail']) ?></p><?php if ($check['suggest']!==''): ?><small><?= h($check['suggest']) ?></small><?php endif; ?></article>
      <?php endforeach; ?>
    </section>
    <details class="panel health-fold" id="health-normal-details"><summary>Verificações normais e informações <span class="muted tiny"><?= count($normal) ?> itens</span></summary><div class="health-fold-body">
      <?php foreach ($normal as $check): ?><div class="health-ok-row"><span class="health-mark <?= $check['status']==='ok'?'good':'neutral' ?>"><?= $check['status']==='ok'?'✓':'i' ?></span><strong><?= h($check['name']) ?></strong><details class="compact-help"><summary aria-label="Detalhes de <?= h($check['name']) ?>">?</summary><div class="compact-help-content"><?= h($check['detail']) ?><?php if ($check['suggest']!==''): ?><p><?= h($check['suggest']) ?></p><?php endif; ?></div></details></div><?php endforeach; ?>
    </div></details>
    <section class="panel health-backup-compact"><div class="panel-heading"><div class="compact-heading"><h2>Integridade do backup</h2><details class="compact-help"><summary aria-label="Ajuda sobre verificação de backup">?</summary><div class="compact-help-content">Confere a estrutura do arquivo local mais recente. Não restaura conteúdo e não substitui um teste real de restauração em ambiente separado.</div></details></div></div>
      <?php if ($latest): ?><form method="post" action="/admin.php?action=health_verify" class="health-inline-form"><?= csrf_field() ?><input type="hidden" name="filename" value="<?= h($latest['name']) ?>"><div><strong><?= h($latest['name']) ?></strong><small class="muted health-backup-date"><?= number_format($latest['size']/1048576,2,',','.') ?> MB · <?= h($latest['date']) ?></small></div><button class="btn outline small" type="submit">Validar</button></form>
      <?php else: ?><p class="alert warning">Nenhum backup disponível. Gere um em Configurações → Backups.</p><?php endif; ?>
    </section>
    <details class="panel health-fold" id="health-events"><summary>Eventos técnicos recentes</summary><div class="health-fold-body"><p class="muted tiny">Sem IP, senhas ou conteúdo das notícias.</p>
      <?php $lines=igua_health_log_lines(); if($lines): ?><div class="health-log" role="region" aria-label="Registros técnicos"><?php foreach($lines as $line): ?><code><?= h($line) ?></code><?php endforeach; ?></div>
      <?php else: ?><p class="muted">Nenhuma falha registrada por este monitor. Isso não comprova ausência de erros.</p><?php endif; ?></div></details>
    <?php
}
