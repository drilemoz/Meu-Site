<?php
declare(strict_types=1);
require_once __DIR__.'/../../sitemap_admin.php';
require_ability('manage_settings',$me);
$indexEnabled=setting('seo_index','0')==='1';
$siteUrl=setting('seo_site_url','');
$publisher=setting('adsense_publisher','');
$diagnostics=igua_sitemap_local_diagnostics($siteUrl,$indexEnabled,IGUA_PUBLIC,$publisher);
$catalog=['items'=>[],'overflow'=>false];$catalogError='';
try {$catalog=igua_sitemap_catalog($db,$siteUrl);}catch(Throwable $e){
    $catalogError='Configure o domínio HTTPS oficial para visualizar as URLs elegíveis. Se ele já estiver configurado, confira a conexão com o banco e a Central de Saúde.';
    if(!($e instanceof InvalidArgumentException)&&function_exists('igua_health_log'))igua_health_log('SITEMAP_ADMIN_FAILED',$e);
}
$all=$catalog['items'];
$kinds=['all'=>'Todas','noticia'=>'Notícias','categoria'=>'Categorias','aplicativo'=>'Aplicativos','site'=>'Portal'];
$kind=is_string($_GET['kind']??null)?$_GET['kind']:'all';if(!isset($kinds[$kind]))$kind='all';
$query=is_string($_GET['q']??null)?trim($_GET['q']):'';
$query=function_exists('mb_substr')?mb_substr($query,0,100,'UTF-8'):substr($query,0,100);
$pageRaw=filter_var($_GET['page']??1,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>999999]]);
$page=$pageRaw===false?1:(int)$pageRaw;
$filtered=igua_sitemap_filter($all,$kind,$query);
$perPage=20;$total=count($filtered);$totalPages=max(1,(int)ceil($total/$perPage));
$page=min($page,$totalPages);$visible=array_slice($filtered,($page-1)*$perPage,$perPage);
$counts=array_count_values(array_column($all,'kind'));
?>
<section class="panel sitemap-center" aria-labelledby="sitemap-title">
  <div class="panel-heading sitemap-heading"><div><h2 id="sitemap-title">Sitemap e Indexação</h2><p class="muted">Consulte as URLs geradas pelo portal e verifique os arquivos de busca. Os resultados não garantem indexação no Google.</p></div><a class="btn outline small" href="<?=h(admin_link('settings'))?>">Configurações SEO</a></div>
  <div class="sitemap-stats">
    <div class="sitemap-stat"><small>URLs elegíveis</small><strong><?=count($all)?></strong><span><?= $indexEnabled&&$catalogError===''?'Sitemap ativo nas configurações':'Prévia / indexação desativada' ?></span></div>
    <div class="sitemap-stat"><small>Notícias</small><strong><?=(int)($counts['noticia']??0)?></strong><span>Publicadas, não demonstrativas</span></div>
    <div class="sitemap-stat"><small>Páginas de aplicativos</small><strong><?=(int)($counts['aplicativo']??0)?></strong><span>Somente conteúdo publicado</span></div>
  </div>
  <?php if($catalogError!==''): ?><p class="alert warning"><?=h($catalogError)?></p><?php endif; ?>
  <?php if($catalog['overflow']): ?><p class="alert warning">O sitemap atingiu 50.000 URLs. Há mais páginas elegíveis que não cabem no XML atual. Será necessário dividir o sitemap em partes antes de incluir mais URLs; nenhuma publicação foi removida.</p><?php endif; ?>
  <?php if(!$indexEnabled): ?><p class="alert warning">A indexação está desativada. A listagem abaixo é apenas uma prévia: <code>/sitemap.php</code> responde 404 até a ativação em Configurações e SEO.</p><?php endif; ?>
  <div class="sitemap-site-links">
    <a class="btn outline small" href="/sitemap.php" target="_blank" rel="noopener">Abrir sitemap XML ↗</a>
    <a class="btn outline small" href="/robots.txt" target="_blank" rel="noopener">Abrir robots.txt ↗</a>
    <a class="btn outline small" href="/ads.txt" target="_blank" rel="noopener">Abrir ads.txt ↗</a>
    <button class="btn primary small" type="button" id="sitemap-check-files">Verificar online</button>
  </div>
  <div class="sitemap-checks" aria-label="Diagnóstico local dos arquivos">
    <div><span class="sitemap-dot <?= $diagnostics['domain_ok']?'is-ok':'is-warning' ?>"></span><strong>Domínio oficial</strong><small><?= $diagnostics['domain_ok']?h($siteUrl):'HTTPS não configurado' ?></small></div>
    <div><span class="sitemap-dot <?= $indexEnabled?'is-ok':'is-warning' ?>"></span><strong>Indexação</strong><small><?= $indexEnabled?'Habilitada no painel':'Desativada no painel' ?></small></div>
    <div><span class="sitemap-dot <?= $diagnostics['sitemap_announced']?'is-ok':'is-warning' ?>"></span><strong>robots.txt</strong><small><?= $diagnostics['sitemap_announced']?'Anuncia o sitemap configurado':($diagnostics['robots_exists']?'Existe, mas não anuncia este sitemap':'Não encontrado no projeto') ?></small></div>
    <div><span class="sitemap-dot <?= $diagnostics['ads_match']?'is-ok':'is-warning' ?>"></span><strong>ads.txt</strong><small><?= $diagnostics['ads_match']?'ID AdSense correspondente':($diagnostics['ads_exists']?($diagnostics['ads_configured']?'Confira o ID AdSense no arquivo':'Arquivo encontrado; ID não configurado'): 'Ainda não publicado') ?></small></div>
  </div>
  <div class="sitemap-online-results" id="sitemap-online-results" aria-live="polite" role="status">Diagnóstico local concluído. Clique em <strong>Verificar online</strong> para conferir o HTTP dos três arquivos pelo navegador.</div>
  <p class="muted tiny">O diagnóstico acima consulta as configurações e os arquivos locais. Para confirmar HTTP 200, use “Verificar online”. Para conhecer cobertura e indexação reais, consulte o <a href="https://search.google.com/search-console" target="_blank" rel="noopener noreferrer">Google Search Console ↗</a>. O <a href="<?=h(admin_link('ads'))?>">ads.txt é administrado em Publicidade</a>.</p>
