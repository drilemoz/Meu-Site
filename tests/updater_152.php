<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function check_update(bool $pass,string $name):void{if(!$pass){fwrite(STDERR,"FALHOU: $name\n");exit(1);}echo "OK: $name\n";}
check_update(igua_updater_safe_path('public/admin.php'),'aceita arquivo público');
check_update(igua_updater_safe_path('app/updater.php'),'aceita módulo privado');
check_update(igua_updater_safe_path('public/.htaccess'),'aceita configuração pública protegida');
check_update(!igua_updater_safe_path('public/uploads/2026/11/private.jpg'),'não substitui imagens');
check_update(!igua_updater_safe_path('storage/config.php'),'não substitui credenciais');
check_update(!igua_updater_safe_path('app/../storage/config.php'),'bloqueia traversal');
check_update(!igua_updater_safe_path('/etc/passwd'),'bloqueia caminho absoluto');
check_update(!igua_updater_safe_path('app/dir\\file.php'),'bloqueia barra invertida');
check_update(!igua_updater_safe_path('app/payload.sql'),'bloqueia SQL no ZIP');
check_update(version_compare(igua_updater_version(),'1.0.60','>='),'versão do código 1.0.60');
if(!function_exists('sodium_crypto_sign_keypair')){echo "IGNORADO: sodium indisponível para teste de assinatura\n";exit(0);}
$pair=sodium_crypto_sign_keypair();$public=base64_encode(sodium_crypto_sign_publickey($pair));
$files=[
['path'=>'VERSION','size'=>7,'sha256'=>str_repeat('a',64)],
['path'=>'public/admin.php','size'=>12,'sha256'=>str_repeat('b',64)],
['path'=>'app/updater.php','size'=>24,'sha256'=>str_repeat('c',64)],
];
$manifest=['format'=>IGUA_UPDATE_FORMAT,'version'=>'1.0.53','schema_revision'=>36,'min_php'=>'8.1.0','files'=>$files];
$raw=json_encode($manifest,JSON_THROW_ON_ERROR);
$sig=sodium_crypto_sign_detached($raw,sodium_crypto_sign_secretkey($pair));
check_update(igua_updater_check_manifest($raw,$sig,$public)['version']==='1.0.53','verifica Ed25519 assinado');
try{igua_updater_check_manifest($raw.' ', $sig,$public);check_update(false,'rejeita conteúdo adulterado');}catch(RuntimeException $e){check_update(str_contains($e->getMessage(),'Assinatura'),'rejeita conteúdo adulterado');}
try{igua_updater_check_manifest($raw,random_bytes(64),$public);check_update(false,'rejeita assinatura inválida');}catch(RuntimeException $e){check_update(true,'rejeita assinatura inválida');}
$manifest['files'][2]['path']='app/../storage/config.php';$bad=json_encode($manifest,JSON_THROW_ON_ERROR);
$badSig=sodium_crypto_sign_detached($bad,sodium_crypto_sign_secretkey($pair));
try{igua_updater_check_manifest($bad,$badSig,$public);check_update(false,'rejeita arquivo fora da raiz');}catch(RuntimeException $e){check_update(true,'rejeita arquivo fora da raiz');}
$manifest['files'][2]=$files[1];$bad=json_encode($manifest,JSON_THROW_ON_ERROR);
$badSig=sodium_crypto_sign_detached($bad,sodium_crypto_sign_secretkey($pair));
try{igua_updater_check_manifest($bad,$badSig,$public);check_update(false,'rejeita entrada duplicada');}catch(RuntimeException $e){check_update(true,'rejeita entrada duplicada');}
echo "Testes do atualizador concluídos. Ainda requer validação Plesk com ZipArchive e MySQL reais.\n";
