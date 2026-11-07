<?php
declare(strict_types=1);
require_once __DIR__.'/../app/schema_migrations.php';
function migration133(bool $ok,string $name): void {if(!$ok)throw new RuntimeException('Falha: '.$name);echo "OK: $name\n";}
class Mig133Stmt extends PDOStatement {
    public function __construct(private Mig133PDO $db,private string $sql){}
    public function execute(?array $params=null):bool{
        if(str_starts_with($this->sql,'INSERT')){$this->db->rev=(string)$params[1];$this->db->saved++;}
        return true;
    }
    public function fetchColumn(int $column=0):mixed{return $this->db->rev===null?false:$this->db->rev;}
}
class Mig133IndexStmt extends PDOStatement {
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array{return [];}
}
class Mig133PDO extends PDO {
    public ?string $rev=null;public int $saved=0;
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new Mig133Stmt($this,$query);}
    public function exec(string $statement):int|false{$this->ddlStatements[]=$statement;return 0;}
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs):PDOStatement|false{return new Mig133IndexStmt();}
    public array $ddlStatements=[];
}
$GLOBALS['migrations133']=[];
$GLOBALS['migration_fail133']=false;
function mig133(string $name):void{$GLOBALS['migrations133'][]=$name;if($GLOBALS['migration_fail133']&&$name==='bus')throw new RuntimeException('Falha simulada na migração');}
function migrate_article_fields(PDO $db):array{mig133('fields');return [];}
function migrate_editorial_schema(PDO $db):array{mig133('editorial');return [];}
function migrate_bus_schema(PDO $db):void{mig133('bus');}
function migrate_ads_schema(PDO $db):void{mig133('ads');}
function migrate_news_schema(PDO $db):void{mig133('news');}
function migrate_article_slug_schema(PDO $db):void{mig133('slug');}
function migrate_analytics_schema(PDO $db):void{mig133('analytics');}
$db=new Mig133PDO();
migration133(igua_schema_revision($db)===0,'esquema antigo ainda sem marcador');
migration133(igua_migrate_schema($db)===true,'esquema antigo migra');
migration133($db->saved===1 && $db->rev===(string)IGUA_SCHEMA_REVISION,'marcador gravado após sucesso');
migration133(count($GLOBALS['migrations133'])===7 && count($db->ddlStatements)===5 && str_contains($db->ddlStatements[0],'admin_remember_tokens') && str_contains($db->ddlStatements[1],'CREATE TABLE IF NOT EXISTS elections') && str_contains($db->ddlStatements[2],'CREATE TABLE IF NOT EXISTS election_candidates') && str_contains($db->ddlStatements[3],'idx_public_featured') && str_contains($db->ddlStatements[4],'idx_public_popular'),'migrações e tabela de tokens criadas na ordem');
migration133(igua_migrate_schema($db)===false && count($GLOBALS['migrations133'])===7,'esquema atual não refaz migrações');
$db->rev='31';$GLOBALS['migrations133']=[];$GLOBALS['migration_fail133']=true;
try{igua_migrate_schema($db);throw new LogicException('Aceitou migração falha');}
catch(RuntimeException){migration133($db->rev==='31'&&$db->saved===1,'falha não promove revisão do esquema');}
$GLOBALS['migration_fail133']=false;
migration133(igua_migrate_schema($db)===true && $db->rev===(string)IGUA_SCHEMA_REVISION,'migração pode ser repetida após falha');
echo "Migração 1.0.33: simulação aprovada; MySQL real pendente.\n";
