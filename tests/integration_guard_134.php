<?php
declare(strict_types=1);
/** Verificações estáticas do executor integrado; o ensaio real fica em tests/integration/. */
function igua134_assert(bool $ok,string $message): void {if(!$ok)throw new RuntimeException('Falha: '.$message);echo "OK: $message\n";}
$runner=(string)file_get_contents(__DIR__.'/integration/restore_mariadb_134.php');
$workflow=(string)file_get_contents(__DIR__.'/../.github/workflows/integration-mariadb.yml');
igua134_assert(str_contains($runner,"IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB'"),'execução destrutiva exige confirmação explícita');
igua134_assert(str_contains($runner,"^igua_ci_") && str_contains($runner,"information_schema.tables"),'banco exige nome de teste e ausência de tabelas');
igua134_assert(str_contains($runner,"extension_loaded('pdo_mysql')") && str_contains($runner,"class_exists('ZipArchive')"),'ensaio exige extensões reais');
igua134_assert(str_contains($runner,'restore_backup($db,$bad)') && str_contains($runner,'restore_media_bundle($db,$brokenFkBundle)'),'rollback transacional e de mídia são exercitados');
igua134_assert(str_contains($runner,'restore_media_bundle($db,$bundle)') && str_contains($runner,'hash_file(\'sha256\',$mediaPath)' ),'round-trip de ZIP e foto com hash são exercitados');
igua134_assert(str_contains($workflow,'mariadb:11.4') && str_contains($workflow,'tests/integration/restore_mariadb_134.php'),'CI contém MariaDB real e chama o ensaio');
igua134_assert(str_contains($workflow,'IGUA_TEST_ALLOW_WRITE: YES_ISOLATED_DB') && str_contains($workflow,'permissions:'),'CI limita execução e permissões');
igua134_assert(str_contains((string)file_get_contents(__DIR__.'/../app/schema_migrations.php'),'const IGUA_SCHEMA_REVISION = 37'),'revisão SQL 36 acompanha índices, eleições e sessões persistentes');
echo "1.0.34: salvaguardas estáticas do teste integrado aprovadas.\n";
