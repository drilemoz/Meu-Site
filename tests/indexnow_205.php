<?php
declare(strict_types=1);
require_once __DIR__.'/support/integrations_source.php';
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/indexnow.php';
function ix205(bool $ok,string $reason):void{if(!$ok)throw new RuntimeException('IndexNow 205: '.$reason);}
$root=sys_get_temp_dir().'/indexnow205-'.bin2hex(random_bytes(6));
mkdir($root,0700);mkdir($root.'/public',0700);mkdir($root.'/storage',0700);
try{
 $cfg=igua_indexnow_setup('https://iguatama.online',$root.'/public',$root.'/storage');
 ix205(igua_indexnow_key_valid($cfg['key']) && strlen($cfg['key'])===32,'chave segura');
 ix205(igua_indexnow_file_ready($cfg,$root.'/public'),'arquivo público contém chave');
 ix205(igua_indexnow_read($root.'/storage')['key']===$cfg['key'],'configuração privada legível');
 $again=igua_indexnow_setup('https://iguatama.online',$root.'/public',$root.'/storage');
 ix205($again['key']===$cfg['key'],'não gera chave repetida');
 $wrong=false;try{igua_indexnow_setup('https://outra.example',$root.'/public',$root.'/storage');}catch(RuntimeException){$wrong=true;}
 ix205($wrong,'impede mudança silenciosa de domínio');
 $catalog=[
  ['kind'=>'site','loc'=>'https://iguatama.online/index.php','path'=>'/index.php'],
  ['kind'=>'noticia','loc'=>'https://iguatama.online/noticia/a','path'=>'/noticia/a'],
  ['kind'=>'noticia','loc'=>'https://iguatama.online/noticia/b','path'=>'/noticia/b'],
  ['kind'=>'aplicativo','loc'=>'https://iguatama.online/uaicortes/privacidade','path'=>'/uaicortes/privacidade'],
  ['kind'=>'noticia','loc'=>'https://golpista.test/roubo','path'=>'/roubo'],
 ];
 $urls=igua_indexnow_select_urls($catalog,'https://iguatama.online','',10);
 ix205(count($urls)===3,'lote inclui apenas notícias e legais no domínio');
 ix205(igua_indexnow_select_urls($catalog,'https://iguatama.online','/noticia/b',10)===[ 'https://iguatama.online/noticia/b'],'notificação específica');
 $rejected=false;try{igua_indexnow_select_urls($catalog,'https://iguatama.online','/admin.php',10);}catch(InvalidArgumentException){$rejected=true;}
 ix205($rejected,'rejeita URL fora do sitemap');
 $called=0;
 $ok=igua_indexnow_submit($cfg,$urls,$root.'/public',static function(string $endpoint,string $body)use(&$called):array{
    $called++;$data=json_decode($body,true);
    ix205($endpoint==='https://api.indexnow.org/indexnow','endpoint fixo');
    ix205($data['host']==='iguatama.online'&&count($data['urlList'])===3,'host e batch corretos');
    ix205(str_ends_with($data['keyLocation'],$data['key'].'.txt'),'keyLocation válida');
    return ['status'=>202];
 });
 ix205($called===1&&$ok['status']===202&&$ok['count']===3,'aceita recebido 202 sem declarar indexação');
 foreach([400,403,422,429,503] as $status){
   $rejected=false;try{igua_indexnow_submit($cfg,$urls,$root.'/public',static fn()=>['status'=>$status]);}catch(RuntimeException $e){$rejected=str_contains($e->getMessage(),'HTTP '.$status);}
   ix205($rejected,'explica HTTP '.$status);
 }
 $rejected=false;try{igua_indexnow_submit($cfg,['https://evil.example/steal'],$root.'/public',static fn()=>['status'=>200]);}catch(InvalidArgumentException){$rejected=true;}
 ix205($rejected,'bloqueia host externo');
 $rejected=false;try{igua_indexnow_submit($cfg,['https://iguatama.online.evil.test/path'],$root.'/public',static fn()=>['status'=>200]);}catch(InvalidArgumentException){$rejected=true;}
 ix205($rejected,'bloqueia prefixo enganoso');
 ix205(str_contains(file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php'),"'indexnow_submit'=>'indexnow'"),'rota de ação conhecida');
 $router=file_get_contents(__DIR__.'/../public/admin.php');
 ix205(str_contains($router,"'indexnow_setup'=>'manage_settings'")&&str_contains($router,"'indexnow_setup','indexnow_submit'"),'ACL e allowlist');
 $ui=file_get_contents(__DIR__.'/../app/admin_views/routes/sitemap.php');
 ix205(str_contains($ui,'indexnow_setup')&&str_contains($ui,'indexnow_submit')&&str_contains($ui,'csrf_field()'),'UI exige CSRF');
 $catalogView=igua_test_integrations_source();
 ix205(str_contains($catalogView,'/admin.php?action=sitemap#indexnow'),'catálogo aponta para integração real');
}finally{
 foreach(glob($root.'/public/*')?:[] as $f)@unlink($f);
 foreach(glob($root.'/storage/integrations/*')?:[] as $f)@unlink($f);
 @rmdir($root.'/storage/integrations');@rmdir($root.'/public');@rmdir($root.'/storage');@rmdir($root);
}
echo "IndexNow 205: ativação, domínio, URLs, envio, diagnóstico e ACL aprovados.\n";
