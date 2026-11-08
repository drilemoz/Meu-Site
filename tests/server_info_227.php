<?php
declare(strict_types=1);
require __DIR__.'/../app/server_info.php';
$count=0;
function ok227(bool $value,string $label):void{global $count;$count++;if(!$value)throw new RuntimeException('Falhou: '.$label);}
ok227(igua_server_info_ini_flag('On')===true,'on');
ok227(igua_server_info_ini_flag('0')===false,'off');
ok227(igua_server_info_ini_flag('stderr')===null,'desconhecido não classificado');
ok227(igua_server_info_samesite('Lax')==='Lax','sameSite Lax');
ok227(igua_server_info_samesite('Unexpected<secret>')==='Não informado','sameSite fechado');
$capabilities=igua_server_info_capabilities(['pdo_mysql'=>true,'zip'=>false,'gd'=>true,'fileinfo'=>false],true,true);
ok227(count($capabilities)===6,'6 recursos');
ok227($capabilities[0]['ok'] && $capabilities[1]['ok'] && !$capabilities[2]['ok'],'capacidade PHP, PDO e ZIP');
ok227($capabilities[5]['ok'],'uploads');
$_SERVER['SERVER_SOFTWARE']='hidden-identification-<secret>';
$snapshot=igua_server_info_snapshot(null,__DIR__);
ok227(count($snapshot['capabilities'])===6,'capacidade via snapshot');
ok227(array_key_exists('max_file_uploads',$snapshot) && array_key_exists('file_uploads',$snapshot),'limites de upload');
ok227(count($snapshot['php_settings'])===6,'6 diretivas restritas');
ok227(!isset($snapshot['server_software'])&&!isset($snapshot['hostname'])&&!isset($snapshot['root_path']),'sem detalhes privados');
function h($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function admin_link(string $action):string{return '/admin.php?action='.$action;}
$db=null;
ob_start();require __DIR__.'/../app/admin_views/routes/server_info.php';$html=ob_get_clean();
ok227(str_contains($html,'Recursos disponíveis para o site'),'resumo de compatibilidade');
ok227(str_contains($html,'Configurações do PHP')&&str_contains($html,'Cookie de sessão: HttpOnly'),'sessão');
ok227(str_contains($html,'Arquivos por envio'),'limites de upload');
ok227(!str_contains($html,'hidden-identification')&&!str_contains($html,__DIR__),'sem vazamentos na tela');
$css=file_get_contents(__DIR__.'/../public/assets/css/modules/19-server-info.css');
ok227(str_contains($css,'.server-info-capabilities')&&str_contains($css,'1.0.127'),'estilo compatibilidade');
echo "OK server_info_227: {$count} verificações.\n";
