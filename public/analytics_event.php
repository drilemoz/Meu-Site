<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/analytics.php';
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){http_response_code(405);exit;}
if(!installed()){http_response_code(204);exit;}
// Bloqueio de chamadas cross-origin: endpoint não aceita medições de outro domínio.
if(isset($_SERVER['HTTP_SEC_FETCH_SITE'])&&!in_array($_SERVER['HTTP_SEC_FETCH_SITE'],['same-origin','none'],true)){http_response_code(403);exit;}
$origin=(string)($_SERVER['HTTP_ORIGIN']??'');
if($origin!==''){
    $originHost=parse_url($origin,PHP_URL_HOST);
    $siteHost=preg_replace('/:\d+$/','',(string)($_SERVER['HTTP_HOST']??''));
    if(!is_string($originHost)||strcasecmp($originHost,$siteHost)!==0){http_response_code(403);exit;}
}
$size=(int)($_SERVER['CONTENT_LENGTH']??0);
if($size>2048){http_response_code(413);exit;}
$raw=file_get_contents('php://input',false,null,0,2049);
if($raw===false||strlen($raw)>2048){http_response_code(413);exit;}
$data=json_decode($raw,true);
if(!is_array($data)||($data['type']??'')!=='click'||!is_string($data['page']??null)||!is_string($data['target']??null)){http_response_code(400);exit;}
if(strlen($data['page'])>600||strlen($data['target'])>600){http_response_code(400);exit;}
if(session_status()===PHP_SESSION_ACTIVE)session_write_close();
try{
    $db=db();
    // limite por visitante/dia em pequena janela para reduzir spam de cliques
    $hash=analytics_visitor_hash(analytics_client_ip($_SERVER),substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,240),date('Y-m-d'),analytics_secret());
    $st=$db->prepare("SELECT COUNT(*) FROM analytics_events WHERE visitor_day_hash=? AND event_type='click' AND occurred_at >= ?");
    $st->execute([$hash,date('Y-m-d H:i:s',time()-60)]);
    if((int)$st->fetchColumn()<30)analytics_log($db,'click',$data['page'],$data['target'],$_SERVER,$_COOKIE);
}catch(Throwable){/* Métricas não devem derrubar a navegação. */}
http_response_code(204);
