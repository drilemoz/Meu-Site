<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/backup.php';
function health_assert(bool $ok,string $message): void {if(!$ok)throw new RuntimeException('Falha: '.$message); echo "OK: {$message}\n";}
$sample='senha-altamente-secreta-123';
igua_health_log('TEST_EVENT',new RuntimeException($sample));
$logs=igua_health_log_lines();
health_assert(count($logs)>=1 && str_contains($logs[0],'TEST_EVENT'),'evento técnico registrado');
health_assert(!str_contains(implode("\n",$logs),$sample),'mensagem e segredo não ficam no log');
$dir=IGUA_STORAGE.'/backups';if(!is_dir($dir))mkdir($dir,0700,true);
$name='manual-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.json';
$tables=array_fill_keys(BACKUP_TABLES,[]);
$tables['users']=[['id'=>1,'name'=>'Editor de exemplo','role'=>'admin','active'=>1,'password_hash'=>'hash-demo']];
$raw=json_encode(['format'=>'iguanews-backup','version'=>12,'tables'=>$tables],JSON_THROW_ON_ERROR);
$path=$dir.'/'.$name;
try {
    file_put_contents($path,$raw);
    $info=igua_health_verify_backup($name);
    health_assert($info['version']===12 && $info['rows']===1 && $info['tables']===count(BACKUP_TABLES),'backup válido lido sem restaurar');
    health_assert($info['sha256']===hash('sha256',$raw),'SHA-256 do backup corresponde');
    file_put_contents($path,'{"invalido":true}');
    try {igua_health_verify_backup($name);throw new LogicException('Aceitou backup adulterado');}
    catch(RuntimeException $e){health_assert(str_contains($e->getMessage(),'inválida'),'rejeita backup corrompido');}
    try {igua_health_verify_backup('../storage/config.php');throw new LogicException('Aceitou nome malicioso');}
    catch(RuntimeException $e){health_assert(str_contains($e->getMessage(),'inválido'),'nome de arquivo perigoso recusado');}
} finally { @unlink($path); }
$src=file_get_contents(__DIR__.'/../public/admin.php');
health_assert(str_contains($src,"'health'=>'manage_settings'") && str_contains($src,"'health_verify'=>'backup'"),'diagnóstico e verificação têm permissões');
health_assert(str_contains($src,'check_csrf();'),'ações POST validam CSRF');
echo "Testes de saúde isolados concluídos. Sem MariaDB real.\n";
