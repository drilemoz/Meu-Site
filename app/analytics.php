<?php
declare(strict_types=1);

require_once __DIR__ . '/analytics_helpers.php';
/** Métricas próprias: IP só em memória, identificador diário pseudonimizado, sem cookies de rastreamento. */
function migrate_analytics_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS analytics_events (
      id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      occurred_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      event_day DATE NOT NULL,
      event_type ENUM('pageview','click') NOT NULL,
      page_path VARCHAR(260) NOT NULL,
      target VARCHAR(260) NOT NULL DEFAULT '',
      visitor_day_hash CHAR(64) NOT NULL,
      source VARCHAR(40) NOT NULL DEFAULT 'direto',
      referrer_host VARCHAR(190) NOT NULL DEFAULT '',
      campaign VARCHAR(80) NOT NULL DEFAULT '',
      country_code CHAR(2) NOT NULL DEFAULT '',
      country_name VARCHAR(100) NOT NULL DEFAULT '',
      region VARCHAR(120) NOT NULL DEFAULT '',
      device VARCHAR(18) NOT NULL DEFAULT 'desconhecido',
      is_bot TINYINT(1) NOT NULL DEFAULT 0,
      bot_name VARCHAR(60) NOT NULL DEFAULT '',
      INDEX idx_analytics_date (event_day,event_type),
      INDEX idx_analytics_path (page_path,event_day),
      INDEX idx_analytics_bot (event_day,is_bot),
      INDEX idx_analytics_visitor (visitor_day_hash,event_day)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function analytics_log(PDO $db,string $type,string $page,string $target='',array $server=[],array $cookies=[],array $query=[]): bool {
    if(!in_array($type,['pageview','click'],true)||!analytics_enabled()||analytics_opted_out($server,$cookies)||admin_user())return false;
    $ua=substr((string)($server['HTTP_USER_AGENT']??''),0,240);
    $bot=analytics_bot($ua);
    if($type==='click'&&$bot!=='')return false;
    $ip=analytics_client_ip($server);
    $occurredAt=date('Y-m-d H:i:s');
    $day=substr($occurredAt,0,10);
    $key=analytics_secret();
    $hash=analytics_visitor_hash($ip,$ua,$day,$key);
    // Detector conservador de rajadas; a identidade não é verificada, e pode haver NAT.
    if($type==='pageview' && $bot==='' && $ip!=='' && $ua!==''){
        $rate=$db->prepare("SELECT COUNT(*) FROM analytics_events WHERE visitor_day_hash=? AND event_day=? AND event_type='pageview' AND occurred_at >= ?");
        $rate->execute([$hash,$day,date('Y-m-d H:i:s',time()-60)]);
        if((int)$rate->fetchColumn()>=29)$bot='Volume suspeito';
    }
    $route=analytics_page($page,$query);
    $dest=$type==='click'?analytics_target($target):'';
    if($type==='click'&&$dest==='')return false;
    [$source,$refHost]=analytics_referrer((string)($server['HTTP_REFERER']??''),$server);
    $campaign='';
    if($type==='pageview'){
        $utm=$query['utm_source']??'';
        if(is_string($utm)){
            $campaign=analytics_campaign($utm);
            if($campaign!=='')$source='campanha';
        }
    }
    [$countryCode,$country,$region]=analytics_geo($ip);
    $q=$db->prepare('INSERT INTO analytics_events (occurred_at,event_day,event_type,page_path,target,visitor_day_hash,source,referrer_host,campaign,country_code,country_name,region,device,is_bot,bot_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$occurredAt,$day,$type,$route,$dest,$hash,$source,$refHost,$campaign,$countryCode,$country,$region,analytics_device($ua),(int)($bot!==''),$bot]);
    // limpeza oportunística leve; cron separado também disponível
    if(random_int(1,400)===1)analytics_prune($db,90);
    return true;
}
function analytics_prune(PDO $db,int $days=90): int {
    $days=max(7,min(365,$days));
    return $db->exec('DELETE FROM analytics_events WHERE event_day < DATE_SUB(CURDATE(), INTERVAL '.$days.' DAY)') ?: 0;
}
/** Janela fechada em America/Sao_Paulo; o filtro de 24h é móvel, não "ontem + hoje". */
function analytics_time_range(int|string $period,?DateTimeImmutable $clock=null): array {
    $tz=new DateTimeZone('America/Sao_Paulo');
    $now=($clock??new DateTimeImmutable('now',$tz))->setTimezone($tz);
    $p=(string)$period;
    if(!in_array($p,['today','24h','7','30','90'],true))$p='30';
    $hourly=$p==='today'||$p==='24h';
    $start=$p==='today'?$now->setTime(0,0,0):($p==='24h'?$now->modify('-24 hours'):$now->setTime(0,0,0)->modify('-'.((int)$p-1).' days'));
    return [
      'period'=>$p,'days'=>$hourly?0:(int)$p,'hourly'=>$hourly,
      'label'=>match($p){'today'=>'Hoje','24h'=>'Últimas 24 horas',default=>'Últimos '.$p.' dias'},
      'start'=>$start,'end'=>$now,
      'params'=>[$start->format('Y-m-d'),$now->format('Y-m-d'),$start->format('Y-m-d H:i:s'),$now->format('Y-m-d H:i:s')],
      'where'=>'event_day >= ? AND event_day <= ? AND occurred_at >= ? AND occurred_at <= ?',
    ];
}
/** Preenche intervalos vazios: público provável, robôs declarados e suspeitos. */
function analytics_complete_series(array $raw,array $range): array {
    $map=[];
    foreach($raw as $r){
        $map[(string)$r['bucket']]=[
            'views'=>(int)($r['views']??0),'bots'=>(int)($r['bots']??0),
            'suspect'=>(int)($r['suspect']??0),'clicks'=>(int)($r['clicks']??0)
        ];
    }
    $hourly=(bool)$range['hourly'];
    $cursor=$hourly?$range['start']->setTime((int)$range['start']->format('H'),0,0):$range['start']->setTime(0,0,0);
    $end=$range['end'];$series=[];
    for($i=0;$cursor<=$end&&$i<100;$i++,$cursor=$cursor->modify($hourly?'+1 hour':'+1 day')){
        $key=$cursor->format($hourly?'Y-m-d H:00:00':'Y-m-d');
        $totals=$map[$key]??['views'=>0,'bots'=>0,'suspect'=>0,'clicks'=>0];
        $series[]=['day'=>$key,'label'=>$cursor->format($hourly?($range['period']==='today'?'H\h':'d/m H\h'):'d/m'),
          'title'=>$cursor->format($hourly?'d/m/Y H:00':'d/m/Y'),...$totals];
    }
    return $series;
}
function analytics_group(string $group): string {
    return in_array($group,['human','bot','suspect'],true)?$group:'human';
}
/** Grupos sem DDL: is_bot e bot_name já existem no schema 37 e backup v12. */
function analytics_admin_data(PDO $db,int|string $period=30,?DateTimeImmutable $clock=null,string $group='human'): array {
    $group=analytics_group($group);
    $range=analytics_time_range($period,$clock);
    $where=$range['where'];$params=$range['params'];
    $suspect=analytics_suspect_sql();
    $human="is_bot=0";
    $declared="is_bot=1 AND NOT $suspect";
    $uncertain="is_bot=1 AND $suspect";
    $filters=['human'=>$human,'bot'=>$declared,'suspect'=>$uncertain];
    $summary=$db->prepare("SELECT COUNT(*) AS events,
      COALESCE(SUM(event_type='pageview' AND $human),0) AS views,
      COUNT(DISTINCT CASE WHEN event_type='pageview' AND $human THEN visitor_day_hash END) AS visitors_day,
      COALESCE(SUM(event_type='pageview' AND is_bot=1),0) AS automation_total,
      COALESCE(SUM(event_type='pageview' AND $declared),0) AS bots,
      COALESCE(SUM(event_type='pageview' AND $uncertain),0) AS suspect,
      COALESCE(SUM(event_type='click' AND $human),0) AS clicks,
      COUNT(DISTINCT CASE WHEN event_type='click' AND $human THEN visitor_day_hash END) AS engaged_ids
      FROM analytics_events WHERE $where");
    $summary->execute($params);
    $bucket=$range['hourly']?"DATE_FORMAT(occurred_at,'%Y-%m-%d %H:00:00')":"event_day";
    $series=$db->prepare("SELECT $bucket AS bucket,
      COALESCE(SUM(event_type='pageview' AND $human),0) AS views,
      COALESCE(SUM(event_type='pageview' AND $declared),0) AS bots,
      COALESCE(SUM(event_type='pageview' AND $uncertain),0) AS suspect,
      COALESCE(SUM(event_type='click' AND $human),0) AS clicks
      FROM analytics_events WHERE $where GROUP BY bucket ORDER BY bucket");
    $series->execute($params);
    $pageFilter="event_type='pageview' AND (".$filters[$group].")";
    $query=function(string $column,string $kind='pages',int $max=8)use($db,$where,$params,$pageFilter):array{
        $allowed=['page_path','target','source','referrer_host','country_name','region','bot_name','device','campaign'];
        if(!in_array($column,$allowed,true))throw new InvalidArgumentException('Dimensão inválida');
        $filter=$kind==='clicks'?"event_type='click' AND is_bot=0":$pageFilter;
        if($column==='campaign')$filter.=" AND campaign<>''";
        $s=$db->prepare("SELECT `$column` AS label, COUNT(*) AS total FROM analytics_events WHERE $where AND $filter GROUP BY `$column` ORDER BY total DESC LIMIT ".(int)$max);
        $s->execute($params);return $s->fetchAll();
    };
    $s=$summary->fetch()?:[];
    $pages=(int)($s['views']??0)+(int)($s['bots']??0)+(int)($s['suspect']??0);
    return ['period'=>$range['period'],'days'=>$range['days'],'label'=>$range['label'],'hourly'=>$range['hourly'],
      'group'=>$group,'pageviews_total'=>$pages,'summary'=>$s,
      'series'=>analytics_complete_series($series->fetchAll(),$range),
      'pages'=>$query('page_path'),'clicks'=>$query('target','clicks'),
      'sources'=>$query('source'),'referrers'=>$query('referrer_host'),
      'countries'=>$query('country_name'),'regions'=>$query('region'),
      'devices'=>$query('device'),'robots'=>$query('bot_name'),'campaigns'=>$query('campaign')];
}
/** Somente IDs de medição GA4; não aceita tags HTML, GTM ou código JavaScript arbitrário. */
function analytics_ga4_measurement_id(string $id): string {
    $id=strtoupper(trim($id));
    return preg_match('/^G-[A-Z0-9]{4,20}$/D',$id)?$id:'';
}
function analytics_ga4_public_id(): string {
    return setting('ga4_enabled','0')==='1'?analytics_ga4_measurement_id(setting('ga4_measurement_id')):'';
}
