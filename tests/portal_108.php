<?php
declare(strict_types=1);
require __DIR__.'/../app/bus_import.php';
require __DIR__.'/../app/contact.php';
function ok108(bool $ok,string $desc):void{if(!$ok)throw new RuntimeException('Falha: '.$desc);echo "OK: {$desc}\n";}
$tz=new DateTimeZone('America/Sao_Paulo');
$departure=new DateTimeImmutable('2026-10-18 23:45:00',$tz);
$arrival=new DateTimeImmutable('2026-10-19 00:25:00',$tz);
ok108(bus_duration_label($departure,$arrival)==='Duração: 40 min','duração ao cruzar meia-noite');
ok108(bus_duration_label($departure,null)==='Duração a confirmar','não inventa duração desconhecida');
ok108(bus_duration_label($departure,$departure->modify('+1 day +2 hours +10 minutes'))==='Duração: 1 dia e 2 h e 10 min','duração de mais de um dia');
ok108(bus_import_endpoints('IGUATAMA / ARCOS')===['IGUATAMA','ARCOS'],'nomes de cidades separados por barra');
$der="Relatório oficial - transporte intermunicipal\nCOD_LINH\tNUM_LINH\tNOME_LINH\tHORA_PARTIDA\tTRAJETO\tSEGUNDA\tTERCA\tQUARTA\tQUINTA\tSEXTA\tSABADO\tDOMINGO\n178\t110\tIGUATAMA / ARCOS\t07h05\tIDA\tX\tX\tX\tX\tX\t\t\n";
$tmp=tempnam(sys_get_temp_dir(),'der-');file_put_contents($tmp,$der);
$rows=bus_import_csv_rows($tmp);unlink($tmp);
ok108(count($rows)===1 && $rows[0]['cod_linh']==='178','TXT do DER com preâmbulo e tabs');
$r=bus_import_build_candidates('der_mg',$rows,'Iguatama');
ok108(count($r['groups'])===1 && $r['groups'][0]['departures'][0]['time']==='07:05','TXT/DER horario 07h05 e cidades com barra');
$public=(string)file_get_contents(__DIR__.'/../public/index.php');
ok108(!str_contains($public,'drilemoz@gmail.com') && !str_contains($public,'mailto:'),'destinatário não aparece no código HTML público');
foreach(['privacidade','termos','anuncie','contato'] as $page)ok108(str_contains($public,"\$route==='".$page."'")||str_contains($public,"'".$page."'"),'rota '.$page.' registrada');
