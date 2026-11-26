<?php
declare(strict_types=1);
$root=sys_get_temp_dir().'/igua-report-'.bin2hex(random_bytes(6));
mkdir($root,0700);define('IGUA_ROOT',$root);define('IGUA_PUBLIC',$root.'/public');define('IGUA_STORAGE',$root.'/storage');
require __DIR__.'/../app/updater.php';require __DIR__.'/../app/schema_migrations.php';require __DIR__.'/../app/health.php';
class ReportPDO163 extends PDO {public function __construct(){} public function prepare(string $query,array $options=[]): PDOStatement|false {throw new RuntimeException('SECRET_DB_PASSWORD');} public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs): PDOStatement|false {throw new RuntimeException('SECRET_DB_PASSWORD');} public function getAttribute(int $attribute): mixed{return 'fixture MariaDB';}}
function verify163(bool $value,string $description):void{if(!$value)throw new RuntimeException($description);echo "OK: $description\n";}
try{
    foreach(['app','public','storage','storage/backups','storage/updater','storage/updater/jobs','storage/updater/vps','storage/updater/vps/private'] as $dir)mkdir($root.'/'.$dir,0750);
    file_put_contents($root.'/VERSION',"1.0.163\n");file_put_contents($root.'/storage/config.php','SECRET_DB_PASSWORD');
    file_put_contents(IGUA_UPDATE_GITHUB_AUTH,'{"token":"SECRET_GITHUB_PAT"}');
    file_put_contents(IGUA_UPDATE_DIR.'/vps/status.json',json_encode(['stage'=>'failed','at'=>time(),'token'=>'SECRET_PROGRESS_TOKEN','message'=>'SECRET_RAW_MESSAGE']));
    $_SESSION=[];
    igua_updater_report_event('diagnose_zip',['ok'=>false,'version'=>'1.0.164','package_schema'=>40,'server_schema'=>39,'token'=>'SECRET_EVENT_TOKEN','diagnostic'=>igua_updater_diagnostic(new RuntimeException('schema diferente'))],new RuntimeException('SECRET_EXCEPTION_TEXT'));
    putenv('IGUA_DEPLOY_VPS=1');
    $check=igua_updater_preflight(new ReportPDO163());
    verify163(in_array('PHP 8.1+',array_column($check['checks'],'name'),true),'preserva verificações iniciais no modo VPS');
    $report=igua_updater_report_text(new ReportPDO163(),$_SESSION['igua_update_report_events']);
    foreach(['1.0.163','Schema esperado pelo código: 39','Schema registrado: indisponível','PERMISSÕES','storage','vps/private/.code.lock','package_schema','40','diagnose_zip','Última etapa: failed','REGISTROS TÉCNICOS'] as $text)verify163(str_contains($report,$text),'relatório contém '.$text);
    verify163(!str_contains($report,'SECRET_'),'relatório exclui segredos, mensagens brutas e tokens');
    $view=file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');$admin=file_get_contents(__DIR__.'/../public/admin.php');
    verify163(substr_count($view,'data-updater-report')>=5,'download disponível em todas as telas principais');
    verify163(str_contains($admin,'Content-Disposition: attachment')&&str_contains($admin,"isset(\$_GET['updater_report'])"),'rota de relatório autenticada');
    $src=file_get_contents(__DIR__.'/../app/updater.php');verify163(str_contains($src,"\$workerChecks=\$health['checks']"),'dados do worker não sobrescrevem a vistoria');
}finally{putenv('IGUA_DEPLOY_VPS');igua_updater_delete_tree($root);}
