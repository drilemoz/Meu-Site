<?php
declare(strict_types=1);
require __DIR__.'/../app/bus_import.php';
function bt(bool $ok,string $label):void{if(!$ok)throw new RuntimeException('Falhou: '.$label);echo "OK: $label\n";}
$tmp=tempnam(sys_get_temp_dir(),'der_test_');
file_put_contents($tmp,"COD_LINH;NOME_LINH;HORA_PARTIDA;TRAJETO;SEGUNDA;TERCA;QUARTA;QUINTA;SEXTA;SABADO;DOMINGO;FERIADO\n123;IGUATAMA - ARCOS;07:10;IGUATAMA - ARCOS;X;X;X;X;X;;;\n123;IGUATAMA - ARCOS;17:10;IGUATAMA - ARCOS;;;;;;X;X;\n222;FORMIGA - DIVINOPOLIS;09:00;FORMIGA - DIVINOPOLIS;X;;;;;;;\n333;Rota sem delimitador;10:00;estrada sem destino;X;;;;;;;\n");
$rows=bus_import_csv_rows($tmp);unlink($tmp);
$res=bus_import_build_candidates('der_mg',$rows,'Iguatama');
bt(count($res['groups'])===1,'filtro por Iguatama e DER-MG');
bt(count($res['groups'][0]['departures'])===2,'duas saídas de dias diferentes');
bt($res['groups'][0]['departures'][0]['days_mask']===62,'máscara de segunda a sexta');
bt($res['groups'][0]['departures'][1]['days_mask']===65,'fim de semana');
$tmp=tempnam(sys_get_temp_dir(),'antt_test_');
file_put_contents($tmp,"prefixo,descricao_linha,sentido,horario,razao_social,segunda_feira,terca_feira,quarta_feira,quinta_feira,sexta_feira,sabado,domingo\n01-1234-00,IGUATAMA (MG) - CAMPINAS (SP),ida,06:45,EMPRESA EXEMPLO,X,X,X,X,X,,\n01-1234-00,IGUATAMA (MG) - CAMPINAS (SP),volta,15:10,EMPRESA EXEMPLO,,,,,,X,X\n");
$res=bus_import_build_candidates('antt',bus_import_csv_rows($tmp),'');unlink($tmp);
bt(count($res['groups'])===2,'separa ida e volta da ANTT');
bt($res['groups'][1]['cities']===['CAMPINAS','IGUATAMA'],'inverte trajeto de volta');
bt(bus_import_mask(['segunda_feira'=>''],'antt')===0,'dias desconhecidos não viram todos os dias');
bt(bus_import_endpoints('CAMPO GRANDE(MS) - BELO HORIZONTE(MG)')===['CAMPO GRANDE','BELO HORIZONTE'],'extremidades e siglas de estado');
$unknown=bus_clean_stops(['Iguatama','Arcos'],['Rodoviária','Rodoviária'],['0','']);
bt($unknown[1]['minutes_from_start']===null,'chegada desconhecida não é inventada');
$future=bus_upcoming_departures([['route_id'=>1,'origin_minutes'=>0,'destination_minutes'=>null,'origin_city'=>'Iguatama','destination_city'=>'Arcos']],[['id'=>1,'route_id'=>1,'start_time'=>'10:00:00','days_mask'=>127,'active'=>1]],new DateTimeImmutable('2026-10-18 09:00:00',new DateTimeZone('America/Sao_Paulo')));
bt(count($future)>0 && $future[0]['arrives_at']===null,'consultas exibem saída sem chegada inventada');
