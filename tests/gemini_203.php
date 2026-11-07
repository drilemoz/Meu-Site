<?php
declare(strict_types=1);
const IGUA_STORAGE=__DIR__.'/__not_a_real_storage_dir';
require_once __DIR__.'/../app/gemini_api.php';
function gem203(bool $condition,string $message):void {if(!$condition)throw new RuntimeException('Gemini v1.0.103: '.$message);echo "OK: $message\n";}
$path=sys_get_temp_dir().'/igua_gemini_'.bin2hex(random_bytes(7)).'/credentials.json';
$key='AQ.'.str_repeat('b',40);
try {
    gem203(igua_gemini_read($path)===null,'sem chave cadastrada');
    gem203(igua_gemini_model('gemini-3.8-flash')==='gemini-3.8-flash','modelo validado');
    foreach(['../../etc/passwd','http://localhost','gemini-../secret','gemini-foo/bar'] as $bad){
        try{igua_gemini_model($bad);throw new RuntimeException('Modelo perigoso aceito: '.$bad);}catch(InvalidArgumentException){}
    }
    gem203(true,'modelos arbitrários e caminhos rejeitados');
    igua_gemini_store($key,'gemini-3.8-flash',true,$path);
    $saved=igua_gemini_read($path);
    gem203($saved!==null && $saved['key']===$key && $saved['enabled']===true,'segredo gravado e lido somente no caminho privado');
    $permission=fileperms($path)&0777;
    gem203($permission===0600,'permissão privada 0600');
    $job=igua_gemini_prompt('summary','Notícia teste','Fato relatado de forma parcial.','Aconteceu o evento em Iguatama.');
    gem203($job['field']==='summary'&&str_contains($job['prompt'],'Não invente fatos'),'instruções editoriais e target conhecidos');
    try{igua_gemini_prompt('delete_all','a','b','c');throw new RuntimeException('Tarefa arbitrária aceita');}catch(InvalidArgumentException){}
    gem203(true,'tarefas restritas ao editor');
    $extract=igua_gemini_extract(['status'=>200,'body'=>json_encode(['candidates'=>[['content'=>['parts'=>[['text'=>'Uma frase de teste.']]],'finishReason'=>'STOP']]])]);
    gem203($extract==='Uma frase de teste.','resposta Gemini validada');
    foreach([401,403,404,429,503] as $code){
        try{igua_gemini_extract(['status'=>$code,'body'=>'{"error":{"message":"SECRET_TEST_MUST_NOT_LEAK"}}']);throw new RuntimeException('HTTP '.$code.' aceito');}
        catch(RuntimeException $e){gem203(!str_contains($e->getMessage(),'SECRET_TEST_MUST_NOT_LEAK'),'erro HTTP '.$code.' sanitizado');}
    }
    try{igua_gemini_extract(['status'=>200,'body'=>'{"candidates":[{"finishReason":"SAFETY"}]}']);throw new RuntimeException('Bloqueio desconsiderado');}catch(RuntimeException $e){gem203(str_contains($e->getMessage(),'bloqueou'),'bloqueio de segurança respeitado');}
    $root=dirname(__DIR__);
    $routes=file_get_contents($root.'/app/admin_controllers/post_routes.php');
    $admin=file_get_contents($root.'/public/admin.php');
    foreach(['integrations_gemini_save','integrations_gemini_test','integrations_gemini_remove','news_gemini_suggest'] as $action){
        gem203(str_contains($routes,"'".$action."'")&&str_contains($admin,"'".$action."'"),'ação '.$action.' roteada e autorizada');
    }
    $view=file_get_contents($root.'/app/admin_views/routes/integrations.php');
    gem203(str_contains($view,'Testar API')&&str_contains($view,'type="password" id="int-gemini-key"'),'formulário de chave e teste presente');
    gem203(!str_contains($view,$key),'nenhuma chave de teste inserida em HTML');
}finally {if(is_file($path))unlink($path);if(is_dir(dirname($path)))rmdir(dirname($path));}
echo "Gemini 1.0.103: testes offline aprovados. API real não consultada sem chave do proprietário.\n";
