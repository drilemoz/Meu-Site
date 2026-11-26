<?php
declare(strict_types=1);
require __DIR__.'/home_news_156.php';
function portal161(bool $ok,string $m):void{if(!$ok)throw new RuntimeException($m);echo "OK: $m\n";}
$o=igua_portal_options();portal161($o['recent_count']===3&&$o['section_count']===1&&!$o['popular']&&!$o['labels']&&$o['max_age']===14,'home compacta padrão');
portal161(array_column(igua_portal_active_categories($cats,'[1,4]'),'id')===[1,4],'categorias arquivadas não aparecem no cadastro novo');
portal161(igua_portal_home_prefs($cats,'[1,2,3]','4','')===['ids'=>[1],'region'=>4],'configuração anterior vira home compacta');
$db->articles=[];for($i=1;$i<=10;$i++)$db->articles[]=['id'=>$i,'published'=>true,'featured'=>$i===10,'cats'=>match($i){9,1=>[1],8,2=>[4],default=>[5]},'views'=>$i,'published_at'=>date('Y-m-d H:i:s',strtotime($i<=2?'-20 days':'-1 day'))];
$home=igua_portal_home($db,$cats,['ids'=>[1],'region'=>4],$o);
portal161($home['featured']['id']===10&&array_column($home['recent'],'id')===[7,6,5]&&array_column($home['sections'],'title')===['Cidade','Região']&&$home['popular']===[],'manchetes reservadas e três últimas notícias');
$ids=[$home['featured']['id'],...array_column($home['recent'],'id')];foreach($home['sections'] as $s)array_push($ids,...array_column($s['rows'],'id'));portal161(count($ids)===6&&count(array_unique($ids))===6,'máximo padrão de seis notícias, sem duplicatas');
$db->articles=array_filter($db->articles,static fn($a)=>$a['id']!==9);$home=igua_portal_home($db,$cats,['ids'=>[1],'region'=>4],$o);portal161(!in_array('Cidade',array_column($home['sections'],'title'),true),'seção antiga omitida em vez de preencher espaço');
$o['featured']=false;$o['sections']=false;$o['recent']=false;$home=igua_portal_home($db,$cats,['ids'=>[1],'region'=>4],$o);portal161($home===['featured'=>false,'recent'=>[],'sections'=>[],'popular'=>[]],'blocos desligados não consomem matérias');
portal161(igua_portal_nav_category($cats,'none',['cidade'])===null&&igua_portal_nav_category($cats,'4',[])['id']===4,'atalhos configuráveis');
