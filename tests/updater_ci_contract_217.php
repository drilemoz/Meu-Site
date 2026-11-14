<?php
declare(strict_types=1);
/** Auditoria offline da integração real: NÃO conecta ao banco nem instala código. */
$root=dirname(__DIR__);
$workflow=(string)file_get_contents($root.'/.github/workflows/integration-mariadb.yml');
$test=(string)file_get_contents($root.'/tests/integration/updater_mariadb_217.php');
function assert217(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('FALHOU: '.$message);
    echo 'OK: '.$message."\n";
}
assert217(str_contains($workflow,'php tests/integration/restore_mariadb_134.php')&&
    str_contains($workflow,'php tests/integration/updater_mariadb_217.php'),
    'CI executa restauração e ensaio de atualização');
assert217(strpos($workflow,'php tests/integration/restore_mariadb_134.php')<
    strpos($workflow,'php tests/integration/updater_mariadb_217.php'),
    'ensaio integrado do atualizador usa banco já preparado');
assert217(substr_count($workflow,'IGUA_TEST_ALLOW_WRITE: YES_ISOLATED_DB')>=2&&
    str_contains($workflow,'IGUA_TEST_DB_NAME: igua_ci_backup134'),
    'CI mantém opt-in e banco exclusivo de testes');
foreach(['PHP_SAPI',"'YES_ISOLATED_DB'","/^igua_ci_",'127.0.0.1','pdo_mysql','ZipArchive',
    "define('IGUA_ROOT',", "define('IGUA_STORAGE',", "define('IGUA_PUBLIC',",
    'igua_updater_install_zip(', 'igua_updater_manual_rollback(', 'backup_path(',
    'backup_logs', 'SHA-256', 'cleanup217($root)'] as $token){
    assert217(str_contains($test,$token),'teste integrado preserva guarda: '.$token);
}
assert217(!str_contains($test,'/app/bootstrap.php')&&
    !str_contains($test,'DROP DATABASE') && !str_contains($test,'TRUNCATE TABLE') &&
    !str_contains($test,'restore_backup('),
    'teste não define raiz publicada nem restaura ou elimina dados de banco');
assert217(str_contains($test,'strlen($tamperedPayload)===strlen($originalPayload)') &&
    str_contains($test,'hash(\'sha256\',$tamperedPayload)!==hash(\'sha256\',$originalPayload)') &&
    str_contains($test,"'SHA-256'") &&
    str_contains($test,"'arquivo inesperado ou incorreto'"),
    'CI testa separadamente tamanho divergente e hash divergente com tamanho idêntico');
assert217(str_contains($test,'schema 37')&&str_contains($test,'1.0.118'),
    'teste integrado acompanha versão e schema atuais');
echo "Contrato do teste integrado aprovado. MariaDB e ZipArchive reais são exercitados no CI, não nesta suíte offline.\n";
