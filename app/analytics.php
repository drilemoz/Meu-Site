<?php
declare(strict_types=1);
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
function analytics_client_ip(array $server): string {
    // Sem confiar em X-Forwarded-For de visitantes: usar REMOTE_ADDR do servidor web.
    $ip=(string)($server['REMOTE_ADDR']??'');
    return filter_var($ip,FILTER_VALIDATE_IP)?$ip:'';
}
function analytics_secret(): string {
    $path=IGUA_STORAGE.'/analytics.key';
    if(is_file($path)){
        $key=trim((string)file_get_contents($path));
        if(preg_match('/^[a-f0-9]{64}$/D',$key))return $key;
        throw new RuntimeException('Chave de métricas inválida. Restaure analytics.key.');
    }
    if(!is_dir(IGUA_STORAGE)) @mkdir(IGUA_STORAGE,0700,true);
    $key=bin2hex(random_bytes(32));
    // criação exclusiva em concorrência; arquivo fora da raiz pública
    $fd=@fopen($path,'x');
    if($fd!==false){@chmod($path,0600);fwrite($fd,$key."\n");fclose($fd);return $key;}
    if(is_file($path))return analytics_secret();
    throw new RuntimeException('Storage indisponível para a chave local de métricas.');
}
function analytics_visitor_hash(string $ip,string $ua,string $day,string $secret): string {
    return hash_hmac('sha256',$day."\0".$ip."\0".$ua,$secret);
}
function analytics_bot(string $ua): string {
    if($ua==='')return 'Automação não identificada';
    foreach([
      'Googlebot'=>'googlebot','Bingbot'=>'bingbot','DuckDuckBot'=>'duckduckbot','Baiduspider'=>'baiduspider',
      'YandexBot'=>'yandexbot','Applebot'=>'applebot','GPTBot'=>'gptbot','ChatGPT-User'=>'chatgpt-user',
      'Facebook'=>'facebookexternalhit','WhatsApp'=>'whatsapp','Telegram'=>'telegrambot',
      'Headless'=>'(headlesschrome|phantomjs|puppeteer|playwright|selenium|webdriver)','curl'=>'curl/','wget'=>'wget/','Python'=>'(python-requests|python-urllib|aiohttp)',
      'Cliente HTTP'=>'(go-http-client|libwww-perl|node-fetch|httpx/|axios/|scrapy/|okhttp/|java/)',
      'Monitoramento'=>'(uptimerobot|statuscake|pingdom|site24x7|datadog|bytespider|petalbot|ccbot|semrushbot)',
      'Scanner'=>'(scrapy|spider|crawler|bot/|\bbot\b|uptime|scanner|httpclient|ahrefs|semrush)'
    ] as $name=>$pattern){if($pattern[0]==='(' ? preg_match('~'.$pattern.'~i',$ua) : stripos($ua,$pattern)!==false)return $name;}
    return '';
}
function analytics_device(string $ua): string {
    if(analytics_bot($ua)!=='')return 'robô';
    if(preg_match('/tablet|ipad|kindle/i',$ua))return 'tablet';
    if(preg_match('/mobile|android|iphone/i',$ua))return 'celular';
    if(preg_match('/windows|macintosh|linux|x11/i',$ua))return 'computador';
    return 'desconhecido';
}
/** Registra apenas rotas públicas conhecidas. Não registra buscas, tokens ou consultas arbitrárias. */
function analytics_page(string $p,array $routeContext=[]): string {
    $parts=parse_url($p);
    if($parts===false)return '/index.php';
    $path=(string)($parts['path']??'/index.php');
    if(preg_match('~^/(noticia|categoria)/([a-z0-9]+(?:-[a-z0-9]+)*)/?$~D',$path,$m) && strlen($m[2])<=95){
        return '/'.$m[1].'/'.$m[2];
    }
    // Somente uma rota legal que o roteador público já identificou como válida.
    if(($routeContext['p']??'')==='app' && is_string($routeContext['app_slug']??null)){
        $slug=$routeContext['app_slug'];$page=$routeContext['app_page']??'inicio';
        if(is_string($page) && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$slug) && strlen($slug)<=95
           && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$page) && strlen($page)<=95){
           return '/'.$slug.($page==='inicio'?'':'/'.$page);
        }
    }
    if(!in_array($path,['/', '/index.php'],true))return '/index.php';
    $query=[];parse_str((string)($parts['query']??''),$query);
    $safe=[];
    foreach(['p','slug'] as $key){
        $v=$query[$key]??'';
        if(is_string($v)&&preg_match('/^[a-z0-9_-]{1,95}$/D',$v))$safe[$key]=$v;
    }
    return '/index.php'.($safe?'?'.http_build_query($safe):'');
}
function analytics_target(string $href): string {
    $parts=parse_url($href);
    if($parts===false)return '';
    if(!empty($parts['host'])){
        $host=strtolower((string)$parts['host']);
        if(!filter_var($host,FILTER_VALIDATE_DOMAIN,FILTER_FLAG_HOSTNAME))return '';
        return 'externo:'.substr($host,0,190);
    }
    return analytics_page($href);
}
function analytics_referrer(string $ref,array $server): array {
    $host=strtolower((string)(parse_url($ref,PHP_URL_HOST)?:''));
    if(!preg_match('/^[a-z0-9.-]{1,190}$/D',$host))$host='';
    $siteHost=strtolower((string)($server['HTTP_HOST']??''));
    $siteHost=preg_replace('/:\d+$/','',$siteHost)??$siteHost;
    if($host===''||$host===$siteHost)return ['direto',''];
    if(preg_match('/(^|\.)(google\.[a-z.]+|bing\.com|duckduckgo\.com|yahoo\.com)$/',$host))return ['busca',$host];
    if(preg_match('/(^|\.)(facebook\.com|instagram\.com|t\.co|twitter\.com|x\.com|reddit\.com)$/',$host))return ['rede_social',$host];
    if(preg_match('/(^|\.)(whatsapp\.com|telegram\.org)$/',$host))return ['mensagem',$host];
    return ['referencia',$host];
}
function analytics_campaign(string $value): string {
    return preg_match('/^[a-zA-Z0-9_ .-]{1,80}$/D',$value)?$value:'';
}
function analytics_geo(string $ip): array {
    // Base offline opcional. NUNCA envia IP a provedores externos.
    $file=IGUA_STORAGE.'/geoip/GeoLite2-City.mmdb';
    if(!is_file($file)||!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return ['','',''];
    $loader=IGUA_ROOT.'/vendor/autoload.php';if(!is_file($loader))return ['','',''];
    require_once $loader;
    if(!class_exists('GeoIp2\\Database\\Reader'))return ['','',''];
    try{
        $reader=new \GeoIp2\Database\Reader($file);$r=$reader->city($ip);$reader->close();
        $code=(string)($r->country->isoCode??'');
        $name=(string)($r->country->names['pt-BR']??$r->country->name??'');
        $region=(string)($r->mostSpecificSubdivision->names['pt-BR']??$r->mostSpecificSubdivision->name??'');
        return [substr($code,0,2),usub($name,0,100),usub($region,0,120)];
    }catch(Throwable){return ['','',''];}
}
function analytics_opted_out(array $server,array $cookies): bool {
    return ($cookies['igua_analytics_optout']??'')==='1'
        || ($server['HTTP_SEC_GPC']??'')==='1'
        || ($server['HTTP_DNT']??'')==='1';
}
function analytics_enabled(): bool {
    return setting('analytics_enabled','1')==='1';
}
function analytics_log(PDO $db,string $type,string $page,string $target='',array $server=[],array $cookies=[],array $query=[]): bool {
    if(!in_array($type,['pageview','click'],true)||!analytics_enabled()||analytics_opted_out($server,$cookies)||admin_user())return false;
    $ua=substr((string)($server['HTTP_USER_AGENT']??''),0,240);
    $bot=analytics_bot($ua);
    if($type==='click'&&$bot!=='')return false;
    $ip=analytics_client_ip($server);
    $day=date('Y-m-d');
    $key=analytics_secret();
    $hash=analytics_visitor_hash($ip,$ua,$day,$key);
    // Detector conservador de rajadas; a identidade não é verificada, e pode haver NAT.
    if($type==='pageview' && $bot==='' && $ip!=='' && $ua!==''){
        $rate=$db->prepare("SELECT COUNT(*) FROM analytics_events WHERE visitor_day_hash=? AND event_day=? AND event_type='pageview' AND occurred_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
        $rate->execute([$hash,$day]);
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
    $q=$db->prepare('INSERT INTO analytics_events (event_day,event_type,page_path,target,visitor_day_hash,source,referrer_host,campaign,country_code,country_name,region,device,is_bot,bot_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
    $q->execute([$day,$type,$route,$dest,$hash,$source,$refHost,$campaign,$countryCode,$country,$region,analytics_device($ua),(int)($bot!==''),$bot]);
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
/** Preenche intervalos vazios com zero. Um período móvel de 24h pode abranger 25 horas de relógio parciais. */
function analytics_complete_series(array $raw,array $range): array {
    $map=[];
    foreach($raw as $r)$map[(string)$r['bucket']]=['views'=>(int)$r['views'],'bots'=>(int)$r['bots'],'clicks'=>(int)$r['clicks']];
    $hourly=(bool)$range['hourly'];
    $cursor=$hourly?$range['start']->setTime((int)$range['start']->format('H'),0,0):$range['start']->setTime(0,0,0);
    $end=$range['end'];$series=[];
    for($i=0;$cursor<=$end&&$i<100;$i++,$cursor=$cursor->modify($hourly?'+1 hour':'+1 day')){
        $key=$cursor->format($hourly?'Y-m-d H:00:00':'Y-m-d');
        $totals=$map[$key]??['views'=>0,'bots'=>0,'clicks'=>0];
        $series[]=['day'=>$key,'label'=>$cursor->format($hourly?($range['period']==='today'?'H\h':'d/m H\h'):'d/m'),
          'title'=>$cursor->format($hourly?'d/m/Y H:00':'d/m/Y'),'views'=>$totals['views'],'bots'=>$totals['bots'],'clicks'=>$totals['clicks']];
    }
    return $series;
}
function analytics_admin_data(PDO $db,int|string $period=30,?DateTimeImmutable $clock=null): array {
    $range=analytics_time_range($period,$clock);
    $where=$range['where'];$params=$range['params'];
    $summary=$db->prepare("SELECT COUNT(*) AS events, COALESCE(SUM(event_type='pageview' AND is_bot=0),0) AS views,COUNT(DISTINCT CASE WHEN event_type='pageview' AND is_bot=0 THEN visitor_day_hash END) AS visitors_day, COALESCE(SUM(event_type='pageview' AND is_bot=1),0) AS bots, COALESCE(SUM(event_type='click'),0) AS clicks FROM analytics_events WHERE $where");
    $summary->execute($params);
    $bucket=$range['hourly']?"DATE_FORMAT(occurred_at,'%Y-%m-%d %H:00:00')":"event_day";
    $series=$db->prepare("SELECT $bucket AS bucket, SUM(event_type='pageview' AND is_bot=0) AS views,SUM(event_type='pageview' AND is_bot=1) AS bots,SUM(event_type='click') AS clicks FROM analytics_events WHERE $where GROUP BY bucket ORDER BY bucket");
    $series->execute($params);
    $query=function(string $column,string $whereExtra='',int $max=8)use($db,$where,$params):array{
        $allowed=['page_path','target','source','referrer_host','country_name','region','bot_name','device'];
        if(!in_array($column,$allowed,true))throw new InvalidArgumentException('Dimensão inválida');
        $extra=['pages'=>"event_type='pageview' AND is_bot=0",'clicks'=>"event_type='click'",'bots'=>'is_bot=1'];
        $filter=$extra[$whereExtra]??"event_type='pageview' AND is_bot=0";
        $s=$db->prepare("SELECT `$column` AS label, COUNT(*) AS total FROM analytics_events WHERE $where AND $filter GROUP BY `$column` ORDER BY total DESC LIMIT ".(int)$max);
        $s->execute($params);return $s->fetchAll();
    };
    return ['period'=>$range['period'],'days'=>$range['days'],'label'=>$range['label'],'hourly'=>$range['hourly'],
      'summary'=>$summary->fetch()?:[],'series'=>analytics_complete_series($series->fetchAll(),$range),
      'pages'=>$query('page_path','pages'),'clicks'=>$query('target','clicks'),
      'sources'=>$query('source'),'referrers'=>$query('referrer_host'),
      'countries'=>$query('country_name'),'regions'=>$query('region'),
      'devices'=>$query('device'),'robots'=>$query('bot_name','bots')];
}
/** Somente IDs de medição GA4; não aceita tags HTML, GTM ou código JavaScript arbitrário. */
function analytics_ga4_measurement_id(string $id): string {
    $id=strtoupper(trim($id));
    return preg_match('/^G-[A-Z0-9]{4,20}$/D',$id)?$id:'';
}
function analytics_ga4_public_id(): string {
    return setting('ga4_enabled','0')==='1'?analytics_ga4_measurement_id(setting('ga4_measurement_id')):'';
}
