<?php
declare(strict_types=1);
$base=sys_get_temp_dir().'/igua-files-'.bin2hex(random_bytes(6));
mkdir($base,0700,true);mkdir($base.'/public/uploads',0700,true);mkdir($base.'/storage',0700,true);
define('IGUA_PUBLIC',$base.'/public');
require __DIR__.'/../app/file_manager.php';
$tests=0;
function ok221(bool $value,string $reason): void {global $tests;$tests++;if(!$value)throw new RuntimeException('Falhou: '.$reason);}
function fails221(callable $fn,string $reason): void {global $tests;$tests++;try{$fn();}catch(InvalidArgumentException|RuntimeException $e){return;}throw new RuntimeException('Não bloqueou: '.$reason);}
function rm221(string $folder): void {
    if(!is_dir($folder))return;
    foreach(new FilesystemIterator($folder) as $item){if($item->isLink()||$item->isFile())unlink($item->getPathname());else rm221($item->getPathname());}
    rmdir($folder);
}
try {
 ok221(igua_files_list('')===[],'pasta vazia inicial');
 foreach(['../storage','/etc/passwd','x/../../storage','x\\y','%2e%2e','.env','a//b',"a\0b"] as $path)
    fails221(static fn()=>igua_files_relative($path),'travessia '.$path);
 foreach(['shell.php','config.phtml','.htaccess','file..txt','ok.svg','a.html','payload.js','x.php.txt'] as $file)
    ok221(!igua_files_allowed($file),'extensão ou nome proibido '.$file);
 ok221(igua_files_allowed('documento.pdf'),'PDF permitido');
 ok221(igua_files_allowed('foto.webp'),'WebP permitido');
 ok221(igua_files_root_ready(false)===null,'pasta não criada por GET');
 igua_files_new_folder('','materias');
 ok221(is_dir($base.'/public/uploads/arquivos/materias'),'mkdir permitido');
 ok221(igua_files_resolve('materias',true)!==null,'diretório resolvido');
 fails221(static fn()=>igua_files_new_folder('','some.php'),'pasta com extensão');
 fails221(static fn()=>igua_files_new_folder('','materias'),'duplicação');
 $special=igua_files_special('ads.txt');
 ok221($special===$base.'/public/ads.txt','ads especial na raiz');
 ok221(igua_files_special('config.php')===null,'somente especiais explícitos');
 igua_files_write_text($special,"google.com, pub-123, DIRECT, xyz\n",'missing');
 ok221(is_file($special),'ads criado');
 ok221(str_contains(igua_files_text_read($special),'google.com'),'leitura texto');
 fails221(static fn()=>igua_files_write_text($special,'alterado','missing'),'hash stale');
 $expected=hash_file('sha256',$special);igua_files_write_text($special,"novo\n",$expected);
 ok221(file_get_contents($special)==="novo\n",'edição conservadora');
 fails221(static fn()=>igua_files_write_text($special,"\0",hash_file('sha256',$special)),'binário impedido');
 $text=$base.'/public/uploads/arquivos/materias/nota.txt';
 igua_files_write_text($text,"Olá\n",'missing');
 ok221(count(igua_files_list('materias'))===1,'listagem');
 igua_files_rename('materias/nota.txt','relato.txt');
 ok221(is_file(dirname($text).'/relato.txt'),'renomear');
 fails221(static fn()=>igua_files_rename('materias/relato.txt','relato.pdf'),'trocar extensão');
 fails221(static fn()=>igua_files_delete('../storage/segredo.txt'),'excluir fora do escopo');
 file_put_contents($base.'/storage/segredo.txt','segredo');
 $link=$base.'/public/uploads/arquivos/link';
 if(function_exists('symlink')&&symlink($base.'/storage',$link)){
  fails221(static fn()=>igua_files_resolve('link/segredo.txt'),'symlink');
  fails221(static fn()=>igua_files_resolve('link',true),'symlink diretório');
 }
 fails221(static fn()=>igua_files_delete('materias'),'pasta não vazia');
 igua_files_delete('materias/relato.txt');igua_files_delete('materias');
 ok221(!file_exists($text),'exclusão');
 ok221(is_file($base.'/storage/segredo.txt'),'fora do escopo preservado');
 $admin=file_get_contents(__DIR__.'/../public/admin.php');
 $layout=file_get_contents(__DIR__.'/../app/admin_views/layout.php');
 $routing=file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php');
 ok221(str_contains($layout,'🧰 Ferramentas') && str_contains($layout,"admin_link('files')"),'submenu ferramentas');
 ok221(str_contains($admin,"'files'=>'manage_settings'") && str_contains($admin,"$".'action' . "==='files_download'"),'ACL/GET download');
 ok221(str_contains($routing,"'files_save'=>'file_manager'"),'POST no router');
 echo "OK file_manager_221: {$tests} verificações.\n";
}finally{rm221($base);}
