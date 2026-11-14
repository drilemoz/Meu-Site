<?php
declare(strict_types=1);
/** Diagnóstico seguro para Termux, SSH/cPanel e agendadores. Não solicita credenciais. */
if (PHP_SAPI!=='cli') {
    http_response_code(404);
    exit;
}
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
$args=array_slice($argv,1);
if (count(array_diff($args,['--json']))!==0 || count($args)>1) {
    fwrite(STDERR,"Uso: php bin/check-environment.php [--json]\n");
    exit(1);
}
$profile=igua_runtime_profile();
$rows=igua_requirements();
foreach ($rows as &$item) {
    if ($item['id']==='session') {
        $item['ok']=false;
        $item['required']=false;
        $item['current']='Sessões não são verificáveis em CLI; confira /setup.php no navegador.';
    }
    if ($item['id']==='webroot') {
        $item['ok']=true;
        $item['required']=false;
        $item['current']='DocumentRoot precisa ser confirmado no navegador da instalação.';
    }
    if ($item['id']==='migration_upload') {
        $item['current'].=' (valor deste PHP CLI; confirme os limites HTTP no instalador)';
    }
}
unset($item);
$ready=igua_required_ready($rows);
$report=[
    'version'=>trim((string)file_get_contents(IGUA_ROOT.'/VERSION')),
    'environment'=>$profile['label'],
    'public_directory'=>IGUA_PUBLIC,
    'cli_preflight_ok'=>$ready,
    'web_checks_pending'=>['session','webroot','local_listener','HTTPS','migration_upload'],
    'requirements'=>$rows,
];
if (in_array('--json',$args,true)) {
    echo json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT|JSON_INVALID_UTF8_SUBSTITUTE)."\n";
} else {
    echo 'Meu Site '.$report['version'].' — '.$report['environment']."\n";
    echo 'Pasta pública: '.$report['public_directory']."\n";
    foreach ($rows as $item) {
        $flag=$item['id']==='session' || $item['id']==='webroot' ? 'WEB' : ($item['ok'] ? 'OK' : ($item['required'] ? 'FALHA' : 'OPCIONAL'));
        echo '['.$flag.'] '.$item['title'].' — '.$item['current']."\n";
        if (!$item['ok'] && ($item['required'] || $item['id']==='migration_upload')) echo '    Correção: '.$item['fix']."\n";
    }
    echo $ready ? "Verificações CLI aprovadas. Termine o diagnóstico no /setup.php.\n"
                 : "Há requisitos pendentes. Corrija-os antes da instalação.\n";
}
exit($ready?0:2);
