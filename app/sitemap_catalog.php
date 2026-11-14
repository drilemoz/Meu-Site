<?php
declare(strict_types=1);
/** Fonte única das URLs elegíveis para o sitemap e a central administrativa.
 * Não consulta sites externos nem altera dados. Nenhuma URL de rascunho/demo é publicada.
 */

function igua_sitemap_base(string $configured): string {
    // A central e o XML devem rejeitar a mesma configuração insegura.
    $base=safe_seo_url($configured);
    if($base==='') throw new InvalidArgumentException('Configure o domínio HTTPS oficial em Configurações e SEO.');
    $parts=parse_url($base);
    if(!is_array($parts) || !in_array((string)($parts['path']??''),['','/'],true)
       || isset($parts['port']) || isset($parts['user']) || isset($parts['pass']))
        throw new InvalidArgumentException('O sitemap exige o domínio HTTPS oficial, sem caminho ou porta.');
    return $base;
}

function igua_sitemap_date(mixed $raw): ?string {
    if(!is_string($raw)||!preg_match('/^(\d{4})-(\d{2})-(\d{2})(?:[ T].*)?$/D',$raw,$m))return null;
    return checkdate((int)$m[2],(int)$m[3],(int)$m[1])?$m[1].'-'.$m[2].'-'.$m[3]:null;
}

function igua_sitemap_item(string $base,string $path,string $kind,string $label,?string $date=null): array {
    if(!str_starts_with($path,'/')||str_starts_with($path,'//')||str_contains($path,'\\')
       || preg_match('/[\x00-\x1f\x7f]/',$path)||strlen($path)>1600)
        throw new InvalidArgumentException('Caminho do sitemap inválido.');
    $loc=$base.$path;
    if(strlen($loc)>2048)throw new InvalidArgumentException('URL do sitemap excede 2048 bytes.');
    return ['loc'=>$loc,'path'=>$path,'kind'=>$kind,'label'=>$label,'lastmod'=>igua_sitemap_date($date)];
}

/** Retorna as mesmas entradas que o XML público, com limite do protocolo.
 * A última entrada excedente é detectada e mostrada como alerta no painel, nunca
 * impressa no XML. Futuro volume superior ao limite exige fragmentação do sitemap.
 * @return array{items:array,overflow:bool}
 */
function igua_sitemap_catalog(PDO $db,string $configured,int $limit=50000): array {
    $base=igua_sitemap_base($configured);
    if($limit<1||$limit>50000)throw new InvalidArgumentException('Limite de sitemap inválido.');
    $items=[];$overflow=false;
    $append=static function(array $item)use(&$items,&$overflow,$limit):void {
        if(count($items)>=$limit){$overflow=true;return;}
        $items[]=$item;
    };
    $append(igua_sitemap_item($base,'/index.php','site','Página inicial'));
    try {
        $query='SELECT COUNT(*) FROM bus_routes r WHERE r.active=1 AND EXISTS (SELECT 1 FROM bus_departures d WHERE d.route_id=r.id AND d.active=1)';
        if((int)$db->query($query)->fetchColumn()>0)
            $append(igua_sitemap_item($base,url(['p'=>'onibus']),'site','Horários de ônibus'));
    }catch(PDOException) { /* instalação sem módulo opcional */ }
    $categories=$db->query("SELECT c.slug,c.name FROM categories c WHERE EXISTS(SELECT 1 FROM articles a WHERE a.category_id=c.id AND a.status='published' AND a.is_demo=0 AND a.published_at<=NOW()) ORDER BY c.id LIMIT ".($limit+1));
    while(($row=$categories->fetch(PDO::FETCH_ASSOC))!==false){
        $slug=(string)$row['slug'];
        if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug))continue;
        $append(igua_sitemap_item($base,url(['p'=>'categoria','slug'=>$slug]),'categoria',(string)$row['name']));
        if($overflow)break;
    }
    // O limit é inteiro local (nunca um parâmetro SQL fornecido pelo navegador).
    $articles=$db->query("SELECT slug,title,updated_at FROM articles WHERE status='published' AND published_at<=NOW() AND is_demo=0 ORDER BY id DESC LIMIT ".($limit+1));
    while(($row=$articles->fetch(PDO::FETCH_ASSOC))!==false){
        $slug=(string)$row['slug'];
        if(!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug))continue;
        $append(igua_sitemap_item($base,url(['p'=>'noticia','slug'=>$slug]),'noticia',(string)$row['title'],(string)$row['updated_at']));
        if($overflow)break;
    }
    try {
        require_once __DIR__.'/apps_legal.php';
        $legal=$db->query("SELECT a.slug AS app_slug,p.slug AS page_slug,p.title,r.created_at AS revised FROM legal_apps a JOIN legal_app_pages p ON p.app_id=a.id JOIN legal_app_revisions r ON r.id=p.published_revision_id AND r.page_id=p.id WHERE a.status='published' AND p.status='published' ORDER BY a.id,p.id LIMIT ".($limit+1));
        while(($row=$legal->fetch(PDO::FETCH_ASSOC))!==false){
            $app=(string)$row['app_slug'];$page=(string)$row['page_slug'];
            if(!igua_app_slug_valid($app)||!igua_app_slug_valid($page))continue;
            $append(igua_sitemap_item($base,igua_app_page_url($app,$page),'aplicativo',(string)$row['title'],(string)$row['revised']));
            if($overflow)break;
        }
    }catch(PDOException $e){
        if(function_exists('igua_health_log'))igua_health_log('SITEMAP_LEGAL_APP_FAILED',$e);
    }
    return ['items'=>$items,'overflow'=>$overflow];
}

/** Diagnóstico da configuração e de arquivos locais, sem confundir presença com HTTP 200 ou indexação Google. */
function igua_sitemap_local_diagnostics(string $base,bool $indexing,string $publicRoot,string $publisher): array {
    $configured='';
    try {$configured=igua_sitemap_base($base);}catch(Throwable) {}
    $robotsPath=rtrim($publicRoot,'/').'/robots.txt';
    $robots=is_file($robotsPath)?@file_get_contents($robotsPath,false,null,0,65536):false;
    $expected=$configured!==''?$configured.'/sitemap.php':'';
    $announced=false;
    if(is_string($robots)&&$expected!==''){
        foreach(preg_split('/\r\n|\r|\n/',$robots)?:[] as $line){
            if(preg_match('/^\s*Sitemap:\s*(\S+)\s*$/iD',trim($line),$m)&&$m[1]===$expected){$announced=true;break;}
        }
    }
    $adsPath=rtrim($publicRoot,'/').'/ads.txt';
    $ads=is_file($adsPath)&&!is_link($adsPath)?@file_get_contents($adsPath,false,null,0,65537):false;
    $adsMatch=false;
    if(is_string($ads)&&strlen($ads)<=65536&&$publisher!==''&&function_exists('igua_adsense_ads_txt_has_publisher'))
        $adsMatch=igua_adsense_ads_txt_has_publisher($ads,$publisher);
    return [
        'domain_ok'=>$configured!=='', 'indexing_on'=>$indexing,
        'sitemap_announced'=>$announced,'robots_exists'=>is_string($robots),
        'ads_exists'=>is_string($ads),'ads_match'=>$adsMatch,'ads_configured'=>preg_match('/^ca-pub-\d{16}$/D',$publisher)===1,
        'sitemap_url'=>$expected,
    ];
}
