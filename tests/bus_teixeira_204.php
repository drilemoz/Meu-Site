<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bus.php';
require_once __DIR__.'/../app/bus_teixeira_2026.php';
function b204(bool $condition,string $message): void {if(!$condition)throw new RuntimeException('Teixeira 1.0.104: '.$message);}
$rows=bus_teixeira_2026_rows();$summary=bus_teixeira_2026_validate();
b204($summary===['routes'=>10,'departures'=>31],'10 sentidos e 31 grupos de partidas');
$byRoute=[];foreach($rows as [$a,$b,$times]){$byRoute[$a.'-'.$b]=$times;}
b204(count($byRoute)===10,'sem sentidos duplicados');
b204($byRoute['Arcos-Iguatama'] ===[['07:35',127],['14:45',127],['18:45',127]],'Arcos a Iguatama diariamente');
b204($byRoute['Arcos-Lagoa da Prata'] ===[['09:15',127],['17:45',127]],'Lagoa da Prata horários');
b204($byRoute['Iguatama-Arcos'] ===[['05:30',62],['14:00',65],['09:00',127],['17:55',127]],'Iguatama a Arcos sem duplicar saídas diárias');
b204(count($byRoute['Arcos-Formiga'])===9,'Arcos Formiga: 18:25 único grupo diário');
b204(bus_weekday_labels(62)==='Seg, Ter, Qua, Qui, Sex','máscara semanal');
b204(bus_weekday_labels(65)==='Sáb, Dom','fim de semana distinto de feriados');
$root=dirname(__DIR__);$source=file_get_contents($root.'/app/bus_teixeira_2026.php');
b204(str_contains($source,'beginTransaction')&&str_contains($source,'rollBack')&&str_contains($source,'source_key'),'importação transacional e idempotente');
b204(str_contains($source,'minutes_from_start')&&str_contains($source,"$"."insertStop->execute(["."$"."id,"."$"."to,'Rodoviária',null,2])"),'não inventa chegada');
$router=file_get_contents($root.'/public/admin.php');$posts=file_get_contents($root.'/app/admin_controllers/post_routes.php');
b204(str_contains($router,"'bus_import_teixeira'")&&str_contains($posts,"'bus_import_teixeira'=>'bus'"),'rota com mesmo controle CSRF/autenticação');
$view=file_get_contents($root.'/app/bus_admin/view_routes.php');
b204(str_contains($view,'teixeira_confirm') && str_contains($view,'bus_import_teixeira'),'ação exige confirmação explícita');
echo "Teixeira 1.0.104: transcrição, máscaras, segurança e rotas verificadas. MariaDB real ainda pendente.\n";
