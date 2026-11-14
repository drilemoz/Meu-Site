<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
function check139(bool $ok,string $message):void{if(!$ok)throw new RuntimeException('Falhou: '.$message);echo "OK: $message\n";}
$base=sys_get_temp_dir().'/meusite-setup-'.bin2hex(random_bytes(5));
mkdir($base,0700);mkdir($base.'/public',0700);
try {
  $result=igua_prepare_runtime_directories($base,$base.'/public');
  foreach (['storage','backups','media','logs','uploads'] as $key)check139($result[$key]===true,'cria '.$key);
  check139(is_file($base.'/public/uploads/.htaccess'),'upload novo exige .htaccess');
  check139(str_contains((string)file_get_contents($base.'/public/uploads/.htaccess'),'FilesMatch'),'scripts são bloqueados pelo Apache');
  file_put_contents($base.'/storage/backups/backup.json','conteudo anterior');
  check139(igua_prepare_runtime_directories($base,$base.'/public')['backups'],'segunda execução idempotente');
  check139(file_get_contents($base.'/storage/backups/backup.json')==='conteudo anterior','não destrói backup');
  unlink($base.'/storage/backups/backup.json');
  // Não seguir ou modificar diretórios controlados via symlink.
  $external=$base.'/external';mkdir($external,0700);
  rmdir($base.'/storage/backups');symlink($external,$base.'/storage/backups');
  check139(igua_prepare_runtime_directories($base,$base.'/public')['backups']===false,'symlink de backup rejeitado');
  check139(count(scandir($external))===2,'destino externo inalterado');
  unlink($base.'/storage/backups');rmdir($external);
} finally {
  foreach(['public/uploads/.htaccess','public/uploads','public','storage/backups','storage/media','storage/logs','storage','external'] as $part) {
    $path=$base.'/'.$part;if(is_link($path)||is_file($path))@unlink($path);elseif(is_dir($path))@rmdir($path);
  }
  @rmdir($base);
}
check139(igua_brand_name()==='Meu Site','nome padrão do produto');
check139(igua_brand_version()===trim((string)file_get_contents(__DIR__.'/../VERSION')),'versão do rodapé');
$setup=file_get_contents(__DIR__.'/../public/setup.php');
check139(str_contains($setup,'<strong>Meu Site</strong>'),'identidade da instalação');
check139(str_contains($setup,'$databaseDefault') && str_contains(file_get_contents(__DIR__.'/../app/environment.php'),"'meu_site'"),'banco padrão local e manual na hospedagem');
check139(str_contains(file_get_contents(__DIR__.'/../app/backup.php'),'iguanews-backup'),'formato legado preservado');
check139(file_exists(__DIR__.'/../storage/backups/.gitkeep'),'marcador no pacote');
