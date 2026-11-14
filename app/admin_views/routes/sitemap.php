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
$perPage=12;$total=count($filtered);$totalPages=max(1,(int)ceil($total/$perPage));
$page=min($page,$totalPages);$visible=array_slice($filtered,($page-1)*$perPage,$perPage);
$counts=array_count_values(array_column($all,'kind'));
require_once __DIR__.'/../../indexnow.php';
$indexNow=igua_indexnow_read(IGUA_STORAGE);
$indexNowReady=$indexNow!==null && igua_indexnow_file_ready($indexNow,IGUA_PUBLIC);
$indexNowSiteOk=$indexNow!==null && $indexNow['site']===rtrim($siteUrl,'/');
$indexNowLast=$_SESSION['igua_indexnow_last']??null;

?>
<?php
require_once __DIR__.'/../../bing_webmaster.php';
$bingCred=igua_bing_read(IGUA_STORAGE);
$bingResult=$_SESSION['igua_bing_result']??null;
?>
<section class="panel sitemap-center sitemap-compact-home" aria-labelledby="sitemap-title">
  <div class="panel-heading sitemap-heading"><div><h2 id="sitemap-title">Sitemap e Indexação</h2><p class="muted">Links públicos e serviços de busca em um só lugar.</p></div><a class="btn outline small" href="<?=h(admin_link('settings'))?>">SEO</a></div>
  <div class="sitemap-compact-stats" aria-label="Resumo do sitemap">
    <div><strong><?=count($all)?></strong><span>URLs elegíveis</span></div>
    <div><strong><?=(int)($counts['noticia']??0)?></strong><span>Notícias</span></div>
    <div><strong><?=(int)($counts['aplicativo']??0)?></strong><span>Páginas de apps</span></div>
  </div>
  <?php if($catalogError!==''): ?><p class="alert warning"><?=h($catalogError)?></p><?php endif; ?>
  <?php if($catalog['overflow']): ?><p class="alert warning">O sitemap atingiu 50.000 URLs. Há mais páginas elegíveis que não cabem no XML atual. Será necessário dividir o sitemap em partes antes de incluir mais URLs; nenhuma publicação foi removida.</p><?php endif; ?>
  <?php if(!$indexEnabled): ?><p class="alert warning">A indexação está desativada. A listagem abaixo é apenas uma prévia: <code>/sitemap.php</code> responde 404 até a ativação em Configurações e SEO.</p><?php endif; ?>
  <div class="sitemap-shortcuts" aria-label="Ferramentas de indexação">
    <button type="button" class="btn outline" data-sitemap-open="sitemap-check-dialog"><span aria-hidden="true">◉</span> Verificações <small><?= $diagnostics['sitemap_announced']?'robots OK':'Atenção' ?></small></button>
    <button type="button" class="btn outline" id="indexnow" data-sitemap-open="sitemap-indexnow-dialog"><span aria-hidden="true">⇪</span> IndexNow <small><?= $indexNowReady&&$indexNowSiteOk?'Preparado':'Configurar' ?></small></button>
    <button type="button" class="btn outline" id="bing-webmaster" data-sitemap-open="sitemap-bing-dialog"><span aria-hidden="true">▣</span> Bing <small><?= $bingCred!==null?'Chave salva':'Configurar' ?></small></button>
  </div>
</section>
<section class="panel sitemap-center" aria-labelledby="sitemap-urls-title">
  <div class="panel-heading"><div><h2 id="sitemap-urls-title">Links do sitemap</h2><p class="muted">Consulte, copie ou teste os endereços públicos.</p></div><span class="muted tiny"><?= $total ?> resultado(s)</span></div>
  <form class="sitemap-search" method="get" action="/admin.php"><input name="action" value="sitemap" type="hidden"><label for="sitemap-query">Buscar URL ou título<input id="sitemap-query" name="q" maxlength="100" value="<?=h($query)?>" placeholder="Nome, notícia, aplicativo..."></label><label for="sitemap-kind">Tipo<select name="kind" id="sitemap-kind"><?php foreach($kinds as $value=>$name): ?><option value="<?=h($value)?>" <?= $kind===$value?'selected':'' ?>><?=h($name)?><?php if($value!=='all'): ?> (<?=(int)($counts[$value]??0)?>)<?php endif; ?></option><?php endforeach; ?></select></label><button class="btn primary small" type="submit">Filtrar</button><a class="btn outline small" href="<?=h(admin_link('sitemap'))?>">Limpar</a></form>
  <div class="sitemap-url-list">
    <?php foreach($visible as $item): ?>
      <article class="sitemap-url-card" aria-label="<?=h(igua_sitemap_kind_title($item['kind']).': '.$item['label'])?>">
        <div class="sitemap-url-head"><strong><?=h($item['label'])?></strong><span><?=h(igua_sitemap_kind_title($item['kind']))?></span></div>
        <p class="sitemap-url-link" title="<?=h($item['loc'])?>"><?=h($item['path'])?></p>
        <div class="sitemap-url-footer"><small><?= $item['lastmod']!==null?'Atualizada em '.h(date('d/m/Y',strtotime($item['lastmod']))):'Data não informada no sitemap' ?></small><div class="sitemap-url-actions"><a class="btn outline small" href="<?=h($item['loc'])?>" target="_blank" rel="noopener noreferrer">Abrir ↗</a><button class="btn outline small" type="button" data-sitemap-copy="<?=h($item['loc'])?>">Copiar</button><button class="btn outline small" type="button" data-sitemap-probe="<?=h($item['path'])?>">Testar HTTP</button></div></div>
        <small class="sitemap-probe-result" aria-live="polite"></small>
      </article>
    <?php endforeach; ?>
    <?php if(!$visible): ?><p class="muted">Nenhum link encontrado. Altere os filtros ou publique conteúdo elegível.</p><?php endif; ?>
  </div>
  <?php if($totalPages>1): ?><nav class="sitemap-pager" aria-label="Paginação dos links"><span>Página <?= $page ?> de <?= $totalPages ?></span><?php if($page>1): ?><a class="btn outline small" href="<?=h(igua_sitemap_admin_query($kind,$query,$page-1))?>">← Anterior</a><?php endif; ?><?php if($page<$totalPages): ?><a class="btn outline small" href="<?=h(igua_sitemap_admin_query($kind,$query,$page+1))?>">Próxima →</a><?php endif; ?></nav><?php endif; ?>
