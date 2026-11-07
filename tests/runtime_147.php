<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
require __DIR__.'/../app/installer.php';
function env147(bool $ok,string $msg):void {if(!$ok)throw new RuntimeException('Falha 1.0.47: '.$msg);echo "OK: $msg\n";}
$hosting=igua_runtime_profile(['HTTPS'=>'on','SERVER_PORT'=>'443','HTTP_HOST'=>'127.0.0.1'], '', '/usr/bin/php','fpm-fcgi');
env147($hosting['kind']==='hosting' && $hosting['https'] && $hosting['db_host']==='localhost','hospedagem e HTTPS independentes de HTTP_HOST');
$termux=igua_runtime_profile(['HTTPS'=>'off','SERVER_PORT'=>'8000'], '/data/data/com.termux/files/usr', '/data/data/com.termux/files/usr/bin/php','cli-server');
env147($termux['kind']==='termux' && !$termux['https'] && $termux['db_host']==='127.0.0.1','Termux reconhecido pelo caminho de execução');
$linux=igua_runtime_profile(['HTTP_USER_AGENT'=>'Termux','HTTP_X_FORWARDED_FOR'=>'127.0.0.1'], '/usr/local','/usr/bin/php','fpm-fcgi');
env147($linux['kind']==='hosting','User-Agent e cabeçalhos HTTP não forjam Termux');
$local=igua_runtime_profile([], '/usr','/usr/bin/php','cli-server');
env147($local['kind']==='local_dev','PHP local sem Termux é diagnosticado separadamente');
$localServer=['REMOTE_ADDR'=>'127.0.0.1','SERVER_ADDR'=>'127.0.0.1'];
env147(igua_auto_install_allowed($termux,$localServer),'provisionamento exclusivamente Termux em loopback');
env147(!igua_auto_install_allowed($hosting,$localServer),'hospedagem não habilita criação de usuário SQL mesmo em loopback');
env147(!igua_auto_install_allowed($termux,['REMOTE_ADDR'=>'192.0.2.1','SERVER_ADDR'=>'127.0.0.1']),'acesso remoto não autoriza criar contas SQL');
env147(!igua_auto_install_allowed($termux,['REMOTE_ADDR'=>'127.0.0.1','SERVER_ADDR'=>'192.0.2.10']),'servidor ligado à rede não autoriza modo privilegiado');
$root=dirname(__DIR__);
$good=igua_webroot_diagnosis(['DOCUMENT_ROOT'=>$root.'/public']);
$bad=igua_webroot_diagnosis(['DOCUMENT_ROOT'=>$root]);
env147($good['ok'] && !$bad['ok'],'bloqueia webroot que exporia storage e app');
$alias=$root.'/public_html';
if (!is_dir($alias))mkdir($alias,0700);
try {
    file_put_contents($alias.'/index.php','<?php');
    $hostedRoot=igua_public_directory($root,['DOCUMENT_ROOT'=>$alias,'SCRIPT_FILENAME'=>$alias.'/index.php']);
    env147(realpath($hostedRoot)===realpath($alias),'hospedagem public_html com app/storage irmãos');
    env147(igua_webroot_diagnosis(['DOCUMENT_ROOT'=>$alias],$hostedRoot)['ok'],'public_html isolado é um DocumentRoot válido');
    env147(igua_public_directory($root,['DOCUMENT_ROOT'=>$alias,'SCRIPT_FILENAME'=>$root.'/public/index.php'])===$root.'/public','não aceita pasta pública alheia ao script executado');
    env147(igua_public_directory($root,[], 'public_html')===$root.'/public_html','CLI pode selecionar uploads da hospedagem por dica validada');
    env147(igua_public_directory($root,[], '../../storage')===$root.'/public','dica CLI não admite caminho arbitrário');
} finally { unlink($alias.'/index.php');rmdir($alias); }

env147(!igua_webroot_diagnosis(['DOCUMENT_ROOT'=>$root.'/app'])['ok'],'rejeita pasta privada como DocumentRoot');
env147(!igua_webroot_diagnosis(['DOCUMENT_ROOT'=>'/nao/existe'])['ok'],'raiz web não verificável falha fechada');
$setup=(string)file_get_contents($root.'/public/setup.php');
env147(str_contains($setup,'if ($autoAvailable)') && str_contains($setup,'if (!$autoAvailable)'), 'instalador muda interface e valida ação no servidor');
env147(str_contains((string)file_get_contents($root.'/app/health.php'),'igua_webroot_diagnosis()'),'Central de Saúde reavalia a segurança da raiz');
env147(str_contains((string)file_get_contents($root.'/public/router.php'),'http_response_code(404)'), 'roteador continua negando rotas inválidas');
echo "Diagnósticos de instalação em dois ambientes validados sem credenciais reais.\n";
