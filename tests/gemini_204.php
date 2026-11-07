<?php
declare(strict_types=1);
const IGUA_STORAGE=__DIR__.'/__not_a_real_storage_dir';
require_once __DIR__.'/../app/gemini_api.php';
function g204(bool $condition,string $message):void {if(!$condition)throw new RuntimeException('Gemini 1.0.104: '.$message);}
foreach([401,403,404,429,500,502,503,504] as $status){
 try{igua_gemini_extract(['status'=>$status,'body'=>'{"error":{"message":"SECRET_MUST_NOT_LEAK"}}']);throw new RuntimeException('Aceitou HTTP '.$status);}
 catch(RuntimeException $e){g204($e->getCode()===$status,'código HTTP conservado no diagnóstico '.$status);$diag=igua_gemini_diagnostic($e);g204($diag['http_status']===$status && !str_contains(json_encode($diag),'SECRET_MUST_NOT_LEAK'),'diagnóstico sem resposta bruta '.$status);}
}
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/integrations.php');
$posGoogle=strpos($view,'id="google" data-int-section');$posGemini=strpos($view,'id="google-gemini"');$posDiag=strpos($view,'id="diagnostico" data-int-section');
g204($posGoogle<$posGemini && $posGemini<$posDiag,'Gemini sob Serviços Google e fora do Catálogo');
g204(str_contains($view,'class="igua-gemini-main-actions"')&&str_contains($view,'data-gemini-test-open'),'salvar e testar na mesma linha');
g204(str_contains($view,'gemini-test-dialog')&&str_contains($view,'data-gemini-test-result'),'teste em janela própria');
g204(str_contains($view,'int-gemini-remove-password')&&str_contains($view,'Digite sua senha para remover'),'remoção com senha em bloco estilizado');
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/integrations.php');
$router=file_get_contents(__DIR__.'/../public/admin.php');
g204(str_contains($controller,"'gemini_ajax'")&&str_contains($router,"'integrations_gemini_test' &&"),'resposta JSON autenticada e via CSRF');
echo "Gemini 1.0.104: código HTTP, orientação, credenciais protegidas e interface verificados. Sem chave real.\n";
