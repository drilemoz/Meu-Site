<?php
declare(strict_types=1);
/**
 * Resumo privado de operações da Central de Integrações.
 * Somente sessão, sem dados de API, tokens, URL de retorno ou mensagens de exceção.
 * Não dispara qualquer consulta externa ao abrir o painel.
 */
function igua_int_diag_service(string $action, string $strategy='mobile'): ?string {
    return match($action){
        'integrations_ga4_test','integrations_ga4_report'=>'ga4',
        'integrations_adsense_report','integrations_adsense_connect','integrations_adsense_callback'=>'adsense',
        'integrations_search_console_test'=>'search',
        'integrations_search_console_inspect'=>'indexing',
        'integrations_check_dns'=>'dns',
        'integrations_pagespeed_test'=>$strategy==='desktop'?'pagespeed_desktop':'pagespeed_mobile',
        default=>null,
    };
}
function igua_int_diag_reason(Throwable $error): string {
    $code=(int)$error->getCode();
    $message=strtolower($error->getMessage());
    // Apenas classificação: nunca propagar texto arbitrário de API (que pode conter tokens/URL).
    if($code===429 || preg_match('/(?:\b429\b|quota|rate.?limit|muitas requisi|aguarde \d+|intervalo de consulta)/i',$message))return 'limit';
    if($code===401 || preg_match('/(?:\b401\b|invalid_grant|invalid_token|token expir|autentica|credencial inv[aá]lid|oauth recusad)/i',$message))return 'auth';
    if($code===403 || preg_match('/(?:\b403\b|forbidden|permission|permiss[aã]o|n[aã]o autorizad|sem acesso|acesso negad)/i',$message))return 'permission';
    if($code>=500 && $code<=599 || preg_match('/(?:timeout|timed out|curl|ssl|tls|conex[aã]o|indispon[ií]vel|servidor externo|rede)/i',$message))return 'connection';
    if($error instanceof InvalidArgumentException || preg_match('/(?:configure|configura|propriedade|conta de servi[cç]o|n[aã]o cadastrad|sem credencial|salve primeiro)/i',$message))return 'setup';
    return 'unknown';
}
function igua_int_diag_event(array &$session, string $service, string $status, string $reason='', ?int $now=null): void {
    $allowed=['ga4','adsense','search','indexing','dns','pagespeed_mobile','pagespeed_desktop'];
    if(!in_array($service,$allowed,true) || !in_array($status,['success','error'],true))return;
    $reasons=['auth','permission','limit','connection','setup','unknown'];
    $event=['service'=>$service,'status'=>$status,
        'reason'=>$status==='error' && in_array($reason,$reasons,true)?$reason:'',
        'at'=>$now??time()];
    $history=$session['igua_int_diag_history']??[];
    if(!is_array($history))$history=[];
    array_unshift($history,$event);
    $session['igua_int_diag_history']=array_slice($history,0,12);
    $latest=$session['igua_int_diag_latest']??[];
    if(!is_array($latest))$latest=[];
    $latest[$service]=$event;
    $session['igua_int_diag_latest']=$latest;
}
function igua_int_diag_failure(array &$session, string $action, Throwable $error, string $strategy='mobile'): void {
    $service=igua_int_diag_service($action,$strategy);
    if($service!==null)igua_int_diag_event($session,$service,'error',igua_int_diag_reason($error));
}
/** Estado local ≠ conexão testada. Cada ficha distingue pré-requisito, resultado e hora. */
function igua_int_diag_summary(array $cfg,array $session,bool $ga4CredentialsReady,bool $adsenseConnected,string $pageSpeedUrl): array {
    $property=(string)($cfg['ga4_property_id']??'');
    $gaId=(string)($cfg['ga4_measurement_id']??'');
    $publisher=(string)($cfg['adsense_publisher']??'');
    $site=(string)($cfg['seo_site_url']??'');
    $searchUrl='';
    try{$searchUrl=safe_seo_url($site);}catch(Throwable){}
    $services=[
        'ga4'=>['name'=>'Google Analytics 4','anchor'=>'google-ga4','ready'=>$ga4CredentialsReady && preg_match('/^[0-9]{4,20}$/D',$property)===1,
            'hint'=>'Configure propriedade numérica, conta de serviço e permissão de Leitor no GA4. O ID G- do site é independente da autorização à API.'],
        'adsense'=>['name'=>'Google AdSense','anchor'=>'google-adsense','ready'=>$adsenseConnected && preg_match('/^ca-pub-[0-9]{16}$/D',$publisher)===1,
            'hint'=>'Configure o ID ca-pub, conecte a conta com OAuth de leitura e confira a permissão. Não basta cadastrar o ID.'],
        'search'=>['name'=>'Search Console · desempenho','anchor'=>'google-search','ready'=>$ga4CredentialsReady && $searchUrl!=='',
            'hint'=>'Habilite a API, autorize a conta de serviço na propriedade correta e escolha domínio ou prefixo HTTPS.'],
        'indexing'=>['name'=>'Search Console · indexação','anchor'=>'paginas','ready'=>$ga4CredentialsReady && $searchUrl!=='' && ($cfg['seo_index']??'0')==='1',
            'hint'=>'Escolha uma notícia real publicada e autorize a conta de serviço no Search Console. Não é teste ao vivo.'],
        'pagespeed_mobile'=>['name'=>'PageSpeed · celular','anchor'=>'google-pagespeed','ready'=>$pageSpeedUrl!=='',
            'hint'=>'Configure o domínio HTTPS oficial em Geral e SEO. A análise é de laboratório.'],
        'pagespeed_desktop'=>['name'=>'PageSpeed · computador','anchor'=>'google-pagespeed','ready'=>$pageSpeedUrl!=='',
            'hint'=>'Configure o domínio HTTPS oficial em Geral e SEO. A análise é de laboratório.'],
        'dns'=>['name'=>'Search Console · DNS','anchor'=>'google-search','ready'=>trim((string)($cfg['google_dns_verification_txt']??''))!=='' && $searchUrl!=='',
            'hint'=>'Cadastre o TXT de verificação no domínio. Encontrar um TXT não confirma a propriedade na sua conta Google.'],
    ];
    $latest=$session['igua_int_diag_latest']??[];
    if(!is_array($latest))$latest=[];
    foreach($services as $key=>&$service){
        $event=$latest[$key]??null;
        if(!is_array($event) || !in_array($event['status']??'', ['success','error'],true) || !is_int($event['at']??null) || $event['at']<=0){
            $event=null;
        }
        $service['service']=$key;
        $service['status']=$event['status']??'untested';
        $service['reason']=$event['reason']??'';
        $service['checked_at']=$event['at']??null;
    }
    unset($service);
    return $services;
}
function igua_int_diag_status_label(string $status,string $reason=''):string {
    if($status==='success')return 'Consulta concluída';
    if($status==='error')return 'Última consulta falhou';
    return 'Ainda não consultado';
}
function igua_int_diag_error_tip(string $reason):string {
    return match($reason){
        'auth'=>'Revise a autorização OAuth ou a conta de serviço. Tokens inválidos ou expirados podem exigir reconexão.',
        'permission'=>'Confirme o acesso da conta à propriedade/conta e as APIs habilitadas no Google Cloud.',
        'limit'=>'Aguarde o intervalo indicado e consulte novamente; não repita envios em sequência.',
        'connection'=>'Verifique HTTPS, cURL, certificados TLS, saída de rede e disponibilidade do provedor.',
        'setup'=>'Confira os IDs, o domínio oficial e as credenciais na seção correspondente.',
        default=>'Confira a mensagem exibida na operação e o diagnóstico da Central de Saúde, sem compartilhar tokens.',
    };
}
