<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function assert197(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);}
$cases=[
  ['Falha na consulta HTTPS ao GitHub: Could not resolve host: api.github.com','dns'],
  ['Falha na consulta HTTPS ao GitHub: SSL certificate problem','tls'],
  ['Falha na consulta HTTPS ao GitHub: Connection timed out','timeout'],
  ['Consulta à versão no GitHub retornou HTTP 403.','github_limited'],
  ['Consulta à versão no GitHub retornou HTTP 502.','github_remote_error'],
  ['Extensão cURL indisponível.','curl_missing'],
  ['Esta atualização altera a revisão do banco. Exige instalação assistida e backup externo.','schema_migration'],
  ['A versão 1.0.96 já está instalada. Nenhuma alteração foi necessária.','version'],
  ['Extensão ZIP indisponível','zip_missing'],
  ['Manifesto ausente no ZIP','zip_invalid'],
];
foreach($cases as [$message,$expected]){
  $d=igua_updater_diagnostic(new RuntimeException($message));
  assert197(($d['code']??'')===$expected,'Diagnóstico '.$expected.' não identificado');
  assert197(!str_contains(json_encode($d),'Could not resolve host:') && !str_contains(json_encode($d),'Bearer '), 'Sem URLs/tokens brutos');
}
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');
$source=file_get_contents(__DIR__.'/../public/admin.php');
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/modules/16-security.css');
assert197(str_contains($view,'data-updater-diagnose-zip') && str_contains($view,'data-github-diagnostic'),'Dois diagnósticos aparecem na UI');
assert197(str_contains($controller,"$".'operation===\'diagnose_zip\'') && str_contains($source,'igua_updater_diagnostic($e)'), 'Diagnóstico PHP ligado ao servidor');
assert197(str_contains($css,'height:fit-content') && str_contains($css,'min(78dvh,680px)'), 'Altura compacta do diálogo');
assert197(igua_updater_diagnose_zip('/arquivo-inexistente-197.zip')['ok']===false,'Arquivo ausente nunca instalável');
echo "OK: diagnóstico seguro e exposto para GitHub e ZIP, sem bypass de schema\n";
