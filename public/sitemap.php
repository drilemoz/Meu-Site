<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
if(!installed()){http_response_code(404);exit;}
if(setting('seo_index','0')!=='1' || setting('seo_site_url','')===''){
    http_response_code(404);exit('Sitemap não disponível: habilite SEO e configure o domínio público.');
}
require __DIR__.'/../app/editorial.php';
require __DIR__.'/../app/sitemap_catalog.php';
try{$db=db();$catalog=igua_sitemap_catalog($db,setting('seo_site_url'));}
catch(Throwable $e){
    if(function_exists('igua_health_log'))igua_health_log('SITEMAP_GENERATION_FAILED',$e);
    http_response_code(503);exit('Sitemap temporariamente indisponível.');
}
header('Content-Type: application/xml; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
function xml_escape(string $s):string{return htmlspecialchars($s,ENT_XML1|ENT_QUOTES,'UTF-8');}
echo '<?xml version="1.0" encoding="UTF-8"?>',"\n",'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',"\n";
foreach($catalog['items'] as $entry){
    echo '<url><loc>',xml_escape($entry['loc']),'</loc>';
    if($entry['lastmod']!==null)echo '<lastmod>',xml_escape($entry['lastmod']),'</lastmod>';
    echo '</url>',"\n";
}
echo '</urlset>';
