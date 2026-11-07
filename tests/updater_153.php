<?php
/** Testes da vistoria 1.0.53 — somente leitura, sem MariaDB real. */
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function test153(bool $ok,string $name):void {if(!$ok)throw new RuntimeException('FALHOU: '.$name);echo "OK: $name\n";}
test153(igua_updater_ini_bytes('128M')===134217728,'conversão 128M');
test153(igua_updater_ini_bytes('2g')===2147483648,'conversão 2G');
test153(igua_updater_ini_bytes('512K')===524288,'conversão 512K');
test153(igua_updater_ini_bytes('-1')===-1,'sem limite');
test153(igua_updater_ini_bytes('invalido')===0,'limite inválido não é considerado ilimitado');
$updir=IGUA_STORAGE.'/updater';$before=is_dir($updir);
$report=igua_updater_preflight(null);
test153(isset($report['ok'],$report['checks'],$report['warnings'],$report['failures']),'estrutura do relatório');
test153(is_array($report['checks'])&&count($report['checks'])>=12,'vistoria cobre servidor, banco, privacidade e escrita');
test153($before===is_dir($updir),'vistoria não cria diretório do atualizador');
$labels=array_column($report['checks'],'name');
foreach(['Ed25519 legado','Leitura e validação de ZIP','Dados privados fora da web','Layout de publicação','Leitura do MySQL','Atualização pendente','Escrita do código'] as $label)
    test153(in_array($label,$labels,true),'verificação '.$label);
$manifest=['files'=>[['path'=>'app/updater.php'],['path'=>'public/index.php']]];
$report2=igua_updater_preflight(null,$manifest);
test153(is_array($report2['checks']),'vistoria de lista específica de arquivos');
$unsafe=['files'=>[['path'=>'app/../storage/config.php']]];
$report3=igua_updater_preflight(null,$unsafe);
test153($report3['ok']===false,'bloqueia caminho proibido');
test153(in_array('falha',array_column($report3['checks'],'status'),true),'relata a falha sem editar arquivos');
$source=file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
test153(str_contains($source,"$"."operation==='preview'") && str_contains($source,'igua_updater_preview_zip'),'painel oferece simulação');
$updater=file_get_contents(__DIR__.'/../app/updater.php');
test153(str_contains($updater, 'Pré-atualização bloqueada:'),'instalação aplica bloqueio de pré-atualização');
echo "Simulação completa ainda exige ZipArchive, MariaDB e testes no Plesk.\n";
