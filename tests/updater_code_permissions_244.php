<?php
/** Verifica a vistoria sem escrita no servidor real e sem banco. */
declare(strict_types=1);
$root=sys_get_temp_dir().'/igua-perms-244-'.bin2hex(random_bytes(6));
if(!mkdir($root,0700,true))throw new RuntimeException('Sem pasta temporária de teste');
define('IGUA_ROOT',$root);
define('IGUA_PUBLIC',$root.'/public');
define('IGUA_STORAGE',$root.'/storage');
require __DIR__.'/../app/updater.php';
function check244(bool $ok,string $label):void {if(!$ok)throw new RuntimeException('FALHOU: '.$label);echo "OK: $label\n";}
function cleanup244(string $dir):void {
    if(!is_dir($dir)||is_link($dir))return;
    @chmod($dir,0700);
    foreach(scandir($dir)?:[] as $name){
        if($name==='.'||$name==='..')continue;
        $path=$dir.'/'.$name;
        if(is_link($path)||is_file($path)){@chmod($path,0600);@unlink($path);}
        elseif(is_dir($path))cleanup244($path);
    }
    @rmdir($dir);
}
try {
    foreach(['app','public','storage','storage/backups','storage/updater'] as $dir)mkdir($root.'/'.$dir,0755);
    file_put_contents($root.'/VERSION','1.0.143\n');
    file_put_contents($root.'/app/updater.php','<?php // fixture');
    file_put_contents($root.'/public/admin.php','<?php // fixture');
    foreach(['VERSION','app/updater.php','public/admin.php'] as $file)chmod($root.'/'.$file,0444);
    $manifest=['files'=>[['path'=>'VERSION'],['path'=>'app/updater.php'],['path'=>'public/admin.php']]];
    $before=[];
    foreach(['VERSION','app/updater.php','public/admin.php'] as $file)$before[$file]=hash_file('sha256',$root.'/'.$file);
    foreach(['VERSION','app/updater.php','public/admin.php'] as $file)
        check244(igua_updater_code_target_issue($file)===null,'arquivo somente leitura com diretório gravável: '.$file);
    $report=igua_updater_preflight(null,$manifest);
    $codeChecks=array_values(array_filter($report['checks'],static fn($item)=>$item['name']==='Escrita do código'));
    check244(count($codeChecks)===1 && $codeChecks[0]['status']==='ok','preflight admite rename nos três arquivos');
    check244(igua_updater_code_target_issue('app/../storage/config.php')==='caminho não autorizado','traversal bloqueado');
    symlink($root.'/storage',$root.'/app/linked');
    check244(igua_updater_code_target_issue('app/linked/secret.php')==='link simbólico em pasta do código','symlink de pasta bloqueado');
    symlink($root.'/VERSION',$root.'/app/alias.php');
    check244(igua_updater_code_target_issue('app/alias.php')==='arquivo é link simbólico','symlink de arquivo bloqueado');
    chmod($root.'/public',0555);
    if(!is_writable($root.'/public')) {
        check244(igua_updater_code_target_issue('public/admin.php')==='pasta de destino sem escrita/travessia','diretório protegido bloqueado');
        $protected=igua_updater_preflight(null,$manifest);
        $protectedChecks=array_values(array_filter($protected['checks'],static fn($item)=>$item['name']==='Escrita do código'));
        check244($protectedChecks[0]['status']==='falha','vistoria reprova diretório protegido');
    }else echo "INFO: teste de permissão de diretório exige usuário não-root; verificação ignorada neste processo\n";
    chmod($root.'/public',0755);
    chmod($root.'/VERSION',0000);
    if(!is_readable($root.'/VERSION'))check244(igua_updater_code_target_issue('VERSION')==='arquivo não pode ser lido para backup','backup inacessível bloqueado');
    else echo "INFO: verificação de leitura sem privilégios exige usuário não-root\n";
    chmod($root.'/VERSION',0444);
    foreach($before as $file=>$hash)check244(hash_file('sha256',$root.'/'.$file)===$hash,'vistoria não alterou '.$file);
    echo "OK: permissões de atualização 1.0.144\n";
}finally{cleanup244($root);}
