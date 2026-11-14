<?php
declare(strict_types=1);
/** Testes isolados de histórico, comparação e integridade do atualizador 1.0.55. */
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function verify155(bool $ok,string $message):void{
    if(!$ok)throw new RuntimeException('FALHOU: '.$message);
    echo "OK: $message\n";
}
function cleanup155(string $dir):void{
    if(!is_dir($dir)||is_link($dir))return;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $item){if($item->isDir()&&!$item->isLink())rmdir($item->getPathname());else unlink($item->getPathname());}
    rmdir($dir);
}
verify155(version_compare(igua_updater_version(),'1.0.60','>='),'versão atual sincronizada');
$base=sys_get_temp_dir().'/igua-updater155-'.bin2hex(random_bytes(8));
mkdir($base,0700);mkdir($base.'/app',0700);mkdir($base.'/public',0700);
try{
    file_put_contents($base.'/VERSION',"1.0.55\n");
    file_put_contents($base.'/app/updater.php','integro');
    file_put_contents($base.'/public/admin.php','anterior');
    $files=[
        ['path'=>'VERSION','sha256'=>hash('sha256',"1.0.55\n"),'size'=>7],
        ['path'=>'app/updater.php','sha256'=>hash('sha256','integro'),'size'=>7],
        ['path'=>'public/admin.php','sha256'=>hash('sha256','novo'),'size'=>4],
        ['path'=>'public/nova.php','sha256'=>hash('sha256','nova'),'size'=>4],
        ['path'=>'app/../storage/config.php','sha256'=>hash('sha256','x'),'size'=>1]
    ];
    $report=igua_updater_file_changes(['files'=>$files],$base);
    verify155($report['counts']===['new'=>1,'changed'=>1,'identical'=>2,'unsafe'=>1],'prévia diferencia novo, alterado, idêntico e inseguro');
    verify155($report['total']===5 && count($report['examples'])===3,'prévia resume arquivos sem copiar conteúdos');
    $good=['id'=>str_repeat('a',24),'from'=>'1.0.54','to'=>'1.0.55','status'=>'complete','at'=>'2026-11-06T10:00:00-03:00',
      'files'=>[['path'=>'VERSION','new_sha256'=>hash('sha256',"1.0.55\n")],['path'=>'app/updater.php','new_sha256'=>hash('sha256','integro')]]];
    $integrity=igua_updater_installed_integrity($good,$base);
    verify155($integrity['available'] && $integrity['status']==='ok' && $integrity['verified']===2,'integridade confirmada por SHA-256');
    file_put_contents($base.'/app/updater.php','adulterado');
    $integrity=igua_updater_installed_integrity($good,$base);
    verify155($integrity['status']==='changed' && count($integrity['issues'])===1 && $integrity['issues'][0]['status']==='changed','integridade detecta arquivo divergente');
    unlink($base.'/app/updater.php');
    $integrity=igua_updater_installed_integrity($good,$base);
    verify155($integrity['issues'][0]['status']==='missing','integridade detecta arquivo ausente');
    verify155(igua_updater_installed_integrity(array_merge($good,['status'=>'rolled_back']),$base)['available']===false,'rollback não é declarado instalação íntegra');
    verify155(igua_updater_installed_integrity(array_merge($good,['to'=>'1.0.56']),$base)['status']==='version_mismatch','versão alterada fora do mecanismo não recebe verificação falsa');
    $jobs=$base.'/jobs';mkdir($jobs,0700);
    foreach([1=>'complete',2=>'rolled_back',3=>'recovery_needed'] as $n=>$status){
        $id=str_repeat(dechex($n),24);mkdir($jobs.'/'.$id,0700);
        $job=['id'=>$id,'from'=>'1.0.'.(51+$n),'to'=>'1.0.'.(52+$n),'status'=>$status,'at'=>'2026-11-06T10:00:0'.$n.'-03:00'];
        file_put_contents($jobs.'/'.$id.'/state.json',json_encode($job));
        touch($jobs.'/'.$id.'/state.json',time()-100+$n);
    }
    $history=igua_updater_history(2,$jobs);
    verify155(count($history)===2 && $history[0]['to']==='1.0.55' && $history[1]['to']==='1.0.54','histórico retorna apenas as duas operações mais recentes');
    file_put_contents($jobs.'/'.str_repeat('3',24).'/state.json','{json corrompido');
    $history=igua_updater_history(10,$jobs);
    verify155(count($history)===2,'histórico ignora journal corrompido');
    verify155(igua_updater_history(3,$base.'/nao-existe')===[],'histórico ausente é vazio sem criar diretórios');
    $controller=file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
    $view=file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');
    verify155(str_contains($controller,"'changes'=>\$result['changes']"),'prévia redirecionada com resumo de diferenças');
    verify155(str_contains($view,'igua_updater_installed_integrity') && str_contains($view,'igua_updater_history(6)'),'nova interface usa integridade e histórico');
    verify155(substr_count($view,'name="confirm_password"')===3 && substr_count($view,'name="confirm_install"')===2,'confirmação e senha separadas, sem regressão');
} finally {cleanup155($base);}
echo "Auditoria local concluída. Banco e hospedagem reais ainda exigem teste.\n";
