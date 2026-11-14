<?php
declare(strict_types=1);
require __DIR__.'/../app/bus.php';
function assert_bus(bool $ok,string $message):void {if(!$ok)throw new RuntimeException('Falhou: '.$message);echo "OK: $message\n";}
assert_bus(bus_weekday_mask(['1','2','3','4','5'])===62,'segunda a sexta');
assert_bus(bus_weekday_mask(['0','6'])===65,'fim de semana');
assert_bus(bus_weekday_labels(127)==='Todos os dias','todos os dias');
foreach([[],[9],['foo']] as $bad){try{bus_weekday_mask($bad);throw new RuntimeException('aceitou dias inválidos');}catch(InvalidArgumentException){}}
assert_bus(true,'dias de circulação validados');
$stops=bus_clean_stops(['Iguatama','Arcos','Formiga'],['Rodoviária','','Terminal'],['0','45','95']);
assert_bus(count($stops)===3 && $stops[2]['minutes_from_start']===95,'paradas ordenadas');
foreach([
  [['Iguatama','Arcos'],['',''],['10','20']],
  [['Iguatama','Arcos'],['',''],['0','0']],
  [['Iguatama','Arcos','Iguatama'],['','',''],['0','15','30']],
  [['Iguatama'],[''],['0']]
] as $bad){try{bus_clean_stops(...$bad);throw new RuntimeException('aceitou parada inválida');}catch(InvalidArgumentException){}}
assert_bus(true,'trajetos inválidos bloqueados');
$tz=new DateTimeZone('America/Sao_Paulo');
$now=new DateTimeImmutable('2026-10-19 16:30:00',$tz); // segunda-feira
$routes=[['route_id'=>1,'route_name'=>'Iguatama-Arcos-Formiga','company'=>'Operadora de teste','origin_city'=>'Iguatama','destination_city'=>'Formiga','origin_label'=>'','destination_label'=>'','origin_minutes'=>0,'destination_minutes'=>95], ['route_id'=>1,'route_name'=>'Iguatama-Arcos-Formiga','company'=>'Operadora de teste','origin_city'=>'Arcos','destination_city'=>'Formiga','origin_label'=>'','destination_label'=>'','origin_minutes'=>45,'destination_minutes'=>95]];
$deps=[['id'=>1,'route_id'=>1,'start_time'=>'07:00:00','days_mask'=>127,'active'=>1],['id'=>2,'route_id'=>1,'start_time'=>'18:00:00','days_mask'=>62,'active'=>1]];
$trips=bus_upcoming_departures([$routes[0]],$deps,$now,8);
assert_bus($trips[0]['departs_at']->format('Y-m-d H:i')==='2026-10-19 18:00','próximo horário hoje às 18h');
assert_bus(bus_relative_departure($trips[0]['departs_at'],$now)==='Sai em 1 h e 30 min','contagem regressiva');
$viaArcos=bus_upcoming_departures([$routes[1]],$deps,$now,8);
assert_bus($viaArcos[0]['departs_at']->format('H:i')==='18:45','origem em parada intermediária');
assert_bus($viaArcos[0]['arrives_at']->format('H:i')==='19:35','chegada estimada com offset');
$late=new DateTimeImmutable('2026-10-19 23:30:00',$tz);
assert_bus(bus_upcoming_departures([$routes[0]],$deps,$late,1)[0]['departs_at']->format('Y-m-d H:i')==='2026-10-20 07:00','pula para saída de amanhã quando hoje acabou');
$week=[['id'=>3,'route_id'=>1,'start_time'=>'06:00:00','days_mask'=>1<<1,'active'=>1]];
assert_bus(bus_upcoming_departures([$routes[0]],$week,$late,1)[0]['departs_at']->format('Y-m-d H:i')==='2026-10-26 06:00','serviço semanal');
$offset=[['route_id'=>4,'route_name'=>'Noturno','company'=>'','origin_minutes'=>1600,'destination_minutes'=>1700]];
$startYesterday=[['id'=>4,'route_id'=>4,'start_time'=>'22:00:00','days_mask'=>127,'active'=>1]];
$t=bus_upcoming_departures($offset,$startYesterday,new DateTimeImmutable('2026-10-20 00:00',$tz),1)[0];
assert_bus($t['departs_at']->format('Y-m-d H:i')==='2026-10-20 00:40','offset superior a 24h a partir do início da viagem');
assert_bus(bus_upcoming_departures([$routes[0]],[], $now)===[],'sem horários cadastrados não cria saídas');
echo "Testes de ônibus concluídos.\n";
