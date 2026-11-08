  <section class="int-section" id="paginas" data-int-section aria-labelledby="int-pages-title">
    <div class="int-section-head"><div><span class="int-eyebrow">CHECKLIST DA PUBLICAÇÃO</span><h3 id="int-pages-title">Verificador de Páginas</h3></div><details class="compact-help"><summary aria-label="Sobre o verificador de páginas">?</summary><div class="compact-help-content">Analisa o banco e os arquivos do próprio portal. Não acessa sites externos, não mede HTTP, não consulta o Google e não garante indexação. Selecione uma notícia publicada, uma categoria ou a página inicial.</div></details></div>
    <form action="/admin.php#paginas" method="get" class="int-page-picker" data-page-inspection>
      <input type="hidden" name="action" value="integrations">
      <label for="int-page-choice">Página a verificar</label>
      <div class="int-page-controls"><select id="int-page-choice" name="page" aria-label="Selecionar página">
        <?php foreach($options as $item): ?><option value="<?=h($item['value'])?>" <?=$choice===$item['value']?'selected':''?>><?=h($item['label'])?></option><?php endforeach; ?>
      </select><button class="btn primary" type="submit">Verificar</button></div>
    </form>
    <div class="int-page-overview" aria-label="Resumo da verificação">
      <div><small>ANALISANDO</small><strong><?=h($subject['title'])?></strong><span><?= $report['kind']==='article'?'Notícia publicada':($report['kind']==='category'?'Categoria':'Página inicial') ?></span></div>
      <div class="int-page-stats"><span class="int-page-stat int-okay"><b><?=(int)$report['counts']['ok']?></b>OK local</span><span class="int-page-stat int-warn"><b><?=(int)$report['counts']['attention']?></b>Avisos</span><span class="int-page-stat int-error"><b><?=(int)$report['counts']['error']?></b>Erros</span></div>
    </div>
    <?php if($report['canonical']!==''): ?><div class="int-page-address"><span>URL prevista</span><code><?=h($report['canonical'])?></code><a href="<?=h($report['canonical'])?>" target="_blank" rel="noopener noreferrer" aria-label="Abrir página publicada">↗</a></div><?php endif; ?>
    <div class="int-page-results" role="list">
      <?php foreach($report['checks'] as $i=>$check): ?>
        <div class="int-page-row int-page-<?=h($check['status'])?>" role="listitem">
          <span class="int-page-symbol" aria-hidden="true"><?=['ok'=>'✓','attention'=>'!','error'=>'×','unknown'=>'·'][$check['status']]??'·'?></span>
          <span class="int-page-label"><?=h($check['label'])?></span>
          <span class="int-page-status"><?=['ok'=>'OK','attention'=>'Revisar','error'=>'Erro','unknown'=>'Não testado'][$check['status']]??'?'?></span>
          <details class="compact-help"><summary aria-label="Explicação de <?=h($check['label'])?>">?</summary><div class="compact-help-content"><?=h($check['detail'])?></div></details>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="button" class="btn outline int-open-inspection" data-int-open-panel="google-url-inspection">⌕ Abrir inspeção Google em janela</button>
    <section class="int-index-api" id="google-url-inspection" aria-labelledby="int-index-title">
      <div class="int-method-heading"><strong id="int-index-title">Índice Google · notícia</strong><details class="compact-help"><summary aria-label="Sobre inspeção de URLs no Google">?</summary><div class="compact-help-content">A inspeção usa a API oficial do Search Console e mostra a última versão conhecida pelo índice Google. Não testa a URL ao vivo, não solicita indexação e não garante posição em resultados. Exige credencial Google e permissão para a propriedade selecionada na aba Google. Consultas consomem cota.</div></details></div>
      <?php if($inspectionTarget!==null): ?>
        <p class="int-sc-hint">Notícia selecionada: <strong><?=h($inspectionTarget['title'])?></strong>. Consulta individual e manual com a propriedade <strong><?=h($searchConsoleProps[$searchConsoleMode]??'')?></strong>.</p>
        <form method="post" action="/admin.php?action=integrations_search_console_inspect" data-url-inspection-form><?=csrf_field() ?>
          <input type="hidden" name="inspection_page" value="<?=h($choice)?>">
          <button type="submit" class="btn primary" <?=!$ga4CredentialsReady || ($searchConsoleProps[$searchConsoleMode]??'')===''?'disabled':''?>>⌕ Consultar indexação</button>
          <small data-url-inspection-wait role="status" aria-live="polite" hidden>Verificando índice Google…</small>
        </form>
        <?php if($inspectionLast!==null): ?>
          <?php $label=['indexed'=>'Indexada segundo Google','excluded'=>'Não indexada (excluída)','issue'=>'Problema de indexação','unknown'=>'Situação não conclusiva'][$inspectionLast['state']]??'Situação não conclusiva'; ?>
          <div class="int-index-result int-index-<?=h($inspectionLast['state'])?>" role="status">
            <strong><?=h($label)?></strong>
            <small>Última consulta: <?=date('d/m/Y H:i',(int)$inspectionLast['checked_at'])?></small>
            <?php if($inspectionLast['coverage']!==''): ?><p><b>Cobertura:</b> <?=h($inspectionLast['coverage'])?></p><?php endif; ?>
            <div class="int-index-facts">
              <span><b>Rastreamento:</b> <?=h(['ALLOWED'=>'Permitido','DISALLOWED'=>'Bloqueado','UNKNOWN'=>'Não informado'][$inspectionLast['robots']]??'Não informado')?></span>
              <span><b>Indexação:</b> <?=h(['INDEXING_ALLOWED'=>'Permitida','BLOCKED_BY_META_TAG'=>'Bloqueada por metatag','BLOCKED_BY_HTTP_HEADER'=>'Bloqueada por cabeçalho','UNKNOWN'=>'Não informada'][$inspectionLast['indexing']]??'Não informada')?></span>
              <span><b>Busca:</b> <?=h(['SUCCESSFUL'=>'Bem-sucedida','NOT_FOUND'=>'404','SOFT_404'=>'Soft 404','BLOCKED_ROBOTS_TXT'=>'Bloqueada pelo robots','SERVER_ERROR'=>'Erro do servidor','UNKNOWN'=>'Não informada'][$inspectionLast['fetch']]??$inspectionLast['fetch'])?></span>
              <span><b>Último rastreamento:</b> <?=h($inspectionLast['last_crawl']!==''?$inspectionLast['last_crawl']:'Não informado')?></span>
            </div>
            <?php if($inspectionLast['google_canonical']!==''): ?><p><b>Canônica Google:</b> <?=h($inspectionLast['google_canonical'])?></p><?php endif; ?>
            <?php if($inspectionLast['user_canonical']!==''): ?><p><b>Canônica declarada:</b> <?=h($inspectionLast['user_canonical'])?></p><?php endif; ?>
            <?php if($inspectionLast['recommendations']!==[]): ?><details class="int-index-tips"><summary>Ver <?=count($inspectionLast['recommendations'])?> orientação(ões)</summary><div><?php foreach($inspectionLast['recommendations'] as $tip): ?><p><?=h($tip)?></p><?php endforeach; ?></div></details><?php endif; ?>
            <small>Resultado referente à versão armazenada no índice do Google. Não é uma análise HTTP atual.</small>
          </div>
        <?php else: ?><p class="int-dns-idle">Esta notícia ainda não foi consultada nesta sessão.</p><?php endif; ?>
      <?php else: ?><p class="int-sc-hint">Selecione acima uma notícia real e publicada, com URL HTTPS e indexação habilitada, para consultar seu estado no Google. Páginas de demonstração não são elegíveis.</p><?php endif; ?>
    </section>
    <div class="int-page-links"><a class="btn outline small" href="<?=h(admin_link('seo'))?>">SEO editorial</a><a class="btn outline small" href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">Search Console ↗</a></div>
    <div class="int-section-note"><span class="int-note-icon" aria-hidden="true">ⓘ</span><p>O checklist acima é local. A consulta de indexação é uma verificação externa separada e sob demanda, usando o estado conhecido pelo Google, não teste de URL ao vivo.</p></div>
  </section>
