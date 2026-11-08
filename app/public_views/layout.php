<?php
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#ffffff"><title><?= h($seoTitle) ?></title><meta name="description" content="<?= h($seoDesc) ?>">
<?php require_once __DIR__.'/../adsense_review.php'; echo igua_adsense_verification_meta(setting('adsense_publisher','')); require_once __DIR__.'/../integrations.php'; echo igua_google_verification_meta(setting('google_site_verification','')); ?><meta name="robots" content="<?= $indexable?'index,follow':'noindex,nofollow' ?>">
<?php if($canonical!==''): ?><link rel="canonical" href="<?= h($canonical) ?>"><?php endif; ?>
<meta property="og:type" content="<?= $article?'article':'website' ?>"><meta property="og:site_name" content="<?= h(igua_brand_name()) ?>"><meta property="og:title" content="<?= h($seoTitle) ?>"><meta property="og:description" content="<?= h($seoDesc) ?>">
<?php if($canonical!==''): ?><meta property="og:url" content="<?= h($canonical) ?>"><?php endif; ?>
<?php if(filter_var($socialImg,FILTER_VALIDATE_URL) && str_starts_with($socialImg,'https://')): ?><meta property="og:image" content="<?= h($socialImg) ?>"><?php endif; ?>
<meta name="twitter:card" content="summary_large_image">
<?php if($ld): ?><script type="application/ld+json"><?= json_encode($ld,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script><?php endif; ?>
<?php
$iguaFavicon=trim((string)setting('seo_favicon_image',''));
if($iguaFavicon!=='') {
  if(str_starts_with($iguaFavicon,'/uploads/')) {
    // Imagens da biblioteca são verificadas ao salvar; URL da raiz pública.
  } elseif(!filter_var($iguaFavicon,FILTER_VALIDATE_URL) || !str_starts_with($iguaFavicon,'https://')) $iguaFavicon='';
}
if($iguaFavicon==='')$iguaFavicon='/assets/img/favicon.svg';
?><link rel="icon" href="<?=h($iguaFavicon)?>">
<link rel="stylesheet" href="/assets/css/style.css?v=<?= h(igua_brand_version()) ?>"></head><body>
<header class="site-header"><div class="header-upper container"><a class="brand" href="/index.php" aria-label="<?= h(igua_brand_name()) ?>, início"><span class="brand-icon"><svg viewBox="0 0 74 60" aria-hidden="true"><circle cx="57" cy="13" r="10" fill="#f8ba28"/><path d="M1 44Q15 14 33 40Q50 5 71 43V57H1Z" fill="#28a866"/><path d="M1 47Q20 35 37 51Q53 31 71 45V59H1Z" fill="#11764d"/><rect x="43" y="32" width="11" height="25" rx="1" fill="#e8f0ff"/><path d="M41 32 48 17 56 32Z" fill="#1754a7"/><rect x="46" y="38" width="5" height="7" fill="#2661a1"/></svg></span><span class="brand-copy"><small>PORTAL DE NOTÍCIAS</small><strong><?= h(igua_brand_name()) ?></strong></span></a>
<div class="tagline"><?= h(setting('site_tagline','A notícia da nossa cidade, sempre com você.')) ?></div>
<form class="header-search" action="/index.php" method="get"><input type="hidden" name="p" value="buscar"><button type="submit" aria-label="Buscar">⌕</button><input type="search" name="q" placeholder="Buscar notícias, eventos, serviços..." value="<?= h($search) ?>"></form>
<div class="local-info"><span class="sun">☀</span><span><b><?= h(setting('city','Iguatama - MG')) ?></b><small><?= h(date('d/m/Y')) ?></small></span></div>
<button type="button" class="mobile-menu" id="menuToggle" aria-label="Abrir menu" aria-controls="mainNav" aria-expanded="false">☰</button></div>
<div class="nav-wrap"><nav class="container main-nav" id="mainNav" aria-label="Navegação principal"><a class="<?= $route==='inicio'?'active':'' ?>" href="/index.php">Início</a><?php foreach (array_slice($categories,0,6) as $c): ?><a href="<?= h(cat_link($c)) ?>" class="<?= $category&&$category['id']===$c['id']?'active':'' ?>"><?= h($c['name']) ?></a><?php endforeach; ?><a href="/index.php?p=onibus" class="<?= $route==='onibus'?'active':'' ?>">🚌 Ônibus</a><a class="submit-story" href="/index.php?p=contato">✉ Enviar notícia</a></nav></div></header>
<?php $ga4Id=analytics_ga4_public_id(); ?>
<main class="container page-main">
<?php // O aviso de demonstração pertence exclusivamente a cada matéria marcada is_demo. ?>
<?php require __DIR__.'/pages.php'; ?>
<?php ad_display_slot($db,'p03'); ?>
</main>
<?php if($ga4Id!==''): ?>
<div id="ga4ConsentConfig" data-ga4-id="<?= h($ga4Id) ?>" hidden></div>
<?php endif; ?>
<?php
$googleReady=false;
try {if(setting('ads_enabled','0')==='1' && setting('seo_index','0')==='1' && $demoCount===0 && preg_match('/^ca-pub-\d{16}$/D',setting('adsense_publisher',''))) {
    $googleReady=(bool)$db->query("SELECT COUNT(*) FROM ad_placements WHERE provider='adsense' AND enabled=1 AND adsense_slot<>''")->fetchColumn();
}}catch(Throwable){}
?>
<?php if($googleReady):?><section class="container ad-consent" id="adConsent" hidden aria-label="Preferências de publicidade"><strong>Publicidade Google: sua escolha</strong><p>Com sua autorização, o portal <?= h(igua_brand_name()) ?> poderá carregar anúncios do Google AdSense, que pode utilizar cookies e dados de navegação. Os anúncios dos comerciantes locais não dependem dessa escolha. Consulte a <a href="/index.php?p=sobre">Sobre</a><a href="/index.php?p=privacidade">Política de Privacidade</a>.</p><div class="ad-consent-actions"><button type="button" id="adConsentDeny">Não permitir anúncios Google</button><button type="button" id="adConsentAllow" class="ad-consent-primary">Permitir anúncios Google</button></div></section><?php endif;?>
<footer class="site-footer"><div class="container footer-new">
  <div class="footer-new-top"><div class="footer-identity"><a href="/index.php" class="footer-wordmark"><?=h(igua_brand_name())?></a><p><?=h(setting('site_tagline','A notícia da nossa cidade, sempre com você.'))?></p><span class="footer-place">📍 <?=h(setting('city','Iguatama - MG'))?></span></div>
  <nav aria-label="Links do portal" class="footer-new-nav"><div><strong>Explorar</strong><a href="/index.php">Página inicial</a><a href="/index.php?p=buscar">Notícias</a><a href="/index.php?p=onibus">Horários de ônibus</a></div><div><strong><?=h(igua_brand_name())?></strong><a href="/index.php?p=contato">Contato</a><a href="/index.php?p=anuncie">Anuncie</a><a href="/index.php?p=sobre">Sobre</a><a href="/index.php?p=privacidade">Política de Privacidade</a><a href="/index.php?p=termos">Termos de Uso</a></div></nav></div>
  <div class="footer-new-bottom"><small>© <?=date('Y')?> <?=h(igua_brand_name())?> · <?=h(setting('site_tagline','Informação da nossa região'))?></small><?php if($googleReady):?><a href="#adConsent" id="adConsentChange" class="footer-pref">Preferências de anúncios</a><?php endif;?><?php if($ga4Id!==''):?><a href="/index.php?p=privacidade#preferencias-ga4" class="footer-pref">Preferências de métricas</a><?php endif;?><a href="/admin.php" class="footer-admin-gear" aria-label="Acessar painel administrativo" title="Administração">⚙</a></div>
<?php igua_developer_footer(); ?></div></footer>
<nav class="bottom-nav" aria-label="Navegação móvel"><a href="/index.php" class="<?= $route==='inicio'?'active':'' ?>"><span>⌂</span>Início</a><a href="<?= h(url(['p'=>'categoria','slug'=>'cidade'])) ?>" class="<?= $route==='categoria' && ($category['slug']??'')==='cidade'?'active':'' ?>"><span aria-hidden="true">▥</span>Cidade</a><a href="<?= h(url(['p'=>'buscar'])) ?>" class="<?= $route==='buscar'?'active':'' ?>"><span aria-hidden="true">▤</span>Notícias</a><a href="<?= h(url(['p'=>'categoria','slug'=>'eventos'])) ?>" class="<?= $route==='categoria' && ($category['slug']??'')==='eventos'?'active':'' ?>"><span aria-hidden="true">▣</span>Eventos</a><a href="/index.php?p=onibus" class="<?= $route==='onibus'?'active':'' ?>"><span aria-hidden="true">🚌</span>Ônibus</a></nav>
<script src="/assets/js/main.js" defer></script><script src="/assets/js/analytics.js" defer></script><?php if($ga4Id!==''):?><script src="/assets/js/ga4-consent.js?v=<?= h(igua_brand_version()) ?>" defer></script><?php endif;?></body></html>
