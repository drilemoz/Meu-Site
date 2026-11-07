<?php
$cfg['ga4_property_id']=$cfg['ga4_property_id']??'';
$ga4CredentialsReady=$ga4CredentialsReady??false;
$ga4ServiceEmail=$ga4ServiceEmail??'';
$ga4Live=$ga4Live??null;
$ga4Audience=$ga4Audience??null;
$pageSpeedUrl=$pageSpeedUrl??igua_pagespeed_site_url($cfg);
$pageSpeedLast=$pageSpeedLast??null;
$pageSpeedResults=$pageSpeedResults??['mobile'=>$pageSpeedLast,'desktop'=>null];
$pageSpeedMobile=$pageSpeedResults['mobile']??null;
$pageSpeedDesktop=$pageSpeedResults['desktop']??null;
$pageSpeedAny=$pageSpeedMobile!==null || $pageSpeedDesktop!==null;
$pass=count(array_filter($rows,static fn($v)=>(bool)$v['ok']));
$total=count($rows);
$gaOn=$cfg['ga4_enabled']==='1' && analytics_ga4_measurement_id($cfg['ga4_measurement_id'])!=='';
$adOn=$cfg['adsense_publisher']!=='';
$tokenOn=$cfg['google_site_verification']!=='';
$dnsDomain=$dnsDomain??igua_search_dns_domain($cfg);
$dnsLast=$dnsLast??null;
$dnsConfigured=($cfg['google_dns_verification_txt']??'')!=='';
$searchConsoleProps=$searchConsoleProps??igua_search_console_properties($cfg);
$searchConsoleMode=$searchConsoleMode??(in_array(($cfg['search_console_property_mode']??'domain'),['domain','prefix'],true)?($cfg['search_console_property_mode']??'domain'):'domain');
$searchApiLast=$searchApiLast??null;
$inspectionTarget=$inspectionTarget??null;
$inspectionLast=$inspectionLast??null;
$dnsStatus=is_array($dnsLast)?(string)($dnsLast['status']??''):'';
$dnsFound=in_array($dnsStatus,['matched','found'],true);
$searchStatus=$searchApiLast!==null?'API consultada':($dnsFound?'TXT detectado':($tokenOn?'Tag pronta':($dnsConfigured?'TXT salvo':'Não testado')));
$searchStatusClass=$searchApiLast!==null?'int-on':($dnsFound?'int-on':(($tokenOn||$dnsConfigured)?'int-ready':'int-off'));
$integrationDiag=$integrationDiag??igua_int_diag_summary($cfg,$_SESSION??[],$ga4CredentialsReady,(bool)($adsenseConnected??false),$pageSpeedUrl);
$integrationHistory=$integrationHistory??[];

