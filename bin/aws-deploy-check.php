<?php
/** Vistoria não destrutiva (somente arquivos temporários), executar como iguanews-deploy. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
if(!igua_updater_vps_mode())throw new RuntimeException('Defina IGUA_DEPLOY_VPS=1 no ambiente CLI.');
$report=igua_updater_vps_worker_probe();
foreach($report['checks'] as $name=>$ok){echo ($ok?'OK   ':'FALHA').' '.$name."
";}
echo $report['ready']?"Vistoria da conta deploy aprovada.
":"Vistoria reprovada. NÃO habilite o timer.
";
exit($report['ready']?0:1);
