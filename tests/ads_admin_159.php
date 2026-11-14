<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/ads.php';
require_once __DIR__.'/../app/ads_examples.php';
require_once __DIR__.'/../app/ads_management.php';
function test159(bool $ok,string $label):void {if(!$ok)throw new RuntimeException('Falhou: '.$label);echo "OK: $label\n";}
$start='2026-11-06';$end='2026-11-20';
test159(igua_ad_dates_overlap($start,$end,'2026-11-20','2026-12-01'),'fim e começo no mesmo dia conflitam');
test159(!igua_ad_dates_overlap($start,$end,'2026-11-21',null),'datas separadas não conflitam');
test159(igua_ad_dates_overlap($start,null,'2027-01-01',null),'prazos indefinidos conflitam');
$sample=[
 ['id'=>1,'placement_slug'=>'p01','start_date'=>'2026-11-01','end_date'=>null,'active'=>1],
 ['id'=>2,'placement_slug'=>'p01','start_date'=>'2026-12-01','end_date'=>'2026-12-31','active'=>0],
 ['id'=>3,'placement_slug'=>'p02','start_date'=>'2026-11-01','end_date'=>null,'active'=>1],
];
test159(count(igua_ad_overlapping_campaigns($sample,'p01',$start,$end,0))===1,'conflito só na posição e período ativos');
test159(igua_ad_overlapping_campaigns($sample,'p01',$start,$end,1)===[],'edição ignora a própria campanha');
class AdStmt159 extends PDOStatement {
 public array $bindings=[];
 public function __construct(private AdPDO159 $db, public string $sql) {}
 public function execute(?array $params=null):bool { $this->bindings=$params??[];$this->db->queries[]=['query'=>$this->sql,'params'=>$this->bindings];if($this->db->errorAtDelete&&str_starts_with($this->sql,'DELETE'))throw new PDOException('Falha simulada');return true; }
 public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array {return [];}
 public function fetchColumn(int $column=0):mixed {return $this->db->demoCount;}
 public function rowCount():int {return $this->db->demoCount;}
}
class AdPDO159 extends PDO {
 public array $queries=[];
 public bool $transaction=false,$errorAtDelete=false;
 public int $demoCount=6;
 public function __construct(){}
 public function beginTransaction():bool {$this->transaction=true;$this->queries[]='BEGIN';return true;}
 public function inTransaction():bool {return $this->transaction;}
 public function commit():bool {$this->transaction=false;$this->queries[]='COMMIT';return true;}
 public function rollBack():bool {$this->transaction=false;$this->queries[]='ROLLBACK';return true;}
 public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false {$this->queries[]=$query;return new AdStmt159($this,$query);}
 public function prepare(string $query,array $options=[]):PDOStatement|false {return new AdStmt159($this,$query);}
 public function exec(string $statement):int|false {$this->queries[]=$statement;return 1;}
}
$db=new AdPDO159();test159(igua_ad_demo_count($db)===6,'contador usa alvo demonstrativo');
test159(igua_ad_remove_demo_campaigns($db)===6,'exclusão retorna quantidade de exemplos');
test159(in_array('COMMIT',$db->queries,true),'limpeza confirmada na transação');
$deleted=array_values(array_filter($db->queries,static fn($x)=>is_array($x)&&str_starts_with($x['query'],'DELETE')));
test159(count($deleted)===1 && $deleted[0]['params']===[AD_DEMO_URL_PREFIX.'%'],'DELETE contém filtro exclusivo de demonstração');
test159(!array_filter($db->queries,static fn($x)=>is_string($x)&&str_contains($x,'DROP TABLE')),'nenhuma tabela removida');
$bad=new AdPDO159();$bad->errorAtDelete=true;
try{igua_ad_remove_demo_campaigns($bad);test159(false,'rollback em falha');}catch(PDOException){test159(in_array('ROLLBACK',$bad->queries,true),'falha de remoção causa rollback');}
$root=dirname(__DIR__);
$admin=file_get_contents($root.'/app/ads_admin.php');
$view=file_get_contents($root.'/app/admin_views/routes/ads_dashboard.php');
$maintenance=file_get_contents($root.'/app/admin_views/routes/maintenance.php');
$controller=file_get_contents($root.'/app/admin_controllers/ads.php');
$public=file_get_contents($root.'/public/admin.php');
$routes=file_get_contents($root.'/app/admin_controllers/post_routes.php');
$js=file_get_contents($root.'/public/assets/js/ads-admin.js');
test159(str_contains($view,'+ Nova campanha') && str_contains($view,'data-ad-open="ad-type"'),'criação em janela e botão central');
test159(str_contains($view,'id="ad-settings"')&&str_contains($view,'ad-positions-compact'),'engrenagem e lista compacta');
test159(str_contains($view,'id="ad-new-adsense"')&&str_contains($view,'id="ad-local"'),'dois tipos de anúncio em janelas');
test159(str_contains($view,'id="ad-overlap-warning"') && str_contains($admin,'igua_ad_overlapping_campaigns'),'conflito confirmado pelo servidor');
test159(str_contains($admin,'FOR UPDATE')&&str_contains($admin,'$db->rollBack()'),'edição transacional e travamento de concorrência');
test159(str_contains($view,'ads_delete_demo')&&str_contains($maintenance,'ads_delete_demo'),'limpeza acessível em Publicidade e Manutenção');
test159(str_contains($controller,"'ads_delete_demo'")&&str_contains($routes,"'ads_delete_demo'=>'ads'")&&str_contains($public,"'ads_delete_demo'"),'rota de limpeza passa pela autenticação CSRF');
test159(str_contains($admin,"hash_equals('REMOVER ANUNCIOS'")&&str_contains($admin,"$".'backup=make_backup($db,\'manual\');'),'limpeza protegida por confirmação e backup');
test159(str_contains($js,'textContent')&&!str_contains($js,'innerHTML'),'interação não injeta HTML de anúncios');
test159(str_contains($root?file_get_contents($root.'/app/ads_examples.php'):'','ad_assert_demo_seed_safe'),'geração de exemplos não substitui campanhas reais');
echo "Publicidade 1.0.59: testes isolados; banco real e navegador pendentes.\n";
