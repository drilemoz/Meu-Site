<?php
declare(strict_types=1);
/** Central única de configurações externas: reutiliza settings já empregados por GA4 e AdSense. */
require_once __DIR__.'/analytics.php';
require_once __DIR__.'/editorial.php';
require_once __DIR__.'/ads.php';
require_once __DIR__.'/adsense_review.php';
require_once __DIR__.'/page_inspector.php';
require_once __DIR__.'/ga4_realtime.php';
require_once __DIR__.'/search_console_dns.php';
require_once __DIR__.'/search_console_api.php';
require_once __DIR__.'/search_console_inspection.php';
require_once __DIR__.'/pagespeed_insights.php';

/** Token do Search Console, nunca HTML bruto. A metatag é visível publicamente por definição. */
function igua_google_verification_token(string $value): string {
    $value=trim($value);
    if($value==='')return '';
    if(strlen($value)>200 || !preg_match('~^[A-Za-z0-9_+\-/=]{8,200}$~D',$value))
        throw new InvalidArgumentException('Cole somente o código de verificação do Google, sem a tag HTML.');
    return $value;
}
function igua_google_verification_meta(string $value): string {
    try {$token=igua_google_verification_token($value);}
    catch(InvalidArgumentException){return '';}
    return $token!==''?'<meta name="google-site-verification" content="'.h($token).'">':'';
}
function igua_integrations_save(PDO $db,string $action,array $post): void {
    $data=[];
    if($action==='integrations_save_ga4'){
        if(!is_string($post['ga4_measurement_id']??''))throw new InvalidArgumentException('ID GA4 inválido.');
        $id=trim($post['ga4_measurement_id']??'');
        $id=analytics_ga4_measurement_id($id);
        if($id==='' && trim((string)($post['ga4_measurement_id']??''))!=='')throw new InvalidArgumentException('ID GA4 inválido. Use o formato G-XXXXXXXXXX.');
        if(isset($post['ga4_enabled']) && $id==='')throw new InvalidArgumentException('Informe o ID antes de ativar o Google Analytics.');
        $data=['ga4_measurement_id'=>$id,'ga4_enabled'=>isset($post['ga4_enabled'])?'1':'0'];
        if(array_key_exists('ga4_property_id',$post)){
            if(!is_string($post['ga4_property_id']))throw new InvalidArgumentException('ID numérico da propriedade inválido.');
            $data['ga4_property_id']=igua_ga4_property_id($post['ga4_property_id']);
        }
    }elseif($action==='integrations_save_adsense'){
        $publisher=$post['adsense_publisher']??'';
        if(!is_string($publisher))throw new InvalidArgumentException('ID AdSense inválido.');
        $data=['adsense_publisher'=>ad_validate_publisher($publisher)];
        // Não alterar ads_enabled: a ativação comercial continua no módulo Publicidade.
    }elseif($action==='integrations_save_verification'){
        $token=$post['google_site_verification']??'';
        if(!is_string($token))throw new InvalidArgumentException('Token de verificação inválido.');
        $data=['google_site_verification'=>igua_google_verification_token($token)];
    }elseif($action==='integrations_save_search_console_mode'){
        $mode=$post['search_console_property_mode']??null;
        if(!is_string($mode))throw new InvalidArgumentException('Selecione o tipo de propriedade.');
        $data=['search_console_property_mode'=>igua_search_console_property_mode($mode)];
    }elseif($action==='integrations_save_dns'){
        $token=$post['google_dns_verification_txt']??null;
        if(!is_string($token))throw new InvalidArgumentException('TXT DNS inválido.');
        $data=['google_dns_verification_txt'=>igua_search_dns_expected($token)];
    }else throw new InvalidArgumentException('Operação de integração desconhecida.');
    $db->beginTransaction();
    try {
        $stmt=$db->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
        foreach($data as $name=>$value)$stmt->execute([$name,$value]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
/** Checagens locais e somente leitura. Não executam requisições externas nem validam aprovação Google. */
function igua_integrations_diagnostic(array $cfg,string $publicRoot,string $adsTxt=''): array {
    $rows=[];
    $add=static function(string $name,bool $ok,string $detail,string $group)use(&$rows): void {
        $rows[]=['name'=>$name,'ok'=>$ok,'detail'=>$detail,'group'=>$group];
    };
    $site='';
    try{$site=safe_seo_url((string)($cfg['seo_site_url']??''));}catch(Throwable){}
    $add('URL oficial HTTPS',$site!=='','Endereço canônico HTTPS configurado em Geral e SEO.','site');
    $add('Sitemap do portal',is_file(rtrim($publicRoot,'/').'/sitemap.php'),'Arquivo sitemap.php encontrado no projeto (não comprova acesso pela internet).','site');
    $add('Indexação habilitada',($cfg['seo_index']??'0')==='1','Estado local do robots. Não garante indexação em buscadores.','site');
    $ga=(string)($cfg['ga4_measurement_id']??'');
    $add('GA4 configurado e ativado',($cfg['ga4_enabled']??'0')==='1' && $ga!=='' && analytics_ga4_measurement_id($ga)!=='','A tag externa depende de consentimento do visitante; recebimento não testado.','google');
    $pub=(string)($cfg['adsense_publisher']??'');
    $add('ID do AdSense válido',preg_match('/^ca-pub-\d{16}$/D',$pub)===1,'ID local válido não significa conta aprovada.','google');
    $add('ads.txt corresponde à conta',igua_adsense_ads_txt_has_publisher($adsTxt,$pub),'Verificação do arquivo local; consulte /ads.txt na hospedagem.','google');
    $token=(string)($cfg['google_site_verification']??'');
    try{$valid=$token!=='' && igua_google_verification_token($token)!=='';}catch(Throwable){$valid=false;}
    $add('Metatag Search Console',$valid,'Token pronto no HTML público; confirme a propriedade no Search Console.','google');
    return $rows;
}
function igua_integrations_admin_view(PDO $db): void {
    $cfg=[];
    foreach(['seo_site_url','seo_index','ga4_measurement_id','ga4_enabled','adsense_publisher','ads_enabled','google_site_verification','ga4_property_id','google_dns_verification_txt','search_console_property_mode'] as $key)
        $cfg[$key]=setting($key, in_array($key,['seo_index','ga4_enabled','ads_enabled'],true)?'0':'');
    $path=IGUA_PUBLIC.'/ads.txt';
    $adsTxt=is_file($path) && !is_link($path)?(string)@file_get_contents($path):'';
    $rows=igua_integrations_diagnostic($cfg,IGUA_PUBLIC,$adsTxt);
    $dnsDomain=igua_search_dns_domain($cfg);
    $dnsLast=igua_search_dns_last_check($cfg,$_SESSION);
    $searchConsoleProps=igua_search_console_properties($cfg);
    $searchConsoleMode=in_array($cfg['search_console_property_mode'],['domain','prefix'],true)?$cfg['search_console_property_mode']:'domain';
    $searchApiLast=igua_search_console_last($cfg,$_SESSION);
    $choice=igua_page_inspect_selector($_GET['page']??null);
    $options=igua_page_inspect_options($db);
    $allowed=array_column($options,'value');
    if(!in_array($choice,$allowed,true)){
        // Link vindo do editor: notícias antigas também podem ser inspecionadas.
        $selected=igua_page_inspect_subject($db,$choice);
        if($selected['kind']==='article')$options[]=['value'=>$choice,'label'=>'Notícia · '.$selected['title']];
        else $choice='home';
    }
    $subject=igua_page_inspect_subject($db,$choice);
    $inspectCfg=$cfg;
    $inspectCfg['seo_pretty_urls']=setting('seo_pretty_urls','0');
    $inspectCfg['has_demo']=(bool)$db->query("SELECT 1 FROM articles WHERE is_demo=1 AND status='published' LIMIT 1")->fetchColumn();
    $report=igua_page_inspect_report($subject,$inspectCfg,IGUA_PUBLIC);
    $inspectionTarget=null;
    try{$inspectionTarget=igua_search_inspection_target($subject,$inspectCfg);}catch(InvalidArgumentException){}
    $inspectionLast=$inspectionTarget!==null?igua_search_inspection_last(array_replace($cfg,['search_console_property_mode'=>$searchConsoleMode]),$inspectionTarget,$_SESSION):null;
    $ga4CredentialsReady=false;$ga4ServiceEmail='';
    try{
        $googleService=igua_ga4_read_service_account(igua_ga4_credentials_path());
        $ga4CredentialsReady=$googleService!==null;
        $ga4ServiceEmail=$googleService!==null?(string)$googleService['client_email']:'';
    }catch(Throwable $e){igua_health_log('GA4_CREDENTIAL_UNREADABLE',$e);}
    $pageSpeedUrl=igua_pagespeed_site_url($cfg);
    $pageSpeedResults=[
        'mobile'=>igua_pagespeed_last_check($cfg,$_SESSION,null,'mobile'),
        'desktop'=>igua_pagespeed_last_check($cfg,$_SESSION,null,'desktop'),
    ];
    $pageSpeedLast=$pageSpeedResults['mobile']; // Compatibilidade com visões/testes anteriores.
    $ga4Live=null;
    if(isset($_SESSION['igua_ga4_last_test']) && is_array($_SESSION['igua_ga4_last_test'])){
        $test=$_SESSION['igua_ga4_last_test'];
        if(($test['property_id']??'')===$cfg['ga4_property_id'])$ga4Live=$test;
    }
    $ga4Audience=igua_ga4_audience_last($_SESSION,$cfg['ga4_property_id']);
    require __DIR__.'/admin_views/routes/integrations.php';
}