?>
<div class="int-hub" id="integrations-hub">
  <section class="int-hero" aria-labelledby="int-title">
    <div class="int-hero-orb" aria-hidden="true"></div>
    <div class="int-hero-top"><span class="int-overline"><span class="int-live-dot"></span> CENTRAL DE SERVIÇOS</span><span class="int-release">v<?=h(igua_brand_version())?></span></div>
    <h2 id="int-title">Integrações <span>em um lugar.</span></h2>
    <p>Controle os serviços, confira a configuração e prepare novas integrações.</p>
    <div class="int-hero-metrics" aria-label="Resumo de configuração">
      <div><strong><?= $pass ?><small>/<?= $total ?></small></strong><span>checagens locais</span></div>
      <div><strong><?= (int)$gaOn+(int)$adOn+(int)($tokenOn||$dnsConfigured) ?></strong><span>serviços preparados</span></div>
      <a href="/admin.php?action=integrations&amp;check=1#diagnostico"><span aria-hidden="true">↗</span><span>Conferir<br>agora</span></a>
    </div>
  </section>
  <div class="int-quick-links" aria-label="Acesso rápido aos serviços">
    <a href="#google-ga4"><span>GA4</span><strong><?= $gaOn?'Ativo no site':'Desligado' ?></strong></a>
    <a href="#google-adsense"><span>AdSense</span><strong><?= $adOn?'ID cadastrado':'Sem ID' ?></strong></a>
    <a href="#google-search"><span>Search Console</span><strong><?=h($searchStatus)?></strong></a>
    <a href="#google-pagespeed"><span>PageSpeed</span><strong><?= ($pageSpeedMobile!==null?'Cel '.(int)$pageSpeedMobile['score']:'Cel —').' · '.($pageSpeedDesktop!==null?'PC '.(int)$pageSpeedDesktop['score']:'PC —') ?></strong></a>
  </div>
  <nav class="int-tabs" data-integrations-nav aria-label="Seções da Central de Integrações">
    <button type="button" class="is-active" aria-pressed="true" data-int-target="google"><span aria-hidden="true">◈</span> Google</button>
    <button type="button" aria-pressed="false" data-int-target="diagnostico"><span aria-hidden="true">◉</span> Diagnóstico</button>
    <button type="button" aria-pressed="false" data-int-target="paginas"><span aria-hidden="true">▤</span> Páginas</button>
    <button type="button" aria-pressed="false" data-int-target="catalogo"><span aria-hidden="true">✧</span> Catálogo</button>
  </nav>
  <div class="int-copy-feedback" data-int-copy-feedback role="status" aria-live="polite" hidden></div>
  <section class="int-section" id="google" data-int-section aria-labelledby="int-google-title">
    <div class="int-section-head"><div><span class="int-eyebrow">ATIVOS E PREPARADOS</span><h3 id="int-google-title">Serviços Google</h3></div><details class="compact-help"><summary aria-label="Ajuda sobre as integrações">?</summary><div class="compact-help-content">Usamos os mesmos valores de Métricas e Publicidade. Nenhuma API é ativada sem configuração. Os estados abaixo são locais, não aprovação dos provedores.</div></details></div>
    <details class="int-service int-ga" id="google-ga4">
      <summary><span class="int-service-icon" aria-hidden="true">▥</span><span class="int-service-copy"><strong>Google Analytics 4</strong><small>Medição e consentimento</small></span><span class="int-state <?= $gaOn?'int-on':'int-off' ?>"><?= $gaOn?'Ativo':'Desligado' ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body">
        <div class="int-guide-row"><strong>Configurar Google Analytics 4</strong><details class="compact-help int-guide-help"><summary aria-label="Guia completo: onde obter IDs e configurar Google Analytics 4" title="Como configurar o GA4?">?</summary><div class="compact-help-content">
          <h3>Onde obter os identificadores</h3>
          <ol><li>Acesse <a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">Google Analytics</a> e selecione a propriedade do portal.</li><li>Em <strong>Administrador → Fluxos de dados → Web</strong>, copie o ID de medição no formato <code>G-...</code>.</li><li>Em <strong>Administrador → Detalhes da propriedade</strong>, copie o <strong>ID numérico da propriedade</strong>. É diferente do ID de medição.</li></ol>
          <h3>Para consultar dados pela API</h3><ol><li>No <a href="https://console.cloud.google.com/apis/library/analyticsdata.googleapis.com" target="_blank" rel="noopener noreferrer">Google Cloud</a>, ative a Analytics Data API.</li><li>Em <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank" rel="noopener noreferrer">Contas de serviço</a>, crie uma conta e gere uma chave JSON privada.</li><li>Na propriedade GA4, em <strong>Gerenciamento de acesso à propriedade</strong>, adicione o e-mail da conta de serviço como <strong>Leitor</strong>.</li><li>Abra <strong>Conta de serviço Google</strong> nesta janela e importe o JSON com sua senha administrativa.</li></ol>
          <p><strong>Privacidade:</strong> o rastreamento público permanece sujeito ao consentimento do visitante; relatórios são dados da propriedade e podem incluir outros fluxos. Nunca publique o JSON no GitHub.</p>
          <p><a href="https://support.google.com/analytics/answer/12270356?hl=pt-BR" target="_blank" rel="noopener noreferrer">Ajuda oficial: localizar o ID G-</a> · <a href="https://developers.google.com/analytics/devguides/reporting/data/v1/quickstart" target="_blank" rel="noopener noreferrer">Guia oficial da Data API</a></p>
        </div></details></div>

        <div class="int-hint-row"><span>Rastreamento configurado no portal</span><details class="compact-help"><summary aria-label="Ajuda de privacidade GA4">?</summary><div class="compact-help-content">O Google só carrega após consentimento explícito, conforme as preferências de privacidade. Os relatórios externos são consultados manualmente e não interferem nas métricas internas.</div></details></div>
        <form method="post" action="/admin.php?action=integrations_save_ga4" class="int-form"><?= csrf_field() ?>
          <label class="int-switch"><input type="checkbox" name="ga4_enabled" value="1" <?=$gaOn?'checked':''?>><span>Ativar GA4</span></label>
          <label for="int-ga-id">ID de medição<input id="int-ga-id" name="ga4_measurement_id" value="<?=h($cfg['ga4_measurement_id'])?>" maxlength="22" pattern="G-[a-zA-Z0-9]{4,20}" autocapitalize="characters" spellcheck="false" placeholder="G-XXXXXXXXXX"></label>
          <label for="int-ga-property">Propriedade GA4 (ID numérico)<input id="int-ga-property" name="ga4_property_id" inputmode="numeric" value="<?=h($cfg['ga4_property_id'])?>" maxlength="20" pattern="[0-9]{4,20}" autocomplete="off" placeholder="123456789"></label>
          <div class="int-service-actions"><button class="btn primary" type="submit">Salvar GA4</button><button type="button" class="btn outline" data-int-copy="int-ga-id" aria-label="Copiar ID de medição GA4">Copiar ID</button><a href="https://analytics.google.com/" target="_blank" rel="noopener noreferrer">Abrir Google ↗</a></div>
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
          <div class="int-ga-report" aria-label="Relatório de audiência do Google Analytics">
            <div class="int-live-heading"><span class="int-live-icon" aria-hidden="true">▤</span><div><strong>Audiência e conteúdo · GA4</strong><small>Últimos 28 dias completos, até ontem · toda a propriedade</small></div><details class="compact-help"><summary aria-label="Ajuda do relatório de audiência GA4">?</summary><div class="compact-help-content">O GA4 apresenta dados agregados de uma propriedade, que pode ter mais de um fluxo. Usuários ativos não são o mesmo que sessões ou visualizações; algumas pessoas podem usar vários dispositivos. Os dados dependem do consentimento e podem sofrer atraso ou limiares de privacidade. Uma consulta é feita somente ao tocar no botão, com resultado temporário por até 1 hora.</div></details></div>
            <form method="post" action="/admin.php?action=integrations_ga4_report" class="int-ga-report-form"><?=csrf_field() ?>
              <button type="submit" class="btn primary" <?= !$ga4CredentialsReady||$cfg['ga4_property_id']===''?'disabled':'' ?>>⌕ Consultar audiência</button>
              <?php if(!$ga4CredentialsReady||$cfg['ga4_property_id']===''): ?><small>Informe a propriedade e cadastre a conta de serviço para habilitar.</small><?php endif; ?>
            </form>
            <?php if($ga4Audience!==null): ?>
              <div class="int-ga-report-results" role="status">
                <small>Propriedade <?=h($ga4Audience['property_id'])?> · consulta <?=date('d/m/Y H:i',(int)$ga4Audience['checked_at'])?> · valores GA4, não métricas internas</small>
                <?php if($ga4Audience['summary']!==null): ?>
                  <div class="int-ga-report-totals">
                    <div><strong><?=number_format((int)$ga4Audience['summary']['users'],0,',','.')?></strong><span>Usuários ativos</span></div>
                    <div><strong><?=number_format((int)$ga4Audience['summary']['sessions'],0,',','.')?></strong><span>Sessões</span></div>
                    <div><strong><?=number_format((int)$ga4Audience['summary']['views'],0,',','.')?></strong><span>Visualizações</span></div>
                  </div>
                <?php else: ?><p class="int-sc-hint">Nenhum total fornecido pela API para o período. Não significa que o site esteja com defeito.</p><?php endif; ?>
                <?php foreach(['pages'=>['Páginas populares','Visualizações'],'channels'=>['Canais de origem','Sessões'],'devices'=>['Dispositivos','Usuários ativos']] as $section=>$labels): ?>
                  <details class="int-sc-list" <?= $section==='pages'?'open':'' ?>><summary><?=h($labels[0])?> · <?=count($ga4Audience[$section])?></summary>
                    <?php if($ga4Audience[$section]===[]): ?><p class="int-sc-hint int-ga-empty">Nenhum registro disponível neste período.</p><?php endif; ?>
                    <?php foreach($ga4Audience[$section] as $item): ?><div class="int-sc-row"><span title="<?=h($item['label'])?>"><?=h($item['label'])?></span><small><?=number_format((int)$item['count'],0,',','.')?> <?=h($labels[1])?></small></div><?php endforeach; ?>
                  </details>
                <?php endforeach; ?>
                <p class="int-sc-hint">Páginas agrupadas pelo título registrado no GA4, inclusive se dois endereços tiverem o mesmo título. Os resultados podem incluir outros fluxos da propriedade.</p>
              </div>
            <?php else: ?><p class="int-sc-hint">Nenhuma consulta realizada nesta sessão. Isso não significa ausência de visitantes.</p><?php endif; ?>
          </div>
          <details class="int-credentials">
            <summary>Conta de serviço Google · GA4 e Search Console</summary>
            <div class="int-credentials-inner">
              <p>Importe o JSON da conta de serviço do Google Cloud. A mesma conta pode consultar GA4 e Search Console, mas exige permissão separada em cada serviço. A chave fica privada no servidor, fora do site público e dos backups.</p>
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
        <div class="int-guide-row"><strong>Configurar Google AdSense</strong><details class="compact-help int-guide-help"><summary aria-label="Guia completo: ID ca-pub, OAuth e relatórios AdSense" title="Como configurar o AdSense?">?</summary><div class="compact-help-content">
          <h3>Onde obter o ca-pub</h3><ol><li>Acesse <a href="https://www.google.com/adsense/" target="_blank" rel="noopener noreferrer">Google AdSense</a> com a conta responsável.</li><li>Abra <strong>Conta → Configurações → Informações da conta</strong> e copie o ID do editor (<code>pub-...</code>); neste painel use <code>ca-pub-...</code>.</li><li>Salvar o ID não ativa anúncios e não significa que o site foi aprovado. Gerencie posições separadamente em Publicidade.</li></ol>
          <h3>Para consultar receita e impressões</h3><ol><li>No <a href="https://console.cloud.google.com/apis/library/adsense.googleapis.com" target="_blank" rel="noopener noreferrer">Google Cloud</a>, habilite a AdSense Management API.</li><li>Configure a tela de consentimento OAuth e crie um <strong>cliente OAuth 2.0 para aplicativo da Web</strong> em <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">Credenciais</a>.</li><li>Copie exatamente a URL de retorno exibida em <strong>Conectar conta AdSense</strong> para os URIs autorizados.</li><li>Salve ID/segredo do cliente com senha administrativa e autorize a conta Google.</li></ol>
          <p><strong>Importante:</strong> AdSense não usa a conta de serviço do GA4. O acesso do portal usa <code>adsense.readonly</code>. Receita estimada não representa pagamento recebido.</p>
          <p><a href="https://developers.google.com/adsense/management/direct_requests" target="_blank" rel="noopener noreferrer">Documentação oficial OAuth do AdSense</a></p>
        </div></details></div>

        <div class="int-hint-row"><span>O ID é compartilhado com Publicidade</span><details class="compact-help"><summary aria-label="Ajuda do AdSense">?</summary><div class="compact-help-content">Salvar o ID publica apenas a identificação para análise. Não ativa anúncios nem garante aprovação. A ativação das posições continua em Publicidade.</div></details></div>
        <form method="post" action="/admin.php?action=integrations_save_adsense" class="int-form"><?= csrf_field() ?>
          <label for="int-adsense-id">ID do editor<input id="int-adsense-id" name="adsense_publisher" value="<?=h($cfg['adsense_publisher'])?>" maxlength="23" pattern="ca-pub-[0-9]{16}" placeholder="ca-pub-1234567890123456"></label>
          <div class="int-service-actions"><button class="btn primary" type="submit">Salvar AdSense</button><button type="button" class="btn outline" data-int-copy="int-adsense-id" aria-label="Copiar ID AdSense">Copiar ID</button><a href="<?=h(admin_link('ads'))?>">Gerenciar anúncios ↗</a></div>
        </form>
        <section class="int-ga-report" aria-label="Relatórios autorizados Google AdSense">
          <div class="int-live-heading"><span class="int-live-icon" aria-hidden="true">▤</span><div><strong>Receitas e desempenho · AdSense</strong><small>Últimos 30 dias completos · conta do editor, não somente este site</small></div><details class="compact-help"><summary aria-label="Ajuda para OAuth AdSense">?</summary><div class="compact-help-content">O AdSense exige autorização do proprietário com OAuth 2.0, não aceita a conta de serviço usada pelo GA4. O escopo é apenas adsense.readonly. Os valores são estimados e não equivalem a pagamentos recebidos. Não revela números publicamente nem executa consultas em segundo plano.</div></details></div>
          <div class="int-live-status"><span class="int-live-badge <?=($adsenseConnected??false)?'is-ready':'is-waiting'?>"><?=($adsenseConnected??false)?'Conta Google autorizada':(($adsenseOAuth??null)!==null?'Cliente OAuth salvo; autorização pendente':'Cliente OAuth não configurado')?></span></div>
          <?php if(($adsenseConnected??false)): ?>
            <form method="post" action="/admin.php?action=integrations_adsense_report" class="int-ga-report-form"><?=csrf_field()?><button class="btn primary" type="submit">⌕ Consultar relatório AdSense</button></form>
          <?php else: ?><p class="int-sc-hint">A consulta real só será habilitada depois de cadastrar o cliente OAuth e autorizar a conta Google que possui o ID ca-pub informado acima.</p><?php endif; ?>
          <?php if(($adsenseReport??null)!==null): ?>
            <div class="int-ga-report-results" role="status">
              <small><?=h($adsenseReport['account'])?> · consulta <?=date('d/m/Y H:i',(int)$adsenseReport['checked_at'])?> · moeda <?=h($adsenseReport['currency']?:'não informada')?> · conta inteira</small>
              <?php if($adsenseReport['totals']!==null): ?><div class="int-ga-report-totals">
                <div><strong><?=h($adsenseReport['currency']?:'') ?> <?=number_format((float)$adsenseReport['totals']['earnings'],2,',','.')?></strong><span>Receita estimada</span></div>
                <div><strong><?=number_format((int)$adsenseReport['totals']['impressions'],0,',','.')?></strong><span>Impressões</span></div>
                <div><strong><?=number_format((int)$adsenseReport['totals']['clicks'],0,',','.')?></strong><span>Cliques em anúncios</span></div>
                <div><strong><?=number_format((int)$adsenseReport['totals']['page_views'],0,',','.')?></strong><span>Visualizações de página (AdSense)</span></div>
              </div><?php else: ?><p class="int-sc-hint">O Google não retornou totais nesse período. Isso não comprova que a conta esteja com defeito.</p><?php endif; ?>
            </div>
          <?php endif; ?>
          <details class="int-credentials"><summary>Conectar conta AdSense via Google OAuth 2.0</summary>
            <div class="int-credentials-inner">
              <p>1. No <a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener noreferrer">Google Cloud → Credenciais</a>, habilite a <strong>AdSense Management API</strong> e crie um <strong>Cliente OAuth 2.0 tipo Aplicativo da Web</strong>.</p>
              <p>2. Configure a URL de retorno autorizada exatamente como abaixo. O Google pode exigir configuração da tela de consentimento e aprovação do acesso.</p>
              <div class="int-sc-identity"><label for="int-ads-callback">URL de retorno OAuth (pública)</label><div><input id="int-ads-callback" readonly value="<?php try{echo h(igua_adsense_callback_uri($cfg['seo_site_url']));}catch(Throwable){echo 'Configure a URL HTTPS oficial em Geral e SEO';} ?>"><button type="button" class="btn outline" data-int-copy="int-ads-callback">Copiar</button></div></div>
              <p>3. Salve as credenciais privadas, confirme com sua senha administrativa e conecte sua conta Google. Não cole esses segredos no GitHub ou em chats.</p>
              <form method="post" action="/admin.php?action=integrations_adsense_credentials" class="int-form"><?=csrf_field() ?>
                <label>ID do cliente OAuth<input type="text" name="adsense_client_id" required autocomplete="off" maxlength="240" placeholder="...apps.googleusercontent.com"></label>
                <label>Segredo do cliente OAuth<input type="password" name="adsense_client_secret" required autocomplete="new-password" maxlength="256"></label>
                <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                <button class="btn outline" type="submit">Salvar cliente OAuth privado</button>
              </form>
              <?php if(($adsenseOAuth??null)!==null): ?>
                <form method="post" action="/admin.php?action=integrations_adsense_connect" class="int-form"><?=csrf_field() ?>
                  <button class="btn primary" type="submit" <?=($cfg['adsense_publisher']??'')===''?'disabled':''?>>↗ Autorizar com Google</button>
                </form>
                <form method="post" action="/admin.php?action=integrations_adsense_disconnect" class="int-form" data-confirm="Apagar as credenciais privadas do AdSense desta hospedagem?">
                  <?=csrf_field() ?><input type="hidden" name="confirm_remove" value="1">
                  <label>Senha administrativa<input type="password" name="confirm_password" required autocomplete="current-password"></label>
                  <button type="submit" class="btn outline">Remover conexão e credenciais</button>
                </form>
              <?php endif; ?>
              <p class="int-sc-hint">A autorização é independente de GA4/Search Console. Os tokens de renovação ficam em storage/integrations, fora dos pacotes ZIP. Relatórios não significam aprovação do site nem receita recebida.</p>
            </div>
          </details>
        </section>
      </div>
    </details>
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
    <details class="int-service int-gemini-service" id="google-gemini">
      <summary><span class="int-service-icon igua-gemini-mark" aria-hidden="true">✦</span><span class="int-service-copy"><strong>Google Gemini</strong><small>Assistente editorial opcional</small></span><span class="int-state <?= $geminiConfig!==null && $geminiConfig['enabled']?'int-on':'int-off' ?>"><?= $geminiConfig===null?'Não configurado':($geminiConfig['enabled']?'Ativado':'Desativado') ?></span><span class="int-chevron" aria-hidden="true"></span></summary>
      <div class="int-service-body igua-gemini-config">
        <p class="muted">Chave guardada exclusivamente no servidor. Os textos são enviados ao Google apenas quando você solicita uma sugestão; nenhuma matéria é publicada automaticamente.</p>
        <div class="int-guide-row"><strong>Configurar Gemini</strong><details class="compact-help int-guide-help"><summary aria-label="Como obter a chave da API Gemini?" title="Como obter a chave?">?</summary><div class="compact-help-content"><p>Acesse <a href="https://aistudio.google.com/api-keys" target="_blank" rel="noopener noreferrer">Google AI Studio</a>, escolha um projeto, crie ou copie sua chave API e cole abaixo. Guarde a chave fora de arquivos públicos e do GitHub.</p><p>Se o teste retornar 404, o modelo escolhido pode não estar disponível na sua conta. Consulte <a href="https://ai.google.dev/gemini-api/docs/models" target="_blank" rel="noopener noreferrer">Modelos Gemini</a> e ajuste o nome. Testar API consome uma pequena quantidade de cota.</p></div></details></div>
        <div class="igua-gemini-status" role="status">
          <strong><?= $geminiConfig!==null?'Chave cadastrada (oculta)':'Chave ainda não cadastrada' ?></strong>
          <small>Modelo: <?=h($geminiConfig['model']??'gemini-3.8-flash')?></small>
          <?php if(is_array($geminiTest) && ($geminiTest['ok']??false)): ?><small>Último teste: conexão confirmada nesta sessão em <?=date('d/m/Y H:i',(int)$geminiTest['time'])?>. Não garante disponibilidade futura.</small><?php endif; ?>
        </div>
        <form method="post" action="/admin.php?action=integrations_gemini_save" id="int-gemini-save-form" class="int-form" autocomplete="off"><?=csrf_field() ?>
          <label class="int-switch"><input type="checkbox" name="gemini_enabled" value="1" <?=($geminiConfig['enabled']??false)?'checked':''?>><span>Ativar assistente Gemini</span></label>
          <label for="int-gemini-key">Chave de API <input type="password" id="int-gemini-key" name="gemini_key" maxlength="512" autocomplete="new-password" autocapitalize="off" spellcheck="false" placeholder="<?= $geminiConfig===null?'Cole a chave do Google AI Studio':'Deixe vazio para manter a chave atual' ?>"></label>
          <button type="button" class="btn outline small" data-gemini-visibility aria-controls="int-gemini-key" aria-label="Mostrar ou ocultar a chave digitada">Mostrar/ocultar</button>
          <label for="int-gemini-model">Modelo da API<input id="int-gemini-model" name="gemini_model" required maxlength="70" pattern="gemini-[a-z0-9][a-z0-9.\-]{2,63}" value="<?=h($geminiConfig['model']??'gemini-3.8-flash')?>" placeholder="gemini-3.8-flash" autocapitalize="off" spellcheck="false"></label>
          <label for="int-gemini-password">Senha administrativa<input id="int-gemini-password" type="password" name="confirm_password" required autocomplete="current-password"></label>
          <div class="igua-gemini-main-actions"><button type="submit" class="btn primary">Salvar configuração</button><button type="button" class="btn outline" data-gemini-test-open <?= !$geminiConfig || !$geminiConfig['enabled']?'disabled':'' ?> aria-haspopup="dialog" aria-controls="gemini-test-dialog">Testar API</button></div>
        </form>
        <form id="int-gemini-test-form" method="post" action="/admin.php?action=integrations_gemini_test" data-gemini-test-form><?=csrf_field() ?><input type="hidden" name="gemini_ajax" value="1"></form>
        <?php if($geminiConfig!==null): ?>
          <div class="igua-gemini-danger"><strong>Remover chave configurada</strong><p class="muted tiny">Exige confirmação da senha administrativa e não afeta os conteúdos salvos.</p>
            <form method="post" action="/admin.php?action=integrations_gemini_remove" data-confirm="Remover a chave Gemini desta hospedagem?" class="igua-gemini-danger-form"><?=csrf_field() ?><input type="hidden" name="confirm_remove" value="1"><label for="int-gemini-remove-password">Senha administrativa<input type="password" id="int-gemini-remove-password" name="confirm_password" required autocomplete="current-password" placeholder="Digite sua senha para remover"></label><button type="submit" class="btn outline danger-link" data-confirm="Remover a chave Gemini da hospedagem?">Remover chave</button></form>
          </div>
        <?php endif; ?>
        <p class="muted tiny">Teste real apenas sob seu comando. Pode consumir cota, sem expor chave ou conteúdo da resposta. Mensagens de erro aparecem no painel.</p>
      </div>
    </details>
  </section>
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
  <section class="int-section" id="catalogo" data-int-section aria-labelledby="int-catalog-title">
    <div class="int-section-head"><div><span class="int-eyebrow">EXPANSÃO MODULAR</span><h3 id="int-catalog-title">Próximas integrações</h3></div><details class="compact-help"><summary aria-label="Ajuda sobre futuras integrações">?</summary><div class="compact-help-content">Estes serviços ainda não possuem conexão ativa, tokens cadastráveis nem testes de API. Cada um precisará de autenticação e validação separadas.</div></details></div>
    <div class="int-catalog-grid">
      <?php foreach([
        ['▤','Google Tag Manager','Gerenciamento de tags'],['↗','Google Ads','Campanhas e conversões'],['▥','Google Notícias','Dados estruturados'],['⌕','Bing Webmaster','Busca e indexação'],['◈','Microsoft Clarity','Análise de experiência'],['⬡','Cloudflare','Proteção e desempenho'],['⇪','IndexNow','Aviso de URLs novas']
      ] as $item): ?><div class="int-future"><span aria-hidden="true"><?=h($item[0])?></span><div><strong><?=h($item[1])?></strong><small><?=h($item[2])?></small></div><em>Em breve</em></div><?php endforeach; ?>
    </div>
  </section>
  <dialog id="gemini-test-dialog" class="igua-modal igua-modal--narrow igua-gemini-test-dialog" aria-labelledby="gemini-test-title" aria-modal="true">
    <div class="igua-gemini-test-shell">
      <header><h2 id="gemini-test-title">Teste da API Gemini</h2><button type="button" class="btn outline" data-gemini-test-close aria-label="Fechar diagnóstico">×</button></header>
      <div class="igua-gemini-test-body"><p>O teste envia uma solicitação mínima ao modelo salvo. Pode consumir cota e não altera notícias.</p>
        <div data-gemini-test-result role="status" aria-live="polite">Toque em Iniciar teste para consultar a API.</div>
        <div class="igua-gemini-test-actions"><button type="button" class="btn outline" data-gemini-test-close>Fechar</button><button type="button" class="btn primary" data-gemini-test-run>Iniciar teste</button></div>
      </div>
    </div>
  </dialog>
  <dialog id="int-dialog" class="igua-modal igua-modal--medium int-dialog" aria-labelledby="int-dialog-title" aria-modal="true">
    <div class="int-dialog-shell">
      <header class="int-dialog-header">
        <button type="button" class="int-dialog-back" data-int-dialog-back aria-label="Voltar ao conteúdo anterior" hidden>←</button>
        <h2 id="int-dialog-title" data-int-dialog-title>Integração</h2>
        <button type="button" class="int-dialog-x" data-int-dialog-close aria-label="Fechar janela">×</button>
      </header>
      <div class="int-dialog-scroll" data-int-dialog-scroll><div class="int-dialog-panel" data-int-dialog-panel></div></div>
      <div class="int-dialog-feedback" data-int-dialog-feedback role="status" aria-live="polite" hidden></div>
      <footer class="int-dialog-footer"><button type="button" class="btn outline" data-int-dialog-close>Fechar</button></footer>
    </div>
  </dialog>

</div>
