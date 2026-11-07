<?php
// Acesso ocorre após require_admin(), require_ability() e check_csrf() no roteador central.
require_ability('manage_settings',$me);
if($action==='integrations_pagespeed_test'){
    $strategy=$_POST['pagespeed_strategy']??'mobile'; // Formulários antigos permanecem mobile.
    if(!is_string($strategy))throw new InvalidArgumentException('Estratégia PageSpeed inválida.');
    $strategy=igua_pagespeed_strategy($strategy);
    $cfg=['seo_site_url'=>setting('seo_site_url','')];
    $target=igua_pagespeed_site_url($cfg);
    if($target==='')throw new InvalidArgumentException('Configure primeiro uma URL oficial HTTPS pública em Geral e SEO.');
    $last=igua_pagespeed_last_check($cfg,$_SESSION,null,$strategy);
    if($last!==null && time()-(int)$last['checked_at']<180){
        flash('Exibindo análise recente. Aguarde 3 minutos para repetir a consulta deste dispositivo.');
        redirect('/admin.php?action=integrations#google-pagespeed');
    }
    $attempts=$_SESSION['igua_pagespeed_attempts']??[];
    if(!is_array($attempts))$attempts=[];
    $attempt=$attempts[$strategy]??null;
    if(is_array($attempt) && ($attempt['url']??'')===$target && time()-(int)($attempt['time']??0)<90)
        throw new RuntimeException('Aguarde 90 segundos entre consultas do mesmo dispositivo.');
    // Uma análise por vez. Um resultado antigo é preservado em caso de erro de API.
    $attempts[$strategy]=['url'=>$target,'time'=>time()];
    $_SESSION['igua_pagespeed_attempts']=$attempts;
    $_SESSION['igua_pagespeed_results'][$strategy]=igua_pagespeed_check($cfg,null,null,$strategy);
    igua_security_event('admin_action','pagespeed_manual_test_'.$strategy);
    flash('PageSpeed consultado para '.($strategy==='mobile'?'celular':'computador').'. O resultado é de laboratório.');
    redirect('/admin.php?action=integrations#google-pagespeed');
}
if($action==='integrations_search_console_test'){
    $mode=setting('search_console_property_mode','domain');
    $cfg=['seo_site_url'=>setting('seo_site_url',''),'search_console_property_mode'=>$mode];
    if(igua_search_console_property($cfg,$mode)==='')throw new InvalidArgumentException('Configure a URL HTTPS oficial na raiz em Geral e SEO.');
    $last=igua_search_console_last($cfg,$_SESSION);
    if($last!==null && time()-(int)$last['checked_at']<180){
        flash('Exibindo consulta recente. Aguarde 3 minutos antes de atualizar o relatório.');
        redirect('/admin.php?action=integrations#google-search');
    }
    $attempt=$_SESSION['igua_search_console_api_attempt']??null;
    if(is_array($attempt) && ($attempt['property']??'')===igua_search_console_property($cfg,$mode)
        && time()-(int)($attempt['time']??0)<90)
        throw new RuntimeException('Aguarde 90 segundos antes de consultar novamente a mesma propriedade.');
    $_SESSION['igua_search_console_api_attempt']=['property'=>igua_search_console_property($cfg,$mode),'time'=>time()];
    $credentials=igua_ga4_read_service_account(igua_ga4_credentials_path());
    if($credentials===null)throw new InvalidArgumentException('Importe primeiro a conta de serviço na seção Google Analytics 4.');
    $_SESSION['igua_search_console_api_last']=igua_search_console_query($credentials,$cfg,$mode);
    igua_security_event('admin_action','search_console_report_test');
    flash('Consulta autorizada ao Search Console concluída. Confira os resultados e o período abaixo.');
    redirect('/admin.php?action=integrations#google-search');
}
if($action==='integrations_search_console_inspect'){
    $raw=$_POST['inspection_page']??null;
    if(!is_string($raw) || !preg_match('/^article:[1-9][0-9]{0,8}$/D',$raw))
        throw new InvalidArgumentException('Selecione uma notícia publicada.');
    $cfg=[
        'seo_site_url'=>setting('seo_site_url',''),
        'seo_index'=>setting('seo_index','0'),
        'seo_pretty_urls'=>setting('seo_pretty_urls','0'),
        'search_console_property_mode'=>setting('search_console_property_mode','domain'),
    ];
    // O ID é resolvido no banco. Nunca aceitar URL enviada no formulário.
    $subject=igua_page_inspect_subject($db,$raw);
    $target=igua_search_inspection_target($subject,$cfg);
    $mode=in_array($cfg['search_console_property_mode'],['domain','prefix'],true)?$cfg['search_console_property_mode']:'domain';
    $cfg['search_console_property_mode']=$mode;
    $last=igua_search_inspection_last($cfg,$target,$_SESSION);
    if($last!==null && time()-(int)$last['checked_at']<180){
        flash('Exibindo a inspeção recente. Aguarde 3 minutos para atualizar.');
        redirect('/admin.php?action=integrations&page='.rawurlencode($raw).'#paginas');
    }
    $attempt=$_SESSION['igua_search_inspection_attempt']??null;
    if(is_array($attempt) && ($attempt['url']??'')===$target['url'] && time()-(int)($attempt['time']??0)<90)
        throw new RuntimeException('Aguarde 90 segundos para repetir a inspeção da notícia.');
    $_SESSION['igua_search_inspection_attempt']=['url'=>$target['url'],'time'=>time()];
    $credentials=igua_ga4_read_service_account(igua_ga4_credentials_path());
    if($credentials===null)throw new InvalidArgumentException('Cadastre a conta de serviço Google na seção GA4 antes de inspecionar notícias.');
    $_SESSION['igua_search_inspection_last']=igua_search_inspection_query($credentials,$cfg,$mode,$target);
    igua_security_event('admin_action','search_console_url_inspection');
    flash('Inspeção concluída. O resultado é do índice conhecido pelo Google, não um teste ao vivo.');
    redirect('/admin.php?action=integrations&page='.rawurlencode($raw).'#paginas');
}
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
if($action==='integrations_ga4_report'){
    $property=igua_ga4_property_id(setting('ga4_property_id',''));
    if($property==='')throw new InvalidArgumentException('Salve o ID numérico da propriedade GA4 para consultar o relatório.');
    $last=igua_ga4_audience_last($_SESSION,$property);
    if($last!==null && time()-(int)$last['checked_at']<180){
        flash('Exibindo relatório recente. Aguarde 3 minutos para consultar novamente.');
        redirect('/admin.php?action=integrations#google-ga4');
    }
    $attempt=$_SESSION['igua_ga4_audience_attempt']??null;
    if(is_array($attempt) && ($attempt['property_id']??'')===$property && time()-(int)($attempt['time']??0)<90)
        throw new RuntimeException('Aguarde 90 segundos antes de repetir o relatório GA4.');
    $_SESSION['igua_ga4_audience_attempt']=['property_id'=>$property,'time'=>time()];
    $credentials=igua_ga4_read_service_account(igua_ga4_credentials_path());
    if($credentials===null)throw new InvalidArgumentException('Importe primeiro a conta de serviço Google em GA4.');
    $_SESSION['igua_ga4_audience_last']=igua_ga4_audience_report($credentials,$property);
    igua_security_event('admin_action','ga4_audience_report');
    flash('Relatório do Google Analytics consultado. Dados agregados da propriedade, últimos 28 dias completos.');
    redirect('/admin.php?action=integrations#google-ga4');
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
        unset($_SESSION['igua_ga4_last_test'],$_SESSION['igua_ga4_audience_last'],$_SESSION['igua_ga4_audience_attempt'],$_SESSION['igua_search_console_api_last'],$_SESSION['igua_search_console_api_attempt']);
        igua_security_event('settings_changed','ga4_credential_updated');
        flash('Conta de serviço salva em armazenamento privado. Ela precisa de acesso de Leitor à propriedade GA4.');
    }elseif($action==='integrations_ga4_remove'){
        if(($_POST['confirm_remove']??'')!=='1')throw new InvalidArgumentException('Confirme a remoção da credencial.');
        igua_ga4_delete_service_account(igua_ga4_credentials_path());
        unset($_SESSION['igua_ga4_last_test'],$_SESSION['igua_ga4_audience_last'],$_SESSION['igua_ga4_audience_attempt'],$_SESSION['igua_search_console_api_last'],$_SESSION['igua_search_console_api_attempt']);
        igua_security_event('settings_changed','ga4_credential_removed');
        flash('Credencial privada removida; rastreamento público não foi alterado.');
    }else {
        $property=igua_ga4_property_id(setting('ga4_property_id',''));
        if($property==='')throw new InvalidArgumentException('Salve o ID numérico da propriedade GA4 antes de testar.');
        $credentials=igua_ga4_read_service_account(igua_ga4_credentials_path());
        if($credentials===null)throw new InvalidArgumentException('Importe o JSON da conta de serviço antes do teste.');
        unset($_SESSION['igua_ga4_last_test'],$_SESSION['igua_ga4_audience_last'],$_SESSION['igua_ga4_audience_attempt'],$_SESSION['igua_search_console_api_last'],$_SESSION['igua_search_console_api_attempt']);
        $_SESSION['igua_ga4_last_test']=igua_ga4_test_receiving($credentials,$property);
        igua_security_event('admin_action','ga4_realtime_test');
        flash('Consulta à Google Analytics Data API concluída. Veja o resultado abaixo.');
    }
    redirect('/admin.php?action=integrations#google-ga4');
}
igua_integrations_save($db,$action,$_POST);
if($action==='integrations_save_ga4')unset($_SESSION['igua_ga4_last_test'],$_SESSION['igua_ga4_audience_last'],$_SESSION['igua_ga4_audience_attempt']);
if($action==='integrations_save_dns')unset($_SESSION['igua_search_dns_last_check']);
if($action==='integrations_save_search_console_mode')unset($_SESSION['igua_search_console_api_last'],$_SESSION['igua_search_console_api_attempt'],$_SESSION['igua_search_inspection_last'],$_SESSION['igua_search_inspection_attempt']);
igua_security_event('settings_changed','integrations');
flash('Integração atualizada. Verifique o diagnóstico local para conferir a configuração.');
redirect('/admin.php?action=integrations'.(in_array($action,['integrations_save_dns','integrations_save_search_console_mode'],true)?'#google-search':''));
