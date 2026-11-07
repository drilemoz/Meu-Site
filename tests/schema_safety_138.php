<?php
declare(strict_types=1);
require_once __DIR__.'/../app/schema_migrations.php';
class Schema138Stmt extends PDOStatement {
    public function __construct(private Schema138PDO $db, private string $query) {}
    public function execute(?array $params=null): bool {
        if(str_starts_with($this->query,'INSERT')){$this->db->writes++; $this->db->marker=(string)$params[1];}
        return true;
    }
    public function fetchColumn(int $column=0):mixed{return $this->db->marker??false;}
}
class Schema138PDO extends PDO {
    public string $marker='37'; public int $writes=0;public int $ddl=0;
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false {return new Schema138Stmt($this,$query);}
}
function migrate_article_fields(PDO $db):array{$db->ddl++;return [];}
function migrate_editorial_schema(PDO $db):array{$db->ddl++;return [];}
function migrate_bus_schema(PDO $db):void {$db->ddl++;}
function migrate_ads_schema(PDO $db):void {$db->ddl++;}
function migrate_news_schema(PDO $db):void {$db->ddl++;}
function migrate_article_slug_schema(PDO $db):void {$db->ddl++;}
function migrate_analytics_schema(PDO $db):void {$db->ddl++;}
$db=new Schema138PDO;
try {igua_migrate_schema($db);throw new LogicException('Permitiu rebaixamento de versão');}
catch(RuntimeException $e) {if(!str_contains($e->getMessage(),'mais recente'))throw $e;}
if($db->marker!=='37'||$db->writes!==0||$db->ddl!==0)throw new RuntimeException('Código antigo modificou revisão futura');
echo "OK: revisão futura bloqueada sem escrita ou migração\n";
foreach(['abc','-1','33.5','0033','','999999999999999999999999999'] as $invalid){
    $db->marker=$invalid;
    try {igua_migrate_schema($db);throw new LogicException('Aceitou marcador inválido: '.$invalid);}
    catch(RuntimeException $e){if(!str_contains($e->getMessage(),'Revisão de esquema'))throw $e;}
}
if($db->ddl!==0||$db->writes!==0)throw new RuntimeException('Marcador inválido causou modificação');
echo "OK: marcadores inválidos e overflow recusados sem escrita\n";
$db->marker=(string)IGUA_SCHEMA_REVISION;
if(igua_migrate_schema($db)!==false || $db->writes!==0 || $db->ddl!==0)throw new RuntimeException('Revisão atual migrou novamente');
echo "OK: revisão atual não repete DDL\n";
