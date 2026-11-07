<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bing_webmaster.php';
function b206(bool $ok,string $message):void{if(!$ok)throw new RuntimeException('Bing 206: '.$message);}
$storage=sys_get_temp_dir().'/igua-bing206-'.bin2hex(random_bytes(6));mkdir($storage,0700);
try {
  $key='BINGAPIKEY-0123456789test';
  igua_bing_store($storage,$key);
  $cfg=igua_bing_read($storage);
  b206($cfg!==null&&$cfg['key']===$key,'chave armazenada e recuperada');
  b206(!is_dir($storage.'/public'),'nenhuma chave exposta em public');
  $perms=fileperms(igua_bing_file($storage))&0777;
  b206(($perms&0007)===0,'sem acesso de outros usuários');
  foreach(['a',"AAA\r\nX-Header: leaked",str_repeat('a',400)] as $invalid){
    $rejected=false;try{igua_bing_key($invalid);}catch(InvalidArgumentException){$rejected=true;}
    b206($rejected,'rejeita chave inválida');
  }
  b206(igua_bing_origin('https://iguatama.online/ ')==='https://iguatama.online','origem normalizada');
  foreach(['http://iguatama.online','https://outra.tld/dir','https://user:pass@iguatama.online','https://iguatama.online:8443'] as $invalid){
    $rejected=false;try{igua_bing_origin($invalid);}catch(InvalidArgumentException){$rejected=true;}
    b206($rejected,'bloqueia origem imprópria: '.$invalid);
  }
  $calls=[];
  $transport=static function(string $method,string $provided,?string $site)use(&$calls,$key):array{
    b206($provided===$key,'chave usada somente no servidor');$calls[]=$method;
    if($method==='GetUserSites')return ['status'=>200,'body'=>json_encode(['d'=>[['Url'=>'https://iguatama.online/','IsVerified'=>true,'AuthenticationCode'=>'NOT_PUBLIC']]])];
    b206($method==='GetFeeds'&&$site==='https://iguatama.online/','consulta sitemap somente do domínio verificado');
    return ['status'=>200,'body'=>json_encode(['d'=>[['Url'=>'https://iguatama.online/sitemap.php','Status'=>'Success','UrlCount'=>20,'Private'=>'secret']]])];
  };
  $res=igua_bing_check($key,'https://iguatama.online',$transport);
  b206($calls===['GetUserSites','GetFeeds']&&$res['verified']===true,'consultou propriedade e feeds');
  b206(count($res['feeds'])===1&&$res['feeds'][0]['count']===20&&!isset($res['feeds'][0]['Private']),'retorno limitado e sem segredos');
  $unverified=igua_bing_check($key,'https://iguatama.online',static fn()=>['status'=>200,'body'=>'{"d":[]}']);
  b206(!$unverified['verified']&&$unverified['feeds']===[],'nenhuma propriedade não inventa feeds');
  foreach([400,403,404,429,503] as $http){
    $failed=false;try{igua_bing_request('GetUserSites',$key,null,static fn()=>['status'=>$http,'body'=>'sensitive']);}catch(RuntimeException $e){$failed=str_contains($e->getMessage(),'HTTP '.$http)&&!str_contains($e->getMessage(),'sensitive');}
    b206($failed,'diagnóstico sanitizado '.$http);
  }
  $failed=false;try{igua_bing_request('GetUserSites',$key,null,static fn()=>['status'=>200,'body'=>'{"not_d":1}']);}catch(RuntimeException){$failed=true;}
  b206($failed,'formato de API inesperado sem falso sucesso');
  igua_bing_remove($storage);
  b206(igua_bing_read($storage)===null,'remoção segura');
  $admin=file_get_contents(__DIR__.'/../public/admin.php');$post=file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php');
  foreach(['bing_save','bing_test','bing_remove'] as $action){
    b206(str_contains($post,"'".$action."'=>'bing'") && str_contains($admin,"'".$action."'=>'manage_settings'")&&str_contains($admin,"'".$action."'=>'sitemap'"),'ACL, roteamento e retorno '.$action);
  }
  $view=file_get_contents(__DIR__.'/../app/admin_views/routes/sitemap.php');
  b206(str_contains($view,'action=bing_test')&&str_contains($view,'action=bing_save')&&str_contains($view,'action=bing_remove')&&str_contains($view,'csrf_field()'),'formulários com CSRF');
  $bus=file_get_contents(__DIR__.'/../app/bus_admin/view_routes.php');
  b206((bool)preg_match('/<button[^>]*data-confirm="Cadastrar os horários da Teixeira/s',$bus),'confirmar somente no botão, não no formulário');
  b206(!preg_match('/<form[^>]*data-confirm="Cadastrar os horários da Teixeira/s',$bus),'formulário não recebe data-confirm');
}finally{
  @unlink(igua_bing_file($storage));@rmdir($storage.'/integrations');@rmdir($storage);
}
echo "Bing 206: armazenamento, privacidade, consulta, erros, roteamento e formulário Teixeira aprovados.\n";
