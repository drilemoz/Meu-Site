<?php
declare(strict_types=1);
// Contrato do ZIP gerador: executa integração real quando ZipArchive estiver habilitado.
if(!class_exists('ZipArchive')){echo "SKIP migration_archive_228: ZipArchive não disponível no PHP local.\n";exit(0);}
$root=sys_get_temp_dir().'/igua-migration-zip-'.bin2hex(random_bytes(5));
foreach(['app','public/uploads/arquivos','public/assets','storage/media','storage/backups','bin'] as $folder)mkdir($root.'/'.$folder,0700,true);
define('IGUA_ROOT',$root);define('IGUA_STORAGE',$root.'/storage');define('IGUA_PUBLIC',$root.'/public');
define('MEDIA_BUNDLE_MAX_ARCHIVE_BYTES',350*1024*1024);define('MEDIA_BUNDLE_MAX_MEDIA_BYTES',300*1024*1024);
// Somente para testar o encapsulamento ZIP, sem requerer banco ou exportar registros pessoais.
function make_media_bundle(PDO $db):string{$p=IGUA_STORAGE.'/backups/fixture.zip';file_put_contents($p,'fixture-dados');return $p;}
class MigrationFakePDO extends PDO{public function __construct(){}}
require __DIR__.'/../app/migration_package.php';
function clean228b(string $dir):void{foreach(new FilesystemIterator($dir) as $f){$p=$f->getPathname();if($f->isDir()&&!$f->isLink())clean228b($p);else unlink($p);}rmdir($dir);}
try{
 file_put_contents($root.'/VERSION','1.0.128');
 foreach(['public/setup.php','app/bootstrap.php','app/backup.php','app/migration_package.php','app/schema.php'] as $f)file_put_contents($root.'/'.$f,'<?php // code');
 file_put_contents($root.'/public/ads.txt','google.com, pub-example, DIRECT, example');
 file_put_contents($root.'/public/uploads/.htaccess','Deny from all');
 file_put_contents($root.'/app/config.php','senha-privada');
 file_put_contents($root.'/storage/media/documento.docx','doc-fake');
 file_put_contents($root.'/public/uploads/arquivos/foto.jpg','foto-fake');
 $name=igua_make_migration_package(new MigrationFakePDO());
 $zip=new ZipArchive();if($zip->open($name)!==true)throw new RuntimeException('ZIP criado não abre.');
 try{
   foreach(['VERSION','app/bootstrap.php','public/setup.php','public/ads.txt','storage/migration/backup.zip','storage/migration/manifest.json','storage/migration/extras/public/uploads/arquivos/foto.jpg','storage/migration/extras/storage/media/documento.docx','storage/backups/.gitkeep'] as $required){
    if($zip->locateName($required)===false)throw new RuntimeException('Ausente: '.$required);
   }
   foreach(['app/config.php','storage/config.php','storage/security/token.json','public/uploads/arquivos/trojan.php'] as $forbidden){
    if($zip->locateName($forbidden)!==false)throw new RuntimeException('Não deveria incluir: '.$forbidden);
   }
   $manifest=json_decode((string)$zip->getFromName('storage/migration/manifest.json'),true,20,JSON_THROW_ON_ERROR);
   if($manifest['format']!==IGUA_MIGRATION_FORMAT || !hash_equals($manifest['backup_sha256'],hash('sha256','fixture-dados')))throw new RuntimeException('Manifesto inválido.');
   if($zip->getFromName('storage/migration/extras/public/uploads/arquivos/foto.jpg')!=='foto-fake')throw new RuntimeException('Mídia foi alterada no ZIP.');
 }finally{$zip->close();}
 echo "OK migration_archive_228: ZIP válido, instalador/mídias incluídos e segredos excluídos.\n";
}finally{clean228b($root);}
