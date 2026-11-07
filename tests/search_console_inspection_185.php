<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function assert185(bool $x,string $why):void{if(!$x)throw new RuntimeException('URL Inspection 185: '.$why);}
$cfg=['seo_site_url'=>'https://iguatama.online','search_console_property_mode'=>'domain','seo_pretty_urls'=>'1','seo_index'=>'1'];
$article=['id'=>12,'title'=>'Notícia de teste','slug'=>'noticia-de-teste','is_demo'=>0,'status'=>'published','published_at'=>'2026-11-07 06:00:00','summary'=>'Resumo de teste para confirmar a inspeção de notícias publicadas.', 'seo_description'=>'A notícia foi criada para validar o novo painel de indexação.'];
$subject=['kind'=>'article','title'=>'Notícia de teste','data'=>$article];
$target=igua_search_inspection_target($subject,$cfg);
assert185($target['url']==='https://iguatama.online/noticia/noticia-de-teste','URL é do domínio oficial e do slug do banco');
$plain=igua_search_inspection_target($subject,array_replace($cfg,['seo_pretty_urls'=>'0']));
assert185($plain['url']==='https://iguatama.online/index.php?p=noticia&slug=noticia-de-teste','URL de notícia sem rota amigável');
foreach([
 ['subject'=>['kind'=>'home','title'=>'Página inicial'],'cfg'=>$cfg],
 ['subject'=>['kind'=>'article','data'=>array_replace($article,['is_demo'=>1])],'cfg'=>$cfg],
 ['subject'=>['kind'=>'article','data'=>array_replace($article,['status'=>'draft'])],'cfg'=>$cfg],
 ['subject'=>['kind'=>'article','data'=>array_replace($article,['slug'=>'../evil'])],'cfg'=>$cfg],
 ['subject'=>$subject,'cfg'=>array_replace($cfg,['seo_index'=>'0'])],
 ['subject'=>$subject,'cfg'=>array_replace($cfg,['seo_site_url'=>'https://127.0.0.1'])],
] as $case){$fail=false;try{igua_search_inspection_target($case['subject'],$case['cfg']);}catch(InvalidArgumentException){$fail=true;}assert185($fail,'página indevida recusada');}
assert185(igua_search_console_endpoint_allowed('POST','https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',false),'endpoint exato autorizado');
foreach(['https://searchconsole.googleapis.com/v1/urlInspection/index:inspect/../evil','https://evil.invalid/v1/urlInspection/index:inspect','http://searchconsole.googleapis.com/v1/urlInspection/index:inspect'] as $url)
 assert185(!igua_search_console_endpoint_allowed('POST',$url,false),'endpoint externo/travessia não autorizado');
