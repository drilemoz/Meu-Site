<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function check184(bool $ok,string $msg):void {if(!$ok)throw new RuntimeException('Search Console 184: '.$msg);}
$cfg=['seo_site_url'=>'https://iguatama.online','search_console_property_mode'=>'domain'];
$props=igua_search_console_properties($cfg);
check184($props===['domain'=>'sc-domain:iguatama.online','prefix'=>'https://iguatama.online/'],'propriedades derivadas da URL oficial');
check184(igua_search_console_properties(['seo_site_url'=>'https://www.iguatama.online'])['domain']==='sc-domain:iguatama.online','www no domínio');
foreach(['https://127.0.0.1','https://iguatama.online:8443','http://iguatama.online','https://iguatama.online/admin'] as $bad){
 check184(igua_search_console_property(['seo_site_url'=>$bad],'domain')==='','URL não elegível não gera consulta externa');
}
foreach(['https://evil.invalid/','domain\n',''] as $bad){$denied=false;try{igua_search_console_property_mode($bad);}catch(InvalidArgumentException){$denied=true;}check184($denied,'tipo não permitido recusado');}
$crypto=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
if($crypto===false)throw new RuntimeException('OpenSSL não disponível');
openssl_pkey_export($crypto,$private);
$creds=['type'=>'service_account','client_email'=>'portal@demo.iam.gserviceaccount.com','private_key'=>$private,'token_uri'=>'https://oauth2.googleapis.com/token'];
$urls=[];$requests=[];$permission='siteFullUser';$empty=false;
$mock=static function(string $method,string $url,array $headers,array $body,bool $form) use (&$urls,&$requests,&$permission,&$empty,$crypto):array {
    $urls[]=$url;$requests[]=[$method,$body];
    if(str_contains($url,'oauth2.googleapis.com')){
        check184($method==='POST'&&$form,'OAuth POST form');
        $pieces=explode('.',$body['assertion']??'');
        check184(count($pieces)===3,'JWT com três segmentos');
        $claims=json_decode(base64_decode(strtr($pieces[1],'-_','+/')),true);
        check184(($claims['scope']??'')==='https://www.googleapis.com/auth/webmasters.readonly','escopo mínimo Search Console');
        check184(openssl_verify($pieces[0].'.'.$pieces[1],base64_decode(strtr($pieces[2],'-_','+/')),openssl_pkey_get_details($crypto)['key'],OPENSSL_ALGO_SHA256)===1,'assinatura RSA verificada');
        return ['status'=>200,'body'=>'{"access_token":"token.tested.123456789"}'];
    }
    check184($headers===['Authorization: Bearer token.tested.123456789'],'token presente somente no transporte');
    if($method==='GET'){
        check184($url==='https://www.googleapis.com/webmasters/v3/sites','somente listagem de propriedades');
        return ['status'=>200,'body'=>json_encode(['siteEntry'=>[['siteUrl'=>'sc-domain:other.invalid','permissionLevel'=>'siteOwner'], ['siteUrl'=>'sc-domain:iguatama.online','permissionLevel'=>$permission]]])];
    }
    check184($method==='POST'&&str_contains($url,'sc-domain%3Aiguatama.online/searchAnalytics/query'),'endpoint codificado e fixo');
    check184(($body['startDate']??'')==='2026-10-06'&&($body['endDate']??'')==='2026-11-02','28 dias completos em horário do Pacífico');
    $dim=$body['dimensions'][0]??'';
    if($dim==='query')return ['status'=>200,'body'=>json_encode(['rows'=>$empty?[]:[['keys'=>['notícias Iguatama'], 'clicks'=>27,'impressions'=>350]]])];
    if($dim==='page')return ['status'=>200,'body'=>json_encode(['rows'=>$empty?[]:[['keys'=>['https://iguatama.online/noticia/teste'], 'clicks'=>13,'impressions'=>150]]])];
    return ['status'=>200,'body'=>json_encode(['rows'=>$empty?[]:[['clicks'=>42,'impressions'=>850,'ctr'=>0.0494117,'position'=>10.3]]])];
};
$now=(new DateTimeImmutable('2026-11-05T12:00:00',new DateTimeZone('America/Los_Angeles')))->getTimestamp();
$result=igua_search_console_query($creds,$cfg,'domain',$mock,$now);
check184(count($urls)===5,'autenticação, lista, total, pesquisa e páginas');
check184($result['summary']['clicks']===42 && $result['summary']['impressions']===850 && $result['summary']['ctr']===4.9,'resumo numérico de API real simulada');
check184(count($result['queries'])===1&&count($result['pages'])===1&&$result['permission']==='siteFullUser','dados da propriedade autorizada');
check184(!isset($result['access_token'])&&!isset($result['private_key']),'nenhum segredo no cache');
check184(igua_search_console_last($cfg,['igua_search_console_api_last'=>$result],$now+300)!==null,'cache da propriedade e prazo');
check184(igua_search_console_last(['seo_site_url'=>'https://exemplo.com','search_console_property_mode'=>'domain'],['igua_search_console_api_last'=>$result],$now+300)===null,'cache não cruza propriedades');
check184(igua_search_console_last($cfg,['igua_search_console_api_last'=>$result],$now+3601)===null,'cache expira');
$empty=true;$noData=igua_search_console_query($creds,$cfg,'domain',$mock,$now);
check184($noData['summary']===null && $noData['queries']===[],'ausência de dados não cria pontuação zero');
$permission='siteUnverifiedUser';$denied=false;
try{igua_search_console_query($creds,$cfg,'domain',$mock,$now);}catch(RuntimeException $e){$denied=str_contains($e->getMessage(),'não tem acesso');}
check184($denied,'DNS ou propriedade não verificada não é permissão API');
foreach([401,403,404,429,500] as $status){$caught=false;try{igua_search_console_response(['status'=>$status,'body'=>'{"error":"SECRET MOCK"}'],'teste');}catch(RuntimeException $e){$caught=!str_contains($e->getMessage(),'SECRET MOCK');}check184($caught,'erros sem eco de credenciais');}
check184(igua_search_console_total(['rows'=>[['clicks'=>12,'impressions'=>100,'ctr'=>0.1,'position'=>3.5]]])['ctr']===10.0,'CTR calculado sobre proporção');
check184(igua_search_console_total(['rows'=>[['clicks'=>12,'impressions'=>100,'ctr'=>3,'position'=>5]]])===null,'dados malformados não viram relatório');
$ranked=igua_search_console_ranked(['rows'=>[
 ['keys'=>['<script>alert(1)</script>'],'clicks'=>5,'impressions'=>6],
 ['keys'=>['\0x'],'clicks'=>-1,'impressions'=>3],
]],'query');
check184(count($ranked)===1 && str_contains($ranked[0]['label'],'<script>'),'texto de API exige escaping no HTML');
$source=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/integrations.php');
$routes=(string)file_get_contents(__DIR__.'/../public/admin.php');
$action=(string)file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php');
check184(str_contains($source,"h(\$item['label'])") && str_contains($source,"csrf_field()") && str_contains($source,'data-search-console-test-form'),'view escapa conteúdo e tem CSRF');
check184(str_contains($routes,"'integrations_search_console_test'=>'manage_settings'")&&str_contains($action,"'integrations_search_console_test'=>'integrations'"),'rotas autorizadas');
check184(!str_contains($source,'access_token')&&!str_contains($source,'private_key'),'não exibe segredo');
// Checa whitelist sem depender de cURL instalada no runner local.
check184(igua_search_console_endpoint_allowed('POST','https://oauth2.googleapis.com/token',true),'OAuth autorizado');
check184(igua_search_console_endpoint_allowed('GET','https://www.googleapis.com/webmasters/v3/sites',false),'listagem autorizada');
check184(igua_search_console_endpoint_allowed('POST','https://www.googleapis.com/webmasters/v3/sites/sc-domain%3Aiguatama.online/searchAnalytics/query',false),'propriedade domínio autorizada');
check184(igua_search_console_endpoint_allowed('POST','https://www.googleapis.com/webmasters/v3/sites/https%3A%2F%2Figuatama.online%2F/searchAnalytics/query',false),'prefixo de URL autorizado');
foreach(['https://evil.invalid/','https://www.googleapis.com/webmasters/v3/sites/sc-domain%3Aiguatama.online/../../evil','http://www.googleapis.com/webmasters/v3/sites'] as $url)
    check184(!igua_search_console_endpoint_allowed('POST',$url,false),'destino ou caminho malicioso bloqueado');
