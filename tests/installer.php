<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/installer.php';
function assert_install(bool $v, string $label): void { if (!$v) throw new RuntimeException('FALHOU: '.$label); echo "OK: $label\n"; }
class SqlRecorder extends PDO {
    public array $statements=[];
    public bool $failFirst=false;
    public function __construct() {}
    public function quote(string $string, int $type=PDO::PARAM_STR): string|false { return "'".str_replace("'","''",$string)."'"; }
    public function exec(string $statement): int|false {
        $this->statements[]=$statement;
        if ($this->failFirst && count($this->statements)===1) { $err=new PDOException('Database exists'); $err->errorInfo=['HY000',1007,'Database exists'];throw $err;}
        return 1;
    }
}
assert_install(igua_sql_identifier('iguanews')==='`iguanews`','identificador seguro');
foreach (['x;DROP DATABASE t','../x','com espaco','x`','9banco'] as $bad) {
    try {igua_sql_identifier($bad);throw new RuntimeException('Aceitou nome inválido: '.$bad);} catch (InvalidArgumentException) {}
}
assert_install(true,'rejeita injeção em nome de banco');
assert_install(igua_auto_local_host('localhost') && !igua_auto_local_host('site.remoto.com'),'provisionamento exclusivo local');
$_SERVER['REMOTE_ADDR']='127.0.0.1';assert_install(igua_local_browser(),'página aberta localmente pode provisionar');
$_SERVER['REMOTE_ADDR']='192.168.1.4';assert_install(!igua_local_browser(),'navegador remoto não pode provisionar');
$fake=new SqlRecorder();
$r=igua_provision_database($fake,'iguanews');
assert_install(count($fake->statements)===5,'cinco instruções de provisionamento');
assert_install(str_starts_with($fake->statements[0],'CREATE DATABASE `iguanews`'),'criação exclusivamente novo banco');
assert_install(!str_contains(implode(' ',$fake->statements),'@\'%\''),'sem usuário exposto em todos hosts');
assert_install(substr_count(implode(' ',$fake->statements),'GRANT SELECT')===2,'GRANT limitado a banco e hosts locais');
assert_install(strlen($r['password'])===48 && str_starts_with($r['username'],'iguanews_'),'senha aleatória forte e usuário exclusivo');
assert_install(!str_contains(implode(' ',$fake->statements),'GRANT ALL PRIVILEGES ON *.*'),'não atribui privilégios globais');
$blocked=new SqlRecorder();$blocked->failFirst=true;
try {igua_provision_database($blocked,'iguanews');throw new RuntimeException('Ignorou banco existente');}catch(PDOException){}
assert_install(count($blocked->statements)===1,'não altera banco existente sem autorização explícita');

class DemoPDOStatement extends PDOStatement {
    public function __construct(private array $rows=[]) {}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->rows; }
    public function fetchColumn(int $column=0): mixed { return $this->rows[0]??false; }
}
class ExistingDbRecorder extends SqlRecorder {
    public array $tableList=[];
    public int $tableCount=0;
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement|false {
        if (str_starts_with($query,'SHOW TABLES')) return new DemoPDOStatement($this->tableList);
        if (str_starts_with($query,'SELECT COUNT')) return new DemoPDOStatement([$this->tableCount]);
        throw new RuntimeException('Consulta inesperada: '.$query);
    }
}
$reuse=new ExistingDbRecorder();$reuse->failFirst=true;
$reused=igua_provision_database($reuse,'iguanews','127.0.0.1',3306,true);
assert_install(count($reuse->statements)===5 && $reused['database']==='iguanews','permite reaproveitar base existente sem tabelas, com autorização');
$hasNews=new ExistingDbRecorder();$hasNews->failFirst=true;$hasNews->tableList=['articles'];$hasNews->tableCount=1;
try {igua_provision_database($hasNews,'iguanews','127.0.0.1',3306,true); throw new RuntimeException('Permitiu alterar base ocupada');}catch(RuntimeException $e){if(str_contains($e->getMessage(),'Permitiu alterar')) throw $e;}
assert_install(count($hasNews->statements)===1,'bloqueia base anterior que tem dados');
$foreign=new ExistingDbRecorder();$foreign->failFirst=true;$foreign->tableList=['outro_app'];
try {igua_provision_database($foreign,'iguanews','127.0.0.1',3306,true);throw new RuntimeException('Permitiu tabela desconhecida');}catch(RuntimeException $e){if(str_contains($e->getMessage(),'Permitiu tabela')) throw $e;}
assert_install(count($foreign->statements)===1,'bloqueia base com tabelas de outros sistemas');

echo "Testes unitários do provisionamento concluídos (sem MySQL real).\n";
