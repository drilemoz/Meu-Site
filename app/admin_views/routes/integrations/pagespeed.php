    <details class="int-service int-speed" id="google-pagespeed">
      <summary><span class="int-service-icon" aria-hidden="true">◷</span><span class="int-service-copy"><strong>PageSpeed Insights</strong><small>4 notas Lighthouse · celular + PC</small></span><span class="int-state <?= $pageSpeedAny?'int-ready':'int-off' ?>"><?= $pageSpeedAny?'Medido':'Não testado' ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-guide-row"><strong>Entender o PageSpeed</strong><details class="compact-help int-guide-help"><summary aria-label="Guia completo: PageSpeed, notas, LCP, CLS e problemas comuns" title="Como funciona o PageSpeed?">?</summary><div class="compact-help-content">
          <h3>Como executar e interpretar</h3><ol><li>Configure a URL HTTPS oficial do portal em <strong>Geral e SEO</strong>.</li><li>Escolha <strong>Medir celular</strong> ou <strong>Medir computador</strong>; as estratégias são independentes.</li><li>As notas de <strong>desempenho, acessibilidade, boas práticas e SEO técnico</strong> vão de 0 a 100, quando retornadas. 90–100 é bom, 50–89 precisa de atenção e abaixo de 50 é ruim na escala Lighthouse.</li></ol>
          <h3>Métricas úteis</h3><p><strong>LCP:</strong> tempo até o maior elemento visível carregar. <strong>CLS:</strong> estabilidade visual (movimentos inesperados). <strong>FCP:</strong> primeiro conteúdo pintado. Valores mudam conforme conexão, dispositivo e teste.</p>
          <p>As notas Lighthouse são medições de laboratório; não provam experiência de todos os visitantes nem indexação no Google. A API pode aplicar limites; consultas frequentes podem exigir chave. O portal executa análises apenas sob demanda.</p>
          <p><a href="https://pagespeed.web.dev/" target="_blank" rel="noopener noreferrer">Abrir PageSpeed</a> · <a href="https://developers.google.com/speed/docs/insights/v5/get-started" target="_blank" rel="noopener noreferrer">API oficial</a></p>
        </div></details></div>

        <div class="int-hint-row"><span>Teste cada dispositivo separadamente, somente quando solicitado</span><details class="compact-help"><summary aria-label="Ajuda PageSpeed Insights">?</summary><div class="compact-help-content">Usa a URL pública HTTPS salva em Geral e SEO. O Google executa simulações Lighthouse com perfis diferentes de celular e computador e retorna desempenho, acessibilidade, boas práticas e SEO quando disponíveis. As notas são de laboratório, não medições da experiência de todos os visitantes. Cada botão pode acionar uma requisição e consumir cota.</div></details></div>
        <div class="int-speed-target"><span>URL analisada</span><strong><?=h($pageSpeedUrl!==''?$pageSpeedUrl:'Configure a URL HTTPS em Geral e SEO')?></strong><?php if($pageSpeedUrl===''): ?><a href="<?=h(admin_link('settings'))?>">Configurar URL ↗</a><?php endif; ?></div>
        <div class="int-speed-tabs" role="tablist" data-int-speed-tabs aria-label="Escolha o dispositivo para a medição PageSpeed">
          <button type="button" class="int-speed-tab" id="int-speed-tab-mobile" data-int-speed-tab="mobile" role="tab" aria-selected="true" aria-controls="int-speed-mobile">▣ Celular</button>
          <button type="button" class="int-speed-tab" id="int-speed-tab-desktop" data-int-speed-tab="desktop" role="tab" aria-selected="false" aria-controls="int-speed-desktop">▤ Computador</button>
        </div>
        <div class="int-speed-grid" data-int-speed-grid>
          <?php foreach(['mobile'=>'Celular','desktop'=>'Computador'] as $strategy=>$deviceLabel): ?>
            <?php $speedResult=$pageSpeedResults[$strategy]??null; ?>
            <section class="int-speed-device" id="int-speed-<?=h($strategy)?>" data-int-speed-panel="<?=h($strategy)?>" role="tabpanel" aria-labelledby="int-speed-tab-<?=h($strategy)?>" aria-label="Diagnóstico PageSpeed para <?=h($deviceLabel)?>">
              <div class="int-speed-device-head"><strong><?= $strategy==='mobile'?'▣':'▤' ?> <?=h($deviceLabel)?></strong><small><?= $speedResult!==null?'Última medição':'Ainda não testado' ?></small></div>
              <?php if($speedResult!==null): ?>
                <div class="int-speed-report" role="status" aria-label="Último teste PageSpeed em <?=h($deviceLabel)?>">
                  <div class="int-speed-score <?= $speedResult['score']>=90?'is-good':($speedResult['score']>=50?'is-mid':'is-low') ?>"><strong><?= (int)$speedResult['score'] ?></strong><span>/100</span></div>
                  <div class="int-speed-summary"><strong><?=h($deviceLabel)?></strong><small>Lighthouse · <?=date('d/m/Y H:i',(int)$speedResult['checked_at'])?></small><span><?= $speedResult['score']>=90?'Boa nota no laboratório':($speedResult['score']>=50?'Oportunidades de melhoria':'Atenção ao desempenho') ?></span></div>
                </div>
                <?php $scores=$speedResult['scores']??['performance'=>$speedResult['score']]; ?>
                <div class="int-speed-categories" aria-label="Notas Lighthouse em <?=h(strtolower($deviceLabel))?>">
                  <?php foreach(igua_pagespeed_categories() as $categoryKey=>$categoryLabel): ?>
                    <?php $scoreValue=$scores[$categoryKey]??null; ?>
                    <div class="int-speed-category <?=is_int($scoreValue)?($scoreValue>=90?'is-good':($scoreValue>=50?'is-mid':'is-low')):'is-na'?>">
                      <strong><?=is_int($scoreValue)?(int)$scoreValue:'—'?></strong>
                      <span><?=h($categoryLabel)?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
                <?php if(!empty($speedResult['metrics'])): ?><div class="int-speed-metrics" role="list">
                  <?php foreach($speedResult['metrics'] as $metric): ?><div role="listitem"><small><?=h((string)$metric['label'])?></small><strong><?=h((string)$metric['value'])?></strong></div><?php endforeach; ?>
                </div><?php endif; ?>
                <?php if(!empty($speedResult['recommendations'])): ?>
                  <details class="int-speed-recommendations">
                    <summary>Revisar <?=count($speedResult['recommendations'])?> sugestões <span aria-hidden="true">⌄</span></summary>
                    <div class="int-speed-fix-list">
                      <?php foreach($speedResult['recommendations'] as $recommendation): ?>
                      <div class="int-speed-fix">
                        <small><?=h((string)$recommendation['category'])?> · auditoria <?= (int)$recommendation['score']?>/100</small>
                        <strong><?=h((string)$recommendation['title'])?></strong>
                        <p><?=h((string)$recommendation['tip'])?></p>
                      </div>
                      <?php endforeach; ?>
                    </div>
                  </details>
                <?php else: ?><p class="int-speed-fix-empty">Sem sugestões reconhecidas nesta análise. Isso não garante ausência de problemas.</p><?php endif; ?>
              <?php else: ?><p class="int-speed-empty">Sem medição de <?=h(strtolower($deviceLabel))?>. Inicie o teste abaixo.</p><?php endif; ?>
              <form method="post" action="/admin.php?action=integrations_pagespeed_test" data-pagespeed-test-form><?=csrf_field() ?>
                <input type="hidden" name="pagespeed_strategy" value="<?=h($strategy)?>">
                <button type="submit" class="btn primary" <?= $pageSpeedUrl===''?'disabled':'' ?>>◷ Medir <?=h(strtolower($deviceLabel))?></button>
                <span data-pagespeed-wait role="status" aria-live="polite" hidden>Google analisando <?=h(strtolower($deviceLabel))?>…</span>
              </form>
            </section>
          <?php endforeach; ?>
        </div>
        <p class="int-speed-disclaimer">Notas de celular e computador usam configurações diferentes. Não representam todos os acessos reais e podem variar entre testes. O resultado fica disponível nesta sessão por até 1 hora. Uma categoria ausente é indicada por traço, não por nota zero. As sugestões aparecem somente quando o Lighthouse aponta a auditoria correspondente como reprovada.</p>
        <div class="int-service-actions"><a href="https://pagespeed.web.dev/" target="_blank" rel="noopener noreferrer">Abrir PageSpeed ↗</a><details class="compact-help"><summary aria-label="Como interpretar a nota">?</summary><div class="compact-help-content">90–100 é bom, 50–89 pede atenção e 0–49 é ruim na escala Lighthouse. SEO técnico não confirma indexação. As sugestões vêm de auditorias do relatório. Métricas de campo e laboratório não devem ser confundidas; cotas e falhas podem impedir consultas.</div></details></div>
      </div>
    </details>
    <div class="int-section-note"><span class="int-note-icon" aria-hidden="true">ⓘ</span><p>GA4, DNS e PageSpeed usam verificações sob demanda. Valores salvos, registros encontrados e notas Lighthouse não significam aprovação no Google.</p></div>
