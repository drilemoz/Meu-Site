<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/public_search.php';
require_once __DIR__.'/../app/portal_options.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store');
header('X-Content-Type-Options: nosniff');
if (($_SERVER['REQUEST_METHOD']??'GET') !== 'GET') {
    header('Allow: GET'); http_response_code(405); echo '[]'; exit;
}
if (!installed()) {http_response_code(503);echo '[]';exit;}
try {
    $active=json_decode(setting('portal_active_categories',''),true);$active=is_array($active)&&array_is_list($active)?$active:null;
    $portal=igua_portal_options(setting('portal_options',''));
    $items=igua_public_search_suggestions(db(), $_GET['q']??'',6,$active,$portal['bus']);
    echo json_encode($items, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT);
} catch (Throwable $e) {
    http_response_code(503);
    echo '[]';
}
