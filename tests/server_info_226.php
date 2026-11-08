<?php
declare(strict_types=1);
require __DIR__.'/../app/server_info.php';
$count=0;
function check226(bool $ok,string $why):void{global $count;$count++;if(!$ok)throw new RuntimeException('Falhou: '.$why);}
check226(igua_server_info_ini_bytes('8M')===8*1048576,'limite M');
check226(igua_server_info_ini_bytes('1G')===1073741824,'limite G');
check226(igua_server_info_ini_bytes('128K')===131072,'limite K');
check226(igua_server_info_ini_bytes('-1')===null,'limite sem teto');
check226(igua_server_info_ini_bytes('unknown')===null,'valor inválido');
check226(igua_server_info_platform('nginx/1.20')==='Nginx','família simplificada');
check226(igua_server_info_platform('my-sensitive-server-example')==='Não identificado','não expor software arbitrário');
check226(igua_server_info_size(1048576)==='1,0 MiB','formatação binária');
$_SERVER['SERVER_SOFTWARE']='example-secret-host/11';
$snapshot=igua_server_info_snapshot(null,__DIR__);
check226($snapshot['server_family']==='Não identificado','oculta nome de servidor');
check226($snapshot['db']['status']==='Indisponível','sem conexão não inventa estado');
check226($snapshot['disk']===null || (isset($snapshot['disk']['free'],$snapshot['disk']['total']) && $snapshot['disk']['free']<=$snapshot['disk']['total']),'disco consistente');
check226(count($snapshot['extensions'])===9,'extensões enumeradas sem revelar ini privado');
check226(!isset($snapshot['hostname']) && !isset($snapshot['ip']) && !isset($snapshot['root_path']),'sem endereço e caminho privado');
function h($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function admin_link($action):string{return '/admin.php?action='.$action;}
$db=null;
ob_start();require __DIR__.'/../app/admin_views/routes/server_info.php';$html=ob_get_clean();
check226(str_contains($html,'Informações do servidor') && str_contains($html,'Atualizar dados'),'visual renderizado');
check226(!str_contains($html,'example-secret-host') && !str_contains($html,__DIR__),'sem informações sensíveis');
$admin=file_get_contents(__DIR__.'/../public/admin.php');
check226(str_contains($admin,"'server_info'=>'manage_settings'"),'autorização administrativa');
check226(str_contains($admin,"'maintenance_reset','server_info','files'"),'rota permitida explicitamente');
$layout=file_get_contents(__DIR__.'/../app/admin_views/layout.php');
check226(str_contains($layout,"$".'toolsActions=[\'files\',\'server_info\']') && str_contains($layout,'Informações do Servidor'),'submenu Ferramentas');
check226(!str_contains(file_get_contents(__DIR__.'/../app/server_info.php'),'shell_exec('),'não executa shell');
echo "OK server_info_226: {$count} verificações.\n";
