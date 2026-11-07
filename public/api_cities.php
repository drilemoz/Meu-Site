<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/bus.php';
require __DIR__.'/../app/cities.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, max-age=120');
header('X-Content-Type-Options: nosniff');
if(!installed()){http_response_code(503);echo '{"error":"Instalação pendente"}';exit;}
$q=trim((string)($_GET['q']??''));
if(mb_bus_len($q)<2 || mb_bus_len($q)>60){echo '[]';exit;}
try{echo json_encode(cities_suggest(db(),$q),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);}catch(Throwable){http_response_code(503);echo '{"error":"Busca de cidades temporariamente indisponível"}';}
