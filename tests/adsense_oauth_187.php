<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function check187(bool $ok,string $why):void {if(!$ok)throw new RuntimeException('AdSense 187: '.$why);}
$client=['client_id'=>'1234567890-test.apps.googleusercontent.com','client_secret'=>'GOCSPX-testing-secret'];
check187(igua_adsense_callback_uri('https://iguatama.online')==='https://iguatama.online/admin.php?action=integrations_adsense_callback','callback exata');
foreach(['http://iguatama.online','https://iguatama.online/extra','https://example.com:8443','https://127.0.0.1'] as $url){
    $failed=false;try{igua_adsense_callback_uri($url);}catch(InvalidArgumentException){$failed=true;}check187($failed,'URL insegura rejeitada: '.$url);
}
$s=[];$now=1794070000;
$link=igua_adsense_api_authorize($client,'https://iguatama.online/admin.php?action=integrations_adsense_callback',$s,13,$now);
check187(str_starts_with($link,'https://accounts.google.com/o/oauth2/v2/auth?'),'somente endpoint auth Google');
parse_str((string)parse_url($link,PHP_URL_QUERY),$query);
check187($query['scope']===IGUA_ADSENSE_SCOPE&&$query['access_type']==='offline'&&$query['prompt']==='consent','scope read-only e refresh');
check187($query['code_challenge_method']==='S256'&&!isset($query['client_secret']),'PKCE sem segredo em URL');
check187(strlen($query['state'])===64,'estado de 256 bits');
$session0=$s;$bad=false;
try{igua_adsense_api_oauth_pending($s,str_repeat('a',64),13,$query['redirect_uri'],$now+2);}catch(RuntimeException){$bad=true;}
check187($bad&&!isset($s['igua_adsense_oauth_pending']),'state incorreto consome pendência');
$s=$session0;$bad=false;
try{igua_adsense_api_oauth_pending($s,$query['state'],14,$query['redirect_uri'],$now+2);}catch(RuntimeException){$bad=true;}
check187($bad,'userId amarrado');
$s=$session0;$bad=false;
try{igua_adsense_api_oauth_pending($s,$query['state'],13,$query['redirect_uri'],$now+601);}catch(RuntimeException){$bad=true;}
check187($bad,'state com TTL 10 min');
$s=$session0;$verifier=igua_adsense_api_oauth_pending($s,$query['state'],13,$query['redirect_uri'],$now+10);
$challenge=rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'=');
check187($challenge===$query['code_challenge'],'challenge e verifier batem');
$bad=false;try{igua_adsense_api_oauth_pending($s,$query['state'],13,$query['redirect_uri'],$now+12);}catch(RuntimeException){$bad=true;}
check187($bad,'callback replay bloqueado');
$requests=[];
$oauthTransport=static function(string $url,array $headers=[],?array $form=null)use(&$requests,$client,$query,$verifier):array {
    $requests[]=[$url,$headers,$form];
    check187($url==='https://oauth2.googleapis.com/token','POST somente OAuth oficial');
    check187(($form['client_id']??null)===$client['client_id']&&($form['client_secret']??null)===$client['client_secret'],'cliente permanece privado');
    check187($form['code_verifier']===$verifier&&$form['redirect_uri']===$query['redirect_uri'],'PKCE e callback exatos');
    return ['status'=>200,'body'=>'{"access_token":"access.mocked.123456","refresh_token":"refresh.mocked.abcdef"}'];
};
$tokens=igua_adsense_api_exchange($client,'auth.code.mock.1234567',$query['redirect_uri'],$verifier,$oauthTransport);
check187($tokens['refresh_token']==='refresh.mocked.abcdef'&&count($requests)===1,'troca código OAuth valida dados');
$credentials=$client+['refresh_token'=>$tokens['refresh_token'],'account'=>'accounts/pub-1234567890123456'];
$apiCalls=[];
$transport=static function(string $url,array $headers=[],?array $form=null)use(&$apiCalls,$credentials):array {
    $apiCalls[]=[$url,$headers,$form];
    if($url==='https://oauth2.googleapis.com/token'){
        check187($form['grant_type']==='refresh_token' && $form['refresh_token']===$credentials['refresh_token'],'refresh token somente POST no google');
        return ['status'=>200,'body'=>'{"access_token":"access.mocked.123456"}'];
    }
    check187($headers===['Authorization: Bearer access.mocked.123456'],'token só no header');
    if($url==='https://adsense.googleapis.com/v2/accounts')return ['status'=>200,'body'=>json_encode(['accounts'=>[['name'=>'accounts/pub-1234567890123456','state'=>'READY']]],JSON_THROW_ON_ERROR)];
    check187(str_starts_with($url,'https://adsense.googleapis.com/v2/accounts/pub-1234567890123456/reports:generate?'),'account resource fixo');
    check187(str_contains($url,'dateRange=LAST_30_DAYS')&&substr_count($url,'metrics=')===4,'30 dias e quatro métricas oficiais');
    return ['status'=>200,'body'=>json_encode(['headers'=>[
        ['name'=>'ESTIMATED_EARNINGS','type'=>'METRIC_CURRENCY','currencyCode'=>'BRL'],
        ['name'=>'IMPRESSIONS','type'=>'METRIC_TALLY'],['name'=>'CLICKS','type'=>'METRIC_TALLY'],['name'=>'PAGE_VIEWS','type'=>'METRIC_TALLY'],
    ],'rows'=>[['cells'=>[['value'=>'12.37'],['value'=>'850'],['value'=>'11'],['value'=>'1250']]]]],JSON_THROW_ON_ERROR)];
};
$report=igua_adsense_api_report($credentials,'ca-pub-1234567890123456',$transport,$now);
check187(count($apiCalls)===3,'refresh, contas e relatório');
check187($report['currency']==='BRL'&&$report['totals']===['earnings'=>'12.37','impressions'=>850,'clicks'=>11,'page_views'=>1250],'métricas e moeda corretas');
check187(!str_contains(json_encode($report),'access.mocked')&&!str_contains(json_encode($report),'refresh.mocked'),'relatório não contém tokens');
check187(igua_adsense_api_last(['igua_adsense_api_last'=>$report],'ca-pub-1234567890123456',$now+3600)!==null,'cache 1h');
check187(igua_adsense_api_last(['igua_adsense_api_last'=>$report],'ca-pub-1234567890123456',$now+3601)===null,'cache expira');
$bad=false;try{igua_adsense_api_report($credentials,'ca-pub-0000000000000000',$transport,$now);}catch(RuntimeException){$bad=true;}
check187($bad&&count($apiCalls)===3,'não consulta outro ca-pub');
$bad=false;try{igua_adsense_api_account('access.mocked.123456','ca-pub-0000000000000000',$transport);}catch(RuntimeException){$bad=true;}
check187($bad,'lista de contas não confirma conta diferente');
$bad=false;try{igua_adsense_api_report_parse(['headers'=>[['name'=>'ESTIMATED_EARNINGS','currencyCode'=>'BRL']], 'rows'=>[['cells'=>[['value'=>'<img onerror=alert(1)>']]]]],$credentials['account'],$now);}catch(RuntimeException){$bad=true;}
check187($bad,'resposta maliciosa não exibida como moeda');
$empty=igua_adsense_api_report_parse(['headers'=>[['name'=>'ESTIMATED_EARNINGS'],['name'=>'IMPRESSIONS'],['name'=>'CLICKS'],['name'=>'PAGE_VIEWS']]],$credentials['account'],$now);
check187($empty['totals']===null,'sem dados não fabrica zero');
$_SESSION=[];
$private=tempnam(sys_get_temp_dir(),'iguaads-');@unlink($private);
igua_adsense_api_write($credentials,$private);
check187(is_file($private) && (fileperms($private)&077)===0,'arquivo privado com modo 0600');
check187(igua_adsense_api_read($private)===$credentials,'lê JSON e valida campos');
igua_adsense_api_delete($private);check187(!file_exists($private),'remove credenciais');
$admin=(string)file_get_contents(__DIR__.'/../public/admin.php');
$post=(string)file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php');
$ctl=(string)file_get_contents(__DIR__.'/../app/admin_controllers/integrations.php');
check187(str_contains($admin,"'integrations_adsense_callback'=>'manage_settings'")&&str_contains($admin,"'integrations_adsense_report'=>'manage_settings'"),'ACL callback/relatório');
check187(str_contains($admin,'igua_adsense_api_oauth_pending($_SESSION') && str_contains($ctl,"'integrations_adsense_api_attempt'")===false,'state privado e nomes distintos');
check187(str_contains($post,"'integrations_adsense_report'=>'integrations'")&&str_contains($admin,'check_csrf();'),'POST protegido por ACL e CSRF');
check187(str_contains($ctl,"'igua_adsense_api_attempt'")&&str_contains($ctl,'<90'),'rate limit');
// Renderização real do painel com dados controlados (inclui escape de HTML e ausência de tokens).
if(!function_exists('admin_link')){function admin_link(string $action):string{return '/admin.php?action='.$action;}}
$cfg=['seo_site_url'=>'https://iguatama.online','seo_index'=>'1','ga4_enabled'=>'0','ga4_measurement_id'=>'','ga4_property_id'=>'',
 'adsense_publisher'=>'ca-pub-1234567890123456','ads_enabled'=>'0','google_site_verification'=>''];
