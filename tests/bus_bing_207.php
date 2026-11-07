<?php
declare(strict_types=1);
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function csrf_field():string{return '<input type="hidden" name="_csrf" value="test">';}
class B207Stmt extends PDOStatement {
    public function __construct(private array $rows){}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args):array{return $this->rows;}
}
class B207PDO extends PDO {
    public function __construct(){}
    public function query(string $sql,?int $fetchMode=null,mixed ...$args):PDOStatement|false{
        if(!str_contains($sql,'FROM bus_routes r') || !str_contains($sql,'AS origin_city') || !str_contains($sql,'ORDER BY stop_order ASC LIMIT 1'))throw new RuntimeException('Origem não é lida da primeira parada.');
        return new B207Stmt([
            ['id'=>1,'name'=>'Iguatama → Arcos','company'=>'Teixeira','stop_count'=>2,'dep_count'=>3,'origin_city'=>'Iguatama','destination_city'=>'Arcos','source_type'=>'manual','sync_mode'=>'fixed','active'=>1],
            ['id'=>2,'name'=>'Arcos → Formiga','company'=>'Teixeira','stop_count'=>2,'dep_count'=>5,'origin_city'=>'Arcos','destination_city'=>'Formiga','source_type'=>'der_mg','sync_mode'=>'auto','active'=>1],
        ]);
    }
}
require_once __DIR__.'/../app/bus_admin.php';
$_GET['origin']='Arcos';ob_start();bus_admin_view_routes(new B207PDO(),'bus');$html=ob_get_clean();
foreach(['Cidade de origem','origin','Linhas cadastradas','Arcos → Formiga','Editar percurso','action=bus_toggle_fixed','_csrf','bus-admin-route-card'] as $s)if(!str_contains($html,$s))throw new RuntimeException('Campo de ônibus ausente: '.$s);
if(str_contains($html,'<table')||str_contains($html,'Iguatama → Arcos</strong>'))throw new RuntimeException('Filtro ou substituição da tabela falhou.');
$_GET['origin']='';ob_start();bus_admin_view_routes(new B207PDO(),'bus');$html=ob_get_clean();
if(!str_contains($html,'Iguatama → Arcos')||!str_contains($html,'Arcos → Formiga'))throw new RuntimeException('Todas as linhas devem aparecer sem filtro.');
$menu=file_get_contents(__DIR__.'/../app/admin_views/layout.php');
if(!str_contains($menu,'▣ Páginas')||str_contains($menu,'▣ Aplicativos e Páginas Legais'))throw new RuntimeException('Rótulo do menu não abreviado.');
$xml=file_get_contents(__DIR__.'/../public/BingSiteAuth.xml');
if($xml===false||!preg_match('~^<\?xml version="1\.0"\?>\s*<users>\s*<user>[A-F0-9]{32}</user>\s*</users>\s*$~D',$xml))throw new RuntimeException('Arquivo Bing inválido.');
$expected="<?xml version=\"1.0\"?>\n<users>\n\t<user>B779E1E9DFC6B2721F88D8B22B403DFD</user>\n</users>";
if($xml!==$expected)throw new RuntimeException('XML não corresponde ao documento fornecido.');
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/sitemap.php');
if(!str_contains($view,'href="/BingSiteAuth.xml"'))throw new RuntimeException('Link de verificação Bing ausente.');
echo "Bing 207: XML público preservado, menu curto e ônibus filtráveis em cartões.\n";