</section>
<section class="panel sitemap-center" aria-labelledby="sitemap-urls-title">
  <div class="panel-heading"><div><h2 id="sitemap-urls-title">Links do sitemap</h2><p class="muted">Pesquise e abra links públicos. A inclusão é automática conforme o estado de publicação de cada conteúdo.</p></div><span class="muted tiny"><?= $total ?> resultado(s)</span></div>
  <form class="sitemap-search" method="get" action="/admin.php"><input name="action" value="sitemap" type="hidden"><label for="sitemap-query">Buscar URL ou título<input id="sitemap-query" name="q" maxlength="100" value="<?=h($query)?>" placeholder="Nome, notícia, aplicativo..."></label><label for="sitemap-kind">Tipo<select name="kind" id="sitemap-kind"><?php foreach($kinds as $value=>$name): ?><option value="<?=h($value)?>" <?= $kind===$value?'selected':'' ?>><?=h($name)?><?php if($value!=='all'): ?> (<?=(int)($counts[$value]??0)?>)<?php endif; ?></option><?php endforeach; ?></select></label><button class="btn primary small" type="submit">Filtrar</button><a class="btn outline small" href="<?=h(admin_link('sitemap'))?>">Limpar</a></form>
  <div class="sitemap-url-list">
    <?php foreach($visible as $item): ?>
      <article class="sitemap-url-card" aria-label="<?=h(igua_sitemap_kind_title($item['kind']).': '.$item['label'])?>">
        <div class="sitemap-url-head"><strong><?=h($item['label'])?></strong><span><?=h(igua_sitemap_kind_title($item['kind']))?></span></div>
        <p class="sitemap-url-link"><?=h($item['loc'])?></p>
        <div class="sitemap-url-footer"><small><?= $item['lastmod']!==null?'Atualizada em '.h(date('d/m/Y',strtotime($item['lastmod']))):'Data não informada no sitemap' ?></small><div class="sitemap-url-actions"><a class="btn outline small" href="<?=h($item['loc'])?>" target="_blank" rel="noopener noreferrer">Abrir ↗</a><button class="btn outline small" type="button" data-sitemap-copy="<?=h($item['loc'])?>">Copiar</button><button class="btn outline small" type="button" data-sitemap-probe="<?=h($item['path'])?>">Testar HTTP</button></div></div>
        <small class="sitemap-probe-result" aria-live="polite"></small>
      </article>
    <?php endforeach; ?>
    <?php if(!$visible): ?><p class="muted">Nenhum link encontrado. Altere os filtros ou publique conteúdo elegível.</p><?php endif; ?>
  </div>
  <?php if($totalPages>1): ?><nav class="sitemap-pager" aria-label="Paginação dos links"><span>Página <?= $page ?> de <?= $totalPages ?></span><?php if($page>1): ?><a class="btn outline small" href="<?=h(igua_sitemap_admin_query($kind,$query,$page-1))?>">← Anterior</a><?php endif; ?><?php if($page<$totalPages): ?><a class="btn outline small" href="<?=h(igua_sitemap_admin_query($kind,$query,$page+1))?>">Próxima →</a><?php endif; ?></nav><?php endif; ?>
</section>
