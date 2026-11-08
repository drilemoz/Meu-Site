<?php
declare(strict_types=1);
$root=sys_get_temp_dir().'/igua-cmd-'.bin2hex(random_bytes(6));
mkdir($root.'/public/uploads',0700,true);mkdir($root.'/app',0700,true);mkdir($root.'/storage',0700,true);
file_put_contents($root.'/app/index.php','<?php echo 1;');
file_put_contents($root.'/app/config.php','<?php /* secrets */');
file_put_contents($root.'/storage/secret.key','KEY');
define('IGUA_ROOT',$root);define('IGUA_PUBLIC',$root.'/public');
require __DIR__.'/../app/file_manager.php';
$checks=0;
function ok223(bool $result,string $message):void{global $checks;$checks++;if(!$result)throw new RuntimeException('Falhou: '.$message);}
function rejected223(callable $fn,string $message):void{global $checks;$checks++;try{$fn();}catch(InvalidArgumentException|RuntimeException $e){return;}throw new RuntimeException('Não rejeitou: '.$message);}
function cleanup223(string $root):void{foreach(new FilesystemIterator($root) as $file){if($file->isDir()&&!$file->isLink())cleanup223($file->getPathname());else unlink($file->getPathname());}rmdir($root);}
function h($value):string{return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');}
function admin_link(string $action):string{return '/admin.php?action='.$action;}
function csrf_field():string{return '<input type="hidden" name="csrf" value="test">';}
function igua_updater_version():string{return '1.0.123';}
try {
 $_SESSION=[];$_GET=['folder'=>''];
 ob_start();require __DIR__.'/../app/admin_views/routes/files.php';$rootHtml=ob_get_clean();
 ok223(str_contains($rootHtml,'Baixar seleção em ZIP') && str_contains($rootHtml,'app'),'tela da raiz renderiza barra de seleção');
 $_GET=['folder'=>'public/uploads/arquivos'];
 ob_start();require __DIR__.'/../app/admin_views/routes/files.php';$managedHtml=ob_get_clean();
 ok223(str_contains($managedHtml,'Compactar') && str_contains($managedHtml,'Enviar arquivo'),'área de uploads virtual renderiza antes de criar pasta');
 $_GET=[];
 igua_files_new_folder('','origem');igua_files_new_folder('','destino');
 file_put_contents($root.'/public/uploads/arquivos/origem/primeiro.txt','um');
 file_put_contents($root.'/public/uploads/arquivos/origem/segundo.txt','dois');
 $origem='public/uploads/arquivos/origem';$destino='public/uploads/arquivos/destino';
 ok223(igua_fm_permissions('app/index.php')!=='Indisponível','leitura de permissões da raiz');
 rejected223(fn()=>igua_fm_chmod('app/index.php','0644'),'chmod no código bloqueado');
 rejected223(fn()=>igua_fm_chmod('storage/secret.key','0644'),'chmod storage bloqueado');
 rejected223(fn()=>igua_fm_chmod($origem.'/primeiro.txt','0777'),'chmod perigoso bloqueado');
 igua_fm_chmod($origem.'/primeiro.txt','0600');
 ok223(igua_fm_permissions($origem.'/primeiro.txt')==='0600','modo 0600 aplicado');
 igua_fm_chmod($origem.'/primeiro.txt','0644');
 ok223(igua_fm_permissions($origem.'/primeiro.txt')==='0644','modo 0644 aplicado');
 $session=[];
 igua_fm_clipboard($session,'copy',[$origem.'/primeiro.txt']);
 ok223(igua_fm_clipboard_info($session)['mode']==='copy','clipboard em sessão');
 ok223(igua_fm_paste($session,$destino)===1,'cópia feita');
 ok223(file_get_contents($root.'/'.$destino.'/primeiro.txt')==='um','cópia íntegra');
 rejected223(fn()=>igua_fm_paste($session,$destino),'não sobrescreve cópia');
 igua_fm_clipboard($session,'cut',[$origem.'/segundo.txt']);
 ok223(igua_fm_paste($session,$destino)===1,'recortar e colar');
 ok223(!file_exists($root.'/'.$origem.'/segundo.txt') && is_file($root.'/'.$destino.'/segundo.txt'),'movimentação real');
 ok223(igua_fm_clipboard_info($session)===null,'clipboard limpo após mover');
 rejected223(fn()=>igua_fm_clipboard($session,'copy',['app/index.php']),'origem executável fora do gerenciado');
 rejected223(fn()=>igua_fm_clipboard($session,'copy',[$origem,$origem.'/primeiro.txt']),'seleção redundante');
 rejected223(fn()=>igua_fm_clipboard($session,'copy',['../../etc/passwd']),'travessia');
 igua_fm_clipboard($session,'copy',[$origem]);
 rejected223(fn()=>igua_fm_paste($session,$origem),'não copia pasta para dentro dela própria');
 rejected223(fn()=>igua_fm_paste($session,'app'),'colagem em código bloqueada');
 rejected223(fn()=>igua_fm_delete_selection(['app/index.php']),'exclusão de código bloqueada');
 ok223(igua_fm_delete_selection([$destino])===1,'exclusão recursiva');
 ok223(!file_exists($root.'/'.$destino),'pasta e conteúdo excluídos');
 $link=$root.'/public/uploads/arquivos/origem/link';
 if(function_exists('symlink')&&@symlink($root.'/storage',$link)){
  rejected223(fn()=>igua_fm_delete_selection([$origem]),'não apaga árvore com symlink');
  unlink($link);
 }
 ok223(is_file($root.'/storage/secret.key'),'storage privado preservado');
 if(!class_exists('ZipArchive')){
  rejected223(fn()=>igua_fm_zip_selection([$origem],'public/uploads/arquivos','backup.zip'),'zip sem extensão explica indisponibilidade');
  rejected223(fn()=>igua_fm_zip_extract($origem.'/primeiro.txt'),'extração sem extensão');
  rejected223(fn()=>igua_fm_export_zip(['app/index.php']),'export sem extensão');
 }
 $ui=file_get_contents(__DIR__.'/../app/admin_views/routes/files.php');
 $controller=file_get_contents(__DIR__.'/../app/admin_controllers/file_manager.php');
 foreach(['files-bulk','files-select-all','files-bulk-delete','Baixar seleção em ZIP','Compactar ZIP','Descompactar','Aplicar permissões','Colar nesta pasta'] as $feature){
  ok223(str_contains($ui,$feature),'comando na tela: '.$feature);
 }
 foreach(['clipboard_clear','bulk_delete','unzip','chmod','export_zip'] as $feature){
  ok223(str_contains($controller,$feature),'rota POST: '.$feature);
 }
 echo 'OK file_manager_223: '.$checks." verificações.\n";
}finally {cleanup223($root);}