$crypto=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
if(!$crypto || !openssl_pkey_export($crypto,$private))throw new RuntimeException('OpenSSL indisponível');
$creds=['type'=>'service_account','client_email'=>'demo@project.iam.gserviceaccount.com','private_key'=>$private,'token_uri'=>'https://oauth2.googleapis.com/token'];
$attempts=[];$permission='siteRestrictedUser';$result=['inspectionResult'=>['indexStatusResult'=>[
 'verdict'=>'PASS','coverageState'=>'Enviada e indexada','robotsTxtState'=>'ALLOWED','indexingState'=>'INDEXING_ALLOWED','lastCrawlTime'=>'2026-11-06T13:14:22Z',
 'pageFetchState'=>'SUCCESSFUL','googleCanonical'=>'https://iguatama.online/noticia/noticia-de-teste','userCanonical'=>'https://iguatama.online/noticia/noticia-de-teste',
]]];
$mock=static function(string $method,string $url,array $headers,array $body,bool $form) use(&$attempts,&$result,&$permission):array{
 $attempts[]=['method'=>$method,'url'=>$url,'headers'=>$headers,'body'=>$body,'form'=>$form];
 if($url==='https://oauth2.googleapis.com/token'){
    assert185($method==='POST'&&$form,'JWT OAuth POST');
    $parts=explode('.',(string)($body['assertion']??''));assert185(count($parts)===3,'JWT completo');
    $claims=json_decode(base64_decode(strtr($parts[1],'-_','+/')),true);
    assert185(($claims['scope']??'')==='https://www.googleapis.com/auth/webmasters.readonly','escopo de somente leitura');
    return ['status'=>200,'body'=>'{"access_token":"access.token.123456789"}'];
 }
 assert185($headers===['Authorization: Bearer access.token.123456789'],'cabeçalho autenticado');
 if($method==='GET')return ['status'=>200,'body'=>json_encode(['siteEntry'=>[['siteUrl'=>'sc-domain:iguatama.online','permissionLevel'=>$permission]]])];
 assert185($url==='https://searchconsole.googleapis.com/v1/urlInspection/index:inspect','URL inspeção fixa');
 assert185(($body['inspectionUrl']??'')==='https://iguatama.online/noticia/noticia-de-teste','corpo URL restrita');
 assert185(($body['siteUrl']??'')==='sc-domain:iguatama.online' && ($body['languageCode']??'')==='pt-BR','propriedade/idioma corretos');
 return ['status'=>200,'body'=>json_encode($result,JSON_THROW_ON_ERROR)];
};
$now=1794000000;
$ok=igua_search_inspection_query($creds,$cfg,'domain',$target,$mock,$now);
assert185(count($attempts)===3,'somente OAuth, verificação propriedade e inspeção');
assert185($ok['state']==='indexed' && $ok['robots']==='ALLOWED' && $ok['last_crawl']!=='','resumo positivo válido');
assert185(!isset($ok['access_token'])&&!isset($ok['private_key'])&&!isset($ok['inspectionResult']),'sem segredo ou JSON bruto no resultado');
assert185(igua_search_inspection_last($cfg,$target,['igua_search_inspection_last'=>$ok],$now+100)!==null,'cache válido');
assert185(igua_search_inspection_last($cfg,array_replace($target,['article_id'=>11]),['igua_search_inspection_last'=>$ok],$now+100)===null,'cache não cruza notícias');
assert185(igua_search_inspection_last(array_replace($cfg,['seo_site_url'=>'https://other.example']),$target,['igua_search_inspection_last'=>$ok],$now+100)===null,'cache não cruza propriedade');
assert185(igua_search_inspection_last($cfg,$target,['igua_search_inspection_last'=>$ok],$now+3601)===null,'cache expira');
$permission='siteUnverifiedUser';$denied=false;
try{igua_search_inspection_query($creds,$cfg,'domain',$target,$mock,$now);}catch(RuntimeException $e){$denied=str_contains($e->getMessage(),'não tem acesso');}
assert185($denied,'sem permissão Google não autoriza inspeção');
$permission='siteFullUser';
$result['inspectionResult']['indexStatusResult']=array_replace($result['inspectionResult']['indexStatusResult'],[
 'verdict'=>'NEUTRAL','coverageState'=>'<img src=x onerror=alert(1)>','robotsTxtState'=>'DISALLOWED','indexingState'=>'BLOCKED_BY_META_TAG',
 'googleCanonical'=>'https://iguatama.online/outra-url','pageFetchState'=>'NOT_FOUND',
]);
$excluded=igua_search_inspection_query($creds,$cfg,'domain',$target,$mock,$now);
assert185($excluded['state']==='excluded' && count($excluded['recommendations'])>=3,'exclusão, orientações condicionais');
assert185(str_contains($excluded['coverage'],'<img'),'conteúdo Google preservado somente como texto');
$result['inspectionResult']['indexStatusResult']['verdict']='FAIL';
assert185(igua_search_inspection_query($creds,$cfg,'domain',$target,$mock,$now)['state']==='issue','erro tratado separadamente');
$result['inspectionResult']['indexStatusResult']['verdict']='INVALID';
assert185(igua_search_inspection_query($creds,$cfg,'domain',$target,$mock,$now)['state']==='unknown','veredito desconhecido não produz falsa indexação');
$result=['inspectionResult'=>[]];$invalid=false;
try{igua_search_inspection_query($creds,$cfg,'domain',$target,$mock,$now);}catch(RuntimeException){$invalid=true;}
assert185($invalid,'sem indexStatusResult não inventa indexação');
foreach(['https://evil.invalid/noticia/falsa','https://iguatama.online/admin.php','http://iguatama.online/noticia/falsa','https://iguatama.online.evil.invalid/noticia/falsa'] as $bad){
 $invalid=false;try{igua_search_inspection_query($creds,$cfg,'domain',array_replace($target,['url'=>$bad]),$mock,$now);}catch(InvalidArgumentException){$invalid=true;}
 assert185($invalid,'URL arbitrária recusada antes de enviar token');
}
$routes=file_get_contents(__DIR__.'/../public/admin.php');$post=file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php');
assert185(str_contains($routes,"'integrations_search_console_inspect'=>'manage_settings'") && str_contains($post,"'integrations_search_console_inspect'=>'integrations'"),'roteamento com ACL');
assert185(str_contains($routes,'check_csrf();'),'CSRF aplicado antes do POST');
assert185(str_contains(file_get_contents(__DIR__.'/../app/admin_controllers/integrations.php'),'igua_page_inspect_subject($db,$raw)'),'post escolhe notícia pelo banco');
assert185(!str_contains(file_get_contents(__DIR__.'/../app/admin_views/routes/integrations.php'),'private_key'),'HTML não expõe chave');
// Renderização de resposta externa maliciosa usando escaping central h().
require_once __DIR__.'/../app/branding.php';
if(!function_exists('admin_link')){function admin_link(string $action):string{return '/admin.php?action='.$action;}}
$viewCfg=array_replace($cfg,['ga4_enabled'=>'0','ga4_measurement_id'=>'','adsense_publisher'=>'','google_site_verification'=>'','ga4_property_id'=>'','google_dns_verification_txt'=>'']);
$cfg=$viewCfg;$rows=igua_integrations_diagnostic($viewCfg,IGUA_PUBLIC,'');
$choice='article:12';$options=[['value'=>$choice,'label'=>'Notícia teste']];$report=igua_page_inspect_report($subject,$viewCfg,IGUA_PUBLIC);
$inspectionTarget=$target;$inspectionLast=$excluded;$ga4CredentialsReady=true;
ob_start();require __DIR__.'/../app/admin_views/routes/integrations.php';$html=ob_get_clean();
assert185(str_contains($html,'&lt;img src=x onerror=alert(1)&gt;')&&!str_contains($html,'<img src=x onerror=alert(1)>'),'HTML malicioso da API escapado');
assert185(str_contains($html,'data-url-inspection-form')&&str_contains($html,'name="inspection_page"'),'form presente com ID sem URL livre');
assert185(substr_count($html,'<details')===substr_count($html,'</details>'),'componentes HTML balanceados');
echo 'Inspeção 1.0.85: OAuth, propriedade, URL/ACL, respostas, cache, status e HTML seguro aprovados.'.PHP_EOL;
