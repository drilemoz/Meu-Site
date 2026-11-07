<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/content_maintenance.php';
function check158(bool $ok,string $what):void {if(!$ok)throw new RuntimeException('FALHOU: '.$what);echo "OK: $what\n";}
class Statement158 extends PDOStatement {
    public function __construct(private mixed $value=0, private array $rows=[]) {}
    public function fetchColumn(int $column=0):mixed {return $this->value;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array {return $this->rows;}
}
class FakeDB158 extends PDO {
    public array $sql=[];
    public bool $inTx=false;
    public bool $breakDelete=false;
    public function __construct(){}
    public function beginTransaction():bool {$this->inTx=true;$this->sql[]='BEGIN';return true;}
    public function inTransaction():bool {return $this->inTx;}
    public function commit():bool {$this->sql[]='COMMIT';$this->inTx=false;return true;}
    public function rollBack():bool {$this->sql[]='ROLLBACK';$this->inTx=false;return true;}
    public function query(string $query,?int $fetchMode=null,mixed ...$args):PDOStatement|false {
        $this->sql[]=$query;
        return new Statement158(3);
    }
    public function exec(string $statement):int|false {
        $this->sql[]=$statement;
        if ($this->breakDelete && str_starts_with($statement,'DELETE FROM articles')) throw new PDOException('simulated');
        return 3;
    }
}
$root=dirname(__DIR__);
$layout=file_get_contents($root.'/app/public_views/layout.php');
$article=file_get_contents($root.'/app/public_views/routes/article.php');
$setup=file_get_contents($root.'/public/setup.php');
$schema=file_get_contents($root.'/app/schema.php');
check158(!str_contains($layout,'<div class="demo-banner">'),'página geral não apresenta banner de demonstração');
check158(str_contains($article,"if ($".'article[\'is_demo\'])'),'matéria demonstrativa contém aviso individual');
check158(str_contains($article,'Acervo de referência — matéria demonstrativa'),'identificação específica para notícia demonstrativa');
check158(str_contains($setup,'seed_initial_categories($db);'),'instalador cria categorias independente de notícia demonstrativa');
check158(!str_contains($setup,"!request_post()||!empty(\$_POST['demo'])"),'notícias fictícias desmarcadas por padrão');
check158(str_contains($schema,'if (!$db->inTransaction())'),'sem DDL durante transação do instalador');
check158(str_contains($setup,"$".'action===\'reset_existing\''),'instalador permite reset autenticado de instalação recuperável');
$demo=new FakeDB158();check158(igua_content_clear($demo,'demo')===3,'limpeza de demonstrações retorna contagem');
check158(in_array('DELETE FROM articles WHERE is_demo=1',$demo->sql,true),'limpeza não remove matérias reais');
check158(in_array('DELETE FROM article_drafts WHERE article_id IN (SELECT id FROM articles WHERE is_demo=1)',$demo->sql,true),'rascunhos vinculados à demonstração removidos');
check158(end($demo->sql)==='COMMIT','transação confirmada');
check158(!array_filter($demo->sql,static fn($q)=>str_contains($q,'DELETE FROM categories')||str_contains($q,'DELETE FROM editorial_sources')),'categorias e fontes preservadas');
$all=new FakeDB158();check158(igua_content_clear($all,'all')===3,'limpeza geral retorna contagem');
check158(in_array('DELETE FROM articles',$all->sql,true) && in_array('DELETE FROM article_drafts',$all->sql,true),'limpeza de notícias e rascunhos');
$fail=new FakeDB158();$fail->breakDelete=true;
try{igua_content_clear($fail,'demo');check158(false,'rollback em falha');}catch(PDOException){check158(in_array('ROLLBACK',$fail->sql,true),'falhas são revertidas');}
try{igua_content_clear(new FakeDB158(),'invalid');check158(false,'opção inválida');}catch(InvalidArgumentException){check158(true,'rejeita tipo de limpeza desconhecido');}
$controller=file_get_contents($root.'/app/admin_controllers/maintenance.php');
check158(str_contains($controller,"\$backup=make_backup(\$db,'manual');"),'backup obrigatório antes de exclusão');
check158(str_contains($controller,'password_verify($pass,$hash)'),'reinstalação exige senha administrativa');
check158(str_contains($controller,"hash_equals('REINSTALAR'"),'reinstalação exige confirmação textual');
$routes=file_get_contents($root.'/app/admin_controllers/post_routes.php');
check158(str_contains($routes,"'maintenance_reset'=>'maintenance'"),'ação de reset passa pelo dispatcher fechado');
echo "Testes 1.0.58 aprovados; sem acesso ou alteração a MySQL real.\n";
