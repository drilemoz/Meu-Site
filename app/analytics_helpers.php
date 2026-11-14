<?php
/**
 * Normalização das rotas públicas, privacidade e identificação heurística de acessos.
 * Esta camada não grava eventos nem executa consultas SQL.
 * Carregada por app/analytics.php para manter a API antiga inalterada.
 */
declare(strict_types=1);

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
/** Declaração de agente de rede; NÃO confirma identidade por DNS/IP do provedor. */
function analytics_bot(string $ua): string {
    if(trim($ua)==='')return 'Automação não identificada';
    foreach([
      'Googlebot'=>'googlebot','Google Outros'=>'(googleother|google-inspectiontool|adsbot-google|mediapartners-google)',
      'Bingbot'=>'bingbot','DuckDuckBot'=>'duckduckbot','Baiduspider'=>'baiduspider',
      'YandexBot'=>'yandexbot','Applebot'=>'applebot','GPTBot'=>'gptbot',
      'OAI-SearchBot'=>'oai-searchbot','ChatGPT-User'=>'chatgpt-user',
      'ClaudeBot'=>'(claudebot|claude-searchbot|claude-user)',
      'PerplexityBot'=>'perplexitybot','Amazonbot'=>'amazonbot',
      'CCBot'=>'ccbot','Bytespider'=>'bytespider','PetalBot'=>'petalbot',
      'Facebook'=>'(facebookexternalhit|meta-externalagent|meta-externalfetcher)',
      'WhatsApp'=>'whatsapp','Telegram'=>'telegrambot',
      'Redes sociais'=>'(discordbot|slackbot|linkedinbot|twitterbot)',
      'Headless'=>'(headlesschrome|phantomjs|puppeteer|playwright|selenium|webdriver)',
      'curl'=>'curl/','wget'=>'wget/',
      'Python'=>'(python-requests|python-urllib|aiohttp|python-httpx)',
      'Cliente HTTP'=>'(go-http-client|libwww-perl|node-fetch|httpx/|axios/|scrapy/|okhttp/|java/)',
      'Monitoramento'=>'(uptimerobot|statuscake|pingdom|site24x7|datadog)',
      'Scanner'=>'(scrapy|spider|crawler|bot/|\bbot\b|uptime|scanner|httpclient|ahrefs|semrush)'
    ] as $name=>$pattern){
        if(str_starts_with($pattern,'(') ? preg_match('~'.$pattern.'~i',$ua) : stripos($ua,$pattern)!==false)return $name;
    }
    return '';
}
/** Classificação heurística, sem prometer comprovação de pessoa ou crawler. */
function analytics_traffic_class(string $botName): string {
    if($botName==='')return 'human';
    return in_array($botName,[
       'Automação não identificada','Volume suspeito','Automação suspeita',
       'Headless','curl','wget','Python','Cliente HTTP','Scanner'
    ],true) || str_starts_with($botName,'Suspeito:') ? 'suspect' : 'bot';
}
/** Expressão constante para manter a leitura do histórico compatível com a classificação PHP. */
function analytics_suspect_sql(): string {
    return "(bot_name IN ('Automação não identificada','Volume suspeito','Automação suspeita','Headless','curl','wget','Python','Cliente HTTP','Scanner') OR bot_name LIKE 'Suspeito:%')";
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
