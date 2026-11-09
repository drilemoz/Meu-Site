<?php
declare(strict_types=1);
/** Endpoint exclusivo para a chave IndexNow via Nginx na VPS; não usa banco. */
if(getenv('IGUA_INDEXNOW_VIRTUAL')!=='1'){http_response_code(404);exit;}
$path=(string)(parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH)?:'');
if(!preg_match('~^/([a-f0-9]{32})\.txt$~D',$path,$match)){http_response_code(404);exit;}
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/indexnow.php';
$cfg=igua_indexnow_read(__DIR__.'/../storage');
if($cfg===null || !hash_equals((string)$cfg['key'],$match[1])){http_response_code(404);exit;}
header('Content-Type: text/plain; charset=UTF-8');
header('Cache-Control: public, max-age=300');
header('X-Content-Type-Options: nosniff');
echo $match[1];
