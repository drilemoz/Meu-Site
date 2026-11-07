<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/editorial.php';
function sec164(bool $v,string $name):void{if(!$v)throw new RuntimeException('Segurança 1.0.64: '.$name);echo "OK: $name\n";}
$root=sys_get_temp_dir().'/igua-security-test-'.bin2hex(random_bytes(5));
@mkdir($root,0700,true);
$_SERVER['REMOTE_ADDR']='127.0.0.1';
$now=strtotime('2026-11-06 17:40:00');
try {
    sec164(igua_security_event('login_failed',null,'8.8.8.8',$now,$root),'grava tentativa de login com IP de origem');
    sec164(igua_security_event('login_limited','login','8.8.8.8',$now+1,$root),'grava limite de tentativas');
    sec164(igua_security_event('login_success',null,'2001:4860:4860::8888',$now+2,$root),'grava IPv6 normalizado');
    sec164(!igua_security_event('unknown_event','abc','8.8.8.8',$now,$root),'não aceita tipos desconhecidos');
    sec164(igua_security_event('csrf_rejected','<svg/onload=alert(1)>','8.8.8.8',$now+3,$root),'descarta destino arbitrário');
    $f=$root.'/'.date('Y-m-d',$now).'.jsonl';$data=file_get_contents($f);
    sec164(!str_contains($data,'svg')&&!str_contains($data,'password')&&!str_contains($data,'Cookie'),'não registra valores de formulários nem metadados arbitrários');
    $history=igua_security_history(1,'all','all',null,100,$now+10,$root);
    sec164($history['summary']['total']===4&&$history['summary']['critical']===1,'conta eventos reais e gravidades');
    $only=igua_security_history(1,'warning','login_failed','8.8.8.8',100,$now+10,$root);
    sec164($only['matched']===1&&count($only['rows'])===1,'filtros por IP, tipo e gravidade');
    sec164(!igua_security_blocked('8.8.8.8',$now,$root),'não existe bloqueio automático por alerta');
    igua_security_change_block('8.8.8.8',60,1,$now,$root);
    sec164(igua_security_blocked('8.8.8.8',$now+1,$root),'bloqueio por prazo restrito ao acesso do painel');
    sec164(!igua_security_blocked('8.8.8.8',$now+3601,$root),'bloqueio expira sem cron');
    igua_security_change_block('8.8.8.8',0,1,$now+10,$root);
    sec164(!igua_security_blocked('8.8.8.8',$now+20,$root),'desbloqueio imediato');
    try{igua_security_change_block('127.0.0.1',60,1,$now,$root);throw new RuntimeException('Aceitou bloquear próprio IP');}
    catch(RuntimeException $e){sec164(str_contains($e->getMessage(),'próprio acesso'),'impede bloquear próprio IP');}
    try{igua_security_change_block('10.1.1.1',60,1,$now,$root);throw new RuntimeException('Aceitou IP privado');}
    catch(RuntimeException $e){sec164(str_contains($e->getMessage(),'públicos'),'recusa endereços privados');}
    try{igua_security_change_block('8.8.8.8',1,1,$now,$root);throw new RuntimeException('Aceitou prazo arbitrário');}
    catch(InvalidArgumentException $e){sec164(str_contains($e->getMessage(),'Prazo'),'rejeita prazos arbitrários');}
    file_put_contents($root.'/2026-09-01.jsonl','old');
    igua_security_cleanup($root,$now);
    sec164(!is_file($root.'/2026-09-01.jsonl'),'rotação elimina logs antigos');
    $layout=file_get_contents(__DIR__.'/../app/admin_views/layout.php');
    $admin=file_get_contents(__DIR__.'/../public/admin.php');
    $view=file_get_contents(__DIR__.'/../app/admin_views/security.php');
    sec164(str_contains($layout,"admin_link('security')")&&str_contains($admin,"'security'=>'manage_settings'"),'central integrada ao menu e somente para administrador');
    sec164(str_contains($view,'csrf_field()')&&str_contains($view,'confirm_password'),'bloqueios e desbloqueios exigem CSRF e senha');
    sec164(str_contains($admin,"igua_security_blocked()")&&str_contains($admin,"http_response_code(429)"),'restrição aplicada antes do login e do remember');
    sec164(str_contains($admin,"igua_security_event('login_failed')")&&str_contains($admin,"igua_security_event('login_success')"),'login instrumentado com sucesso e falha');
    $format=file_get_contents(__DIR__.'/../app/schema_migrations.php');
    sec164(str_contains($format,'IGUA_SCHEMA_REVISION = 36'),'esquema MySQL não alterado para atualizar por ZIP v2');
    echo "Segurança 1.0.64: testes de arquivo e integração estática aprovados.\n";
}finally{
    foreach(glob($root.'/*')?:[] as $f){if(is_file($f))@unlink($f);}
    @rmdir($root);
}
