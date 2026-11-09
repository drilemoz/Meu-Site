<?php
declare(strict_types=1);
$base=sys_get_temp_dir().'/igua-backup-ui-'.bin2hex(random_bytes(5));
mkdir($base.'/backups',0700,true);
define('IGUA_STORAGE',$base);
require_once __DIR__.'/../app/backup.php';
function h(mixed $value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function admin_link(string $action):string{return '/admin.php?action='.$action;}
function csrf_field():string{return '<input type="hidden" name="csrf" value="token-de-teste">';}
function test230(bool $ok,string $description):void{static $num=0;$num++;if(!$ok)throw new RuntimeException("Falhou ($num): $description");}
function expect_reject230(callable $test,string $description):void{try{$test();}catch(InvalidArgumentException|RuntimeException $e){return;}throw new RuntimeException('Não rejeitou: '.$description);}
try{
    $created=[];
    foreach(['daily'=>5,'weekly'=>3,'manual'=>2,'safety'=>1] as $kind=>$count){
        for($i=0;$i<$count;$i++){
            $filename=sprintf('%s-20261108-%06d-%06x.json',$kind,120000+$i,$i+1);
            $file=$base.'/backups/'.$filename;file_put_contents($file,'{"teste":true}');touch($file,time()-$i*100);$created[]=$filename;
        }
    }
    test230(igua_backup_policy()===['daily'=>3,'weekly'=>2],'padrão 3 diários e 2 semanais');
    $all=list_backups();test230(count($all)===11,'todos os backups válidos listados');
    $plan=igua_backup_prune_plan();test230(count($plan)===3,'plano não inclui manual/preventivo');
    foreach($plan as $name)test230(str_starts_with($name,'daily-')||str_starts_with($name,'weekly-'),'somente automatizados para limpeza');
    $pinned=$plan[0];igua_backup_pin($pinned,true);
    test230(in_array($pinned,igua_backup_pins(),true),'proteção persiste');
    test230(!in_array($pinned,igua_backup_prune_plan(),true),'proteção fora da limpeza');
    expect_reject230(fn()=>igua_backup_delete($pinned),'não permite apagar protegido');
    igua_backup_policy_save(2,1);test230(igua_backup_policy()===['daily'=>2,'weekly'=>1],'limites alteráveis');
    expect_reject230(fn()=>igua_backup_policy_save(0,99),'recusa limites inseguros');
    rotate_backups();
    test230(is_file($base.'/backups/'.$pinned),'protegido mantido após rotação');
    foreach($created as $f){if(str_starts_with($f,'manual-')||str_starts_with($f,'safety-'))test230(is_file($base.'/backups/'.$f),'manual/preventivo mantido');}
    igua_backup_pin($pinned,false);test230(!in_array($pinned,igua_backup_pins(),true),'desproteção registrada');
    igua_backup_delete($pinned);test230(!file_exists($base.'/backups/'.$pinned),'exclusão explícita executada');
    expect_reject230(fn()=>igua_backup_delete('../../config.php'),'bloqueio de travessia');
    $ui=file_get_contents(__DIR__.'/../app/admin_views/routes/backups.php');
    test230(!str_contains($ui,'</div> ?>') && str_contains($ui,'backup-file-list'),'erro visual solto removido e lista compacta');
    $_GET=[];ob_start();require __DIR__.'/../app/admin_views/routes/backups.php';$html=ob_get_clean();
    test230(str_contains($html,'Backups e migração')&&str_contains($html,'Retenção e limpeza')&&str_contains($html,'Ver todos'),'página de backups renderiza');
    test230(!str_contains($html,'?>'),'PHP não escapa para o HTML');
    test230(substr_count($html,'<details')===substr_count($html,'</details>'),'elementos details balanceados');
    $_GET=['all'=>'1'];ob_start();require __DIR__.'/../app/admin_views/routes/backups.php';$allHtml=ob_get_clean();
    test230(str_contains($allHtml,'Nome do arquivo'),'listagem expandida disponível');
    ob_start();require __DIR__.'/../app/admin_views/routes/backup_restore.php';$restore=ob_get_clean();
    test230(str_contains($restore,'restore_backup')&&str_contains((string)file_get_contents(__DIR__.'/../app/admin_views/routes/backup_restore.php'),'restore_bundle')&&str_contains($restore,'name="confirm_password"'),'restauração em página própria protegida');
    test230(substr_count($restore,'<details')===substr_count($restore,'</details>'),'restauração balanceada');
    // Metadados corrompidos não podem desproteger backups automaticamente.
    file_put_contents($base.'/backups/.protected.json','{corrompido');
    expect_reject230(fn()=>igua_backup_prune_plan(),'proteção corrompida bloqueia rotação');
    unlink($base.'/backups/.protected.json');
    file_put_contents($base.'/backups/.policy.json','{corrompido');
    expect_reject230(fn()=>igua_backup_prune_plan(),'retenção corrompida bloqueia rotação');
    unlink($base.'/backups/.policy.json');
    $admin=file_get_contents(__DIR__.'/../public/admin.php');
    foreach(['backup_restore','backup_policy_save','backup_pin','backup_prune','backup_delete'] as $token)test230(str_contains($admin,"'".$token."'"),'ação permitida: '.$token);
    echo "OK backups_center_230: retenção, exclusão segura, proteção e páginas renderizadas.\n";
}finally{
    foreach(new FilesystemIterator($base.'/backups') as $file){if($file->isFile()||$file->isLink())unlink($file->getPathname());}
    rmdir($base.'/backups');rmdir($base);
}
