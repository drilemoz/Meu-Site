<?php
$cfg['ga4_property_id']=$cfg['ga4_property_id']??'';
$ga4CredentialsReady=$ga4CredentialsReady??false;
$ga4Live=$ga4Live??null;
$pass=count(array_filter($rows,static fn($v)=>(bool)$v['ok']));
$total=count($rows);
$gaOn=$cfg['ga4_enabled']==='1' && analytics_ga4_measurement_id($cfg['ga4_measurement_id'])!=='';
$adOn=$cfg['adsense_publisher']!=='';
$tokenOn=$cfg['google_site_verification']!=='';
$dnsDomain=$dnsDomain??igua_search_dns_domain($cfg);
$dnsLast=$dnsLast??null;
$dnsConfigured=($cfg['google_dns_verification_txt']??'')!=='';
$dnsStatus=is_array($dnsLast)?(string)($dnsLast['status']??''):'';
$dnsFound=in_array($dnsStatus,['matched','found'],true);
$searchStatus=$dnsFound?'TXT detectado':($tokenOn?'Tag pronta':($dnsConfigured?'TXT salvo':'Não testado'));
$searchStatusClass=$dnsFound?'int-on':(($tokenOn||$dnsConfigured)?'int-ready':'int-off');
?>
<div class="int-hub" id="integrations-hub">
  <section class="int-hero" aria-labelledby="int-title">
    <div class="int-hero-orb" aria-hidden="true"></div>
    <div class="int-hero-top"><span class="int-overline"><span class="int-live-dot"></span> CENTRAL DE SERVIÇOS</span><span class="int-release">v<?=h(igua_brand_version())?></span></div>
    <h2 id="int-title">Um painel.<br><span>Todas as conexões.</span></h2>
    <p>Controle os serviços, confira a configuração e prepare novas integrações.</p>
    <div class="int-hero-metrics" aria-label="Resumo de configuração">
      <div><strong><?= $pass ?><small>/<?= $total ?></small></strong><span>checagens locais</span></div>
      <div><strong><?= (int)$gaOn+(int)$adOn+(int)$tokenOn ?></strong><span>serviços preparados</span></div>
      <a href="/admin.php?action=integrations&amp;check=1#diagnostico"><span aria-hidden="true">↗</span><span>Conferir<br>agora</span></a>
    </div>
  </section>
  <nav class="int-tabs" data-integrations-nav aria-label="Seções da Central de Integrações">
    <button type="button" class="is-active" aria-pressed="true" data-int-target="google"><span aria-hidden="true">◈</span> Google</button>
    <button type="button" aria-pressed="false" data-int-target="diagnostico"><span aria-hidden="true">◉</span> Diagnóstico</button>
    <button type="button" aria-pressed="false" data-int-target="paginas"><span aria-hidden="true">▤</span> Páginas</button>
    <button type="button" aria-pressed="false" data-int-target="catalogo"><span aria-hidden="true">✧</span> Catálogo</button>
  </nav>
  <section class="int-section" id="google" data-int-section aria-labelledby="int-google-title">
    <div class="int-section-head"><div><span class="int-eyebrow">ATIVOS E PREPARADOS</span><h3 id="int-google-title">Serviços Google</h3></div><details class="compact-help"><summary aria-label="Ajuda sobre as integrações">?</summary><div class="compact-help-content">Usamos os mesmos valores de Métricas e Publicidade. Nenhuma API é ativada sem configuração. Os estados abaixo são locais, não aprovação dos provedores.</div></details></div>
    <details class="int-service int-ga" id="google-ga4">
      <summary><span class="int-service-icon" aria-hidden="true">▥</span><span class="int-service-copy"><strong>Google Analytics 4</strong><small>Medição e consentimento</small></span><span class="int-state <?= $gaOn?'int-on':'int-off' ?>"><?= $gaOn?'Ativo':'Desligado' ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-hint-row"><span>Rastreamento configurado no portal</span><details class="compact-help"><summary aria-label="Ajuda de privacidade GA4">?</summary><div class="compact-help-content">O Google só carrega após consentimento explícito, conforme as preferências de privacidade. O painel não importa relatórios externos.</div></details></div>
        <form method="post" action="/admin.php?action=integrations_save_ga4" class="int-form"><?= csrf_field() ?>
          <label class="int-switch"><input type="checkbox" name="ga4_enabled" value="1" <?=$gaOn?'checked':''?>><span>Ativar GA4</span></label>
          <label for="int-ga-id">ID de medição<input id="int-ga-id" name="ga4_measurement_id" value="<?=h($cfg['ga4_measurement_id'])?>" maxlength="22" pattern="G-[a-zA-Z0-9]{4,20}" autocapitalize="characters" spellcheck="false" placeholder="G-XXXXXXXXXX"></label>
          <label for="int-ga-property">Propriedade GA4 (ID numérico)<input id="int-ga-property" name="ga4_property_id" inputmode="numeric" value="<?=h($cfg['ga4_property_id'])?>" maxlength="20" pattern="[0-9]{4,20}" autocomplete="off" placeholder="123456789"></label>
          <div class="int-service-actions"><button class="btn primary" type="submit">Salvar GA4</button><a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">Abrir Google ↗</a></div>
        </form>
        <div class="int-live-panel" aria-labelledby="int-live-title">
          <div class="int-live-heading"><span class="int-live-icon" aria-hidden="true">◉</span><div><strong id="int-live-title">Recebimento de dados</strong><small>Consulta autorizada · Últimos 30 minutos e 7 dias</small></div><details class="compact-help"><summary aria-label="Ajuda para o diagnóstico GA4">?</summary><div class="compact-help-content">O ID G- identifica o fluxo do site; a propriedade é numérica. A API verifica eventos da propriedade, que pode ter outros fluxos. Zero eventos não prova falha. É necessário habilitar a Analytics Data API e dar acesso de Leitor à conta de serviço.</div></details></div>
          <div class="int-live-status">
            <span class="int-live-badge <?= $ga4CredentialsReady?'is-ready':'is-waiting' ?>"><?= $ga4CredentialsReady?'Conta de serviço cadastrada':'Sem credencial para API' ?></span>
            <span class="int-live-badge <?= $cfg['ga4_property_id']!==''?'is-ready':'is-waiting' ?>"><?= $cfg['ga4_property_id']!==''?'Propriedade informada':'Propriedade pendente' ?></span>
          </div>
          <?php if($ga4Live!==null): ?>
            <div class="int-live-result" role="status" aria-live="polite">
              <div class="int-live-result-title"><span class="int-live-pulse" aria-hidden="true"></span>
                <strong><?= $ga4Live['status']==='realtime'?'Eventos confirmados em tempo real':($ga4Live['status']==='recent'?'Dados confirmados nos últimos 7 dias':'Nenhum evento retornado no período') ?></strong>
              </div>
              <div class="int-live-values">
                <div><strong><?= (int)$ga4Live['realtime_events'] ?></strong><span>Eventos · 30 min</span></div>
                <div><strong><?= (int)$ga4Live['realtime_users'] ?></strong><span>Usuários ativos</span></div>
                <div><strong><?= (int)$ga4Live['recent_events'] ?></strong><span>Eventos · 7 dias</span></div>
              </div>
              <small><?= ($ga4Live['last_event_date']??'')!==''?'Último dia com eventos: '.h($ga4Live['last_event_date']).' · ':'' ?>Consulta <?=h($ga4Live['checked_at'])?> · Totais da propriedade, não apenas deste site.</small>
            </div>
          <?php endif; ?>
          <form method="post" action="/admin.php?action=integrations_ga4_test" data-ga4-test-form><?=csrf_field() ?>
            <button class="btn primary" type="submit" <?= !$ga4CredentialsReady||$cfg['ga4_property_id']===''?'disabled':'' ?>>↗ Testar recebimento</button>
            <span data-ga4-wait role="status" aria-live="polite" hidden>Consultando Google…</span>
          </form>
          <details class="int-credentials">
            <summary>Conta de serviço · Conectar ou remover</summary>
            <div class="int-credentials-inner">
              <p>Importe o JSON da conta de serviço do Google Cloud. O arquivo fica privado no servidor, fora do portal público e dos backups do site.</p>
              <form class="int-form" action="/admin.php?action=integrations_ga4_credentials" method="post" enctype="multipart/form-data">
                <?=csrf_field() ?>
                <label>Arquivo JSON privado<input type="file" name="ga4_credentials" accept=".json,application/json" required></label>
                <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                <button class="btn primary" type="submit">Salvar credencial</button>
              </form>
              <?php if($ga4CredentialsReady): ?>
                <form class="int-form" action="/admin.php?action=integrations_ga4_remove" method="post" data-confirm="Remover a credencial privada do servidor? O rastreamento público continua configurado.">
                  <?=csrf_field() ?>
                  <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                  <label class="int-confirm"><input type="checkbox" name="confirm_remove" value="1" required> Confirmo a remoção da credencial</label>
                  <button class="btn outline" type="submit">Remover credencial</button>
                </form>
              <?php endif; ?>
              <details class="compact-help"><summary aria-label="Como criar a conta de serviço">?</summary><div class="compact-help-content">No Google Cloud, ative a Google Analytics Data API e crie uma conta de serviço. No Google Analytics, adicione o e-mail dessa conta como Leitor na propriedade correta. Gere uma chave JSON e importe aqui. Nunca envie essa chave em conversa nem a salve no GitHub.</div></details>
            </div>
          </details>
        </div>
      </div>
    </details>
    <details class="int-service int-ads" id="google-adsense">
      <summary><span class="int-service-icon" aria-hidden="true">◇</span><span class="int-service-copy"><strong>Google AdSense</strong><small>Identidade da conta</small></span><span class="int-state <?= $adOn?'int-ready':'int-off' ?>"><?= $adOn?'ID salvo':'Pendente' ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-hint-row"><span>O ID é compartilhado com Publicidade</span><details class="compact-help"><summary aria-label="Ajuda do AdSense">?</summary><div class="compact-help-content">Salvar o ID publica apenas a identificação para análise. Não ativa anúncios nem garante aprovação. A ativação das posições continua em Publicidade.</div></details></div>
        <form method="post" action="/admin.php?action=integrations_save_adsense" class="int-form"><?= csrf_field() ?>
          <label for="int-adsense-id">ID do editor<input id="int-adsense-id" name="adsense_publisher" value="<?=h($cfg['adsense_publisher'])?>" maxlength="23" pattern="ca-pub-[0-9]{16}" placeholder="ca-pub-1234567890123456"></label>
          <div class="int-service-actions"><button class="btn primary" type="submit">Salvar AdSense</button><a href="<?=h(admin_link('ads'))?>">Gerenciar anúncios ↗</a></div>
        </form>
      </div>
    </details>
    <details class="int-service int-search" id="google-search">
      <summary><span class="int-service-icon" aria-hidden="true">⌕</span><span class="int-service-copy"><strong>Search Console</strong><small>DNS TXT e metatag</small></span><span class="int-state <?=h($searchStatusClass)?>"><?=h($searchStatus)?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-method-heading"><strong>Verificação por DNS (TXT)</strong><details class="compact-help"><summary aria-label="Ajuda sobre verificação por DNS">?</summary><div class="compact-help-content">Propriedades de domínio usam um registro TXT criado no painel DNS. O teste procura o registro público; apenas o Search Console confirma a propriedade na conta Google. A propagação pode levar algum tempo.</div></details></div>
        <div class="int-dns-domain"><span>Domínio a consultar</span><strong><?=h($dnsDomain!==''?$dnsDomain:'URL oficial não configurada')?></strong><?php if($dnsDomain===''): ?><a href="<?=h(admin_link('settings'))?>">Configurar HTTPS ↗</a><?php endif; ?></div>
        <form method="post" action="/admin.php?action=integrations_save_dns" class="int-form"><?=csrf_field() ?>
          <label for="int-dns-token">TXT esperado <small>(opcional)</small><input id="int-dns-token" name="google_dns_verification_txt" value="<?=h($cfg['google_dns_verification_txt']??'')?>" maxlength="300" autocomplete="off" spellcheck="false" placeholder="google-site-verification=..."></label>
          <div class="int-service-actions"><button type="submit" class="btn outline">Salvar TXT</button><details class="compact-help"><summary aria-label="Por que informar o registro esperado?">?</summary><div class="compact-help-content">Sem o valor exato, detectamos somente a presença de um registro Google, que pode pertencer a outra conta. Copie o TXT fornecido pelo Search Console e salve para conferir uma correspondência exata. Limpe o campo e salve para voltar ao teste genérico.</div></details></div>
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
    <div class="int-section-note"><span class="int-note-icon" aria-hidden="true">ⓘ</span><p>A consulta do GA4 exige autenticação e ocorre somente por ação do administrador. Configuração local não garante recebimento ou aprovação.</p></div>
  </section>
  <section class="int-section" id="diagnostico" data-int-section aria-labelledby="int-diag-title">
    <div class="int-section-head"><div><span class="int-eyebrow">VERIFICAÇÕES LOCAIS</span><h3 id="int-diag-title">Diagnóstico local</h3></div><details class="compact-help"><summary aria-label="Ajuda do diagnóstico">?</summary><div class="compact-help-content">Conferimos apenas arquivos e valores armazenados no servidor. Esta aba não consulta o DNS automaticamente. A consulta de registro TXT está na seção Search Console da aba Google e só executa sob demanda. O teste GA4 também é manual. Nenhuma verificação é uma certificação do Google.</div></details></div>
    <div class="int-diag-overview"><div><strong><?= $pass ?> de <?= $total ?></strong><span>checagens locais atendidas</span></div><a href="/admin.php?action=integrations&amp;check=1#diagnostico" class="btn outline">↻ Verificar tudo</a></div>
    <div class="int-checks" role="list">
      <?php foreach($rows as $row): ?><div class="int-check" role="listitem"><span class="int-check-mark <?= $row['ok']?'good':'attention' ?>" aria-hidden="true"><?= $row['ok']?'✓':'!' ?></span><div><strong><?=h($row['name'])?></strong><p><?=h($row['detail'])?></p></div><span class="int-check-label"><?= $row['ok']?'OK local':'Atenção' ?></span></div><?php endforeach; ?>
    </div>
    <?php if($dnsLast!==null): ?><div class="int-dns-diag"><strong>DNS Search Console</strong><span><?=h(['matched'=>'TXT esperado encontrado','found'=>'TXT Google encontrado','missing'=>'Não encontrado','mismatch'=>'Divergente','unavailable'=>'Indisponível','no_domain'=>'Sem domínio'][''.$dnsStatus]??'Não confirmado')?></span><small>Consulta sob demanda, não confirmação de propriedade Google.</small></div><?php endif; ?>
    <div class="int-section-note"><span class="int-note-icon" aria-hidden="true">ⓘ</span><p>A conexão GA4 está na aba Google; demais testes de domínio e aprovação seguem pendentes. Não confunda configuração com funcionamento confirmado.</p></div>
  </section>
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
    <div class="int-page-links"><a class="btn outline small" href="<?=h(admin_link('seo'))?>">SEO editorial</a><a class="btn outline small" href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">Search Console ↗</a></div>
    <div class="int-section-note"><span class="int-note-icon" aria-hidden="true">ⓘ</span><p>A análise mostra a preparação local do portal. Para consultar a indexação, abra o Search Console com a conta proprietária. A API requer OAuth e não está conectada nesta versão.</p></div>
  </section>
  <section class="int-section" id="catalogo" data-int-section aria-labelledby="int-catalog-title">
    <div class="int-section-head"><div><span class="int-eyebrow">EXPANSÃO MODULAR</span><h3 id="int-catalog-title">Próximas integrações</h3></div><details class="compact-help"><summary aria-label="Ajuda sobre futuras integrações">?</summary><div class="compact-help-content">Estes serviços ainda não possuem conexão ativa, tokens cadastráveis nem testes de API. Cada um precisará de autenticação e validação separadas.</div></details></div>
    <div class="int-catalog-grid">
      <?php foreach([
        ['◷','PageSpeed Insights','Desempenho mobile'],['✦','Google Gemini','Assistente editorial futuro'],['▤','Google Tag Manager','Gerenciamento de tags'],['↗','Google Ads','Campanhas e conversões'],['▥','Google Notícias','Dados estruturados'],['⌕','Bing Webmaster','Busca e indexação'],['◈','Microsoft Clarity','Análise de experiência'],['⬡','Cloudflare','Proteção e desempenho'],['⇪','IndexNow','Aviso de URLs novas']
      ] as $item): ?><div class="int-future"><span aria-hidden="true"><?=h($item[0])?></span><div><strong><?=h($item[1])?></strong><small><?=h($item[2])?></small></div><em>Em breve</em></div><?php endforeach; ?>
    </div>
  </section>
</div>