$rows=igua_integrations_diagnostic($cfg,IGUA_PUBLIC,'');
$options=[['value'=>'home','label'=>'Página inicial']];$choice='home';$subject=['kind'=>'home','title'=>'Página inicial'];
$report=igua_page_inspect_report($subject,$cfg,IGUA_PUBLIC);
$adsenseOAuth=$credentials;$adsenseConnected=true;
$adsenseReport=igua_adsense_api_report_parse(['headers'=>[
 ['name'=>'ESTIMATED_EARNINGS','currencyCode'=>'BRL'],['name'=>'IMPRESSIONS'],['name'=>'CLICKS'],['name'=>'PAGE_VIEWS']],
 'rows'=>[['cells'=>[['value'=>'1.23'],['value'=>'20'],['value'=>'3'],['value'=>'25']]]]],$credentials['account'],$now);
ob_start();require __DIR__.'/../app/admin_views/routes/integrations.php';$html=ob_get_clean();
check187(str_contains($html,'Receita estimada')&&str_contains($html,'Impressões')&&str_contains($html,'Conectar conta AdSense via Google OAuth'),'UI relatórios e ajuda');
check187(!str_contains($html,$client['client_secret'])&&!str_contains($html,$credentials['refresh_token'])&& !str_contains($html,'access.mocked'),'segredos não aparecem HTML');
check187(substr_count($html,'<details')===substr_count($html,'</details>'),'markup details balanceado');
echo "AdSense 1.0.87: OAuth state/PKCE, refresh, verificação ca-pub, relatório, ACL, sessão e armazenamento privado aprovados.\n";
