<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/sitemap_admin.php';
function sitemap200(bool $ok,string $desc):void {if(!$ok)throw new RuntimeException('Sitemap 200: '.$desc);}
class SitemapStmt200 extends PDOStatement {
    private int $idx=0;
    public function __construct(private array $rows){}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $orientation=PDO::FETCH_ORI_NEXT,int $offset=0):mixed{return $this->rows[$this->idx++]??false;}
    public function fetchColumn(int $column=0):mixed{return $this->rows[0]['count']??false;}
}
class SitemapPDO200 extends PDO {
    public array $queries=[];
    public function __construct(){}
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs):PDOStatement|false{
        $this->queries[]=$query;
        if(str_contains($query,'FROM bus_routes'))return new SitemapStmt200([['count'=>1]]);
        if(str_contains($query,'FROM categories c'))return new SitemapStmt200([
            ['slug'=>'esportes','name'=>'Esportes'],['slug'=>'./invalid','name'=>'Inválida']]);
        if(str_contains($query,'FROM articles WHERE'))return new SitemapStmt200([
            ['slug'=>'noticia-nova','title'=>'Notícia nova','updated_at'=>'2026-11-07 14:00:00'],
            ['slug'=>'teste-xss','title'=>'<script>alert(1)</script>','updated_at'=>'data não válida']]);
        if(str_contains($query,'FROM legal_apps a'))return new SitemapStmt200([
            ['app_slug'=>'uaicortes','page_slug'=>'inicio','title'=>'Apresentação','revised'=>'2026-11-07 11:00:00'],
            ['app_slug'=>'uaicortes','page_slug'=>'privacidade','title'=>'Política de Privacidade','revised'=>'2026-11-07 11:00:00']]);
        throw new RuntimeException('Consulta não prevista: '.$query);
    }
}
$db=new SitemapPDO200();
$site='https://iguatama.online';
$catalog=igua_sitemap_catalog($db,$site);
sitemap200(count($catalog['items'])===7 && !$catalog['overflow'],'home, ônibus, categoria, notícias e 2 páginas legais');
sitemap200($catalog['items'][0]['loc']===$site.'/index.php','home é canônica atual');
sitemap200(str_contains($catalog['items'][2]['loc'],'categoria') && str_contains($catalog['items'][3]['loc'],'noticia-nova'),'URL formada pelo roteamento do projeto');
sitemap200($catalog['items'][3]['lastmod']==='2026-11-07' && $catalog['items'][4]['lastmod']===null,'lastmod real sem inventar datas inválidas');
sitemap200($catalog['items'][5]['loc']===$site.'/uaicortes' && $catalog['items'][6]['loc']===$site.'/uaicortes/privacidade','páginas legais estáveis');
sitemap200(count($db->queries)===4 && str_contains($db->queries[2],"status='published'") && str_contains($db->queries[2],'is_demo=0') && str_contains($db->queries[3],"a.status='published'"),'SQL seleciona apenas páginas publicadas e não demonstrativas');
sitemap200(count(igua_sitemap_filter($catalog['items'],'aplicativo','privacidade'))===1,'filtros de tipo e busca');
sitemap200(count(igua_sitemap_filter($catalog['items'],'noticia',''))===2,'filtro de notícias');
sitemap200(igua_sitemap_admin_query('noticia','teste <x>',2)==='/admin.php?action=sitemap&kind=noticia&q=teste%20%3Cx%3E&page=2','parâmetros codificados');
$limited=igua_sitemap_catalog($db,$site,3);
sitemap200(count($limited['items'])===3 && $limited['overflow'],'limite não excede o sitemap e sinaliza conteúdo restante');
foreach(['','http://iguatama.online','https://iguatama.online/subpasta','https://usuario:senha@site.com'] as $bad){
    $rejected=false;try{igua_sitemap_base($bad);}catch(InvalidArgumentException){$rejected=true;}
    sitemap200($rejected,'base HTTPS estrita: '.$bad);
}
$root=sys_get_temp_dir().'/sitemap200-'.bin2hex(random_bytes(4));mkdir($root);
file_put_contents($root.'/robots.txt',"User-agent: *\nDisallow: /admin.php\nSitemap: https://iguatama.online/sitemap.php\n");
$pub='ca-pub-1234567890123456';file_put_contents($root.'/ads.txt',igua_adsense_ads_txt_line($pub)."\n");
$checks=igua_sitemap_local_diagnostics($site,true,$root,$pub);
sitemap200($checks['sitemap_announced'] && $checks['ads_match'] && $checks['robots_exists'] && $checks['indexing_on'],'diagnóstico local positivo para sitemap/ads');
$checks=igua_sitemap_local_diagnostics($site,false,$root,'ca-pub-9999999999999999');
sitemap200(!$checks['indexing_on'] && !$checks['ads_match'],'desativação e ID AdSense diferente não são aprovação');
@unlink($root.'/robots.txt');@unlink($root.'/ads.txt');rmdir($root);
$admin=(string)file_get_contents(__DIR__.'/../public/admin.php');
$layout=(string)file_get_contents(__DIR__.'/../app/admin_views/layout.php');
$routes=(string)file_get_contents(__DIR__.'/../app/admin_views/pages.php');
$xml=(string)file_get_contents(__DIR__.'/../public/sitemap.php');
$robots=(string)file_get_contents(__DIR__.'/../public/robots.txt');
sitemap200(str_contains($admin,"'sitemap'=>'manage_settings'") && str_contains($admin,"'seo','sitemap'") && str_contains($layout,"admin_link('sitemap')") && str_contains($routes,"$"."action==='sitemap'"),'seção administrativa privada e navegável');
sitemap200(str_contains($xml,'igua_sitemap_catalog') && !str_contains($xml,"status='draft'"),'XML usa mesma fonte, sem rascunhos');
sitemap200(str_contains($robots,'Sitemap: https://iguatama.online/sitemap.php'),'robots declara endereço XML');
$view=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/sitemap.php');
sitemap200(str_contains($view,'data-sitemap-probe') && str_contains($view,'data-sitemap-copy') && str_contains($view,'Verificar online') && str_contains($view,'Google Search Console'),'busca e verificação sob demanda na interface');
echo "Sitemap 200: catálogo, segurança, filtros, rotas, lastmod, robots e ads.txt aprovados.\n";
