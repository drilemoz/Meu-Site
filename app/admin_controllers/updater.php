<?php
declare(strict_types=1);
// Executado pelo admin.php somente depois de autenticação, verificação do papel e CSRF.
require_ability('manage_settings',$me);
$operation=(string)($_POST['operation']??'');
if($operation==='source'){
    igua_updater_set_source(trim((string)($_POST['owner']??'')),trim((string)($_POST['repo']??'')));
    flash('Repositório configurado. Confira a origem antes de instalar; pacotes sem assinatura não comprovam autoria.');
    redirect('/admin.php?action=updates');
}
if($operation==='source_reset'){
    igua_updater_reset_source();
    flash('Origem restaurada para o repositório oficial identificado no projeto.');
    redirect('/admin.php?action=updates');
}
if($operation==='check'){
    $release=igua_updater_remote_release(true);
    flash($release ? 'Consulta concluída. Última versão publicada: '.$release['version'] : 'Configure primeiro o repositório de Releases.');
    redirect('/admin.php?action=updates');
}
if($operation==='preview'){
    $up=$_FILES['update_zip']??[];
    if(($up['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file((string)($up['tmp_name']??''))||
       (int)($up['size']??0)<100||(int)($up['size']??0)>IGUA_UPDATE_MAX_ZIP)
       throw new RuntimeException('Envie um ZIP de atualização com até 32 MB para simular.');
    $result=igua_updater_preview_zip((string)$up['tmp_name'],$db);
    $_SESSION['igua_update_preview']=['at'=>time(),'version'=>$result['version'],'files'=>$result['files'],'signature_status'=>$result['signature_status'],'preflight'=>$result['preflight'],'changes'=>$result['changes']];
    flash('Simulação concluída. Nenhum arquivo do portal ou tabela do MySQL foi alterado.');
    redirect('/admin.php?action=updates');
}
if(in_array($operation,['upload','install_remote','rollback'],true)){
    if($operation==='rollback'){
        // Restauração continua exigindo nova autenticação porque pode substituir
        // código funcional por uma versão anterior.
        $pwd=(string)($_POST['confirm_password']??'');
        $st=$db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1 LIMIT 1');
        $st->execute([(int)$me['id']]);
        $hash=$st->fetchColumn();
        if(!is_string($hash)||!password_verify($pwd,$hash))throw new RuntimeException('Confirme sua senha administrativa para restaurar o código.');
        igua_updater_manual_rollback();
        flash('Arquivos restaurados. O banco de dados não foi alterado pelo atualizador.');
        redirect('/admin.php?action=updates');
    }
    // Ausência de assinatura elimina a prova da autoria do ZIP. Exigir
    // reautenticação mesmo em downloads do GitHub, além de sessão/CSRF.
    if(($_POST['confirm_install']??'')!=='yes')
        throw new RuntimeException('Confirme a instalação antes de atualizar o código.');
    $attempts=array_values(array_filter($_SESSION['igua_update_auth_failures']??[],
        static fn($time)=>is_int($time)&&$time>time()-900));
    if(count($attempts)>=5)throw new RuntimeException('Muitas senhas incorretas. Tente novamente em até 15 minutos.');
    $pwd=(string)($_POST['confirm_password']??'');
    $st=$db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1 LIMIT 1');
    $st->execute([(int)$me['id']]);
    $hash=$st->fetchColumn();
    if(!is_string($hash)||!password_verify($pwd,$hash)){
        $attempts[]=time();$_SESSION['igua_update_auth_failures']=$attempts;
        throw new RuntimeException('Informe sua senha administrativa para autorizar a atualização.');
    }
    unset($_SESSION['igua_update_auth_failures']);
    if($operation==='upload'){
        $up=$_FILES['update_zip']??[];
        if(($up['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file((string)($up['tmp_name']??''))||
            (int)($up['size']??0)<100||(int)($up['size']??0)>IGUA_UPDATE_MAX_ZIP)
            throw new RuntimeException('Envie um ZIP de atualização válido com até 32 MB.');
        $zip=(string)$up['tmp_name'];
    }else{
        $release=igua_updater_remote_release(true);
        if(!$release||!$release['newer']||empty($release['url']))throw new RuntimeException('Nenhuma versão nova com pacote disponível no GitHub Releases.');
        $zip=igua_updater_download_release($release['url']);
    }
    try{$result=igua_updater_install_zip($zip,$db);}
    finally{if($operation==='install_remote' && isset($zip) && is_file($zip))@unlink($zip);}
    igua_security_event('update_installed');
    flash('Portal atualizado para '.$result['version'].'. Backup MySQL: '.$result['backup'].'.');
    redirect('/admin.php?action=updates');
}
throw new RuntimeException('Operação de atualização desconhecida.');