// Renderiza relatório realmente preenchido e verifica escaping de conteúdo Google.
require_once __DIR__.'/../app/branding.php';
if(!function_exists('admin_link')){function admin_link(string $action):string{return '/admin.php?action='.$action;}}
$cfg=array_replace($cfg,['seo_index'=>'1','ga4_enabled'=>'0','ga4_measurement_id'=>'','adsense_publisher'=>'','google_site_verification'=>'','ga4_property_id'=>'','google_dns_verification_txt'=>'']);
$rows=igua_integrations_diagnostic($cfg,IGUA_PUBLIC,'');
$choice='home';$options=[['value'=>'home','label'=>'Página inicial']];
$subject=['kind'=>'home','title'=>'Página inicial'];
$report=igua_page_inspect_report($subject,$cfg,IGUA_PUBLIC);
$searchApiLast=$result;
$searchApiLast['queries'][]=['label'=>'<img src=x onerror=alert(1)>','clicks'=>4,'impressions'=>9];
ob_start();require __DIR__.'/../app/admin_views/routes/integrations.php';$html=ob_get_clean();
check184(str_contains($html,'&lt;img src=x onerror=alert(1)&gt;') && !str_contains($html,'<img src=x onerror=alert(1)>'),'relatório externo sempre escapado na tela');
check184(substr_count($html,'<details')===substr_count($html,'</details>'),'blocos de ajuda balanceados no resultado');
check184(str_contains($html,'42')&&str_contains($html,'850')&&str_contains($html,'Pesquisas principais'),'relatório numérico é renderizado corretamente');
echo 'Search Console 1.0.84: autenticação, escopo, propriedades, métricas, cache, permissão, rotas e limites aprovados.'.PHP_EOL;
