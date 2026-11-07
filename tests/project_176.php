<?php
declare(strict_types=1);
/** Auditoria de contrato: não exige banco de produção ou ZipArchive no PHP local. */
$root=dirname(__DIR__);
$required=['VERSION','app_identity.json','github-manager.json','README.md','AGENTS.md','docs/INDICE.md','docs/BACKUP.md','docs/ATUALIZADOR.md','docs/INTEGRACOES.md','docs/AUDITORIA_176.md','tests/fixtures/backup133_duplicate_names.zip'];
foreach($required as $file){if(!is_file($root.'/'.$file))throw new RuntimeException('Ausente: '.$file);}
$version=trim((string)file_get_contents($root.'/VERSION'));
foreach(['app_identity.json','github-manager.json'] as $file){
    $meta=json_decode((string)file_get_contents($root.'/'.$file),true,512,JSON_THROW_ON_ERROR);
    if(($meta['version']??null)!==$version || ($meta['projectType']??null)!=='web' || ($meta['repository']??null)!=='drilemoz/Meu-Site')throw new RuntimeException('Metadados: '.$file);
}
$source=(string)file_get_contents($root.'/app/backup.php');
foreach(['function make_backup(', 'function make_media_bundle(', 'function restore_backup(', 'function restore_media_bundle(', 'SELECT kind,filename FROM article_media', "'media_library'", 'MEDIA_BUNDLE_MAX_MEDIA_BYTES'] as $token){
    if(!str_contains($source,$token))throw new RuntimeException('Contrato do backup alterado: '.$token);
}
$view=(string)file_get_contents($root.'/app/admin_views/routes/backups.php');
foreach(['Backup completo: banco + mídias','Somente banco de dados','não faz backup de todo o servidor'] as $token){
    if(!str_contains($view,$token))throw new RuntimeException('Rotulagem de backup: '.$token);
}
$docs=(string)file_get_contents($root.'/docs/BACKUP.md');
foreach(['40 MiB','350 MiB','300 MiB','3.000','não', 'ZipArchive'] as $token){
    if(!str_contains($docs,$token))throw new RuntimeException('Documentação de backup incompleta: '.$token);
}
if(preg_match('/\b(?:android|uaicortes)\b/i',(string)file_get_contents($root.'/github-manager.json')))throw new RuntimeException('Identidade Android');
echo "OK: auditoria de contrato Meu Site {$version}, backup lógico e completo separados\n";
