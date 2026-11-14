<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/analytics.php';
if(!installed())exit("Instalação pendente.\n");
try{
    migrate_analytics_schema(db());
    echo 'Removidos '.analytics_prune(db(),90)." registros antigos.\n";
}catch(Throwable $e){fwrite(STDERR,"Não foi possível limpar métricas. Confira o banco e as permissões.\n");exit(1);}
