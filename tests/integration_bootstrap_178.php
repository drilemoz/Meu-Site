<?php
declare(strict_types=1);
// Garantia de que o teste eleitoral carrega as migrações antes de usar o marcador.
$source=(string)file_get_contents(__DIR__.'/integration/elections_mariadb_141.php');
$schema=(string)file_get_contents(__DIR__.'/../app/schema_migrations.php');
$require="require_once __DIR__.'/../../app/schema_migrations.php';";
if(strpos($source,$require)===false)throw new RuntimeException('O teste eleitoral não carrega schema_migrations.php.');
if(strpos($schema,'function igua_schema_revision(PDO $db): int')===false)
    throw new RuntimeException('Leitura de revisão ausente no módulo de migrações.');
require_once __DIR__.'/../app/schema_migrations.php';
if(!function_exists('igua_schema_revision') || IGUA_SCHEMA_REVISION!==36)
    throw new RuntimeException('Dependências de revisão do banco indisponíveis.');
echo "OK: dependência eleitoral de revisão de esquema carregada.\n";
