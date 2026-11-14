<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function check186(bool $ok,string $why):void {if(!$ok)throw new RuntimeException('GA4 186: '.$why);}
$key=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
if(!$key || !openssl_pkey_export($key,$private))throw new RuntimeException('OpenSSL indisponível para teste de assinatura');
$creds=['type'=>'service_account','client_email'=>'exemplo@project.iam.gserviceaccount.com','private_key'=>$private,'token_uri'=>'https://oauth2.googleapis.com/token'];
$requests=[];
$transport=static function(string $url,array $headers,array $body,bool $form)use(&$requests,$key):array{
    $requests[]=[$url,$headers,$body,$form];
    if($form){
        check186($url==='https://oauth2.googleapis.com/token' && isset($body['assertion']),'OAuth é destino fixo');
        $jwt=explode('.',$body['assertion']);
        check186(count($jwt)===3,'JWT tem três partes');
        $claims=json_decode(base64_decode(strtr($jwt[1],'-_','+/')),true);
        check186(($claims['scope']??'')==='https://www.googleapis.com/auth/analytics.readonly','escopo somente leitura');
        $sig=base64_decode(strtr($jwt[2],'-_','+/'));
        check186(openssl_verify($jwt[0].'.'.$jwt[1],$sig,openssl_pkey_get_details($key)['key'],OPENSSL_ALGO_SHA256)===1,'JWT assinado corretamente');
        return ['status'=>200,'body'=>'{"access_token":"token.mocked.1234567890"}'];
    }
    check186($url==='https://analyticsdata.googleapis.com/v1beta/properties/123456789:batchRunReports','lote pertence à propriedade fixa');
    check186($headers===['Authorization: Bearer token.mocked.1234567890'],'token só no header HTTPS');
    check186(count($body['requests']??[])===4,'quatro consultas em lote único');
    foreach($body['requests'] as $i=>$request)check186($request['dateRanges']===[['startDate'=>'28daysAgo','endDate'=>'yesterday']],'28 dias completos em cada consulta');
    check186($body['requests'][1]['dimensions'][0]['name']==='pageTitle' && $body['requests'][2]['dimensions'][0]['name']==='sessionDefaultChannelGroup' && $body['requests'][3]['dimensions'][0]['name']==='deviceCategory','dimensões oficiais');
    return ['status'=>200,'body'=>json_encode(['reports'=>[
        ['metricHeaders'=>[['name'=>'activeUsers'],['name'=>'sessions'],['name'=>'screenPageViews']], 'rows'=>[['metricValues'=>[['value'=>'25'],['value'=>'37'],['value'=>'88']]]]],
        ['dimensionHeaders'=>[['name'=>'pageTitle']],'metricHeaders'=>[['name'=>'screenPageViews']], 'rows'=>[['dimensionValues'=>[['value'=>'<img src=x onerror=alert(1)>']],'metricValues'=>[['value'=>'12']]]]],
        ['dimensionHeaders'=>[['name'=>'sessionDefaultChannelGroup']],'metricHeaders'=>[['name'=>'sessions']], 'rows'=>[['dimensionValues'=>[['value'=>'Organic Search']],'metricValues'=>[['value'=>'22']]]]],
        ['dimensionHeaders'=>[['name'=>'deviceCategory']],'metricHeaders'=>[['name'=>'activeUsers']], 'rows'=>[['dimensionValues'=>[['value'=>'mobile']],'metricValues'=>[['value'=>'19']]]]],
    ]],JSON_THROW_ON_ERROR)];
};
$now=1794000000;
$result=igua_ga4_audience_report($creds,'123456789',$transport,$now);
check186(count($requests)===2,'somente OAuth e uma consulta batch');
check186($result['summary']===['users'=>25,'sessions'=>37,'views'=>88],'métricas de tipos diferentes não misturadas');
check186($result['pages'][0]['count']===12 && $result['channels'][0]['count']===22 && $result['devices'][0]['count']===19,'linhas de cada relatório');
check186(!isset($result['access_token']) && !isset($result['private_key']) && !str_contains(json_encode($result),'token.mocked'),'nenhuma credencial no resultado');
check186(igua_ga4_audience_last(['igua_ga4_audience_last'=>$result],'123456789',$now+3600)!==null,'TTL exato de uma hora');
check186(igua_ga4_audience_last(['igua_ga4_audience_last'=>$result],'123456789',$now+3601)===null,'expiração de uma hora');
check186(igua_ga4_audience_last(['igua_ga4_audience_last'=>$result],'111111111',$now+5)===null,'não cruza propriedades');
check186(igua_ga4_audience_last(['igua_ga4_audience_last'=>$result],'123456789',$now-1)===null,'tempo inconsistente rejeitado');
check186(igua_ga4_safe_number('-3')===null && igua_ga4_safe_number('4e8')===null && igua_ga4_safe_number(str_repeat('9',16))===null,'não aceita métricas malformadas');
check186(igua_ga4_report_rows(['dimensionHeaders'=>[['name'=>'pageTitle']],'metricHeaders'=>[['name'=>'screenPageViews']],'rows'=>[['dimensionValues'=>[['value'=>'<script>alert(1)</script>']],'metricValues'=>[['value'=>'-3']]]]],'pageTitle','screenPageViews')===[],'contagem inválida descartada');
$invalid=false;try{igua_ga4_audience_report($creds,'G-ABCD',$transport,$now);}catch(InvalidArgumentException){$invalid=true;}check186($invalid,'ID G- não serve como propriedade');
$bad=static fn($url,$headers,$body,$form):array=>$form?['status'=>200,'body'=>'{"access_token":"token.mocked.1234567890"}']:['status'=>200,'body'=>'{"reports":[]}'];
$invalid=false;try{igua_ga4_audience_report($creds,'123456789',$bad,$now);}catch(RuntimeException){$invalid=true;}check186($invalid,'batch incompleto não vira relatório vazio');
$blank=static fn($url,$headers,$body,$form):array=>$form?['status'=>200,'body'=>'{"access_token":"token.mocked.1234567890"}']:['status'=>200,'body'=>'{"reports":[{"rows":[]},{"rows":[]},{"rows":[]},{"rows":[]}]}'];
$empty=igua_ga4_audience_report($creds,'123456789',$blank,$now);
check186($empty['summary']===null && $empty['pages']===[] && $empty['channels']===[],'sem informação não vira zero inventado');
$admin=(string)file_get_contents(__DIR__.'/../public/admin.php');
$post=(string)file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php');
$ctl=(string)file_get_contents(__DIR__.'/../app/admin_controllers/integrations.php');
check186(str_contains($admin,"'integrations_ga4_report'=>'manage_settings'") && str_contains($post,"'integrations_ga4_report'=>'integrations'"),'rota com ACL');
check186(str_contains($admin,'check_csrf();') && str_contains($ctl,'igua_ga4_audience_last($_SESSION,$property)'),'CSRF e cache protegidos');
check186(str_contains($ctl,"'igua_ga4_audience_attempt'") && str_contains($ctl,'<90'),'antirrepetição de 90 segundos');
require_once __DIR__.'/../app/branding.php';
if(!function_exists('admin_link')){function admin_link(string $action):string{return '/admin.php?action='.$action;}}
$cfg=['seo_site_url'=>'https://iguatama.online','seo_index'=>'1','ga4_enabled'=>'1','ga4_measurement_id'=>'G-ABCD123456','ga4_property_id'=>'123456789','adsense_publisher'=>'','ads_enabled'=>'0','google_site_verification'=>''];
$rows=igua_integrations_diagnostic($cfg,IGUA_PUBLIC,'');
$options=[['value'=>'home','label'=>'Página inicial']];$choice='home';$subject=['kind'=>'home','title'=>'Página inicial'];
$report=igua_page_inspect_report($subject,$cfg,IGUA_PUBLIC);
$ga4Audience=$result;$ga4CredentialsReady=true;
ob_start();require __DIR__.'/../app/admin_views/routes/integrations.php';$html=ob_get_clean();
check186(str_contains($html,'&lt;img src=x onerror=alert(1)&gt;') && !str_contains($html,'<img src=x onerror=alert(1)>'),'título vindo da API escapado');
check186(str_contains($html,'Usuários ativos') && str_contains($html,'Páginas populares') && str_contains($html,'Canais de origem') && str_contains($html,'Dispositivos'),'painéis exibidos');
check186(substr_count($html,'<details')===substr_count($html,'</details>'),'HTML de detalhes balanceado');
check186(!str_contains($html,'token.mocked') && !str_contains($html,'private_key'),'nenhuma credencial renderizada');
check186(str_contains($html,'integrations_ga4_report') && str_contains($html,'name="csrf"'),'POST com CSRF');
echo 'GA4 1.0.86: JWT, batch de 4 relatórios, métricas, cache, ACL, XSS e UI aprovados.'.PHP_EOL;
