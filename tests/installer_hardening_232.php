<?php
declare(strict_types=1);
require_once __DIR__.'/../app/installer.php';
require_once __DIR__.'/../app/setup_access.php';
$checks=0;
function ok232(bool $condition,string $message): void {global $checks;$checks++;if(!$condition)throw new RuntimeException('Falhou: '.$message);}
function reject232(callable $fn,string $message):void {global $checks;$checks++;try{$fn();}catch(DomainException|RuntimeException $e){return;}throw new RuntimeException('Aceitou: '.$message);}
class ProvisionRecorder232 extends PDO {
    public array $sql=[];
    public string $failOn='';
    public function __construct() {}
    public function exec(string $statement): int|false {
        $this->sql[]=$statement;
        if($this->failOn!=='' && str_starts_with($statement,$this->failOn))throw new PDOException('Falha simulada');
        return 1;
    }
    public function quote(string $string,int $type=PDO::PARAM_STR):string|false{return "'".str_replace("'","''",$string)."'";}
}
$root=sys_get_temp_dir().'/igua-setup-232-'.bin2hex(random_bytes(5));
mkdir($root,0700,true);
try{
    $key=igua_setup_access_create($root);
    ok232(strlen($key)===48 && preg_match('/\A[a-f0-9]{48}\z/D',$key)===1,'chave forte 192 bits');
    ok232(igua_setup_access_read($root)===$key,'chave privada acessível');
    reject232(static fn()=>igua_setup_access_create($root),'não substitui chave existente');
    $session=[];$now=time();
    ok232(!igua_setup_access_valid($key,$session,$now),'sem desbloqueio');
    ok232(!igua_setup_access_unlock(str_repeat('0',48),$key,$session,$now),'chave errada recusa');
    ok232(igua_setup_access_unlock($key,$key,$session,$now),'chave correta aceita');
    ok232(igua_setup_access_valid($key,$session,$now+120),'liberação de sessão válida');
    ok232(!igua_setup_access_valid($key,$session,$now+1801),'sessão expirada');
    ok232(!igua_setup_access_valid(str_repeat('a',48),$session,$now),'não confunde outra chave');
    $link=$root.'/.setup-access.key';unlink($link);
    file_put_contents($root.'/secret.txt',$key);
    if(function_exists('symlink')&&@symlink($root.'/secret.txt',$link)){
        ok232(igua_setup_access_read($root)===null,'chave por symlink bloqueada');unlink($link);
    }
    $p=new ProvisionRecorder232();$p->failOn='GRANT';$events=[];
    try{
        igua_provision_hosting_local($p,'site_test_232','127.0.0.1',3306,static function($event,$value)use(&$events):void{$events[]=[$event,$value];});
        throw new RuntimeException('Provisionamento com GRANT falho não abortou.');
    }catch(PDOException){}
    ok232(count($events)===2 && $events[0][0]==='database_created' && $events[1][0]==='user_created','registra banco e conta antes de GRANT');
    ok232(count(array_filter($p->sql,static fn($q)=>str_starts_with($q,'DROP ')))===0,'nunca DROP automático em falha parcial');
    $p2=new ProvisionRecorder232();$p2->failOn='CREATE USER';$events2=[];
    try{igua_provision_hosting_local($p2,'site_test_232b','localhost',3306,static function($e,$v)use(&$events2){$events2[]=$e;});throw new RuntimeException('Não falhou');}catch(PDOException){}
    ok232($events2===['database_created'],'falha CREATE USER ainda registra banco');
    ok232(igua_mysql_tls_options(false)===[],'TLS desligado não exige CA');
    reject232(static fn()=>igua_mysql_tls_options(true,$root),'TLS sem CA rejeitado');
    $setup=file_get_contents(__DIR__.'/../public/setup.php');
    foreach(['unlock_setup','igua_setup_access_valid','igua_setup_probe_schema_permissions','mysql_tls','reuse_privileged_mysql','app_username','provisionProgress','igua_setup_access_file()'] as $needle){
        ok232(str_contains($setup,$needle),'instalador tem '.$needle);
    }
    $migration=file_get_contents(__DIR__.'/../app/migration_package.php');
    ok232(str_contains($migration,"'storage/.setup-access.key'"),'pacote portátil inclui chave privada');
    $bootstrap=file_get_contents(__DIR__.'/../app/bootstrap.php');
    ok232(str_contains($bootstrap,'igua_mysql_tls_options'),'conexão final persiste validação TLS');
    echo 'OK installer_hardening_232: '.$checks." verificações.\n";
}finally{
    foreach(new FilesystemIterator($root) as $file)unlink($file->getPathname());rmdir($root);
}
