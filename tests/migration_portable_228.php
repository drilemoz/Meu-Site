<?php
declare(strict_types=1);
$root=sys_get_temp_dir().'/igua-migration-'.bin2hex(random_bytes(6));
foreach(['app','public/uploads/arquivos','storage/media','storage/migration/extras/public/uploads/arquivos','storage/backups','bin'] as $folder)mkdir($root.'/'.$folder,0700,true);
define('IGUA_ROOT',$root);define('IGUA_STORAGE',$root.'/storage');define('IGUA_PUBLIC',$root.'/public');
require_once __DIR__.'/../app/backup.php';
require_once __DIR__.'/../app/migration_package.php';
$tests=0;
function check228(bool $yes,string $message):void{global $tests;$tests++;if(!$yes)throw new RuntimeException('Falha: '.$message);}
function reject228(callable $fn,string $message):void{global $tests;$tests++;try{$fn();}catch(Throwable $e){return;}throw new RuntimeException('Aceitou: '.$message);}
function purge228(string $dir):void{foreach(new FilesystemIterator($dir) as $f){$p=$f->getPathname();if($f->isDir()&&!$f->isLink())purge228($p);else unlink($p);}rmdir($dir);}
try{
 file_put_contents($root.'/VERSION','1.0.128');
 foreach(['public/setup.php','app/bootstrap.php','app/backup.php','app/migration_package.php','app/schema.php'] as $file)file_put_contents($root.'/'.$file,'<?php // teste');
 file_put_contents($root.'/app/config.php','<?php return ["secret"=>"hide"];');
 file_put_contents($root.'/app/news.php','<?php echo "test";');
 file_put_contents($root.'/public/uploads/arquivos/foto.png','IMG');
 file_put_contents($root.'/public/uploads/arquivos/trojan.php','<?php echo 123;');
 file_put_contents($root.'/storage/media/doc.pdf','PDF');
 file_put_contents($root.'/storage/media/malware.sh','bash');
 $files=igua_migration_code_files($root);
 check228(isset($files['public/setup.php'])&&isset($files['app/migration_package.php'])&&isset($files['VERSION']),'instalador e código incluídos');
 check228(!isset($files['app/config.php']) && !isset($files['public/uploads/arquivos/trojan.php']),'segredos e uploads executáveis fora do código');
 $extras=igua_migration_extra_files($root);
 check228(isset($extras['public/uploads/arquivos/foto.png'])&&isset($extras['storage/media/doc.pdf']),'imagens e anexos incluídos');
 check228(!isset($extras['public/uploads/arquivos/trojan.php'])&&!isset($extras['storage/media/malware.sh']),'arquivos executáveis recusados');
 foreach(['storage/config.php','public/uploads/../config.php','public/uploads/arquivos/novo.php','public/uploads/a/.env','/etc/passwd','public/uploads/\x.txt'] as $path){check228(!igua_migration_extra_allowed($path),'caminho de mídia inseguro rejeitado: '.$path);}
 $bundle=$root.'/storage/migration/backup.zip';file_put_contents($bundle,'simulado-banco');
 $media=$root.'/storage/migration/extras/public/uploads/arquivos/foto.png';file_put_contents($media,'IMG');
 $manifest=['format'=>IGUA_MIGRATION_FORMAT,'version'=>1,'site_version'=>'1.0.128','backup'=>'storage/migration/backup.zip','backup_sha256'=>hash_file('sha256',$bundle),'extra'=>['public/uploads/arquivos/foto.png'=>hash_file('sha256',$media)]];
 file_put_contents($root.'/storage/migration/manifest.json',json_encode($manifest));
 $p=igua_migration_payload($root);
 check228(is_array($p)&&$p['bundle']===$bundle,'backup portátil detectado');
 file_put_contents($media,'MODIFICADO');
 reject228(fn()=>igua_migration_payload($root),'alteração de mídia é detectada');
 file_put_contents($media,'IMG');
 $manifest['site_version']='1.0.999';file_put_contents($root.'/storage/migration/manifest.json',json_encode($manifest));
 reject228(fn()=>igua_migration_payload($root),'versão diferente rejeitada');
 $manifest['site_version']='1.0.128';file_put_contents($root.'/storage/migration/manifest.json',json_encode($manifest));
 $p=igua_migration_payload($root);
 igua_migration_cleanup($p);
 check228(!file_exists($bundle)&&!file_exists($media)&&is_file($root.'/public/uploads/arquivos/foto.png'),'limpa somente payload e mantém mídia original');
 $setup=file_get_contents(__DIR__.'/../public/setup.php');
 $admin=file_get_contents(__DIR__.'/../public/admin.php');
 $downloads=file_get_contents(__DIR__.'/../app/admin_controllers/downloads.php');
 $backups=file_get_contents(__DIR__.'/../app/admin_views/routes/backups.php');
 check228(str_contains($setup,'Instalação limpa')&&str_contains($setup,'Migrar meu portal')&&str_contains($setup,"'install_migration'")&&str_contains($setup,'igua_migration_restore'),'instalação dupla e restauração integrada');
 check228(str_contains($admin,"'download_migration'=>'backup'")&&str_contains($downloads,'igua_make_migration_package'),'download autenticado registrado');
 check228(str_contains($backups,'Baixar pacote para migrar hospedagem')&&str_contains($backups,'csrf_field()'),'ação exibida com CSRF');
 echo 'OK migration_portable_228: '.$tests." verificações.\n";
}finally{purge228($root);}
