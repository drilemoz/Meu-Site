<?php
declare(strict_types=1);
require_once __DIR__.'/../app/schema.php';
require_once __DIR__.'/../app/admin_controllers/post_routes.php';

function check254(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException($message);
}

class Category254Statement extends PDOStatement {
    public function __construct(private Category254PDO $db){}
    public function execute(?array $params=null):bool {
        [$name,$slug,$icon,$color,$order]=$params;
        $this->db->categories[$slug]??=compact('name','icon','color','order');
        return true;
    }
}
class Category254PDO extends PDO {
    public array $categories=[];
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false {
        check254(str_contains($query,'ON DUPLICATE KEY UPDATE slug=slug'),'Seed deve preservar a categoria existente.');
        return new Category254Statement($this);
    }
}

$db=new Category254PDO();
seed_initial_categories($db);
check254(count($db->categories)===11 && $db->categories['economia']['name']==='Economia','Economia na instalação nova.');
$db->categories['economia']['name']='Economia local';
seed_initial_categories($db);
check254(count($db->categories)===11 && $db->categories['economia']['name']==='Economia local','Seed repetido não duplica ou renomeia Economia.');

$root=dirname(__DIR__);
$admin=file_get_contents($root.'/public/admin.php');
$taxonomy=file_get_contents($root.'/app/admin_controllers/news/taxonomy.php');
$editor=file_get_contents($root.'/app/news_admin_view/details.php');
$public=file_get_contents($root.'/public/index.php');
check254(igua_admin_post_module('news_create_author')==='news','Rota de cadastro no controlador de notícias.');
check254(substr_count($admin,"'news_create_author'")===2 && str_contains($admin,'check_csrf();'),'Ação autorizada, protegida por CSRF e incluída no handler JSON.');
check254(str_contains($taxonomy,"require_ability('manage_taxonomy'") && str_contains($taxonomy,'INSERT INTO author_profiles(name) VALUES(?)'),'Cadastro somente com permissão, sem criar conta.');
check254(str_contains($editor,'data-news-taxonomy="author"') && str_contains($editor,'name="author_profile_id"'),'Autor selecionável no editor.');
check254(str_contains($public,'$profileAuthor!==\'\' ? $profileAuthor : $accountAuthor') && str_contains($public,"'@type'=>\$article['author_type']"),'Autor do perfil prevalece, nos dados estruturados e no texto.');
echo "OK: Economia padrão, idempotência, autoria e rotas protegidas\n";
