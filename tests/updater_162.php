<?php
/** Atualizador 1.0.62: pacotes v2 sem chave, v1 legado autenticado e proteções. */
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function check162(bool $value,string $description): void {
    if(!$value)throw new RuntimeException('FALHOU: '.$description);
    echo 'OK: '.$description."\n";
}
function rejected162(callable $fn,string $message):void{
    try{$fn();}catch(Throwable $e){check162(true,$message);return;}
    check162(false,$message);
}
check162(version_compare(igua_updater_version(),'1.0.62','>='),'versão 1.0.62 ou superior');
$files=[
 ['path'=>'VERSION','size'=>7,'sha256'=>hash('sha256',"1.0.62\n")],
 ['path'=>'public/admin.php','size'=>8,'sha256'=>str_repeat('a',64)],
 ['path'=>'app/updater.php','size'=>12,'sha256'=>str_repeat('b',64)]
];
$m=['format'=>IGUA_UPDATE_FORMAT,'version'=>'1.0.63','schema_revision'=>36,'min_php'=>'8.1.0','files'=>$files];
$raw=json_encode($m,JSON_THROW_ON_ERROR);
$check=igua_updater_check_manifest($raw);
check162($check['signature_status']==='unsigned','aceita formato v2 sem release.sig');
check162(count($check['files'])===3,'mantém manifesto SHA-256 obrigatório');
$m['format']=IGUA_UPDATE_LEGACY_FORMAT;
$legacy=json_encode($m,JSON_THROW_ON_ERROR);
rejected162(static fn()=>igua_updater_check_manifest($legacy),'v1 não pode perder assinatura');
if(function_exists('sodium_crypto_sign_keypair')){
    $pair=sodium_crypto_sign_keypair();
    $pub=base64_encode(sodium_crypto_sign_publickey($pair));
    $sig=sodium_crypto_sign_detached($legacy,sodium_crypto_sign_secretkey($pair));
    check162(igua_updater_check_manifest($legacy,$sig,$pub)['signature_status']==='signed','v1 assinado ainda é aceito');
    rejected162(static fn()=>igua_updater_check_manifest($legacy,random_bytes(64),$pub),'v1 assinado adulterado recusado');
    $m['format']=IGUA_UPDATE_FORMAT;$v2=json_encode($m,JSON_THROW_ON_ERROR);
    rejected162(static fn()=>igua_updater_check_manifest($v2,random_bytes(64),$pub),'assinatura inválida presente no v2 não pode ser ignorada');
}
$m['format']=IGUA_UPDATE_FORMAT;
$m['files'][1]['path']='public/uploads/foto.jpg';
rejected162(static fn()=>igua_updater_check_manifest(json_encode($m,JSON_THROW_ON_ERROR)),'bloqueia atualização de uploads');
$m['files'][1]['path']='public/ads.txt';
rejected162(static fn()=>igua_updater_check_manifest(json_encode($m,JSON_THROW_ON_ERROR)),'preserva ads.txt da hospedagem');
$m['files'][1]['path']='app/../storage/config.php';
rejected162(static fn()=>igua_updater_check_manifest(json_encode($m,JSON_THROW_ON_ERROR)),'bloqueia traversal');
$m['files'][1]['path']='public/admin.php';$m['files'][1]['sha256']='0';
rejected162(static fn()=>igua_updater_check_manifest(json_encode($m,JSON_THROW_ON_ERROR)),'rejeita hash inválido');
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');
check162(str_contains($controller,'password_verify($pwd,$hash)') && str_contains($controller,'igua_update_auth_failures'),'instalação protegida por senha e limite de tentativas');
check162(substr_count($view,'name="confirm_password"')===3,'senha nos dois modos de instalação e restauração');
check162(substr_count($view,'name="confirm_install"')===2,'instalações exigem aceite explícito');
check162(!str_contains($view,'Todo pacote continua exigindo assinatura Ed25519'),'tela deixou de impor chave perdida');
$preflight=igua_updater_preflight(null);
check162(in_array('Ed25519 legado',array_column($preflight['checks'],'name'),true),'sodium opcional para v2');
echo "OK: novos testes sem banco real.\n";
