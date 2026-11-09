<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Somente pela linha de comando.');}
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/setup_access.php';
if(installed()) {fwrite(STDERR,"O portal já está instalado.\n");exit(1);}
try{
    $secret=igua_setup_access_create();
    echo "Chave de instalação (guarde em local privado): ".$secret."\n";
    echo "Informe esta chave em /setup.php antes de configurar o MySQL.\n";
}catch(Throwable){fwrite(STDERR,"Não foi possível criar a chave. Confira storage/ e se já existe uma chave.\n");exit(1);}
