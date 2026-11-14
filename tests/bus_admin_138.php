<?php
declare(strict_types=1);
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_field():string{return '<input name="_csrf" value="test">';}
function require_ability(string $ability,array $me):void {if($ability!=='manage_bus')throw new RuntimeException('Wrong ability');}
class Bus138Stmt extends PDOStatement {
    public function __construct(private array $rows, private int $total=0){}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT, ...$args):array{return $this->rows;}
    public function fetchColumn(int $column=0):mixed{return $this->total;}
}
class Bus138PDO extends PDO {
    public function __construct(){}
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs):PDOStatement|false{
        if(str_contains($query,'COUNT(*) FROM bus_import_queue'))return new Bus138Stmt([],1);
        if(str_contains($query,'FROM bus_import_queue'))return new Bus138Stmt([['id'=>7,'source'=>'der_mg','payload'=>json_encode(['name'=>'Belo Horizonte → Iguatama','company'=>'Empresa A','departures'=>[[]]])]]);
        if(str_contains($query,'FROM bus_routes r'))return new Bus138Stmt([['id'=>3,'name'=>'Iguatama → Arcos','company'=>'Empresa B','stop_count'=>2,'dep_count'=>1,'source_type'=>'manual','sync_mode'=>'fixed','active'=>1]]);
        throw new RuntimeException('Unexpected SQL: '.$query);
    }
}
require __DIR__.'/../app/bus_admin.php';
$db=new Bus138PDO;
foreach(['bus','bus_import'] as $action){
 ob_start();bus_admin_view($db,$action);$html=ob_get_clean();
 if($action==='bus_import'){
   if(hash('sha256',$html)!=='eaf62b02d2f907b452a63ecf6f43269327e0012e676e02c6b26d5716674128a4')throw new RuntimeException('Importação DER/ANTT foi alterada inesperadamente.');
 }else{
   foreach(['Linhas e horários','bus_import_teixeira','Novo horário','Iguatama → Arcos','Teixeira'] as $needed)
     if(!str_contains($html,$needed))throw new RuntimeException('HTML de ônibus perdeu: '.$needed);
 }
 echo 'OK: HTML '.$action.' conservado/com importador Teixeira\n';
}
try {bus_admin_post($db,'invalid',['id'=>1]);throw new LogicException('Unknown action accepted');}catch(RuntimeException $e){if($e->getMessage()!=='Ação inválida para ônibus.')throw $e;}
echo "OK: ação desconhecida rejeitada\n";
$facade=(string)file_get_contents(__DIR__.'/../app/bus_admin.php');
$all=['bus_stage_import','bus_approve_import','bus_ignore_import','bus_toggle_fixed','save_bus_wizard','save_bus_route','delete_bus_route','save_bus_departure','toggle_bus_departure','delete_bus_departure'];
foreach($all as $action)if(!str_contains($facade,"'".$action."'"))throw new RuntimeException('Ação não mapeada: '.$action);
if(!str_contains($facade,"require_ability('manage_bus',\$me)"))throw new RuntimeException('Permissão não centralizada');
echo "OK: ações explícitas e permissão obrigatória\n";
