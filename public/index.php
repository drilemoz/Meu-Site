<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
require __DIR__.'/../app/news_archive.php';
require __DIR__.'/../app/news_queries.php';
require_once __DIR__.'/../app/article_workflow.php';
require __DIR__.'/../app/editorial.php';
require __DIR__.'/../app/bus_public.php';
require __DIR__.'/../app/contact.php';
require __DIR__.'/../app/ads.php';
require __DIR__.'/../app/analytics.php';
require_once __DIR__.'/../app/apps_legal.php';
if (!installed()) redirect('/setup.php');
try { $db=db(); } catch (Throwable $e) {igua_health_log('PUBLIC_DB_UNAVAILABLE',$e); http_response_code(503); exit('Banco de dados indisponível. Verifique a configuração.'); }
$route=(string)($_GET['p']??'inicio');
$legalPage=null;
if($route==='app'){
    $appSlug=(string)($_GET['app_slug']??'');
    $pageSlug=(string)($_GET['app_page']??'inicio');
    if(igua_app_slug_valid($appSlug) && igua_app_slug_valid($pageSlug)){
        $legalPage=igua_app_public($db,$appSlug,$pageSlug);
        if(!$legalPage && in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)){
            $replacement=igua_app_slug_alias($db,$appSlug,$pageSlug);
            if($replacement!==null){header('Location: '.igua_app_page_url($replacement,$pageSlug),true,301);exit;}
        }
    }
    if(!$legalPage){$route='404';http_response_code(404);}
}
if($route==='mais')redirect('/index.php?p=onibus');
$contactErrors=[];$contactInput=[];
if(in_array($route,['contato','anuncie'],true) && request_post()){
    $contactInput=$_POST;
    try{check_csrf();igua_contact_send($_POST);$_SESSION['contact_success']=true;redirect('/index.php?p='.$route);}
    catch(Throwable $e){$contactErrors[]=$e->getMessage();http_response_code(422);}
}

$categories=$db->query('SELECT id,name,slug,icon,color FROM categories ORDER BY sort_order,name')->fetchAll();
// A interface precisa apenas saber se existe acervo demonstrativo; não precisa contar todas as linhas.
$demoCount=has_published_demo_news($db) ? 1 : 0;
$GLOBALS['igua_demo_count']=$demoCount;
$filter=published_filter();
function news_link(array $article): string { return url(['p'=>'noticia','slug'=>$article['slug']]); }
function cat_link(array $c): string { return url(['p'=>'categoria','slug'=>$c['slug']]); }
function news_card(array $a, string $kind='normal'): void { ?>
  <a class="news-card <?= h($kind) ?>" href="<?= h(news_link($a)) ?>">
    <div class="thumb"><img data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($a['image'])) ?>" alt="" loading="lazy"></div>
    <div class="news-card-body"><span class="category-label" style="--category-color:<?= h($a['category_color']??'#1260e6') ?>"><?= h($a['category']??'Geral') ?></span>
      <h3><?= h($a['title']) ?></h3><p><?= h($a['summary']) ?></p>
      <span class="news-meta">◷ <?= h(readable_date($a['published_at'])) ?><?= $a['is_demo']?' · Exemplo':'' ?></span>
    </div>
  </a>
<?php }
function mini_card(array $a): void { ?>
  <a class="mini-card" href="<?= h(news_link($a)) ?>"><img data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($a['image'])) ?>" alt="" loading="lazy"><span><strong><?= h($a['title']) ?></strong><small>◷ <?= h(readable_date($a['published_at'])) ?><?= !empty($a['is_demo'])?' · Exemplo':'' ?></small></span></a>
