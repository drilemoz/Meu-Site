<?php
/** Regressões isoladas da home e da busca 1.0.145, sem MariaDB e sem requisições externas. */
declare(strict_types=1);
function check245(bool $condition,string $label):void {
    if (!$condition) throw new RuntimeException('FALHOU: '.$label);
    echo "OK: $label\n";
}
function published_filter(): string {return "a.status = 'published' AND a.published_at <= NOW()";}
function url(array $params): string {return '/index.php?'.http_build_query($params);}
function usub(string $s, int $start,int $length): string {return substr($s,$start,$length);}
require __DIR__.'/../app/public_search.php';
require __DIR__.'/../app/bus_home.php';
class FakeStmt245 extends PDOStatement {
    private FakeDB245 $db;
    private string $query;
    private array $data=[];
    public function __construct(FakeDB245 $db,string $query){$this->db=$db;$this->query=$query;}
    public function execute(?array $params=null):bool {
        $this->db->history[]=['query'=>$this->query,'params'=>$params];
        if($this->db->throwOn!=='' && str_contains($this->query,$this->db->throwOn))throw new PDOException('Simulação de falha');
        foreach ($this->db->rows as $token=>$data) {
            if(str_contains($this->query,$token)){$this->data=$data;return true;}
        }
        $this->data=[];return true;
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args):array {return $this->data;}
}
class FakeDB245 extends PDO {
    public array $history=[];
    public array $rows=[];
    public string $throwOn='';
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new FakeStmt245($this,$query);}
}
$db=new FakeDB245();
check245(igua_public_search_suggestions($db,'a')===[] && $db->history===[],'não pesquisa com menos de 2 caracteres');
check245(igua_public_search_suggestions($db,str_repeat('x',101))===[],'não pesquisa acima de 100 caracteres');
check245(igua_public_search_suggestions($db,[])===[],'não aceita GET com valor de array');
$db->rows=[
  'FROM articles a'=>[['title'=>'Notícia de Iguatama','slug'=>'noticia-local']],
  'FROM categories'=>[['name'=>'Cidade','slug'=>'cidade']],
  'FROM bus_stops a'=>[['origin_city'=>'Iguatama','destination_city'=>'Bambuí']],
];
$suggestions=igua_public_search_suggestions($db,'Iguatama');
check245(count($suggestions)===3,'notícias, editorias e rotas sugeridas');
check245($suggestions[0]['type']==='Notícia' && str_contains($suggestions[0]['url'],'noticia-local'),'notícia aponta para artigo publicado');
check245($suggestions[1]['type']==='Editoria' && $suggestions[2]['type']==='Ônibus','tipos corretos');
check245(str_contains($suggestions[2]['url'],'origem=Iguatama') && str_contains($suggestions[2]['url'],'destino=Bambu'),'link do ônibus usa cidades cadastradas');
check245(str_contains($db->history[0]['query'],"a.status = 'published'") && str_contains($db->history[2]['query'],'r.active=1'),'query exclui notícias não publicadas e linhas desativadas');
$db->history=[];
igua_public_search_suggestions($db,'100%_!');
check245($db->history[0]['params'][0]==='%100!%!_!!%','coringas LIKE escapados');
$db->throwOn='FROM bus_stops a';
check245(count(igua_public_search_suggestions($db,'Iguatama'))===2,'falta de tabelas ônibus não derruba busca de notícias');
$db->throwOn='';

$now=new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo'));
$start=$now->modify('+45 minutes');
$routes=[['route_id'=>9,'route_name'=>'Linha 9','company'=>'Empresa X','origin_city'=>'Iguatama','origin_label'=>'Rodoviária','origin_minutes'=>0,'destination_city'=>'Bambuí','destination_minutes'=>120]];
$db->rows=[
  'FROM bus_routes r JOIN bus_stops a'=>$routes,
  'FROM bus_departures'=>[['id'=>7,'route_id'=>9,'start_time'=>$start->format('H:i'),'days_mask'=>127,'active'=>1]],
];
$summary=bus_home_summary($db,'Iguatama - MG');
check245($summary['origin']==='Iguatama' && $summary['status']==='ok','origem configurada e partidas existentes');
check245(count($summary['trips'])>=1 && count($summary['trips'])<=3,'limite genérico do agregador preservado');
check245($summary['trips'][0]['destination_city']==='Bambuí' && $summary['trips'][0]['departs_at']>=$now,'destino e horário válidos calculados pelo módulo existente');
$db->rows['FROM bus_departures']=[];
check245(bus_home_summary($db,'Iguatama - MG')['status']==='no_departures','sem horários, não fabrica partida');
$db->rows['FROM bus_routes r JOIN bus_stops a']=[];
check245(bus_home_summary($db,'Iguatama - MG')['status']==='empty','sem rotas, comunica ausência');
$db->throwOn='FROM bus_routes r JOIN bus_stops a';
check245(bus_home_summary($db,'Iguatama - MG')['status']==='unavailable','erro da consulta não quebra home');
$home=file_get_contents(__DIR__.'/../app/public_views/routes/home.php');
check245(!str_contains($home,'class="categories-strip"'),'faixa de categorias removida da home');
check245(strpos($home,'home-bus')<strpos($home,'headline-heading'),'card de ônibus antes das Últimas notícias');
$layout=file_get_contents(__DIR__.'/../app/public_views/layout.php');
check245(str_contains($layout,'data-public-search') && str_contains($layout,'search-suggest.js'),'busca automática integrada ao layout');
echo "OK: regressões home/busca 1.0.145\n";
