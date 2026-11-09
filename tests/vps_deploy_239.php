<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/schema_migrations.php';
require __DIR__.'/../app/updater.php';
function check239(bool $ok,string $desc): void {if(!$ok)throw new RuntimeException('VPS 239: '.$desc);}
$old=getenv('IGUA_DEPLOY_VPS');$oldDir=IGUA_UPDATE_DIR.'/vps';
$created=!is_dir($oldDir);$private=$oldDir.'/private';$createdPrivate=!is_dir($private);
try{
    putenv('IGUA_DEPLOY_VPS=1');
    if(!is_dir(IGUA_UPDATE_DIR))@mkdir(IGUA_UPDATE_DIR,0700,true);
    if($created)mkdir($oldDir,0770);
    if($createdPrivate)mkdir($private,0750);
    check239(igua_updater_maintenance()===$private.'/maintenance','marcador protegido pelo worker');
    check239(!igua_updater_maintenance_present(),'sem manutenção inicial');
    check239(igua_updater_vps_private_path('.code.lock')===$private.'/.code.lock','lock privado de troca');
    try{igua_updater_vps_private_path('../app/updater.php');throw new RuntimeException('deveria falhar');}
    catch(InvalidArgumentException){check239(true,'controle não permitido');}
    $tmp=sys_get_temp_dir().'/igua-atomic-'.bin2hex(random_bytes(5));mkdir($tmp);
    check239(igua_updater_vps_probe_rename($tmp),'grava e renomeia em diretório gravável');
    check239(count(glob($tmp.'/.igua-probe-*')?:[])===0,'sem arquivos temporários restantes');
    file_put_contents($tmp.'/sentinel','original');
    check239(igua_updater_vps_probe_rename($tmp),'segunda prova não modifica arquivos existentes');
    check239(file_get_contents($tmp.'/sentinel')==='original','sentinela preservada');
    unlink($tmp.'/sentinel');rmdir($tmp);
    $mock=sys_get_temp_dir().'/igua-atomic-missing-'.bin2hex(random_bytes(4));
    check239(!igua_updater_vps_probe_rename($mock),'recusa diretório ausente');
    $source=(string)file_get_contents(__DIR__.'/../app/bootstrap.php');
    check239(str_contains($source,"/updater/vps/private/maintenance"),'bootstrap bloqueia portal por marcador privado');
    $docs=(string)file_get_contents(__DIR__.'/../docs/AWS_EC2_DEPLOY.md');
    check239(str_contains($docs,'chown iguanews-deploy:iguanews-deploy "$SITE"'),'documentação corrige ownership da raiz');
    check239(str_contains($docs,'bin/aws-deploy-check.php'),'documenta checagem real');
    $dir=$oldDir;
    $marker=$private.'/maintenance';
    if(file_exists($marker))throw new RuntimeException('Não deve modificar marcador existente neste teste.');
    $canBeWorker=function_exists('posix_geteuid')&&posix_geteuid()!==0&&
        fileowner(IGUA_ROOT)===posix_geteuid()&&fileowner($private)===posix_geteuid();
    if($canBeWorker){
        igua_updater_set_maintenance(str_repeat('a',24));
        check239(igua_updater_maintenance_present(),'worker cria marcador privado');
    }else{
        try{igua_updater_set_maintenance(str_repeat('a',24));throw new RuntimeException('identidade indevida não foi barrada');}
        catch(RuntimeException $e){check239(str_contains($e->getMessage(),'identidade')||str_contains($e->getMessage(),'POSIX'),'acesso de identidade indevida recusado');}
        check239(!igua_updater_maintenance_present(),'identidade indevida não criou marcador');
        file_put_contents($marker,str_repeat('a',24));
        check239(igua_updater_maintenance_present(),'marcador privado detectado');
    }
    igua_updater_clear_maintenance();
    check239(!igua_updater_maintenance_present(),'marcador protegido removido');
    echo "OK: VPS 239 caminhos privados, manutenção, rename, documentação e regressões\n";
}finally{
    if(isset($marker)&&is_file($marker))@unlink($marker);
    if($createdPrivate)@rmdir($private);
    if($created)@rmdir($oldDir);
    if($old===false)putenv('IGUA_DEPLOY_VPS');else putenv('IGUA_DEPLOY_VPS='.$old);
}
