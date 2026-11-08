<?php
declare(strict_types=1);
$root=sys_get_temp_dir().'/igua-explorer-'.bin2hex(random_bytes(5));
mkdir($root,0700,true);mkdir($root.'/app',0700);mkdir($root.'/public/uploads',0700,true);
mkdir($root.'/storage',0700);mkdir($root.'/app/admin_views',0700);
file_put_contents($root.'/app/index.php',"<?php echo 'hello';\n");
file_put_contents($root.'/app/config.php',"<?php return ['secret' => 'X'];\n");
file_put_contents($root.'/storage/private.json','segredo');
file_put_contents($root.'/public/ads.txt','google.com, pub-123, DIRECT, X');
file_put_contents($root.'/VERSION','1.0.122');
define('IGUA_ROOT',$root);define('IGUA_PUBLIC',$root.'/public');
require __DIR__.'/../app/file_manager.php';
$tests=0;
function ok222(bool $good,string $name): void {global $tests;$tests++;if(!$good)throw new RuntimeException('Falhou: '.$name);}
function fail222(callable $test,string $name):void{global $tests;$tests++;try{$test();}catch(InvalidArgumentException|RuntimeException $e){return;}throw new RuntimeException('Aceitou: '.$name);}
function cleanup222(string $path): void {foreach(new FilesystemIterator($path) as $item){if($item->isDir()&&!$item->isLink())cleanup222($item->getPathname());else unlink($item->getPathname());}rmdir($path);}
try{
 $rootList=igua_files_project_list('')['items'];
 $names=array_column($rootList,'name');
 ok222(in_array('app',$names,true) && in_array('public',$names,true) && in_array('storage',$names,true),'raiz do projeto visível');
 $protected=array_column($rootList,'blocked','name');
 ok222($protected['storage']===true,'armazenamento privado bloqueado');
 ok222(str_contains(igua_files_project_resolve('app/index.php',false),'/app/index.php'),'fonte permitido em leitura');
 ok222(igua_files_project_editable('app/index.php')===false,'PHP não editável');
 ok222(igua_files_project_editable('public/ads.txt')===true,'ads editável');
 ok222(igua_files_project_text_previewable('app/index.php'),'fonte texto para prévia');
 ok222(igua_files_project_text_previewable('VERSION'),'versão visualizável');
 ok222(igua_files_project_list('public/uploads')['items'][0]['name']==='arquivos','pasta gerenciada virtual');
 ok222(igua_files_project_list('public/uploads/arquivos')['items']===[],'GET não cria pasta');
 ok222(!file_exists($root.'/public/uploads/arquivos'),'GET sem efeitos de gravação');
 foreach(['../storage/private.json','/etc/passwd','app/../storage','app//index.php','app\\index.php','app/.env','app/.git/config'] as $rel){
   fail222(static fn()=>igua_files_project_resolve($rel),'travessia '.$rel);
 }
 foreach(['storage/private.json','app/config.php','.env','public/secret.txt'] as $rel){
   fail222(static fn()=>igua_files_project_resolve($rel),'segredo '.$rel);
 }
 fail222(static fn()=>igua_files_project_managed_relative('app/index.php'),'PHP fora da área de escrita');
 fail222(static fn()=>igua_files_project_managed_relative('public/uploads/arquivos/../config.php'),'travessia na escrita');
 ok222(igua_files_project_managed_relative('public/uploads/arquivos')==='','área de escrita base');
 ok222(igua_files_project_managed_relative('public/uploads/arquivos/teste.txt')==='teste.txt','arquivo gerenciado');
 igua_files_new_folder('','documentos');
 ok222(is_dir($root.'/public/uploads/arquivos/documentos'),'escrita área antiga preservada');
 $link=$root.'/app/atalho';
 if(function_exists('symlink') && @symlink($root.'/storage', $link)){
     fail222(static fn()=>igua_files_project_resolve('app/atalho/private.json'),'symlink privado');
 }
 // Verifica layout sem exigir MariaDB; nunca renderiza PHP real do site.
 $page=file_get_contents(__DIR__.'/../app/admin_views/routes/files.php');
 ok222(!str_contains($page,'Biblioteca de mídias') && !str_contains($page,'Arquivos essenciais do site'),'blocos antigos removidos');
 ok222(str_contains($page,'Arquivos do projeto') && str_contains($page,'igua_files_project_list'),'tela exclusiva com pastas do site');
 ok222(str_contains($page,'files-create-menu'),'ações discretas recolhidas');
 $admin=file_get_contents(__DIR__.'/../public/admin.php');
 ok222(str_contains($admin,"'files_download'"), 'rota autenticada de download');
 ok222(str_contains($admin,"igua_files_project_download"), 'download de arquivo do projeto');
 echo "OK file_manager_222: {$tests} verificações.\n";
}finally{cleanup222($root);}
