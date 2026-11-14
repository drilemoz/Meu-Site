<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/setup_chunked_backup.php';
function ensure150(bool $ok, string $label): void { if (!$ok) throw new RuntimeException('v1.0.50: '.$label); echo 'OK: '.$label."\n"; }
ensure150(igua_setup_chunk_size('2M','8M')===524288,'PHP 2M/8M permite blocos de 512 KiB');
ensure150(igua_setup_chunk_size('512K','512K')===262144,'PHP 512K permite blocos de 256 KiB');
ensure150(igua_setup_chunk_size('128K','8M')===0,'PHP abaixo de 256 KiB não anuncia fallback');
ensure150(igua_setup_chunk_size('20M','24M')===524288,'Bloco limitado a 512 KiB');
ensure150(IGUA_BACKUP_IMPORT_MAX_BYTES===15728640,'Limite de 15 MiB preservado');
try { igua_setup_chunk_path('../config'); throw new RuntimeException('Aceitou traversal'); }
catch (DomainException) {ensure150(true,'Caminhos arbitrários rejeitados');}
try { igua_setup_read_staged_backup(str_repeat('a',32)); throw new RuntimeException('Aceitou upload sem sessão'); }
catch (DomainException) {ensure150(true,'Arquivo sem sessão rejeitado');}
$setup=(string)file_get_contents(IGUA_PUBLIC.'/setup.php');
ensure150(str_contains($setup,"$".'action===\'backup_chunk\'') && str_contains($setup,'igua_setup_read_staged_backup'), 'Endpoint de blocos integrado à etapa 3');
$script=(string)file_get_contents(IGUA_PUBLIC.'/assets/js/setup-backup-upload.js');
ensure150(str_contains($script,'file.slice(offset, end)') && str_contains($script,'credentials:\'same-origin\''), 'JS divide o arquivo e usa cookies same-origin');
echo "Regressão de envio segmentado 1.0.50 aprovada.\n";