</section>

<dialog id="sitemap-check-dialog" class="igua-modal igua-modal--wide sitemap-tool-dialog" aria-label="Verificações de indexação">
  <div class="sitemap-tool-head"><h2>Verificações de indexação</h2><button type="button" class="admin-ui-close" data-sitemap-close aria-label="Fechar Verificações de indexação">×</button></div>
  <div class="sitemap-tool-scroll">
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

  </div>
</dialog>
<dialog id="sitemap-indexnow-dialog" class="igua-modal igua-modal--wide sitemap-tool-dialog" aria-label="IndexNow">
  <div class="sitemap-tool-head"><h2>IndexNow</h2><button type="button" class="admin-ui-close" data-sitemap-close aria-label="Fechar IndexNow">×</button></div>
  <div class="sitemap-tool-scroll">
  <div class="panel-heading"><div><h2 id="indexnow-title">IndexNow · avisar buscadores</h2><p class="muted">Notificações manuais para mecanismos participantes, como Bing. O Google não participa. Uma notificação recebida não garante indexação.</p></div><span class="int-state <?= $indexNowReady&&$indexNowSiteOk?'int-ready':'int-off' ?>"><?= $indexNowReady&&$indexNowSiteOk?'Preparado':'Não configurado' ?></span></div>
  <?php if(!$indexEnabled): ?><p class="alert warning">Ative a indexação do portal antes de configurar ou utilizar IndexNow.</p><?php endif; ?>
  <?php if($indexNow!==null): ?>
    <p>Arquivo de verificação: <a href="<?=h(igua_indexnow_key_url($indexNow))?>" target="_blank" rel="noopener noreferrer">Abrir chave pública ↗</a></p>
    <?php if(!$indexNowReady): ?><p class="alert warning">A verificação local do arquivo falhou. Confira as permissões e a pasta pública.</p><?php endif; ?>
    <?php if(!$indexNowSiteOk): ?><p class="alert warning">O domínio oficial mudou desde a configuração. Não faça envios sem revisar a chave.</p><?php endif; ?>
    <?php if($indexNowLast && is_array($indexNowLast)): ?><p class="muted tiny">Último envio nesta sessão: <?= (int)($indexNowLast['count']??0) ?> URL(s), HTTP <?= (int)($indexNowLast['status']??0) ?>, em <?=date('d/m/Y H:i',(int)($indexNowLast['time']??0))?>. Não comprova indexação.</p><?php endif; ?>
    <form method="post" action="/admin.php?action=indexnow_submit">
      <?=csrf_field()?><input type="hidden" name="indexnow_path" value="">
      <button class="btn primary" type="submit" data-confirm="Enviar até 10 URLs já publicadas ao IndexNow?" <?= !$indexEnabled||!$indexNowReady||!$indexNowSiteOk?'disabled':'' ?>>Notificar até 10 URLs</button>
      <small class="muted">Apenas notícias e páginas legais publicadas do sitemap. Sem envio automático ou chamadas ao abrir esta tela.</small>
    </form>
  <?php else: ?>
    <form method="post" action="/admin.php?action=indexnow_setup">
      <?=csrf_field()?><button class="btn primary" type="submit" data-confirm="Gerar arquivo público de verificação e configurar IndexNow para este domínio?" <?= !$indexEnabled||$catalogError!==''?'disabled':'' ?>>Configurar IndexNow</button>
      <small class="muted">O portal gera uma chave de validação e um arquivo TXT acessível ao público. A hospedagem deve permitir escrita na raiz pública.</small>
    </form>
  <?php endif; ?>
  <p class="muted tiny">Não substitui sitemap.xml, Google Search Console, robots.txt ou inspeção real de indexação. <a href="https://www.bing.com/indexnow/getstarted" target="_blank" rel="noopener noreferrer">Como funciona no Bing ↗</a></p>

  </div>
