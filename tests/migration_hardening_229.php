<?php
declare(strict_types=1);
$root=sys_get_temp_dir().'/igua-mig-harden-'.bin2hex(random_bytes(6));
foreach(['app','public/uploads','storage/migration/extras/public/uploads','storage/media','storage/backups','bin'] as $folder)mkdir($root.'/'.$folder,0700,true);
define('IGUA_ROOT',$root);define('IGUA_STORAGE',$root.'/storage');define('IGUA_PUBLIC',$root.'/public');
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/migration_package.php';
$n=0;
function okay229(bool $value,string $why):void{global $n;$n++;if(!$value)throw new RuntimeException('Falhou: '.$why);}
function reject229(callable $task,string $why):void{global $n;$n++;try{$task();}catch(Throwable){return;}throw new RuntimeException('Aceitou: '.$why);}
function clean229(string $folder):void{foreach(new FilesystemIterator($folder) as $entry){$p=$entry->getPathname();if($entry->isDir()&&!$entry->isLink())clean229($p);else unlink($p);}rmdir($folder);}
try{
 file_put_contents($root.'/VERSION','1.0.129');
 foreach(['public/setup.php','public/.htaccess','public/.user.ini','public/uploads/.htaccess','app/bootstrap.php','app/backup.php','app/migration_package.php','app/schema.php'] as $file){$path=$root.'/'.$file;if(!is_dir(dirname($path)))mkdir(dirname($path),0700,true);file_put_contents($path,'fixture');}
 $code=igua_migration_code_files($root);
 okay229(isset($code['public/.htaccess'],$code['public/.user.ini'],$code['public/uploads/.htaccess']),'configuração do Apache e PHP incluída');
 okay229(igua_migration_code_allowed('public/.user.ini')&&igua_migration_code_allowed('public/.htaccess'),'permissão específica para arquivos de configuração');
 okay229(!igua_migration_code_allowed('app/config.php')&&!igua_migration_code_allowed('storage/config.php'),'credenciais excluídas');
 $bundle=$root.'/storage/migration/backup.zip';file_put_contents($bundle,'fixture');
 $manifest=['format'=>IGUA_MIGRATION_FORMAT,'version'=>IGUA_MIGRATION_SCHEMA,'site_version'=>'1.0.129','backup'=>'storage/migration/backup.zip','backup_sha256'=>hash_file('sha256',$bundle),'code'=>array_map(static fn($file)=>['sha256'=>hash_file('sha256',$file),'bytes'=>filesize($file)],$code),'extra'=>[]];
 $manifestPath=$root.'/storage/migration/manifest.json';file_put_contents($manifestPath,json_encode($manifest,JSON_THROW_ON_ERROR));
 okay229(igua_migration_payload($root)!==null,'integridade inicial dos arquivos');
 file_put_contents($root.'/public/.user.ini','alterado');
 reject229(fn()=>igua_migration_payload($root),'configuração PHP adulterada');
 file_put_contents($root.'/public/.user.ini','fixture');
 $manifest['code']['public/.htaccess']['sha256']=str_repeat('0',64);file_put_contents($manifestPath,json_encode($manifest,JSON_THROW_ON_ERROR));
 reject229(fn()=>igua_migration_payload($root),'hash incorreto no manifesto');
 $manifest['code']['public/.htaccess']['sha256']=hash_file('sha256',$root.'/public/.htaccess');
 $manifest['version']=1;file_put_contents($manifestPath,json_encode($manifest,JSON_THROW_ON_ERROR));
 reject229(fn()=>igua_migration_payload($root),'manifesto antigo sem garantias rejeitado');
 $manifest['version']=IGUA_MIGRATION_SCHEMA;
 file_put_contents($manifestPath,json_encode($manifest,JSON_THROW_ON_ERROR));
 okay229(igua_migration_site_url('')==='','domínio antigo preservado quando campo em branco');
 okay229(igua_migration_site_url('https://novo.exemplo.com/')==='https://novo.exemplo.com','domínio HTTPS normalizado');
 foreach(['http://site.exemplo.com','https://site.exemplo.com/path','https://site.exemplo.com/?token=1','https://user:pass@site.exemplo.com'] as $url)reject229(fn()=>igua_migration_site_url($url),'URL insegura');
 $p=igua_migration_payload($root);
 $blocked=$root.'/storage/migration/extras/public/uploads/atalho.txt';
 if(function_exists('symlink') && @symlink($root.'/public/.htaccess',$blocked)){
   $p['extras']=['public/uploads/atalho.txt'=>str_repeat('0',64)];
   okay229(!igua_migration_cleanup($p),'limpeza identifica link suspeito');
   okay229(is_file($root.'/public/.htaccess'),'não remove destino de symlink');
   unlink($blocked);
 }
 $publicSetup=file_get_contents(__DIR__.'/../public/setup.php');
 okay229(str_contains($publicSetup,"$".'action!==\'install_migration\'')&&str_contains($publicSetup,'igua_migration_preflight')&&str_contains($publicSetup,'igua_migration_capacity_check'),'sem bypass de migração e com pré-verificação');
 okay229(str_contains($publicSetup,'igua_migration_rollback_empty_schema')&&str_contains($publicSetup,'migrationCleanupPending'),'recuperação de esquema vazio e alerta de limpeza pendente');
 echo "OK migration_hardening_229: {$n} verificações.\n";
}finally{clean229($root);}
