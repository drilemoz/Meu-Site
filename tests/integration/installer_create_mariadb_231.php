<?php
declare(strict_types=1);
/** Teste destrutivo APENAS no MariaDB isolado do CI, com nomes efêmeros e únicos. */
if (getenv('CI')!=='true' || getenv('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB'
    || getenv('IGUA_TEST_ADMIN_HOST')!=='127.0.0.1'
    || getenv('IGUA_TEST_ADMIN_USER')!=='root'
    || getenv('IGUA_TEST_ADMIN_PASSWORD')!== 'temporary-ci-root-only') {
    echo "SKIP: provisionamento real somente no serviço MariaDB efêmero do GitHub Actions.\n";
    exit(0);
}
require_once __DIR__.'/../../app/installer.php';
if (!extension_loaded('pdo_mysql')) throw new RuntimeException('CI precisa de pdo_mysql.');
$admin=igua_schema_creation_connection('127.0.0.1',3306,'root','temporary-ci-root-only');
// O endereço informado no DSN do runner não é necessariamente o endereço
// visto pelo MariaDB quando a porta é publicada via bridge Docker.
$mysqlIdentity=(string)$admin->query('SELECT USER()')->fetchColumn();
$pos=strrpos($mysqlIdentity,'@');
$clientHost=$pos===false?'':substr($mysqlIdentity,$pos+1);
if (!in_array($clientHost,['localhost','127.0.0.1','::1'],true)) {
    throw new RuntimeException('Teste de usuário MySQL LOCAL iniciado fora do loopback do MariaDB (cliente identificado como '.$clientHost.'). No CI use o namespace de rede do contêiner, sem criar contas % ou autorizar IPs do Docker.');
}
$prefix='igua_ci_provision231_';
$database=$prefix.bin2hex(random_bytes(3));
$dedicated=$prefix.bin2hex(random_bytes(3));
$created=[];
try {
    igua_create_database_with_login($admin,$database);
    $created[]=$database;
    $db=new PDO('mysql:host=127.0.0.1;port=3306;dbname='.$database.';charset=utf8mb4','root','temporary-ci-root-only',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    if($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)!==[])throw new RuntimeException('Banco novo não está vazio.');
    igua_setup_probe_schema_permissions($db);
    if($db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)!==[])throw new RuntimeException('A verificação deixou tabela residual.');
    try{igua_create_database_with_login($admin,$database);throw new RuntimeException('Recriou banco existente.');}
    catch(PDOException $e){if((int)($e->errorInfo[1]??0)!==1007)throw $e;}
    unset($db);
    $result=igua_provision_hosting_local($admin,$dedicated,'127.0.0.1',3306);
    $created[]=$dedicated;
    if(!preg_match('/\Aiguanews_[a-f0-9]{8}\z/D',$result['username']))throw new RuntimeException('Usuário não aleatório.');
    $accounts=[$result['username']];
    $app=new PDO('mysql:host=127.0.0.1;port=3306;dbname='.$dedicated.';charset=utf8mb4',$result['username'],$result['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    igua_setup_probe_schema_permissions($app);
    if($app->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN)!==[])throw new RuntimeException('A verificação de privilégios deixou resíduo.');
    $app->exec('CREATE TABLE prova (id INT PRIMARY KEY) ENGINE=InnoDB');
    $app->exec('INSERT INTO prova (id) VALUES (1)');
    if((int)$app->query('SELECT COUNT(*) FROM prova')->fetchColumn()!==1)throw new RuntimeException('Usuário criado não tem acesso ao próprio banco.');
    $app->exec('DROP TABLE prova');
    echo "OK: criação MySQL real, recusa duplicata, usuário aleatório e permissões no banco.\n";
}finally {
    unset($app,$db);
    if(isset($accounts))foreach($accounts as $user){
        foreach(['localhost','127.0.0.1'] as $h){$admin->exec('DROP USER IF EXISTS '.$admin->quote($user).'@'.$admin->quote($h));}
    }
    foreach($created as $name){
        if (!preg_match('/\Aigua_ci_provision231_[a-f0-9]{6}\z/D',$name))throw new RuntimeException('Nome fora do teste isolado; limpeza abortada.');
        $admin->exec('DROP DATABASE IF EXISTS '.igua_sql_identifier($name));
    }
}
