<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/analytics_admin.php';
function audience140_check(bool $pass,string $msg):void {if(!$pass)throw new RuntimeException($msg);echo 'OK: '.$msg.PHP_EOL;}
$realBrowsers=[
 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/129.0 Mobile Safari/537.36',
 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148 Safari/604.1',
 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
];
foreach($realBrowsers as $ua) audience140_check(analytics_bot($ua)==='' && analytics_traffic_class(analytics_bot($ua))==='human','navegador sem indícios não recebe categoria de robô');
$botAgents=[
 'Googlebot'=>'Googlebot/2.1 (+http://www.google.com/bot.html)',
 'Bingbot'=>'Mozilla/5.0 (compatible; bingbot/2.0)',
 'GPTBot'=>'GPTBot/1.0',
 'OAI-SearchBot'=>'OAI-SearchBot/1.0',
 'PerplexityBot'=>'PerplexityBot/1.0',
 'ClaudeBot'=>'ClaudeBot/1.0',
 'Facebook'=>'facebookexternalhit/1.1',
];
foreach($botAgents as $name=>$ua) audience140_check(analytics_bot($ua)===$name && analytics_traffic_class(analytics_bot($ua))==='bot','UA declarado de '.$name);
foreach(['','curl/8.4.0','python-requests/2.32','Mozilla/5.0 HeadlessChrome/129.0','Scanner/3.1'] as $ua){
    audience140_check(analytics_traffic_class(analytics_bot($ua))==='suspect','sinal automatizado não declarado: '.substr($ua,0,28));
}
audience140_check(analytics_traffic_class('Volume suspeito')==='suspect','histórico de rajadas recuperado como suspeito');
audience140_check(analytics_traffic_class('Antigo crawler personalizado')==='bot','crawler histórico desconhecido não é reescrito');
audience140_check(analytics_group('human')==='human'&&analytics_group('bot')==='bot'&&analytics_group('suspect')==='suspect','três grupos válidos');
audience140_check(analytics_group('all')==='human'&&analytics_audience(['group'=>['bot']])==='human'&&analytics_audience(['group'=>'suspect'])==='suspect','filtro de grupo à prova de entradas malformadas');
$clock=new DateTimeImmutable('2026-11-08 12:15:00',new DateTimeZone('America/Sao_Paulo'));
$range=analytics_time_range('today',$clock);
$series=analytics_complete_series([['bucket'=>'2026-11-08 11:00:00','views'=>6,'bots'=>4,'suspect'=>3,'clicks'=>2]],$range);
audience140_check($series[11]['views']===6&&$series[11]['bots']===4&&$series[11]['suspect']===3&&$series[11]['clicks']===2,'série de três categorias e cliques');
audience140_check($series[10]['suspect']===0,'horas sem eventos permanecem zero');
class AudienceFakePDO140 extends PDO {
    public array $queries=[];
    public function __construct(){}
    public function prepare(string $query,array $options=[]): PDOStatement|false { $this->queries[]=$query;return new AudienceFakeStatement140(); }
}
class AudienceFakeStatement140 extends PDOStatement {
    public function __construct(){}
    public function execute(?array $params=null):bool {if(count($params??[])!==4)throw new RuntimeException('Parâmetros SQL esperados');return true;}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed {return ['views'=>6,'bots'=>4,'suspect'=>3,'visitors_day'=>3,'clicks'=>2,'engaged_ids'=>1];}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args):array{return [];}
}
foreach(['human','bot','suspect'] as $group){
    $pdo=new AudienceFakePDO140();$result=analytics_admin_data($pdo,'today',$clock,$group);
    audience140_check($result['group']===$group&&count($pdo->queries)===11,'consulta agregada com nove dimensões para '.$group);
    audience140_check(str_contains($pdo->queries[0],'AS suspect')&&str_contains($pdo->queries[0],'AS engaged_ids'),'KPIs independentes de automação e interação');
    audience140_check(str_contains($pdo->queries[1],'SUM(')&&str_contains($pdo->queries[1],'AS suspect'),'gráfico separa suspeitos');
    if($group==='suspect')audience140_check(str_contains($pdo->queries[2],'bot_name LIKE'), 'páginas suspeitas filtradas no SQL');
    if($group==='human')audience140_check(str_contains($pdo->queries[2],'is_bot=0'),'páginas públicas prováveis filtradas no SQL');
}
$code=file_get_contents(__DIR__.'/../app/analytics.php');
audience140_check(str_contains($code,'INSERT INTO analytics_events (occurred_at,event_day,')&&str_contains($code,'$occurredAt,$day'),'registros novos não dependem do fuso SQL');
audience140_check(!str_contains($code,'DATE_SUB(NOW()')&&!str_contains(file_get_contents(__DIR__.'/../public/analytics_event.php'),'DATE_SUB(NOW()'),'rate limit utiliza relógio PHP');
audience140_check(!str_contains($code,'ALTER TABLE analytics_events')&&!str_contains($code,'UPDATE analytics_events'),'eventos históricos não são alterados');
$view=file_get_contents(__DIR__.'/../app/analytics_admin.php');
audience140_check(str_contains($view,'analytics-audience-tabs')&&str_contains($view,'aria-current="page"')&&str_contains($view,'analytics-share-track'),'filtros e visualização em três grupos');
echo 'Métricas v1.0.140: testes de classificação, UI e SQL aprovados.'.PHP_EOL;
