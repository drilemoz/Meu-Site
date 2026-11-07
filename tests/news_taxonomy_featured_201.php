<?php
declare(strict_types=1);
require_once __DIR__.'/../app/admin_controllers/post_routes.php';
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/news_archive.php';
require_once __DIR__.'/../app/news_queries.php';
require_once __DIR__.'/../app/news_query_plans.php';
function regression201(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('Regressão 1.0.101: '.$message);
    echo "OK: $message\n";
}
$admin=(string)file_get_contents(__DIR__.'/../public/admin.php');
$newsController=(string)file_get_contents(__DIR__.'/../app/admin_controllers/news.php');
$js=(string)file_get_contents(__DIR__.'/../public/assets/js/news-editor-modules/08-submit-init.js');
foreach(['news_create_category','news_create_source'] as $action){
    regression201(igua_admin_post_module($action)==='news','controlador POST registrado: '.$action);
    regression201(str_contains($admin,"'".$action."'") && preg_match('/if\s*\(!in_array\(\$action,\[(.*?)\],true\)\)/s',$admin,$m)===1
        && str_contains($m[1],"'".$action."'"),'ação autorizada pela lista HTTP: '.$action);
    regression201(str_contains($newsController,"'".$action."'"),'implementação de gravação existente: '.$action);
}
regression201(str_contains($admin,'check_csrf();') && str_contains($newsController,"require_ability('manage_taxonomy'"),'CSRF e permissão de taxonomia preservados');
regression201(str_contains($admin,'ADMIN_TAXONOMY_FAILED') && str_contains($admin,'http_response_code($internal?500:422)')
    && str_contains($js,'response.json().catch(()=>null)'),'erros AJAX têm status, JSON e diagnóstico privado');

class Featured201Stmt extends PDOStatement {
    public function __construct(public string $sql, private array|false $row){}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed{return $this->row;}
}
class Featured201DB extends PDO {
    public string $lastSql='';
    public array|false $row=false;
    public function __construct(){}
    public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false{
        $this->lastSql=$query;
        // Emular linha não marcada: teste exige filtro antes de retornar algo.
        $eligible=str_contains($query,'a.featured=1');
        return new Featured201Stmt($query,$eligible?$this->row:false);
    }
}
$db=new Featured201DB();
$db->row=['id'=>10,'title'=>'Notícia marcada'];
regression201(fetch_featured_news($db)['id']===10,'notícia com destaque pode ocupar a capa');
regression201(str_contains($db->lastSql,'a.featured=1') && str_contains($db->lastSql,"a.status = 'published'")
    && str_contains($db->lastSql,'a.published_at DESC,a.id DESC LIMIT 1'), 'destaque filtrado e ordenado sem promover não marcadas');
$db->row=false;
regression201(fetch_featured_news($db)===false,'sem notícia marcada, nenhum destaque automático');
$plans=igua_news_explain_cases();
regression201(str_contains($plans['destaque'][0],'a.featured=1'),'plano EXPLAIN mantém o mesmo filtro');
$home=(string)file_get_contents(__DIR__.'/../app/public_views/routes/home.php');
regression201(str_contains($home,'Acompanhe as notícias de Iguatama e região.') &&
    !str_contains($home,'Publique a primeira notícia'), 'home usa comunicação neutra quando não há notícia em destaque');
echo "1.0.101: regressões de rotas AJAX e destaque aprovadas.\n";
