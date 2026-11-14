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
<?php require __DIR__.'/integrations/ga4.php'; ?>
<?php require __DIR__.'/integrations/adsense.php'; ?>
<?php require __DIR__.'/integrations/search_console.php'; ?>
<?php require __DIR__.'/integrations/pagespeed.php'; ?>
<?php require __DIR__.'/integrations/gemini.php'; ?>
  </section>
<?php require __DIR__.'/integrations/diagnostics.php'; ?>
<?php require __DIR__.'/integrations/page_inspector.php'; ?>
<?php require __DIR__.'/integrations/catalog.php'; ?>
<?php require __DIR__.'/integrations/dialogs.php'; ?>
</div>
