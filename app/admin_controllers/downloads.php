<?php
declare(strict_types=1);
// Download de pacotes e backups, sem conteúdo público novo.
if($action==='download_migration'){
    require_ability('backup',$me);
    if(!request_post()){http_response_code(405);exit('Use o formulário administrativo.');}
    check_csrf();
    // Backup portátil contém contas, hashes de senhas e dados pessoais; nunca criar link público.
    $path=igua_make_migration_package($db);
    register_shutdown_function(static function()use($path):void{if(is_file($path))@unlink($path);});
    header('Content-Type: application/zip');header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="'.basename($path).'"');
    header('Cache-Control: no-store');header('Content-Length: '.filesize($path));
    readfile($path);@unlink($path);exit;
}
if($action==='download_bundle'){
    require_ability('backup',$me);
    if(!request_post()){http_response_code(405);exit('Use a ação do painel.');}
    check_csrf();
    $path=make_media_bundle($db);
    // O ZIP completo é um download transitório; não acumular cópias de 350 MiB a cada clique.
    register_shutdown_function(static function()use($path):void{if(is_file($path))@unlink($path);});
    header('Content-Type: application/zip');header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="'.basename($path).'"');
    header('Cache-Control: no-store');header('Content-Length: '.filesize($path));
    readfile($path);
    @unlink($path);
    exit;
}
if ($action==='download') {
    $filename=(string)($_GET['file']??''); $path=backup_path($filename);
    if (!$path) {http_response_code(404);exit('Arquivo não encontrado.');}
    header('Content-Type: application/octet-stream'); header('X-Content-Type-Options: nosniff');
    header('Content-Disposition: attachment; filename="'.basename($path).'"');
    header('Content-Length: '.filesize($path)); header('Cache-Control: no-store');readfile($path);exit;
}
