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
if($operation==='diagnose_zip'){
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store, private');
    $file=$_FILES['update_zip']??[];
    $status=(int)($file['error']??UPLOAD_ERR_NO_FILE);
    if($status!==UPLOAD_ERR_OK || !is_uploaded_file((string)($file['tmp_name']??''))){
        $limit=in_array($status,[UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE],true);
        echo json_encode(['ok'=>false,'diagnostic'=>[
           'code'=>$limit?'php_upload_limit':'upload_error',
           'title'=>$limit?'O arquivo excede o limite da hospedagem':'Arquivo não recebido',
           'detail'=>$limit?'O PHP recusou o envio antes de analisar o ZIP.':'Selecione um arquivo ZIP válido e tente diagnosticar novamente.',
           'action'=>$limit?'Peça ao provedor para revisar upload_max_filesize e post_max_size.':'Confira a conexão e o seletor de arquivos.']],JSON_UNESCAPED_UNICODE);exit;
    }
    $result=igua_updater_diagnose_zip((string)$file['tmp_name'],$db);
    echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;
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
    if(igua_updater_vps_mode() && ($me['role']??'')!=='admin')
        throw new RuntimeException('Deploy na VPS é restrito ao administrador.');
    if(igua_updater_vps_mode() && $operation==='upload')
        throw new RuntimeException('A VPS só aceita instalação da release oficial pelo serviço externo. Envio manual desativado.');
    if($operation==='rollback'){
        // Restauração continua exigindo nova autenticação porque pode substituir
        // código funcional por uma versão anterior.
        $pwd=(string)($_POST['confirm_password']??'');
        $st=$db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1 LIMIT 1');
        $st->execute([(int)$me['id']]);
        $hash=$st->fetchColumn();
        if(!is_string($hash)||!password_verify($pwd,$hash))throw new RuntimeException('Confirme sua senha administrativa para restaurar o código.');
        if(igua_updater_vps_mode())
            throw new RuntimeException('Na VPS, a restauração manual do código requer acesso SSH à conta de implantação. Confira docs/AWS_EC2_DEPLOY.md.');
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
    if(igua_updater_vps_mode()){
        if($operation!=='install_remote')throw new RuntimeException('Operação não aceita pela implantação da VPS.');
        $token=(string)($_POST['update_token']??'');
        igua_updater_vps_enqueue('install_remote',(int)$me['id'],$token);
        flash('Solicitação de implantação registrada. Aguarde o serviço externo; acompanhe o progresso na janela de atualizações.');
        redirect('/admin.php?action=updates');
    }
    $token=(string)($_POST['update_token']??'');
    $userId=(int)$me['id'];
    $sessionReleased=false;
    $zip=null;
    try{
        if($operation==='upload'){
            $up=$_FILES['update_zip']??[];
            if(($up['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_uploaded_file((string)($up['tmp_name']??''))||
                (int)($up['size']??0)<100||(int)($up['size']??0)>IGUA_UPDATE_MAX_ZIP)
                throw new RuntimeException('Envie um ZIP de atualização válido com até 32 MB.');
            $zip=(string)$up['tmp_name'];
        }else{
            igua_updater_progress_write($userId,$token,'connecting','Conectando ao repositório do GitHub.');
            // Libera o bloqueio da sessão para que o navegador consulte as etapas reais.
            if(session_status()===PHP_SESSION_ACTIVE){session_write_close();$sessionReleased=true;}
            $release=igua_updater_remote_release(true);
            if(!$release||!$release['newer']||empty($release['url']))throw new RuntimeException('Nenhuma versão nova com pacote disponível no GitHub Releases.');
            igua_updater_progress_write($userId,$token,'download','Baixando o pacote do GitHub; duração ainda desconhecida.');
            $zip=igua_updater_download_release($release['url']);
        }
        $result=igua_updater_install_zip($zip,$db,
            static function(string $stage,string $label)use($userId,$token):void{
                igua_updater_progress_write($userId,$token,$stage,$label);
            });
    }catch(Throwable $e){
        igua_updater_progress_write($userId,$token,'failed','Operação não confirmada. Confira a mensagem e o histórico.');
        throw $e;
    }finally{
        if($operation==='install_remote' && is_string($zip) && is_file($zip))@unlink($zip);
        if($sessionReleased && session_status()!==PHP_SESSION_ACTIVE)session_start();
    }
    igua_security_event('update_installed');
    flash('Portal atualizado para '.$result['version'].'. Backup MySQL: '.$result['backup'].'.');
    redirect('/admin.php?action=updates');
}
throw new RuntimeException('Operação de atualização desconhecida.');
