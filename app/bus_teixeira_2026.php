<?php
declare(strict_types=1);
/** Transcrição conferível da fotografia de horários da Viação Teixeira, impressa em 14/09/2026.
 * A tabela não informa chegada nem distingue feriados de segundas/sextas de modo automatizável.
 * Nenhum registro é criado ao carregar este arquivo: exige confirmação administrativa explícita.
 */
function bus_teixeira_2026_rows(): array {
    $work=62; // segunda a sexta
    $weekend=65; // sábado e domingo (feriados: confirmar separadamente)
    $daily=127;
    return [
        ['Arcos','Formiga', [
            ['06:10',$work],['06:40',$work],['09:45',$work],['14:10',$work],
            ['09:10',$weekend],['09:40',$weekend],['14:45',$weekend],['17:40',$weekend],['18:25',$daily]]],
        ['Formiga','Arcos', [['06:50',$daily],['08:30',$daily],['14:00',$daily],['17:00',$daily],['18:00',$daily]]],
        ['Arcos','Lagoa da Prata', [['09:15',$daily],['17:45',$daily]]],
        ['Lagoa da Prata','Arcos', [['05:30',$work],['13:00',$work],['08:00',$weekend],['16:30',$weekend]]],
        ['Arcos','Iguatama', [['07:35',$daily],['14:45',$daily],['18:45',$daily]]],
        ['Iguatama','Arcos', [['05:30',$work],['14:00',$weekend],['09:00',$daily],['17:55',$daily]]],
        ['Arcos','Bambuí', [['14:45',$daily]]],
        ['Bambuí','Arcos', [['17:00',$daily]]],
        ['Arcos','Luz', [['15:45',$work]]],
        ['Luz','Arcos', [['08:00',$work]]],
    ];
}
function bus_teixeira_2026_validate(): array {
    $rows=bus_teixeira_2026_rows();
    if(count($rows)!==10)throw new LogicException('Tabela Teixeira incompleta.');
    $count=0; $keys=[];
    foreach($rows as [$from,$to,$departures]) {
        $key=$from.'-'.$to;
        if(isset($keys[$key])||$from===$to)throw new LogicException('Trajeto duplicado.');
        $keys[$key]=true;
        if(!$departures)throw new LogicException('Trajeto sem horário.');
        $seen=[];
        foreach($departures as [$time,$mask]) {
            if(!bus_valid_time($time)||$mask<1||$mask>127)throw new LogicException('Horário Teixeira inválido.');
            for($day=0;$day<7;$day++)if($mask & (1<<$day)){
                $slot=$day.'-'.$time;
                if(isset($seen[$slot]))throw new LogicException('Saídas conflitantes: '.$key.' '.$time);
                $seen[$slot]=true;
            }
            $count++;
        }
    }
    return ['routes'=>count($rows),'departures'=>$count];
}
/** Importação idempotente: não altera nem apaga outras linhas, partidas ou edições preexistentes. */
function bus_teixeira_2026_import(PDO $db): array {
    $totals=bus_teixeira_2026_validate();
    $added=0;$skipped=0;
    $db->beginTransaction();
    try {
        $byKey=$db->prepare('SELECT id FROM bus_routes WHERE source_type=? AND source_key=? FOR UPDATE');
        // Se já houver linha manual Teixeira com o mesmo sentido, não duplicar nem sobrescrevê-la.
        $byName=$db->prepare('SELECT id FROM bus_routes WHERE name=? AND company=? LIMIT 1 FOR UPDATE');
        $insertRoute=$db->prepare("INSERT INTO bus_routes (name,company,notes,active,source_type,source_key,sync_mode) VALUES (?,?,?,1,'manual',?,'fixed')");
        $insertStop=$db->prepare('INSERT INTO bus_stops(route_id,city,location_label,minutes_from_start,stop_order) VALUES (?,?,?,?,?)');
        $insertTime=$db->prepare('INSERT INTO bus_departures(route_id,start_time,days_mask,active) VALUES (?,?,?,1)');
        foreach(bus_teixeira_2026_rows() as [$from,$to,$departures]) {
            $routeName=$from.' → '.$to;
            $key='teixeira-foto-20260914-'.substr(hash('sha256',$from.'|'.$to),0,16);
            $byKey->execute(['manual',$key]);
            $byName->execute([$routeName,'Teixeira']);
            if($byKey->fetchColumn()||$byName->fetchColumn()){$skipped++;continue;}
            $notes='Fonte: fotografia da tabela da Viação Teixeira, datada de 14/09/2026. Horários programados sujeitos a alteração. Feriados: a tabela indica programação de sábado/domingo onde houver, mas este sistema mostra apenas dias semanais; confirmar diretamente com a viação. Horário de chegada e tarifa não informados.';
            $insertRoute->execute([$routeName,'Teixeira',$notes,$key]);
            $id=(int)$db->lastInsertId();
            $insertStop->execute([$id,$from,'Rodoviária',0,1]);
            $insertStop->execute([$id,$to,'Rodoviária',null,2]);
            foreach($departures as [$time,$mask])$insertTime->execute([$id,$time,$mask]);
            $added++;
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    return ['created'=>$added,'skipped'=>$skipped]+$totals;
}
