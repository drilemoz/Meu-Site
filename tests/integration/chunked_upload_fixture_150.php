<?php
/** Apenas em `php -S -t tests/integration` para testar o transportador com uploads HTTP reais. */
declare(strict_types=1);
require __DIR__.'/../../app/bootstrap.php';
require __DIR__.'/../../app/requirements.php';
require __DIR__.'/../../app/backup.php';
require __DIR__.'/../../app/setup_chunked_backup.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
try {
    if (!request_post()) {echo json_encode(['csrf'=>csrf_token(),'chunk_size'=>igua_setup_chunk_size('2M','8M')]);exit;}
    check_csrf();
    $action=(string)($_POST['action']??'');
    if ($action==='inspect') {
        $staged=$_SESSION['iguanews_setup_chunk']??[];
        $p=igua_setup_chunk_path((string)($staged['id']??''));
        echo json_encode(['sha256'=>hash_file('sha256',$p),'size'=>filesize($p)]);
        igua_setup_clear_chunk();exit;
    }
    $result=igua_setup_receive_chunk($_FILES['chunk']??[], (string)($_POST['backup_name']??''),
        (int)($_POST['upload_total']??0),(int)($_POST['upload_offset']??0),(string)($_POST['upload_token']??''));
    echo json_encode($result,JSON_THROW_ON_ERROR);
} catch (DomainException $e) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
catch (Throwable $e) { http_response_code(500); echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); }
