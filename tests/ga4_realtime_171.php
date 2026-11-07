<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/ga4_realtime.php';
function check171(bool $value,string $label): void {if(!$value)throw new RuntimeException('GA4 171: '.$label); echo 'OK: '.$label.PHP_EOL;}
foreach(['','1234','987654321'] as $valid)check171(igua_ga4_property_id($valid)===$valid,'ID de propriedade permitido');
foreach(['G-ABCD1234','123x','https://google.com','0001234',str_repeat('9',50)] as $invalid){$err=false;try{igua_ga4_property_id($invalid);}catch(InvalidArgumentException){$err=true;}check171($err,'ID de propriedade rejeitado');}
$crypto=openssl_pkey_new(['private_key_bits'=>2048,'private_key_type'=>OPENSSL_KEYTYPE_RSA]);
if($crypto===false)throw new RuntimeException('OpenSSL não disponível para testes');
openssl_pkey_export($crypto,$pem);
$creds=['type'=>'service_account','client_email'=>'portal@projeto.iam.gserviceaccount.com','private_key'=>$pem,'token_uri'=>'https://oauth2.googleapis.com/token'];
$raw=json_encode($creds,JSON_THROW_ON_ERROR);
$parsed=igua_ga4_decode_service_account($raw);
check171($parsed['client_email']===$creds['client_email'],'conta de serviço valida e reconhecida');
foreach([['type'=>'oauth_user'],['token_uri'=>'https://example.org/token'],['client_email'=>'x@example.org'],['private_key'=>'invalid']] as $bad){
  $err=false;try{igua_ga4_decode_service_account(json_encode(array_merge($creds,$bad),JSON_THROW_ON_ERROR));}catch(InvalidArgumentException){$err=true;}
  check171($err,'credencial recusada corretamente');
}
$dir=sys_get_temp_dir().'/ga4-test-'.bin2hex(random_bytes(5));
$path=$dir.'/integrations/ga4-service-account.json';
igua_ga4_store_service_account($raw,$path);
check171(is_file($path) && igua_ga4_read_service_account($path)['client_email']===$creds['client_email'],'JSON persiste e lê em pasta privada');
check171(((fileperms($path)&0777)===0600),'arquivo com permissões 0600');
$last=[];$requests=[];
$transport=static function(string $url,array $headers,array $body,bool $form)use(&$requests,&$last,$crypto):array{
    $requests[]=[$url,$headers,$body,$form];
    if(str_contains($url,'oauth2.googleapis.com')){
        check171($form && ($body['grant_type']??'')==='urn:ietf:params:oauth:grant-type:jwt-bearer','JWT via OAuth POST');
        $jwt=explode('.',$body['assertion']);check171(count($jwt)===3,'JWT três partes');
        $sig=base64_decode(strtr($jwt[2],'-_','+/'));$pub=openssl_pkey_get_details($crypto)['key'];
        check171(openssl_verify($jwt[0].'.'.$jwt[1],$sig,$pub,OPENSSL_ALGO_SHA256)===1,'JWT assinado com RSA SHA256');
        $claims=json_decode(base64_decode(strtr($jwt[1],'-_','+/')),true);
        check171($claims['scope']==='https://www.googleapis.com/auth/analytics.readonly' && $claims['iss']==='portal@projeto.iam.gserviceaccount.com','escopo mínimo e identidade corretos');
        return ['status'=>200,'body'=>json_encode(['access_token'=>'token.mocked.1234567890'])];
    }
    check171(!$form&&$headers[0]==='Authorization: Bearer token.mocked.1234567890','Authorization é interna, sem exposição em HTML');
    if(str_contains($url,'runRealtimeReport')){
        check171($body['metrics'][0]['name']==='eventCount','consulta eventos em tempo real');
        return ['status'=>200,'body'=>json_encode(['metricHeaders'=>[['name'=>'eventCount'],['name'=>'activeUsers']], 'rows'=>[['metricValues'=>[['value'=>'14'],['value'=>'3']]]]])];
    }
    check171($body['dateRanges'][0]['startDate']==='6daysAgo' && $body['dimensions'][0]['name']==='date','relatório diário cobre 7 dias');
    return ['status'=>200,'body'=>json_encode(['dimensionHeaders'=>[['name'=>'date']], 'metricHeaders'=>[['name'=>'eventCount']], 'rows'=>[['dimensionValues'=>[['value'=>'20261105']],'metricValues'=>[['value'=>'91']]]]])];
};
$result=igua_ga4_test_receiving($creds,'123456789',$transport,1762470000);
check171($result['status']==='realtime' && $result['realtime_events']===14&&$result['realtime_users']===3&&$result['recent_events']===91 && $result['last_event_date']==='05/11/2026','evento real pela API simulada classificado');
check171(count($requests)===3,'somente 3 chamadas explícitas a hosts fixos');
$zero=static function(string $url,array $headers,array $body,bool $form):array {
    if($form)return ['status'=>200,'body'=>'{"access_token":"token.mocked.1234567890"}'];
    return ['status'=>200,'body'=>'{"metricHeaders":[{"name":"eventCount"}],"rows":[]}'];
};
$result=igua_ga4_test_receiving($creds,'123456789',$zero);
check171($result['status']==='empty' && $result['realtime_events']===0, 'sem eventos não significa falha');
$recent=static function(string $url,array $headers,array $body,bool $form):array {
    if($form)return ['status'=>200,'body'=>'{"access_token":"token.mocked.1234567890"}'];
    if(str_contains($url,'runRealtimeReport'))return ['status'=>200,'body'=>'{"metricHeaders":[{"name":"eventCount"}],"rows":[]}'];
    return ['status'=>200,'body'=>'{"metricHeaders":[{"name":"eventCount"}],"rows":[{"metricValues":[{"value":"5"}]}]}'];
};
check171(igua_ga4_test_receiving($creds,'123456789',$recent)['status']==='recent','distingue tráfego recente de tempo real');
foreach([401,403,404,429,500] as $code){$fail=false;try{igua_ga4_google_response(['status'=>$code,'body'=>'{"error":{"message":"SECRET MOCK"}}'],'teste');}catch(RuntimeException $e){$fail=true;check171(!str_contains($e->getMessage(),'SECRET MOCK'),'erro HTTP sem eco do servidor Google');}check171($fail,'erro HTTP tratado');}
try {igua_ga4_delete_service_account($path);check171(!is_file($path),'remove chave privada');}finally{if(is_file($path))unlink($path);@rmdir(dirname($path));@rmdir($dir);}
$admin=file_get_contents(__DIR__.'/../public/admin.php');
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/integrations.php');
check171(str_contains($admin,"'integrations_ga4_test'=>'manage_settings'") && str_contains($admin,"'integrations_ga4_credentials'=>'manage_settings'"),'rotas protegidas por ACL');
check171(str_contains($view,'data-ga4-test-form') && str_contains($view,'Testar recebimento') && substr_count($view,'csrf_field()')>=5,'painel mobile e CSRF presentes');
check171(!str_contains($view,'private_key')&&!str_contains($view,'access_token'),'credencial e token nunca vão ao HTML');
echo 'GA4 1.0.71: testes offline aprovados; Google externo real pendente de credenciais e permissão.'.PHP_EOL;
