<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function verify188(bool $ok,string $why): void {if(!$ok)throw new RuntimeException('Diagnóstico 188: '.$why);}
$s=[];$now=1800000000;
verify188(igua_int_diag_service('integrations_ga4_report')==='ga4' && igua_int_diag_service('integrations_pagespeed_test','desktop')==='pagespeed_desktop','serviços por ação');
verify188(igua_int_diag_service('integrations_save_ga4')===null && igua_int_diag_service('updates')===null,'salvar configuração não equivale a testar API');
$secret='https://api.exemplo.test/?code=SEGREDOXYZ&token=privado';
foreach([
 [new RuntimeException('HTTP 401 '.$secret),'auth'],[new RuntimeException('HTTP 403 '.$secret),'permission'],
 [new RuntimeException('quota exceeded '.$secret),'limit'],[new RuntimeException('timeout '.$secret),'connection'],
 [new InvalidArgumentException('Configure a propriedade '.$secret),'setup'],[new RuntimeException('falha '.$secret),'unknown'],
] as [$e,$code]){
    verify188(igua_int_diag_reason($e)===$code,'classificação genérica '.$code);
    igua_int_diag_failure($s,'integrations_ga4_test',$e);
    verify188(!str_contains(json_encode($s,JSON_THROW_ON_ERROR),$secret),'erro nunca guarda mensagem original');
}
igua_int_diag_event($s,'ga4','success','',$now);
verify188($s['igua_int_diag_latest']['ga4']['status']==='success'&&$s['igua_int_diag_latest']['ga4']['at']===$now,'sucesso substitui falha anterior');
igua_int_diag_failure($s,'integrations_search_console_inspect',new RuntimeException('HTTP 403 segredos'), 'mobile');
verify188($s['igua_int_diag_latest']['indexing']['reason']==='permission','inspeção independente de relatório');
igua_int_diag_event($s,'pagespeed_desktop','success','',$now);
verify188(!isset($s['igua_int_diag_latest']['pagespeed_mobile']),'desktop e mobile independentes');
for($i=0;$i<20;$i++)igua_int_diag_event($s,'dns','success','',$now+$i);
verify188(count($s['igua_int_diag_history'])===12,'histórico limitado a 12 operações');
igua_int_diag_event($s,'token_privado','error','permission');
verify188(!isset($s['igua_int_diag_latest']['token_privado']),'serviço não permitido ignorado');
$cfg=['seo_site_url'=>'https://iguatama.online','seo_index'=>'1','ga4_measurement_id'=>'G-TEST1234','ga4_enabled'=>'1',
 'ga4_property_id'=>'123456','adsense_publisher'=>'ca-pub-1234567890123456','google_site_verification'=>'',
 'google_dns_verification_txt'=>'google-site-verification=abc','search_console_property_mode'=>'domain'];
$d=igua_int_diag_summary($cfg,$s,true,true,'https://iguatama.online');
verify188(count($d)===7 && $d['ga4']['status']==='success' && $d['adsense']['status']==='untested','diagnóstico não inventa teste externo');
verify188($d['search']['ready'] && $d['indexing']['ready'] && $d['pagespeed_desktop']['ready'],'pré-requisitos locais independentes de resultado');
$d2=igua_int_diag_summary(array_replace($cfg,['seo_site_url'=>'','seo_index'=>'0','ga4_property_id'=>'','adsense_publisher'=>'']),[],false,false,'');
verify188(!$d2['ga4']['ready'] && !$d2['adsense']['ready'] && !$d2['search']['ready'] && !$d2['indexing']['ready'] && !$d2['pagespeed_mobile']['ready'] && !$d2['dns']['ready'],'sem configuração não afirma conexão');
verify188(igua_int_diag_status_label('success')==='Consulta concluída' && str_contains(igua_int_diag_error_tip('permission'),'acesso'),'mensagens condicionais');
$controller=(string)file_get_contents(__DIR__.'/../app/admin_controllers/integrations.php');
$admin=(string)file_get_contents(__DIR__.'/../public/admin.php');
verify188(substr_count($controller,'igua_int_diag_event($_SESSION')>=7,'consultas bem-sucedidas registradas');
verify188(str_contains($admin,'igua_int_diag_failure($_SESSION,$action,$e') && str_contains($admin,"'integrations_adsense_callback'"),'falhas do POST e OAuth registradas');
verify188(str_contains($admin,'check_csrf();') && str_contains($admin,"'integrations'=>'manage_settings'"),'CSRF e ACL preservados');
if(!function_exists('admin_link')){function admin_link(string $action):string{return '/admin.php?action='.$action;}}
$rows=igua_integrations_diagnostic($cfg,IGUA_PUBLIC,'');
$options=[['value'=>'home','label'=>'Página inicial']];$choice='home';$subject=['kind'=>'home','title'=>'Página inicial'];
$report=igua_page_inspect_report($subject,$cfg,IGUA_PUBLIC);
$integrationDiag=$d;$integrationHistory=$s['igua_int_diag_history'];$ga4CredentialsReady=true;$adsenseConnected=true;
ob_start();require __DIR__.'/../app/admin_views/routes/integrations.php';$html=ob_get_clean();
verify188(str_contains($html,'Diagnóstico central')&&str_contains($html,'Histórico recente')&&str_contains($html,'PageSpeed · computador'),'interface sete serviços');
verify188(!str_contains($html,$secret)&&substr_count($html,'<details')===substr_count($html,'</details>'),'segredos ausentes e details válidos');
verify188(substr_count($html,'class="int-health-card"')===7,'sete cartões renderizados');
echo "Diagnóstico 1.0.88: estados, última operação, classificação redigida, histórico limitado, ACL, view e confidencialidade OK.\n";
