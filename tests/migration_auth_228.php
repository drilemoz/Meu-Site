<?php
declare(strict_types=1);
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/migration_package.php';
$archive=sys_get_temp_dir().'/igua-mig-auth-'.bin2hex(random_bytes(7)).'.zip';
$entry='db/manual-20261108-193000-a1b2c3.json';
$tables=array_fill_keys(BACKUP_TABLES,[]);
$tables['users']=[['id'=>1,'name'=>'Admin','email'=>'admin@teste.invalid','role'=>'admin','active'=>1,'password_hash'=>password_hash('SenhaBemForte123!',PASSWORD_DEFAULT)]];
$json=json_encode(['format'=>'iguanews-backup','version'=>12,'created_at'=>date(DATE_ATOM),'tables'=>$tables],JSON_THROW_ON_ERROR);
$manifest=json_encode(['format'=>'iguanews-media-bundle','version'=>2,'db'=>$entry,'db_sha256'=>hash('sha256',$json),'files'=>[]],JSON_THROW_ON_ERROR);
if(!class_exists('ZipArchive')){
 class ZipArchive{
  public int $numFiles=2;
  public static array $mock=[];
  public function open(string $path):bool{return is_file($path);}
  public function statName(string $name):array|false{return isset(self::$mock[$name])?['size'=>strlen(self::$mock[$name]),'name'=>$name]:false;}
  public function getFromName(string $name):string|false{return self::$mock[$name]??false;}
  public function statIndex(int $idx):array|false{$k=array_keys(self::$mock)[$idx]??null;return $k===null?false:['name'=>$k,'size'=>strlen(self::$mock[$k])];}
  public function close():bool{return true;}
 }
 ZipArchive::$mock=['manifest.json'=>$manifest,$entry=>$json];
 file_put_contents($archive,'ZIP_TESTE_SIMULADO');
}else{
 $zip=new ZipArchive();if($zip->open($archive,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Não criou ZIP de teste');
 $zip->addFromString('manifest.json',$manifest);$zip->addFromString($entry,$json);$zip->close();
}
try{
 $p=['bundle'=>$archive];
 if(!igua_migration_authorized($p,'admin@teste.invalid','SenhaBemForte123!'))throw new RuntimeException('Não aceitou administrador correto');
 if(igua_migration_authorized($p,'admin@teste.invalid','senha-errada'))throw new RuntimeException('Aceitou senha incorreta');
 if(igua_migration_authorized($p,'outro@teste.invalid','SenhaBemForte123!'))throw new RuntimeException('Aceitou e-mail incorreto');
 if(igua_migration_authorized($p,'admin@teste.invalid',''))throw new RuntimeException('Aceitou senha vazia');
 $preflight=igua_migration_preflight($p);
 if(($preflight['rows']??0)!==1 || ($preflight['media_files']??-1)!==0)throw new RuntimeException('Falha na pré-verificação do backup fictício');
 echo "OK migration_auth_228: conta antiga obrigatória antes de restaurar banco.\n";
}finally{if(is_file($archive))unlink($archive);}
