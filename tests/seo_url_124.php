<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_editor_view_source.php';
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/article_workflow.php';
require __DIR__.'/../app/backup.php';
function check_url124(bool $value,string $name):void {if(!$value)throw new RuntimeException('Falhou: '.$name);echo "OK: $name\n";}
class SeoURLStatement extends PDOStatement {
    private mixed $value=false;
    public function __construct(private SeoURLDatabase $db,private string $query){}
    public function execute(?array $params=null):bool {
        $key=(string)($params[0]??'');
        if(str_contains($this->query,'SELECT id FROM articles'))$this->value=$this->db->active[$key]??false;
        elseif(str_contains($this->query,'SELECT article_id FROM article_slug_redirects'))$this->value=$this->db->aliases[$key]??false;
        elseif(str_starts_with($this->query,'DELETE FROM article_slug_redirects')){
            if(($this->db->aliases[$key]??0)===(int)$params[1])unset($this->db->aliases[$key]);
        }elseif(str_starts_with($this->query,'INSERT INTO article_slug_redirects')){
            if(isset($this->db->aliases[$key]))throw new PDOException('Registro duplicado');
            $this->db->aliases[$key]=(int)$params[1];
        }
        return true;
    }
    public function fetchColumn(int $column=0):mixed{return $this->value;}
}
class SeoURLDatabase extends PDO {
    public array $active=['noticia-da-cidade'=>3];
    public array $aliases=['endereco-historico'=>3,'slug-reservado'=>4];
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new SeoURLStatement($this,$query);}
}
$db=new SeoURLDatabase();
check_url124(article_editor_slug($db,'Nova manchete','',3,'noticia-da-cidade')==='noticia-da-cidade','edição mantém URL existente');
$legacyLong=str_repeat('slug-legado-',17).'a';
check_url124(article_editor_slug($db,'Nova manchete',$legacyLong,3,$legacyLong)===$legacyLong,'slug legado longo não é truncado');
check_url124(article_editor_slug($db,'Uma pauta','Trânsito na MG 050!',0)==='transito-na-mg-050','normaliza slug manual com acentos');
check_url124(article_editor_slug($db,'Notícia da cidade','')==='noticia-da-cidade-2','slug automático evita endereço ativo');
check_url124(article_editor_slug($db,'Endereço histórico','')==='endereco-historico-2','slug automático evita endereço histórico');
foreach(['noticia-da-cidade','slug-reservado'] as $requested){
    try {article_editor_slug($db,'Título',$requested,8);throw new LogicException('Aceitou slug ocupado');}
    catch(RuntimeException $e){check_url124(str_contains($e->getMessage(),'já pertence'),'rejeita colisão de endereço ativo/histórico');}
}
try {article_editor_slug($db,'Título','!?#',0);throw new LogicException('Aceitou URL vazia');}
catch(RuntimeException $e){check_url124(str_contains($e->getMessage(),'letras ou números'),'recusa slug manual sem caracteres válidos');}
article_record_slug_change($db,3,'noticia-da-cidade','outro-nome');
check_url124(($db->aliases['noticia-da-cidade']??0)===3,'armazena slug antigo em histórico');
article_record_slug_change($db,3,'outro-nome','endereco-historico');
check_url124(!isset($db->aliases['endereco-historico'])&&($db->aliases['outro-nome']??0)===3,'permite recuperar antigo endereço da mesma matéria');
foreach([9=>BACKUP_TABLES_V9,10=>BACKUP_TABLES_V10,11=>BACKUP_TABLES_V11,12=>BACKUP_TABLES] as $version=>$tableNames){
    $tables=array_fill_keys($tableNames,[]);$tables['users']=[['id'=>1,'role'=>'admin','active'=>1]];
    if($version===10)$tables['article_slug_redirects']=[['slug'=>'endereco-antigo','article_id'=>1]];
    $parsed=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>$version,'tables'=>$tables],JSON_THROW_ON_ERROR),'teste.json');
    check_url124(array_keys($parsed['tables'])===BACKUP_TABLES,'restaura formato v'.$version.' com estrutura atual');
    check_url124(count($parsed['tables']['article_slug_redirects'])===($version===10?1:0),'histórico preservado quando existente, vazio em v9');
}
$root=dirname(__DIR__);
check_url124(str_contains(file_get_contents($root.'/public/index.php'),'header(\'Location: \'.url')&&str_contains(file_get_contents($root.'/public/index.php'),',true,301)'),'redirecionamento permanente disponível');
check_url124(str_contains(file_get_contents($root.'/app/admin_controllers/news.php'),'article_record_slug_change')&&str_contains(file_get_contents($root.'/app/admin_controllers/news.php'),'$db->beginTransaction()'),'mudança de slug participa da transação editorial');
check_url124(str_contains(file_get_contents($root.'/public/.htaccess'),'RewriteRule ^(noticia|categoria)'),'Apache tem rota de notícia e categoria');
check_url124(is_file($root.'/public/router.php'),'PHP Termux possui roteador opcional');
check_url124(substr_count(igua_news_editor_view_source(),'data-tab="')===9,'editor mantém três etapas');
echo "SEO URL 1.0.24: verificações isoladas aprovadas; migração MySQL e navegador pendentes.\n";