</dialog>
<dialog id="sitemap-bing-dialog" class="igua-modal igua-modal--wide sitemap-tool-dialog" aria-label="Bing Webmaster Tools">
  <div class="sitemap-tool-head"><h2>Bing Webmaster Tools</h2><button type="button" class="admin-ui-close" data-sitemap-close aria-label="Fechar Bing Webmaster Tools">×</button></div>
  <div class="sitemap-tool-scroll">
  <div class="panel-heading"><div><h2 id="bing-title">Bing Webmaster Tools</h2><p class="muted">Consulte a verificação do site e os sitemaps informados pelo Bing. Para avisar buscadores sobre notícias, use IndexNow acima.</p></div><span class="int-state <?= $bingCred!==null?'int-ready':'int-off' ?>"><?= $bingCred!==null?'Chave salva':'Não configurado' ?></span></div>
  <div class="sitemap-bing-verification" role="note">
    <strong>Verificação de propriedade (arquivo XML)</strong>
    <p class="muted">O arquivo fornecido pelo Bing está incluído no portal. Após instalar a atualização, abra o endereço abaixo e confirme que ele mostra o XML antes de tocar em <strong>Verify</strong> no Bing.</p>
    <a class="btn outline small" href="/BingSiteAuth.xml" target="_blank" rel="noopener noreferrer">Ver BingSiteAuth.xml ↗</a>
    <small>É público por exigência da verificação. Não é a chave da API nem substitui a confirmação feita no Bing.</small>
  </div>
  <div class="sitemap-bing-actions">
    <form method="post" action="/admin.php?action=bing_save" class="sitemap-bing-config">
      <?=csrf_field() ?>
      <label>Chave da API Bing <input name="bing_api_key" type="password" autocomplete="new-password" spellcheck="false" maxlength="256" placeholder="<?= $bingCred!==null?'Chave já salva; deixe vazio para conservar':'Cole a chave de API' ?>"></label>
      <label>Senha administrativa <input type="password" name="confirm_password" required autocomplete="current-password"></label>
      <button type="submit" class="btn primary">Salvar chave</button>
    </form>
    <form method="post" action="/admin.php?action=bing_test">
      <?=csrf_field() ?>
      <button type="submit" class="btn outline" <?= $bingCred===null?'disabled':'' ?>>Testar e consultar sitemaps</button>
    </form>
    <?php if($bingCred!==null): ?>
    <form method="post" action="/admin.php?action=bing_remove" class="sitemap-bing-remove">
      <?=csrf_field() ?><label>Senha para remover <input type="password" name="confirm_password" required autocomplete="current-password"></label>
      <label><input type="checkbox" name="confirm_remove" value="1" required> Confirmar remoção</label>
      <button type="submit" class="btn outline" data-confirm="Remover a chave do Bing? O IndexNow não será alterado.">Remover chave</button>
    </form>
    <?php endif; ?>
  </div>
  <?php if(is_array($bingResult)): ?>
    <div class="sitemap-bing-result" role="status">
      <strong><?= !empty($bingResult['verified'])?'Propriedade verificada no Bing':'Atenção à propriedade Bing' ?></strong>
      <p><?=h((string)($bingResult['message']??''))?></p>
      <small>Última consulta nesta sessão: <?=date('d/m/Y H:i',(int)($bingResult['checked_at']??0))?> · <?=h((string)($bingResult['site']??''))?></small>
      <?php if(!empty($bingResult['feeds'])): ?><div class="sitemap-bing-feeds">
        <?php foreach($bingResult['feeds'] as $feed): ?>
          <div><strong><?=h($feed['url'])?></strong><small>Estado Bing: <?=h($feed['status'])?> · <?= (int)$feed['count'] ?> URL(s) informadas</small></div>
        <?php endforeach; ?>
      </div><?php elseif(!empty($bingResult['verified'])): ?><p class="muted">O Bing não retornou sitemaps para esta propriedade.</p><?php endif; ?>
    </div>
  <?php else: ?><p class="muted tiny">Nenhuma consulta efetuada nesta sessão. A chave é usada somente no servidor, em chamadas manuais.</p><?php endif; ?>
  <p class="muted tiny">Obtenha a chave em <a href="https://www.bing.com/webmasters" target="_blank" rel="noopener noreferrer">Bing Webmaster Tools ↗</a> → Configurações → API Access. A API de leitura pode mudar; uma consulta bem-sucedida não garante indexação. <a href="https://learn.microsoft.com/en-us/bingwebmaster/getting-access" target="_blank" rel="noopener noreferrer">Ajuda oficial ↗</a></p>

  </div>
</dialog>