<?php }
function section_category(PDO $db,array $c): void {
    $list=fetch_news($db,3,(int)$c['id']);
    if (!$list) return; // A página inicial não exibe módulos vazios.
    ?>
    <section class="section-column"><div class="section-heading"><h2><?= h($c['name']) ?></h2><a href="<?= h(cat_link($c)) ?>">Ver todas →</a></div>
      <?php if ($list): $main=array_shift($list); ?><a href="<?= h(news_link($main)) ?>" class="section-lead"><img data-image-fallback="/assets/img/cidade.jpg" src="<?= h(asset_image($main['image'])) ?>" alt="" loading="lazy"><h3><?= h($main['title']) ?></h3><p><?= h($main['summary']) ?></p></a><?php foreach ($list as $a) mini_card($a); else: ?><p class="muted">Em breve, novas notícias.</p><?php endif; ?>
    </section>
<?php }
$article=null;$category=null;$search=archive_search_term($_GET['q']??'');
if ($route==='noticia') {
    $stmt=$db->prepare("SELECT a.*,c.name AS category,c.slug AS category_slug,c.color AS category_color,u.name AS author FROM articles a LEFT JOIN categories c ON c.id=a.category_id LEFT JOIN users u ON u.id=a.author_id WHERE a.slug=? AND ".$filter.' LIMIT 1');
    $stmt->execute([(string)($_GET['slug']??'')]); $article=$stmt->fetch();
    if (!$article) {
        // Redireciona apenas um slug histórico de matéria já publicada, nunca rascunhos.
        try {
            $alias=$db->prepare("SELECT a.slug FROM article_slug_redirects r JOIN articles a ON a.id=r.article_id WHERE r.slug=? AND a.status='published' AND a.published_at<=NOW() LIMIT 1");
            $alias->execute([(string)($_GET['slug']??'')]);$newSlug=$alias->fetchColumn();
            if($newSlug!==false && in_array($_SERVER['REQUEST_METHOD']??'GET',['GET','HEAD'],true)){header('Location: '.url(['p'=>'noticia','slug'=>(string)$newSlug]),true,301);exit;}
        } catch(PDOException $e) {igua_health_log('NEWS_SLUG_REDIRECT_FAILED',$e);}
        http_response_code(404);$route='404';
    } else {
        // Ao ativar URLs bonitas, o antigo link querystring ainda funciona e redireciona.
        $canonicalPath=url(['p'=>'noticia','slug'=>(string)$article['slug']]);
        if(setting('seo_pretty_urls','0')==='1' && str_starts_with((string)($_SERVER['REQUEST_URI']??''),'/index.php?')) {
            header('Location: '.$canonicalPath,true,301);exit;
        }
        $db->prepare('UPDATE articles SET views=views+1 WHERE id=?')->execute([$article['id']]);
    }
} elseif ($route==='categoria') {
    foreach ($categories as $c) if ($c['slug']===($_GET['slug']??'')) { $category=$c;break; }
    if (!$category) {$route='404';http_response_code(404);}
    elseif(setting('seo_pretty_urls','0')==='1' && str_starts_with((string)($_SERVER['REQUEST_URI']??''),'/index.php?')){header('Location: '.archive_link('categoria',archive_page_number($_GET['page']??1),'',(string)$category['slug'],archive_period_days($_GET['period']??'')),true,301);exit;}
} elseif (!in_array($route,['inicio','buscar','onibus','privacidade','termos','anuncie','contato','sobre','app'],true)) {$route='404';http_response_code(404);}
$archivePage=archive_page_number($_GET['page']??1);
$archiveDays=archive_period_days($_GET['period']??'');
$archiveCatSlug=archive_category_slug($categories,$_GET['cat']??'');
$archiveCatId=archive_category_id($categories,$archiveCatSlug);
$archive=null;
if($route==='categoria' && $category) {
    $archive=archive_listing($db,$archivePage,'',(int)$category['id'],$archiveDays);
} elseif($route==='buscar') {
    $archive=archive_listing($db,$archivePage,$search,$archiveCatId,$archiveDays);
}
$GLOBALS['igua_page_route']=$route;
// Captura servidores e robôs; falhas de métricas nunca interrompem o portal.
if(($_SERVER['REQUEST_METHOD']??'GET')==='GET' && $route!=='404'){
    try{analytics_log($db,'pageview',(string)($_SERVER['REQUEST_URI']??'/index.php'),'',$_SERVER,$_COOKIE,$_GET);}catch(Throwable){}
}
if($article && !empty($article['image'])){
    try { $coverAlt=$db->prepare('SELECT alt_text FROM media_library WHERE filename=? AND deleted_at IS NULL LIMIT 1');$coverAlt->execute([(string)$article['image']]);$article['cover_alt']=(string)($coverAlt->fetchColumn()?:''); } catch(PDOException) {$article['cover_alt']='';}
}
if($article && !empty($article['author_profile_id'])){
    try{$pa=$db->prepare('SELECT name FROM author_profiles WHERE id=?');$pa->execute([$article['author_profile_id']]);$authorName=$pa->fetchColumn();if($authorName)$article['author']=$authorName;}catch(Throwable){}
}
$pageTitle= $legalPage ? (string)$legalPage['title'] : ($article ? (trim((string)($article['seo_title']??'')) ?: $article['title']) : ($category['name']??($route==='buscar'?'Buscar notícias':($route==='onibus'?'Horários de ônibus':(['anuncie'=>'Anuncie','contato'=>'Contato','sobre'=>'Sobre o portal','privacidade'=>'Política de Privacidade','termos'=>'Termos de Uso'][$route]??'Últimas notícias')))));
// Páginas fixas podem ter um título SEO completo, sem sufixo obrigatório.
// Títulos de notícias, categorias e aplicativos continuam sendo editados em seus próprios módulos.
$seoPageKey=['inicio'=>'seo_home_title','onibus'=>'seo_page_title_onibus','sobre'=>'seo_page_title_sobre','contato'=>'seo_page_title_contato','anuncie'=>'seo_page_title_anuncie','privacidade'=>'seo_page_title_privacidade','termos'=>'seo_page_title_termos'][$route]??'';
$seoCustomPageTitle=(!$article && !$category && !$legalPage && $seoPageKey!=='')?trim((string)setting($seoPageKey,'')):'';
$seoTitle=$seoCustomPageTitle!==''?$seoCustomPageTitle:page_title((string)$pageTitle);
$seoDesc=$legalPage ? (trim((string)$legalPage['seo_description']) ?: excerpt((string)$legalPage['body'],155)) : ($article ? (trim((string)($article['seo_description']??'')) ?: ($article['summary'] ?: excerpt($article['body'],155))) : ($route==='onibus'?'Consulte horários de ônibus programados, cidades de origem, destinos e próximas saídas em '.igua_brand_name().'.':igua_brand_description()));
$seoDesc=excerpt($seoDesc,260);
$indexable=$legalPage!==null || (setting('seo_index','0')==='1' && !in_array($route,['404','buscar'],true) && !($route==='categoria' && $archiveDays>0) && !($article && (int)$article['is_demo']===1) && ($article || $demoCount===0));
$siteBase=setting('seo_site_url');
try{$siteBase=safe_seo_url($siteBase);}catch(Throwable){$siteBase='';}
$relative = $legalPage ? igua_app_page_url((string)$legalPage['slug'],(string)$legalPage['page_slug']) : ($article ? news_link($article) : ($category ? archive_link('categoria',$archive['page'],'',(string)$category['slug'],$archiveDays) : ($route==='inicio' ? '/index.php' : url(['p'=>(in_array($route,['onibus','privacidade','termos','anuncie','contato','sobre'],true)?$route:'buscar')]))));
if($legalPage && $siteBase==='')$siteBase='https://iguatama.online';
$canonical=$siteBase!==''?$siteBase.$relative:'';
$socialImg=setting('seo_social_image');
if(str_starts_with($socialImg,'/uploads/') && $siteBase!=='')$socialImg=$siteBase.$socialImg;
if($article && $siteBase!=='')$socialImg=$siteBase.asset_image($article['image']);
if($legalPage)$socialImg=($legalPage['logo_filename']!=='' && $siteBase!=='')?$siteBase.asset_image((string)$legalPage['logo_filename']):'';
$ld=null;
if($article && $indexable && $canonical!=='')$ld=['@context'=>'https://schema.org','@type'=>'NewsArticle','headline'=>$article['title'],'description'=>$seoDesc,'datePublished'=>date(DATE_ATOM,strtotime($article['published_at'])),'dateModified'=>date(DATE_ATOM,strtotime($article['updated_at'])),'author'=>['@type'=>'Person','name'=>$article['author']??'Redação '.igua_brand_name()],'publisher'=>['@type'=>'Organization','name'=>igua_brand_name(),'url'=>$siteBase],'mainEntityOfPage'=>$canonical,'image'=>$socialImg!==''?[$socialImg]:[]];
require __DIR__.'/../app/public_views/layout.php';
