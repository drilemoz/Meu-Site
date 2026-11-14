<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {http_response_code(403);exit;}
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/ads.php';
require __DIR__.'/../app/news_editor.php';
if (!installed()) {fwrite(STDERR,"IguaNews não instalado.\n");exit(1);}
try {
    migrate_ads_schema(db());
    migrate_news_schema(db());
    $filename=make_backup(db(),date('w')==='0'?'weekly':'daily');
    // Verifica o conteúdo completo antes de descartar cópias antigas por retenção.
    $path=backup_path($filename);
    if (!$path || !is_file($path)) throw new RuntimeException('Backup ausente após gravação.');
    parse_backup((string)file_get_contents($path),$filename);
    rotate_backups();
    echo "Backup salvo: {$filename}\n";
} catch (Throwable $e) {fwrite(STDERR,'Falha no backup: '.$e->getMessage()."\n");exit(1);}
