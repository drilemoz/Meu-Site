    <details class="int-service int-search" id="google-search">
      <summary><span class="int-service-icon" aria-hidden="true">⌕</span><span class="int-service-copy"><strong>Search Console</strong><small>Tráfego da Busca · DNS e metatag</small></span><span class="int-state <?=h($searchStatusClass)?>"><?=h($searchStatus)?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-guide-row"><strong>Configurar Google Search Console</strong><details class="compact-help int-guide-help"><summary aria-label="Guia completo: Search Console, DNS, metatag e permissões" title="Como configurar o Search Console?">?</summary><div class="compact-help-content">
          <h3>Onde verificar a propriedade</h3><ol><li>Acesse <a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">Google Search Console</a> e escolha <strong>Adicionar propriedade</strong>.</li><li>Para <strong>Domínio</strong>, copie o TXT <code>google-site-verification=...</code> e cadastre no DNS do domínio. Para <strong>Prefixo de URL</strong>, é possível usar a metatag fornecida pelo Google.</li><li>Espere a propagação DNS e finalize a verificação dentro do Search Console. Encontrar um TXT no portal não confirma a propriedade na sua conta.</li></ol>
          <h3>Relatórios e inspeção por API</h3><ol><li>No <a href="https://console.cloud.google.com/apis/library/searchconsole.googleapis.com" target="_blank" rel="noopener noreferrer">Google Cloud</a>, ative a Search Console API.</li><li>Em <strong>Configurações → Usuários e permissões</strong> da propriedade, adicione o e-mail da mesma conta de serviço configurada no GA4, com acesso à propriedade.</li><li>Escolha no painel a propriedade correspondente: domínio ou prefixo HTTPS. Consulte os dados manualmente.</li></ol>
          <p><strong>Indexação:</strong> a inspeção pela API informa a última versão conhecida pelo Google; não testa o site ao vivo e não envia pedido de indexação.</p>
          <p><a href="https://support.google.com/webmasters/answer/34592?hl=pt-BR" target="_blank" rel="noopener noreferrer">Ajuda oficial: adicionar propriedade</a> · <a href="https://developers.google.com/webmaster-tools/v1/urlInspection.index/inspect" target="_blank" rel="noopener noreferrer">API de inspeção</a></p>
        </div></details></div>

        <section class="int-sc-api" aria-labelledby="int-sc-api-title">
          <div class="int-method-heading"><strong id="int-sc-api-title">Desempenho na Pesquisa Google</strong><details class="compact-help"><summary aria-label="Ajuda sobre relatórios Search Console">?</summary><div class="compact-help-content">Consulta real à API Search Console, apenas ao clicar. Exige a Search Console API habilitada no Google Cloud e que a conta de serviço Google tenha acesso à propriedade. O TXT do DNS não concede essa permissão. Relatórios podem ter atraso, anonimização e linhas omitidas.</div></details></div>
          <div class="int-live-status"><span class="int-live-badge <?= $ga4CredentialsReady?'is-ready':'is-waiting' ?>"><?= $ga4CredentialsReady?'Conta Google cadastrada':'Conta Google não cadastrada' ?></span><span class="int-live-badge <?= $searchApiLast!==null?'is-ready':'is-waiting' ?>"><?= $searchApiLast!==null?'Consulta autorizada':'API não consultada' ?></span></div>
          <p class="int-sc-hint">Utiliza a conta de serviço privada cadastrada na seção GA4. É necessário adicionar o e-mail dela como usuário no Search Console, mesmo que o DNS já esteja verificado.</p>
          <?php if($ga4ServiceEmail!==''): ?><div class="int-sc-identity"><label for="int-sc-email">E-mail da conta Google (público, sem chave privada)</label><div><input id="int-sc-email" value="<?=h($ga4ServiceEmail)?>" readonly aria-label="E-mail da conta de serviço"><button class="btn outline" type="button" data-int-copy="int-sc-email">Copiar</button></div></div><?php endif; ?>
          <form method="post" action="/admin.php?action=integrations_save_search_console_mode" class="int-sc-form"><?=csrf_field() ?>
            <label for="int-sc-property">Propriedade no Search Console</label>
            <div class="int-sc-controls"><select id="int-sc-property" name="search_console_property_mode">
              <?php foreach(['domain'=>'Domínio','prefix'=>'Prefixo HTTPS'] as $mode=>$label): ?><option value="<?=h($mode)?>" <?= $mode===$searchConsoleMode?'selected':'' ?>><?=h($label)?> · <?=h($searchConsoleProps[$mode]!==''?$searchConsoleProps[$mode]:'URL não configurada')?></option><?php endforeach; ?>
            </select><button type="submit" class="btn outline">Salvar</button></div>
          </form>
          <form method="post" action="/admin.php?action=integrations_search_console_test" data-search-console-test-form><?=csrf_field() ?>
            <button class="btn primary" type="submit" <?= !$ga4CredentialsReady||($searchConsoleProps[$searchConsoleMode]??'')===''?'disabled':'' ?>>⌕ Consultar dados reais</button>
            <small data-search-console-wait role="status" aria-live="polite" hidden>Consultando Search Console…</small>
          </form>
          <?php if($searchApiLast!==null): ?>
          <div class="int-sc-report" role="status" aria-label="Último relatório autorizado da Search Console API">
            <div class="int-sc-period"><strong><?=h($searchApiLast['property'])?></strong><small>Dados consolidados: <?=h($searchApiLast['start'])?> a <?=h($searchApiLast['end'])?> · consulta <?=date('d/m H:i',(int)$searchApiLast['checked_at'])?></small></div>
            <?php if($searchApiLast['summary']!==null): ?><div class="int-sc-metrics">
              <div><strong><?=number_format((int)$searchApiLast['summary']['clicks'],0,',','.')?></strong><span>Cliques</span></div>
              <div><strong><?=number_format((int)$searchApiLast['summary']['impressions'],0,',','.')?></strong><span>Impressões</span></div>
              <div><strong><?=number_format((float)$searchApiLast['summary']['ctr'],1,',','.')?>%</strong><span>CTR</span></div>
              <div><strong><?=number_format((float)$searchApiLast['summary']['position'],1,',','.')?></strong><span>Posição média</span></div>
            </div><?php else: ?><p class="int-sc-hint">A API autorizou o acesso, mas não retornou dados agregados neste período. Isso não comprova falha de indexação.</p><?php endif; ?>
            <?php foreach(['queries'=>'Pesquisas principais','pages'=>'Páginas na busca'] as $listKey=>$listLabel): ?>
            <details class="int-sc-list"><summary><?=h($listLabel)?> · <?=count($searchApiLast[$listKey])?> registros</summary>
              <?php if($searchApiLast[$listKey]===[]): ?><p class="int-sc-hint">Nenhuma linha retornada. O Google pode omitir consultas ou páginas.</p><?php endif; ?>
              <?php foreach($searchApiLast[$listKey] as $item): ?><div class="int-sc-row"><span title="<?=h($item['label'])?>"><?=h($item['label'])?></span><small><?= (int)$item['clicks']?> cliques · <?= (int)$item['impressions']?> imp.</small></div><?php endforeach; ?>
            </details><?php endforeach; ?>
            <p class="int-sc-hint">Somente resultados resumidos desta sessão; não são dados GA4 nem comprovação de indexação individual.</p>
          </div><?php else: ?><p class="int-dns-idle">Nenhum relatório consultado nesta sessão. Selecione a propriedade e autorize a conta de serviço para obter dados reais.</p><?php endif; ?>
        </section>
        <div class="int-method-heading"><strong>Verificação por DNS (TXT)</strong><details class="compact-help"><summary aria-label="Ajuda sobre verificação por DNS">?</summary><div class="compact-help-content">Propriedades de domínio usam um registro TXT criado no painel DNS. O teste procura o registro público; apenas o Search Console confirma a propriedade na conta Google. A propagação pode levar algum tempo.</div></details></div>
        <div class="int-dns-domain"><span>Domínio a consultar</span><strong><?=h($dnsDomain!==''?$dnsDomain:'URL oficial não configurada')?></strong><?php if($dnsDomain===''): ?><a href="<?=h(admin_link('settings'))?>">Configurar HTTPS ↗</a><?php endif; ?></div>
        <form method="post" action="/admin.php?action=integrations_save_dns" class="int-form"><?=csrf_field() ?>
          <label for="int-dns-token">TXT esperado <small>(opcional)</small><input id="int-dns-token" name="google_dns_verification_txt" value="<?=h($cfg['google_dns_verification_txt']??'')?>" maxlength="300" autocomplete="off" spellcheck="false" placeholder="google-site-verification=..."></label>
          <div class="int-service-actions"><button type="submit" class="btn outline">Salvar TXT</button><button type="button" class="btn outline" data-int-copy="int-dns-token" aria-label="Copiar registro DNS TXT">Copiar TXT</button><details class="compact-help"><summary aria-label="Por que informar o registro esperado?">?</summary><div class="compact-help-content">Sem o valor exato, detectamos somente a presença de um registro Google, que pode pertencer a outra conta. Copie o TXT fornecido pelo Search Console e salve para conferir uma correspondência exata. Limpe o campo e salve para voltar ao teste genérico.</div></details></div>
        </form>
        <form method="post" action="/admin.php?action=integrations_check_dns" class="int-dns-check-form" data-dns-test-form><?=csrf_field() ?>
          <button class="btn primary" type="submit" <?= $dnsDomain===''?'disabled':'' ?>>⌕ Consultar DNS</button>
          <small data-dns-wait role="status" aria-live="polite" hidden>Consultando registro TXT…</small>
        </form>
        <?php if($dnsLast!==null): ?>
          <div class="int-dns-result int-dns-<?=h($dnsStatus)?>" role="status">
            <strong><?=h(['matched'=>'TXT esperado localizado','found'=>'Registro Google encontrado','missing'=>'Nenhum TXT do Google','mismatch'=>'TXT não corresponde','unavailable'=>'Consulta indisponível','no_domain'=>'Domínio não configurado','invalid_config'=>'TXT inválido'][$dnsStatus]??'Consulta DNS')?></strong>
            <p><?=h((string)($dnsLast['detail']??''))?></p>
            <small><?=h($dnsDomain)?> · <?=date('d/m H:i',(int)$dnsLast['checked_at'])?> · <?= (int)($dnsLast['count']??0) ?> registro(s) de verificação</small>
          </div>
        <?php else: ?><div class="int-dns-idle">DNS ainda não consultado nesta sessão. O status do Google não foi confirmado.</div><?php endif; ?>
        <details class="int-method-secondary"><summary>Verificação por metatag <small>(prefixo de URL)</small></summary>
          <form method="post" action="/admin.php?action=integrations_save_verification" class="int-form"><?=csrf_field() ?>
            <label for="int-site-token">Código da metatag<input id="int-site-token" name="google_site_verification" value="<?=h($cfg['google_site_verification'])?>" maxlength="200" autocomplete="off" spellcheck="false" placeholder="Cole o código de verificação"></label>
            <div class="int-service-actions"><button class="btn primary" type="submit">Publicar tag</button><a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">Conferir no Google ↗</a></div>
          </form>
        </details>
        <a class="int-dns-console-link" href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">Abrir Search Console ↗</a>
      </div>
    </details>
