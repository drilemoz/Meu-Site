<?php
// Acesso ocorre após require_admin(), require_ability() e check_csrf() no roteador central.
require_ability('manage_settings',$me);
if($action==='integrations_check_dns'){
    $cfg=['seo_site_url'=>setting('seo_site_url',''),'google_dns_verification_txt'=>setting('google_dns_verification_txt','')];
    $last=igua_search_dns_last_check($cfg,$_SESSION);
    // No máximo uma consulta por domínio/token a cada 15 segundos nesta sessão.
    $result=$last!==null && time()-(int)$last['checked_at']<15
        ?$last:igua_search_dns_check($cfg);
    $result['expected_hash']=hash('sha256',$cfg['google_dns_verification_txt']);
    $_SESSION['igua_search_dns_last_check']=$result;
    igua_security_event('admin_action','search_console_dns_check');
    redirect('/admin.php?action=integrations#google-search');
}
if($action==='integrations_ga4_credentials' || $action==='integrations_ga4_remove' || $action==='integrations_ga4_test'){
    if($action!=='integrations_ga4_test'){
        $password=$_POST['confirm_password']??'';
        if(!is_string($password) || $password==='')throw new RuntimeException('Confirme a senha administrativa.');
        $stmt=$db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1 LIMIT 1');
        $stmt->execute([(int)$me['id']]);
        if(!password_verify($password,(string)$stmt->fetchColumn()))throw new RuntimeException('Senha administrativa incorreta.');
    }
    if($action==='integrations_ga4_credentials'){
        $https=(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off');
        $local=in_array((string)($_SERVER['REMOTE_ADDR']??''),['127.0.0.1','::1'],true);
        if(PHP_SAPI!=='cli' && !$https && !$local)throw new RuntimeException('Importe chaves privadas somente por HTTPS. Confira o TLS da hospedagem.');
        $file=$_FILES['ga4_credentials']??null;
        if(!is_array($file) || (int)($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)
            throw new InvalidArgumentException('Selecione o JSON da conta de serviço.');
        $size=(int)($file['size']??0);$temp=$file['tmp_name']??'';
        if(!is_string($temp) || $size<200 || $size>32768 || !is_uploaded_file($temp))
            throw new InvalidArgumentException('Arquivo JSON inválido ou acima de 32 KiB.');
        $raw=@file_get_contents($temp,false,null,0,32769);
        if(!is_string($raw) || strlen($raw)>32768)throw new InvalidArgumentException('Não foi possível validar o JSON.');
        igua_ga4_store_service_account($raw,igua_ga4_credentials_path());
        unset($_SESSION['igua_ga4_last_test']);
        igua_security_event('settings_changed','ga4_credential_updated');
        flash('Conta de serviço salva em armazenamento privado. Ela precisa de acesso de Leitor à propriedade GA4.');
    }elseif($action==='integrations_ga4_remove'){
        if(($_POST['confirm_remove']??'')!=='1')throw new InvalidArgumentException('Confirme a remoção da credencial.');
        igua_ga4_delete_service_account(igua_ga4_credentials_path());
        unset($_SESSION['igua_ga4_last_test']);
        igua_security_event('settings_changed','ga4_credential_removed');
        flash('Credencial privada removida; rastreamento público não foi alterado.');
    }else {
        $property=igua_ga4_property_id(setting('ga4_property_id',''));
        if($property==='')throw new InvalidArgumentException('Salve o ID numérico da propriedade GA4 antes de testar.');
        $credentials=igua_ga4_read_service_account(igua_ga4_credentials_path());
        if($credentials===null)throw new InvalidArgumentException('Importe o JSON da conta de serviço antes do teste.');
        unset($_SESSION['igua_ga4_last_test']);
        $_SESSION['igua_ga4_last_test']=igua_ga4_test_receiving($credentials,$property);
        igua_security_event('admin_action','ga4_realtime_test');
        flash('Consulta à Google Analytics Data API concluída. Veja o resultado abaixo.');
    }
    redirect('/admin.php?action=integrations#google-ga4');
}
igua_integrations_save($db,$action,$_POST);
if($action==='integrations_save_ga4')unset($_SESSION['igua_ga4_last_test']);
if($action==='integrations_save_dns')unset($_SESSION['igua_search_dns_last_check']);
igua_security_event('settings_changed','integrations');
flash('Integração atualizada. Verifique o diagnóstico local para conferir a configuração.');
redirect('/admin.php?action=integrations'.($action==='integrations_save_dns'?'#google-search':''));
