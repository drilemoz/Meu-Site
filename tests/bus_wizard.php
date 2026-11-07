<?php
declare(strict_types=1);
require __DIR__.'/../app/bus_wizard.php';
function ok_w(bool $cond,string $what): void {if(!$cond)throw new RuntimeException('Falha: '.$what);echo "OK: $what\n";}
$offset=bus_wizard_offsets('23:45',['23:45','00:25','01:20'],['0','1','1']);
ok_w($offset===['0','40','95'],'horas após meia-noite preservam offsets');
$stops=bus_clean_stops(['Iguatama','Garças de Minas','Arcos'],['Rodoviária','Ponto Central','Terminal'],$offset);
ok_w(count($stops)===3 && $stops[1]['minutes_from_start']===40 && $stops[2]['minutes_from_start']===95,'cidades, pontos e offsets crescentes');
ok_w(bus_wizard_offsets('07:00',['07:00','', ''],['0','',''])===['0','',''],'chegada desconhecida é permitida');
foreach([
 ['07:00',['07:00','06:30'],['0','0']],
 ['23:45',['23:45','00:25'],['0','0']],
 ['07:00',['07:01'],['0']],
 ['07:00',['08:00','09:00'],['0','0']],
 ['07:00',['07:00','08:00'],['0','8']],
] as $bad){try{bus_wizard_offsets(...$bad);throw new RuntimeException('Aceitou horas/dias incorretos');}catch(InvalidArgumentException $e) {}}
ok_w(true,'rejeita percurso sem saída válida, dia errado ou menos de duas cidades');
ok_w(bus_wizard_fare('15,50')==='15.50' && bus_wizard_fare('0')==='0.00' && bus_wizard_fare('')===null,'tarifa opcional e reais decimais');
foreach(['-3','1,999','1234567','12.34.00'] as $invalid){try{bus_wizard_fare($invalid);throw new RuntimeException('Tarifa inválida aceita');}catch(InvalidArgumentException $e){}}
ok_w(true,'tarifas inválidas recusadas');
$tz=new DateTimeZone('America/Sao_Paulo');$now=new DateTimeImmutable('2026-10-18 23:55',$tz);
ok_w(bus_calendar_label(new DateTimeImmutable('2026-10-18 23:59',$tz),$now)==='Hoje','5 minutos hoje');
ok_w(bus_calendar_label(new DateTimeImmutable('2026-10-19 07:00',$tz),$now)==='Amanhã','partida amanhã');
ok_w(bus_calendar_label(new DateTimeImmutable('2026-10-21 07:00',$tz),$now)==='Em 3 dias','partida daqui três dias');
ok_w(bus_site_origin_city('Iguatama - MG')==='Iguatama','origem da cidade base');
// Próxima partida genérica, sem filtro de destino específico.
$routes=[['route_id'=>100,'route_name'=>'Iguatama → Formiga','company'=>'Empresa informada','origin_city'=>'Iguatama','destination_city'=>'Formiga','origin_minutes'=>0,'destination_minutes'=>90]];
$dep=[['id'=>1,'route_id'=>100,'start_time'=>'08:00','days_mask'=>127,'active'=>1]];
$found=bus_upcoming_departures($routes,$dep,$now,5);
ok_w(count($found)>=1 && $found[0]['departs_at']->format('Y-m-d H:i')==='2026-10-19 08:00','primeira partida no dia seguinte');
print "Testes do assistente e calendário concluídos.\n";
