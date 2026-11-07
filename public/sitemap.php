<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
if (!installed()){http_response_code(404);exit;}
$base=setting('seo_site_url');
if(setting('seo_index','0')!=='1'||$base===''){http_response_code(404);exit('Sitemap não disponível: habilite SEO e configure o domínio público.');}
require __DIR__.'/../app/editorial.php';
try{$base=safe_seo_url($base);$db=db();}catch(Throwable){http_response_code(503);exit;}
header('Content-Type: application/xml; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
function xml_escape(string $s): string {return htmlspecialchars($s,ENT_XML1|ENT_QUOTES,'UTF-8');}
echo '<?xml version="1.0" encoding="UTF-8"?>',"\n",'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',"\n";
echo '<url><loc>',xml_escape($base.'/index.php'),'</loc></url>',"\n";
try { if ((int)$db->query('SELECT COUNT(*) FROM bus_routes r WHERE r.active=1 AND EXISTS (SELECT 1 FROM bus_departures d WHERE d.route_id=r.id AND d.active=1)')->fetchColumn()>0) echo '<url><loc>',xml_escape($base.url(['p'=>'onibus'])),'</loc></url>',"\n"; }catch(PDOException) {}
foreach($db->query("SELECT c.slug FROM categories c WHERE EXISTS(SELECT 1 FROM articles a WHERE a.category_id=c.id AND a.status='published' AND a.is_demo=0 AND a.published_at<=NOW()) ORDER BY c.id") as $r){echo '<url><loc>',xml_escape($base.url(['p'=>'categoria','slug'=>$r['slug']])),'</loc></url>',"\n";}
foreach($db->query("SELECT slug,updated_at FROM articles WHERE status='published' AND published_at<=NOW() AND is_demo=0 ORDER BY id DESC LIMIT 50000") as $r){echo '<url><loc>',xml_escape($base.url(['p'=>'noticia','slug'=>$r['slug']])),'</loc><lastmod>',date('Y-m-d',strtotime($r['updated_at'])),'</lastmod></url>',"\n";}
// Páginas de aplicativos publicadas também possuem URLs permanentes.
try {
    $pages=$db->query("SELECT a.slug AS app_slug,p.slug AS page_slug,r.created_at AS revised FROM legal_apps a JOIN legal_app_pages p ON p.app_id=a.id JOIN legal_app_revisions r ON r.id=p.published_revision_id AND r.page_id=p.id WHERE a.status='published' AND p.status='published'")->fetchAll();
    require_once __DIR__.'/../app/apps_legal.php';
    foreach($pages as $p){echo '<url><loc>',xml_escape($base.igua_app_page_url((string)$p['app_slug'],(string)$p['page_slug'])),'</loc><lastmod>',xml_escape(substr((string)$p['revised'],0,10)),'</lastmod></url>',"\n";}
}catch(PDOException $e){igua_health_log('SITEMAP_LEGAL_APP_FAILED',$e);}
echo '</urlset>';
