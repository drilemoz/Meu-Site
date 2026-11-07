<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/news_archive.php';
require_once __DIR__.'/../app/news_query_plans.php';
require_once __DIR__.'/../app/schema_migrations.php';
require_once __DIR__.'/../app/article_workflow.php';
function check146(bool $yes,string $why):void {if(!$yes)throw new RuntimeException('1.0.46: '.$why);echo "OK: $why\n";}
class Fake146IndexStatement extends PDOStatement {
    public function __construct(private array $rows){}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array {return $this->rows;}
}
class Fake146IndexDB extends PDO {
    public array $names=[];public array $executed=[];public bool $fail=false;
    public function __construct(){}
    public function query(string $query, ?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false {
        check146($query==='SHOW INDEX FROM articles','migração consulta apenas metadados de índices');
        return new Fake146IndexStatement(array_map(static fn($n)=>['Key_name'=>$n],$this->names));
    }
    public function exec(string $statement):int|false {
        if ($this->fail && str_contains($statement,'idx_public_popular')) throw new PDOException('falha DDL simulada');
        $this->executed[]=$statement;
        if (preg_match('/ADD INDEX `([^`]+)`/',$statement,$m))$this->names[]=$m[1];
        return 0;
    }
}
$db=new Fake146IndexDB();
check146(IGUA_SCHEMA_REVISION===36,'revisão de esquema incrementada');
$added=igua_migrate_public_news_indexes($db);
check146($added===['idx_public_featured','idx_public_popular'] && count($db->executed)===2,'índices auxiliares criados somente quando ausentes');
check146(igua_migrate_public_news_indexes($db)===[] && count($db->executed)===2,'migração repetida não cria índices duplicados');
$db=new Fake146IndexDB();$db->fail=true;
try{igua_migrate_public_news_indexes($db);throw new RuntimeException('Falha DDL não propagada');}
catch(PDOException){check146($db->names===['idx_public_featured'],'falha parcial preserva primeiro índice para retry idempotente');}
$db->fail=false;
check146(igua_migrate_public_news_indexes($db)===['idx_public_popular'],'repetição completa apenas índice pendente');
[$condition,$params]=news_admin_conditions(['role'=>'admin'],'all','50%_!');
check146(str_contains($condition,"ESCAPE '!'") && $params===['%50!%!_!!%','%50!%!_!!%'], 'busca do painel trata coringas como texto literal');
$cases=igua_news_explain_cases();
check146(count($cases)===5 && str_contains($cases['destaque'][0],'featured DESC') && str_contains($cases['mais_lidas'][0],'views DESC'),'EXPLAIN cobre rankings e filtros principais');
check146($cases['busca_trecho'][1]===['%50!%!_!!%','%50!%!_!!%'] && str_contains($cases['busca_trecho'][0],"LIKE ? ESCAPE '!'") ,'EXPLAIN usa os mesmos escapes de busca pública');
class Fake146PlanStmt extends PDOStatement {
    public function __construct(private Fake146PlanDB $db,private string $sql){}
    public function execute(?array $params=null):bool {$this->db->executed[]=['sql'=>$this->sql,'params'=>$params??[]];return true;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array{return [['table'=>'a','type'=>'range','possible_keys'=>'idx_list','key'=>'idx_list','rows'=>12,'Extra'=>'Using where']];}
}
class Fake146PlanDB extends PDO {
    public array $executed=[];
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false {return new Fake146PlanStmt($this,$query);}
}
$planDB=new Fake146PlanDB();$report=igua_news_explain($planDB);
check146(count($report)===5 && count($planDB->executed)===5 && array_reduce($planDB->executed,fn($ok,$s)=>$ok && str_starts_with($s['sql'],'EXPLAIN SELECT'),true),'diagnóstico executa somente EXPLAIN SELECT');
check146($report['recentes'][0]['estimated_rows']===12 && $report['recentes'][0]['key']==='idx_list','retorno do plano é restrito a metadados');
check146(str_contains((string)file_get_contents(__DIR__.'/../app/schema.php'),'INDEX idx_public_featured') && str_contains((string)file_get_contents(__DIR__.'/../app/schema.php'),'INDEX idx_public_popular'),'instalação nova também cria índices');
echo "Índices e análise de planos 1.0.46: testes simulados aprovados; MariaDB real pendente.\n";
