<?php
declare(strict_types=1);
// Smoke test da view inteira sem MySQL, verificando renderização e escaping reais.
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_field():string{return '<input type="hidden" name="csrf" value="fixture">';}
function setting(string $key,string $default=''):string{return $key==='analytics_enabled'?'1':$default;}
require_once __DIR__.'/../app/analytics_admin.php';
class AnalyticsRenderPDO241 extends PDO {
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new AnalyticsRenderStatement241($query);}
}
class AnalyticsRenderStatement241 extends PDOStatement {
    public function __construct(private string $query){}
    public function execute(?array $params=null):bool{return count($params??[])===4;}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed{
        return ['views'=>12,'bots'=>3,'suspect'=>2,'visitors_day'=>7,'clicks'=>4,'engaged_ids'=>2,'events'=>21];
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args):array {
        if(str_contains($this->query,'GROUP BY bucket'))return [];
        return [['label'=>'<img src=x onerror=alert(1)>','total'=>2],['label'=>'Googlebot','total'=>1]];
    }
}
$db=new AnalyticsRenderPDO241();
foreach(['human','bot','suspect'] as $group){
    ob_start();analytics_admin_view($db,'today',$group);$html=(string)ob_get_clean();
    if(!str_contains($html,'Público provável') || !str_contains($html,'Robôs declarados') || !str_contains($html,'Suspeitos'))throw new RuntimeException('Faltam indicadores');
    if(!str_contains($html,'analytics-graph')||!str_contains($html,'analytics-audience-tabs'))throw new RuntimeException('Faltam visualizações');
    if(!str_contains($html,'&lt;img src=x onerror=alert(1)&gt;') || str_contains($html,'<img src=x onerror=alert(1)>'))throw new RuntimeException('Falta escapar rótulo de dimensão');
    if(!str_contains($html,'csrf')||!str_contains($html,'analytics_settings'))throw new RuntimeException('Configuração sem CSRF');
    if(substr_count($html,'<details')!==substr_count($html,'</details>'))throw new RuntimeException('Ajudas não balanceadas');
    if(!str_contains($html,'group='.$group))throw new RuntimeException('Filtro de grupo perdido');
    echo 'OK view '.$group.PHP_EOL;
}
echo 'Renderização completa de métricas 1.0.140 aprovada'.PHP_EOL;
