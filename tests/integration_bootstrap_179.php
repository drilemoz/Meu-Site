<?php
declare(strict_types=1);
/** Contrato do teste de migração 35→36: imports explícitos antes de DDL destrutivo. */
$test=(string)file_get_contents(__DIR__.'/integration/news_indexes_mariadb_146.php');
$imports=[
    'schema.php','schema_migrations.php','article_workflow.php',
    'ads.php','news_editor.php','analytics.php','auth_remember.php',
    'elections/schema.php','apps_legal.php',
];
$prefix="require_once __DIR__.'/../../app/";
foreach ($imports as $name) {
    $needle=$prefix.$name."';";
    if (!str_contains($test,$needle)) throw new RuntimeException('Módulo ausente no teste de índices: '.$name);
}
$before=strpos($test,'// Confirmar todas as funções ANTES de efetuar DDL');
$ddl=strpos($test,"ALTER TABLE articles DROP INDEX idx_public_featured");
if ($before===false || $ddl===false || $before>=$ddl) {
    throw new RuntimeException('Preflight das migrações precisa preceder o ALTER INDEX.');
}
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/schema.php';
require_once __DIR__.'/../app/schema_migrations.php';
require_once __DIR__.'/../app/article_workflow.php';
require_once __DIR__.'/../app/ads.php';
require_once __DIR__.'/../app/news_editor.php';
require_once __DIR__.'/../app/analytics.php';
$functions=[
    'migrate_article_fields', 'migrate_editorial_schema', 'migrate_bus_schema',
    'migrate_ads_schema', 'migrate_news_schema', 'migrate_article_slug_schema',
    'migrate_analytics_schema', 'igua_remember_schema', 'meu_elections_schema',
    'igua_migrate_public_news_indexes','igua_migrate_apps_schema',
];
require_once __DIR__.'/../app/auth_remember.php';
require_once __DIR__.'/../app/elections/schema.php';
require_once __DIR__.'/../app/apps_legal.php';
foreach ($functions as $function) {
    if (!function_exists($function)) throw new RuntimeException('Função de migração ausente: '.$function);
    if (!str_contains($test,"'".$function."'")) throw new RuntimeException('Faltou preflight para: '.$function);
}
if (IGUA_SCHEMA_REVISION!==37) throw new RuntimeException('Revisão de schema inesperada: exige ensaio assistido.');
echo "OK: teste de índices importa todas as migrações antes do DDL.\n";
