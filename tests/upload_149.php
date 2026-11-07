<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
require __DIR__.'/../app/installer.php';
function v149(bool $passed,string $name): void {
    if (!$passed) throw new RuntimeException('Falha v1.0.49: '.$name);
    echo 'OK: '.$name."\n";
}
v149(IGUA_BACKUP_IMPORT_MAX_BYTES===15728640, 'limite logico 15 MiB exato');
v149(igua_backup_upload_capacity('2M','8M')===2*1048576, 'configuracao PHP 2M/8M limita a 2 MiB');
v149(igua_backup_upload_capacity('15M','15M')===14*1048576, 'POST sem margem limita a 14 MiB');
v149(igua_backup_upload_capacity('15M','16M')===IGUA_BACKUP_IMPORT_MAX_BYTES, 'PHP 15M/16M suporta limite logico');
v149(igua_backup_upload_capacity('20M','24M')===IGUA_BACKUP_IMPORT_MAX_BYTES, 'PHP 20M/24M permite backup de 15 MiB');
v149(igua_backup_upload_capacity('3M','8M')===3*1048576, 'capacidade usa o menor limite');
v149(igua_backup_upload_capacity('10M','0')===0, 'sem limite POST definido nao anuncia capacidade sem confirmar');
$bad=igua_upload_limits_diagnosis('2M','8M');
v149(!$bad['ok'] && $bad['max_bytes']===2*1048576 && str_contains($bad['detail'],'2,00 MiB'),'diagnostico detalhado 2M/8M');
v149(igua_upload_limits_diagnosis('20M','24M')['ok'],'diagnostico aprovado 20M/24M');
foreach ([UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE,UPLOAD_ERR_PARTIAL,UPLOAD_ERR_NO_FILE] as $error) {
    try {
        igua_read_import(['name'=>'portal.json.gz','size'=>0,'error'=>$error]);
        throw new RuntimeException('Erro de upload '.$error.' não rejeitado');
    } catch (DomainException $e) { v149(strlen($e->getMessage())>15,'upload error '.$error.' explicado'); }
}
try {igua_read_import(['name'=>'portal.json','size'=>IGUA_BACKUP_IMPORT_MAX_BYTES+1,'error'=>UPLOAD_ERR_OK]);throw new RuntimeException('Tamanho acima de 15 MiB aceito');}
catch(DomainException $e){v149(str_contains($e->getMessage(),'15 MiB'),'PHP recusa arquivo maior que 15 MiB antes de ler dados');}
$public=IGUA_ROOT.'/public';
$config=(string)file_get_contents($public.'/.user.ini');
v149(str_contains($config,'upload_max_filesize = 20M') && str_contains($config,'post_max_size = 24M'),'PHP-FPM/CGI recebe limites 20M/24M');
$apache=(string)file_get_contents($public.'/.htaccess');
$router=(string)file_get_contents($public.'/router.php');
v149(str_contains($apache,'\\.user\\.ini') && str_contains($router,'.well-known'),'Apache e PHP local protegem arquivos ocultos');
$setup=(string)file_get_contents($public.'/setup.php');
v149(str_contains($setup,'data-backup-import') && str_contains($setup,'setup-backup-upload.js'),'wizard faz prevalidacao de tamanho');
$script=(string)file_get_contents(IGUA_ROOT.'/bin/start-termux.sh');
v149(str_contains($script,'upload_max_filesize=20M') && str_contains($script,'post_max_size=24M'),'script Termux define limites no processo web');
echo "Regressão de upload híbrido v1.0.49 aprovada.\n";
