<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require __DIR__.'/bootstrap.php';
require __DIR__.'/backup.php';
require __DIR__.'/schema_migrations.php';
require __DIR__.'/updater.php';
if(($argv[1]??'')==='--rollback'){
    if(!igua_updater_vps_mode()||(function_exists('posix_geteuid')&&posix_geteuid()===0))
        throw new RuntimeException('Recuperação requer conta dedicada não-root.');
    igua_updater_vps_assert_worker();
    igua_updater_manual_rollback();
    echo "Código recuperado; banco MySQL preservado.\n";
}elseif(count($argv)===1)igua_updater_vps_run();
else throw new InvalidArgumentException('Argumentos inválidos.');
