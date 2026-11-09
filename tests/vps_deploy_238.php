<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/schema_migrations.php';
require __DIR__.'/../app/updater.php';
require __DIR__.'/../app/editorial.php';
require __DIR__.'/../app/indexnow.php';
function chk238(bool $v,string $message):void{if(!$v)throw new RuntimeException('VPS 238: '.$message);}
$prev=getenv('IGUA_DEPLOY_VPS');$prevIndex=getenv('IGUA_INDEXNOW_VIRTUAL');
$folder=IGUA_UPDATE_DIR.'/vps';$created=!is_dir($folder);
$lock=IGUA_UPDATE_DIR.'/vps/.dispatch.lock';
try {
    putenv('IGUA_DEPLOY_VPS=1');putenv('IGUA_INDEXNOW_VIRTUAL=1');
    chk238(igua_updater_vps_mode(),'modo VPS detectado por configuração de ambiente');
    chk238(igua_updater_source()['origin']==='vps-fixed','origem fixada sem arquivo gravável');
    foreach(['source','source_reset'] as $operation){
        try {if($operation==='source')igua_updater_set_source('other','test');else igua_updater_reset_source();throw new RuntimeException('deveria falhar');}
        catch(RuntimeException $e){chk238(str_contains($e->getMessage(),'bloqueada')||str_contains($e->getMessage(),'imutável'),'bloqueio de alteração de origem');}
    }
    if(!is_dir(IGUA_UPDATE_DIR))mkdir(IGUA_UPDATE_DIR,0700,true);
    if($created)mkdir($folder,0770);
    if(!is_file($lock))touch($lock);
    igua_updater_vps_write('worker.json',['at'=>time()]);
    $token=bin2hex(random_bytes(16));
    igua_updater_vps_enqueue('install_remote',18,$token);
    $req=igua_updater_vps_read('request.json');
    chk238($req!==null&&$req['user_id']===18&&$req['token']===$token,'fila valida e token aleatório');
    try {igua_updater_vps_enqueue('install_remote',18,bin2hex(random_bytes(16)));throw new RuntimeException('deveria falhar');}
    catch(RuntimeException $e){chk238(str_contains($e->getMessage(),'pendente'),'segunda atualização bloqueada');}
    igua_updater_vps_write('status.json',['user_id'=>18,'token'=>$token,'stage'=>'backup','message'=>'Backup em execução','at'=>time()]);
    chk238(igua_updater_vps_status(18,$token)['stage']==='backup','progresso visível ao próprio usuário');
    chk238(igua_updater_vps_status(19,$token)===null,'progresso isolado por usuário');
    chk238(igua_updater_vps_status(18,'malformed')===null,'token inválido rejeitado');
    foreach(['../../passwd','source.json','../request.json'] as $bad){
        try {igua_updater_vps_path($bad);throw new RuntimeException('deveria falhar');}
        catch(InvalidArgumentException $e){chk238(true,'nome de arquivo rejeitado');}
    }
    $root=sys_get_temp_dir().'/igua-index-vps-'.bin2hex(random_bytes(5));
    mkdir($root);mkdir($root.'/public');mkdir($root.'/storage');
    $cfg=igua_indexnow_setup('https://iguatama.online',$root.'/public',$root.'/storage');
    chk238(is_file($root.'/storage/integrations/indexnow.json'),'IndexNow grava apenas em storage');
    chk238(!is_file($root.'/public/'.$cfg['key'].'.txt'),'IndexNow nao escreve em public');
    chk238(igua_indexnow_file_ready($cfg,$root.'/public'),'IndexNow preparado com modo virtual');
    putenv('IGUA_INDEXNOW_VIRTUAL=0');
    chk238(!igua_indexnow_file_ready($cfg,$root.'/public'),'modo tradicional continua exigindo arquivo');
    unlink($root.'/storage/integrations/indexnow.json');rmdir($root.'/storage/integrations');rmdir($root.'/storage');rmdir($root.'/public');rmdir($root);
    $src=(string)file_get_contents(__DIR__.'/../app/vps_deploy_worker.php');
    chk238(str_contains($src,'igua_updater_manual_rollback'),'recuperacao por CLI');
    chk238(str_contains((string)file_get_contents(__DIR__.'/../app/updater_vps.php'),"'install_remote'"),'deploy so oficial');
    chk238(str_contains((string)file_get_contents(__DIR__.'/../deploy/aws/iguatama-deploy.service'),'User=iguanews-deploy'),'systemd nao usa root nem www-data');
    echo "OK: VPS 238 fila, isolamento, fonte fixa, controle de concorrência e IndexNow virtual\n";
} finally {
    foreach(['request.json','running.json','status.json','worker.json','.dispatch.lock'] as $f){$path=$folder.'/'.$f;if(is_file($path))@unlink($path);}
    if($created)@rmdir($folder);
    if($prev===false)putenv('IGUA_DEPLOY_VPS');else putenv('IGUA_DEPLOY_VPS='.$prev);
    if($prevIndex===false)putenv('IGUA_INDEXNOW_VIRTUAL');else putenv('IGUA_INDEXNOW_VIRTUAL='.$prevIndex);
}
