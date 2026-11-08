<?php
declare(strict_types=1);
/**
 * Injeção de falhas entre substituições reais em uma raiz de testes EFÊMERA.
 * Não abre banco, não lê storage do portal e nunca chama install_zip em produção.
 */
$root=sys_get_temp_dir().'/igua-updater215-'.bin2hex(random_bytes(9));
if(!mkdir($root,0700,true)||!mkdir($root.'/storage',0700)||!mkdir($root.'/app',0700)||!mkdir($root.'/public',0700))
    throw new RuntimeException('Falha ao preparar ambiente isolado.');
define('IGUA_ROOT',$root);
define('IGUA_STORAGE',$root.'/storage');
require __DIR__.'/../app/updater.php';
function verify215(bool $ok,string $message): void {
    if(!$ok)throw new RuntimeException('FALHOU: '.$message);
    echo "OK: $message\n";
}
function cleanup215(string $root): void {
    $iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($iterator as $file){
        if($file->isDir()&&!$file->isLink())rmdir($file->getPathname());
        else unlink($file->getPathname());
    }
    rmdir($root);
}
function scenario215(string $name): array {
    $id=substr(hash('sha256',$name),0,24);
    $job=igua_updater_folder().'/jobs/'.$id;
    if(!mkdir($job.'/new/public',0700,true)||!mkdir($job.'/new/app',0700,true))throw new RuntimeException('Cenário não criado.');
    $old=['VERSION'=>"1.0.114\n",'app/demo.php'=>"<?php // original\n",'public/style.css'=>'/* original */'];
    $new=['VERSION'=>"1.0.115\n",'app/demo.php'=>"<?php // novo\n",'public/style.css'=>'/* novo */', 'public/new.js'=>'// novo arquivo'];
    foreach($old as $path=>$body)file_put_contents(IGUA_ROOT.'/'.$path,$body);
    @unlink(IGUA_ROOT.'/public/new.js');
    foreach($new as $path=>$body)file_put_contents($job.'/new/'.$path,$body);
    $manifest=['files'=>[]];
    foreach($new as $path=>$body)$manifest['files'][]=['path'=>$path,'size'=>strlen($body),'sha256'=>hash('sha256',$body)];
    $entries=igua_updater_backup_code($manifest,$job);
    $journal=['id'=>$id,'from'=>'1.0.114','to'=>'1.0.115','status'=>'applying','files'=>$entries];
    return [$job,$journal,$old,$new];
}
function matching215(array $expected):bool {
    foreach($expected as $path=>$body){
        if(!is_file(IGUA_ROOT.'/'.$path)||file_get_contents(IGUA_ROOT.'/'.$path)!==$body)return false;
    }
    return true;
}
try{
    // Injeção após 1, 3 e 4 substituições (incluindo VERSION como última).
    foreach([1,3,4] as $limit){
        [$job,$journal,$old,$new]=scenario215('after-'.$limit);
        $count=0;
        try{
            igua_updater_apply_code($journal['files'],$job,static function(string $path)use(&$count,$limit): void {
                $count++;
                if($count===$limit)throw new RuntimeException('Falha simulada após '.$path);
            });
            throw new RuntimeException('FALHOU: aplicação não injetou falha');
        }catch(RuntimeException $error){
            verify215(str_starts_with($error->getMessage(),'Falha simulada'),'falha injetada após '.$limit.' substituições');
        }
        verify215($count===$limit,'quantidade de arquivos modificados rastreada');
        if($limit!==4)verify215(file_get_contents(IGUA_ROOT.'/VERSION')===$old['VERSION'],'VERSION só é aplicado no final');
        igua_updater_rollback($job,$journal);
        verify215(matching215($old)&&!file_exists(IGUA_ROOT.'/public/new.js'),'rollback restaura arquivos e remove novo após '.$limit);
    }

    // Defeito da lógica antiga: snapshot de VERSION é conferido POR ÚLTIMO.
    // Com o snapshot danificado, rollback deve recusar antes de restaurar qualquer destino.
    [$job,$journal,$old,$new]=scenario215('corrupt-last-snapshot');
    igua_updater_apply_code($journal['files'],$job);
    verify215(matching215($new),'aplicação em arquivos isolados concluída');
    file_put_contents($job.'/old/VERSION','snapshot adulterado');
    try{
        igua_updater_rollback($job,$journal);
        throw new RuntimeException('FALHOU: rollback aceitou snapshot adulterado');
    }catch(RuntimeException $error){
        verify215(str_contains($error->getMessage(),'recuperação ausente ou adulterado'),'snapshot final adulterado é rejeitado');
    }
    verify215(matching215($new),'nenhum destino é alterado antes de validar todas as cópias');

    // Após atualização completa, edição posterior no servidor não pode ser sobrescrita.
    [$job,$journal,$old,$new]=scenario215('manual-edit');
    igua_updater_apply_code($journal['files'],$job);
    $journal['status']='complete';
    file_put_contents(IGUA_ROOT.'/app/demo.php','alteração posterior do proprietário');
    try{
        igua_updater_rollback($job,$journal);
        throw new RuntimeException('FALHOU: código editado não bloqueou restauração');
    }catch(RuntimeException $error){
        verify215(str_contains($error->getMessage(),'modificados após a atualização'),'edição posterior bloqueia rollback completo');
    }
    verify215(file_get_contents(IGUA_ROOT.'/public/style.css')===$new['public/style.css'],'arquivos válidos não são revertidos se há edição posterior');

    // Falha no MEIO do rollback: status recuperável permite retomar sem perder snapshots.
    [$job,$journal,$old,$new]=scenario215('rollback-interrupted');
    igua_updater_apply_code($journal['files'],$job);
    $journal['status']='recovery_needed';
    $restored=0;
    try{
        igua_updater_rollback($job,$journal,static function(string $path)use(&$restored): void {
            $restored++;
            if($restored===2)throw new RuntimeException('Queda simulada no rollback');
        });
        throw new RuntimeException('FALHOU: rollback não foi interrompido');
    }catch(RuntimeException $error){
        verify215($error->getMessage()==='Queda simulada no rollback','falha injetada durante o rollback');
    }
    verify215($restored===2 && !matching215($old),'restauração parcial detectável');
    igua_updater_rollback($job,$journal);
    verify215(matching215($old)&&!file_exists(IGUA_ROOT.'/public/new.js'),'rollback interrompido pode ser retomado');

    // Uma reversão voluntária de instalação completa entra em manutenção e sai com journal coerente.
    [$job,$journal,$old,$new]=scenario215('complete-manual-recovery');
    igua_updater_apply_code($journal['files'],$job);
    $journal['status']='complete';
    igua_updater_write_json($job.'/state.json',$journal);
    igua_updater_write_json(igua_updater_folder().'/last.json',['id'=>$journal['id']]);
    verify215(!is_file(igua_updater_maintenance()),'site não está em manutenção antes de rollback manual completo');
    igua_updater_manual_rollback();
    verify215(matching215($old)&&!file_exists(IGUA_ROOT.'/public/new.js'),'rollback manual de atualização completa funciona');
    verify215(!file_exists(igua_updater_maintenance()) && igua_updater_read_json($job.'/state.json')['status']==='rolled_back',
       'rollback completo encerra manutenção e persiste estado');

    // Recuperação manual real, com journal e marcador em ambiente efêmero.
    [$job,$journal,$old,$new]=scenario215('manual-recovery');
    try{
        igua_updater_apply_code($journal['files'],$job,static function(string $path): void {
            if($path==='public/new.js')throw new RuntimeException('Queda simulada antes de VERSION');
        });
        throw new RuntimeException('FALHOU: interrupção não ocorreu');
    }catch(RuntimeException $error){
        verify215($error->getMessage()==='Queda simulada antes de VERSION','interrupção simulada no cenário de recuperação manual');
    }
    verify215(file_get_contents(IGUA_ROOT.'/VERSION')==="1.0.114\n",'interrupção mantém VERSION antigo');
    igua_updater_write_json($job.'/state.json',$journal);
    igua_updater_write_json(igua_updater_folder().'/last.json',['id'=>$journal['id']]);
    file_put_contents(igua_updater_maintenance(),$journal['id']);
    verify215(is_file(igua_updater_maintenance()),'marcador de manutenção registrado');
    igua_updater_manual_rollback();
    verify215(matching215($old)&&!file_exists(IGUA_ROOT.'/public/new.js'),'recuperação manual restaura estado original');
    verify215(igua_updater_read_json($job.'/state.json')['status']==='rolled_back','estado final do journal é rolled_back');
    verify215(!file_exists(igua_updater_maintenance()),'recuperação retira manutenção');
    // Não declarar sucesso se o marcador não puder ser removido.
    mkdir(igua_updater_maintenance(),0700);
    try{
        igua_updater_clear_maintenance();
        throw new RuntimeException('FALHOU: marcador não removível aceito');
    }catch(RuntimeException $error){
        verify215(str_contains($error->getMessage(),'Falha ao remover'),'limpeza de manutenção informa erro verificável');
    }
    rmdir(igua_updater_maintenance());
}finally{
    cleanup215($root);
}
echo "Testes locais com falhas injetadas concluídos. Não houve MySQL, Plesk, ZIP real nem deploy.\n";
