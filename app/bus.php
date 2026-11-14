<?php
declare(strict_types=1);
/**
 * IguaNews: horários cadastrados (não são rastreamento em tempo real).
 * Máscara semanal: bit 0=domingo, 1=segunda, ..., 6=sábado.
 * Todos os cálculos de calendário usam America/Sao_Paulo, nunca o relógio SQL.
 */
function bus_weekday_mask(array $days): int {
    $mask = 0;
    foreach ($days as $day) {
        if (!preg_match('/^[0-6]$/D', (string)$day)) throw new InvalidArgumentException('Dia da semana inválido.');
        $mask |= 1 << (int)$day;
    }
    if ($mask < 1 || $mask > 127) throw new InvalidArgumentException('Escolha ao menos um dia de circulação.');
    return $mask;
}
function bus_weekday_labels(int $mask): string {
    $names = ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'];
    if ($mask === 127) return 'Todos os dias';
    $res = [];
    for ($day=1; $day<=7; ++$day) {
        $index = $day % 7;
        if ($mask & (1 << $index)) $res[] = $names[$index];
    }
    return implode(', ', $res);
}
function bus_valid_time(string $value): bool {
    return preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D', $value) === 1;
}
function bus_clean_stops(array $cities, array $labels, array $offsets): array {
    if (count($cities) !== count($labels) || count($cities) !== count($offsets) || count($cities)<2 || count($cities)>25) {
        throw new InvalidArgumentException('Informe de 2 a 25 paradas completas.');
    }
    $out=[]; $previous=-1; $seen=[]; $hasUnknown=false;
    foreach ($cities as $i=>$city) {
        $city=trim((string)$city); $label=trim((string)$labels[$i]); $raw=(string)$offsets[$i];
        if ($city==='' || mb_bus_len($city)>100 || mb_bus_len($label)>150) throw new InvalidArgumentException('Informe a cidade e revise os nomes das paradas.');
        if(trim($raw)==='' && $i>0){$minutes=null;$hasUnknown=true;}
        else {
            if (!preg_match('/^\d{1,5}$/D',$raw) || $hasUnknown) throw new InvalidArgumentException('Informe deslocamentos crescentes ou deixe a chegada desconhecida a partir da parada sem tempo oficial.');
            $minutes=(int)$raw;
            if ($minutes > 10080 || $minutes <= $previous) throw new InvalidArgumentException('Os minutos precisam crescer a cada parada (máximo de 7 dias).');
        }
        if ($i===0 && $minutes!==0) throw new InvalidArgumentException('A primeira parada precisa começar em 0 minuto.');
        $key=function_exists('mb_strtolower')?mb_strtolower($city,'UTF-8'):strtolower($city);
        if (isset($seen[$key])) throw new InvalidArgumentException('Nesta versão, uma cidade só pode aparecer uma vez por linha.');
        $seen[$key]=true; if($minutes!==null)$previous=$minutes;
        $out[]=['city'=>$city,'location_label'=>$label,'minutes_from_start'=>$minutes,'stop_order'=>$i+1];
    }
    return $out;
}
function mb_bus_len(string $s): int { return function_exists('mb_strlen') ? mb_strlen($s,'UTF-8') : strlen($s); }
/** Retorna datas das próximas partidas a partir de qualquer parada intermediária. */
function bus_upcoming_departures(array $routes, array $departures, ?DateTimeImmutable $now=null, int $limit=12): array {
    $tz=new DateTimeZone('America/Sao_Paulo');
    $now=$now ? $now->setTimezone($tz) : new DateTimeImmutable('now',$tz);
    $midnight=$now->setTime(0,0,0);
    $out=[];
    $byRoute=[];
    foreach ($departures as $dep) {
        if (!(int)($dep['active']??1)) continue;
        $byRoute[(int)$dep['route_id']][]=$dep;
    }
    foreach ($routes as $route) {
        $rid=(int)$route['route_id']; if($route['origin_minutes']===null)continue; $offset=(int)$route['origin_minutes'];
        if ($offset<0 || $offset>10080) continue;
        foreach ($byRoute[$rid]??[] as $dep) {
            $time=substr((string)$dep['start_time'],0,5);
            $mask=(int)$dep['days_mask'];
            if (!bus_valid_time($time) || $mask<1 || $mask>127) continue;
            [$hour,$minute]=array_map('intval',explode(':',$time));
            // A viagem pode iniciar dias antes da parada selecionada.
            $daysBack=(int)ceil($offset/1440)+1;
            for ($d=-$daysBack;$d<=16;$d++) {
                $startDate=$midnight->modify(($d>=0?'+':'').$d.' days');
                $weekday=(int)$startDate->format('w');
                if (($mask & (1 << $weekday)) === 0) continue;
                $originTime=$startDate->setTime($hour,$minute)->modify('+'.$offset.' minutes');
                if ($originTime<$now || $originTime>$now->modify('+14 days')) continue;
                $dest=$route['destination_minutes'];
                $travel=$dest===null?null:((int)$dest-$offset);
                if ($travel!==null && $travel<=0) continue;
                $out[]=[...$route,'departure_id'=>(int)$dep['id'],'departs_at'=>$originTime,'arrives_at'=>$travel===null?null:$originTime->modify('+'.$travel.' minutes'),'operator'=>$route['company']??'', 'days_mask'=>$mask];
            }
        }
    }
    usort($out,static fn($a,$b)=>($a['departs_at']<=>$b['departs_at']) ?: ($a['route_id']<=>$b['route_id']));
    $unique=[];$result=[];
    foreach ($out as $item) {
        $key=$item['route_id'].':'.$item['departure_id'].':'.$item['departs_at']->format('c');
        if(isset($unique[$key]))continue;
        $unique[$key]=true;$result[]=$item;
        if(count($result)>=$limit)break;
    }
    return $result;
}
function bus_relative_departure(DateTimeImmutable $depart, ?DateTimeImmutable $now=null): string {
    $now=$now ?: new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo'));
    $seconds=$depart->getTimestamp()-$now->getTimestamp();
    if ($seconds<0) return 'Horário encerrado';
    if ($seconds<60) return 'Sai em menos de 1 min';
    $mins=(int)ceil($seconds/60);
    $days=intdiv($mins,1440);$hours=intdiv($mins%1440,60);$min=$mins%60;
    if($days>0)return 'Sai em '.$days.' dia'.($days===1?'':'s').($hours?' e '.$hours.' h':'');
    if($hours>0)return 'Sai em '.$hours.' h'.($min?' e '.$min.' min':'');
    return 'Sai em '.$min.' min';
}
function bus_cities(PDO $db): array {
    return $db->query('SELECT DISTINCT s.city FROM bus_stops s INNER JOIN bus_routes r ON r.id=s.route_id WHERE r.active=1 ORDER BY s.city COLLATE utf8mb4_unicode_ci')->fetchAll(PDO::FETCH_COLUMN);
}
function bus_matching_routes(PDO $db,string $from,string $to): array {
    $stmt=$db->prepare('SELECT r.id AS route_id,r.name AS route_name,r.company,r.notes,r.fare_amount,r.source_type,r.source_url,r.last_source_update,a.city AS origin_city,a.location_label AS origin_label,a.minutes_from_start AS origin_minutes,b.city AS destination_city,b.location_label AS destination_label,b.minutes_from_start AS destination_minutes FROM bus_routes r INNER JOIN bus_stops a ON a.route_id=r.id INNER JOIN bus_stops b ON b.route_id=r.id AND a.stop_order<b.stop_order WHERE r.active=1 AND a.city=? AND b.city=? ORDER BY r.name');
    $stmt->execute([$from,$to]);return $stmt->fetchAll();
}
function bus_departures_for_routes(PDO $db,array $routes): array {
    if (!$routes) return [];
    $ids=array_values(array_unique(array_map(static fn($r)=>(int)$r['route_id'],$routes)));
    $marks=implode(',',array_fill(0,count($ids),'?'));
    $stmt=$db->prepare('SELECT id,route_id,start_time,days_mask,active FROM bus_departures WHERE active=1 AND route_id IN ('.$marks.')');
    $stmt->execute($ids);return $stmt->fetchAll();
}
/** Resumo das linhas que partem de uma cidade até o último ponto cadastrado. */
function bus_routes_from_city(PDO $db,string $origin): array {
    $stmt=$db->prepare('SELECT r.id AS route_id,r.name AS route_name,r.company,r.notes,r.fare_amount,r.source_type,r.source_url,r.last_source_update, a.city AS origin_city,a.location_label AS origin_label,a.minutes_from_start AS origin_minutes,b.city AS destination_city,b.location_label AS destination_label,b.minutes_from_start AS destination_minutes FROM bus_routes r JOIN bus_stops a ON a.route_id=r.id JOIN bus_stops b ON b.route_id=r.id AND b.stop_order=(SELECT MAX(x.stop_order) FROM bus_stops x WHERE x.route_id=r.id) WHERE r.active=1 AND a.city=? AND a.stop_order<b.stop_order ORDER BY r.name');
    $stmt->execute([$origin]);return $stmt->fetchAll();
}
function bus_site_origin_city(string $configured): string {
    $city=trim((string)(preg_split('/\s+[-,]\s+/u',$configured,2)[0]??''));
    return $city!==''?$city:'Iguatama';
}
function bus_calendar_label(DateTimeImmutable $departure,?DateTimeImmutable $now=null): string {
    $tz=new DateTimeZone('America/Sao_Paulo');
    $today=($now??new DateTimeImmutable('now',$tz))->setTimezone($tz)->setTime(0,0);
    $date=$departure->setTimezone($tz)->setTime(0,0);
    $days=(int)$today->diff($date)->format('%r%a');
    if($days<0)return 'Horário encerrado';
    if($days===0)return 'Hoje';
    if($days===1)return 'Amanhã';
    return 'Em '.$days.' dias';
}

/** Duração calculada apenas quando a origem e a chegada têm horário cadastrado. */
function bus_duration_label(DateTimeImmutable $departure, ?DateTimeImmutable $arrival): string {
    if (!$arrival) return 'Duração a confirmar';
    $minutes=(int)round(($arrival->getTimestamp()-$departure->getTimestamp())/60);
    if ($minutes<0 || $minutes>10080) return 'Duração a confirmar';
    $days=intdiv($minutes,1440);$hours=intdiv($minutes%1440,60);$mins=$minutes%60;
    $pieces=[];
    if($days)$pieces[]=$days.' dia'.($days>1?'s':'');
    if($hours)$pieces[]=$hours.' h';
    if($mins || !$pieces)$pieces[]=$mins.' min';
    return 'Duração: '.implode(' e ',$pieces);
}
