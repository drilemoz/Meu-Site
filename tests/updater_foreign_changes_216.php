<?php
declare(strict_types=1);
/**
 * Teste adversarial: rollback não pode sobrescrever mudanças externas durante
 * instalação incompleta. Usa somente arquivos efêmeros, sem banco ou Plesk.
 */
$root = sys_get_temp_dir().'/igua-updater216-'.bin2hex(random_bytes(8));
if (!mkdir($root,0700,true) || !mkdir($root.'/storage',0700) || !mkdir($root.'/app',0700) || !mkdir($root.'/public',0700)) {
    throw new RuntimeException('Não foi possível preparar a área de testes.');
}
define('IGUA_ROOT',$root);
define('IGUA_STORAGE',$root.'/storage');
require __DIR__.'/../app/updater.php';
function assert216(bool $condition,string $description): void {
    if (!$condition) throw new RuntimeException('FALHOU: '.$description);
    echo 'OK: '.$description."\n";
}
function clean216(string $path): void {
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $item){
        if($item->isDir()&&!$item->isLink())rmdir($item->getPathname());
        else unlink($item->getPathname());
    }
    rmdir($path);
}
function job216(string $name): array {
    $job = igua_updater_folder().'/jobs/'.substr(hash('sha256',$name),0,24);
    if(!mkdir($job.'/new/app',0700,true)||!mkdir($job.'/new/public',0700,true))throw new RuntimeException('Falha ao preparar job.');
    $old = ['VERSION'=>"1.0.115\n",'app/demo.php'=>"<?php // original\n",'public/style.css'=>'/* original */'];
    $new = ['VERSION'=>"1.0.116\n",'app/demo.php'=>"<?php // novo\n",'public/style.css'=>'/* novo */','public/new.js'=>'// novo'];
    @unlink(IGUA_ROOT.'/public/new.js');
    foreach($old as $path=>$body)file_put_contents(IGUA_ROOT.'/'.$path,$body);
    foreach($new as $path=>$body)file_put_contents($job.'/new/'.$path,$body);
    $manifest=['files'=>[]];
    foreach($new as $path=>$body)$manifest['files'][]=['path'=>$path,'sha256'=>hash('sha256',$body),'size'=>strlen($body)];
    $entries=igua_updater_backup_code($manifest,$job);
    return [$job,['id'=>basename($job),'status'=>'applying','files'=>$entries],$old,$new];
}
function rejectsWithoutWrite216(string $job,array $journal,array $unchanged,string $label): void {
    $message='';
    try{igua_updater_rollback($job,$journal);}
    catch(RuntimeException $e){$message=$e->getMessage();}
    assert216(str_contains($message,'modificados fora da atualização'),$label.': edição externa bloqueada');
    foreach($unchanged as $path=>$body){
        assert216(file_get_contents(IGUA_ROOT.'/'.$path)===$body,$label.': '.$path.' não foi sobrescrito');
    }
}
try {
    [$job,$journal,$old,$new]=job216('foreign-applying');
    igua_updater_apply_code($journal['files'],$job);
    file_put_contents(IGUA_ROOT.'/app/demo.php','// alterado manualmente');
    rejectsWithoutWrite216($job,$journal,['app/demo.php'=>'// alterado manualmente','public/style.css'=>$new['public/style.css'],'VERSION'=>$new['VERSION']],'aplicando');

    [$job,$journal,$old,$new]=job216('foreign-prepared');
    $journal['status']='prepared';
    file_put_contents(IGUA_ROOT.'/app/demo.php','// edição anterior à aplicação');
    rejectsWithoutWrite216($job,$journal,['app/demo.php'=>'// edição anterior à aplicação','public/style.css'=>$old['public/style.css']],'preparado');

    [$job,$journal,$old,$new]=job216('foreign-new-file');
    igua_updater_apply_code($journal['files'],$job);
    file_put_contents(IGUA_ROOT.'/public/new.js','// arquivo trocado após falha');
    rejectsWithoutWrite216($job,$journal,['public/new.js'=>'// arquivo trocado após falha','app/demo.php'=>$new['app/demo.php']],'arquivo novo');

    [$job,$journal,$old,$new]=job216('safe-partial-recovery');
    igua_updater_apply_code($journal['files'],$job);
    // Estado parcial legítimo: algumas cópias já restauradas, outras novas.
    file_put_contents(IGUA_ROOT.'/app/demo.php',$old['app/demo.php']);
    @unlink(IGUA_ROOT.'/public/new.js');
    $journal['status']='recovery_needed';
    igua_updater_rollback($job,$journal);
    foreach($old as $path=>$body)assert216(file_get_contents(IGUA_ROOT.'/'.$path)===$body,'retomada legítima: '.$path);
    assert216(!file_exists(IGUA_ROOT.'/public/new.js'),'retomada legítima remove novo arquivo');
} finally {
    clean216($root);
}
echo "Validação somente em arquivos isolados: nenhuma atualização no Plesk.\n";
