<?php
declare(strict_types=1);
/** Migração e busca reais, EXCLUSIVAMENTE em MariaDB local de integração. */
$name=(string)(getenv('IGUA_TEST_DB_NAME')?:'');
if (PHP_SAPI!=='cli' || getenv('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB' ||
    !preg_match('/^igua_ci_[a-z0-9_]{3,40}$/D',$name) ||
    !in_array((string)getenv('IGUA_TEST_DB_HOST'),['localhost','127.0.0.1','::1'],true) ||
    !extension_loaded('pdo_mysql')) {
    fwrite(STDERR,"EXPLAIN 146 bloqueado: exige banco local isolado igua_ci_* e opt-in explícito.\n");
    exit(2);
}
require_once __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/schema.php';
require_once __DIR__.'/../../app/schema_migrations.php';
require_once __DIR__.'/../../app/news_archive.php';
require_once __DIR__.'/../../app/news_query_plans.php';
require_once __DIR__.'/../../app/article_workflow.php';
// igua_migrate_schema() repete migrações de módulos importados por install_schema().
// Este teste roda em outro processo CLI e precisa carregá-los explicitamente.
require_once __DIR__.'/../../app/ads.php';
require_once __DIR__.'/../../app/news_editor.php';
require_once __DIR__.'/../../app/analytics.php';
require_once __DIR__.'/../../app/auth_remember.php';
require_once __DIR__.'/../../app/elections/schema.php';
$db=connection(['host'=>getenv('IGUA_TEST_DB_HOST'),'port'=>getenv('IGUA_TEST_DB_PORT')?:3306,
    'database'=>$name,'username'=>getenv('IGUA_TEST_DB_USER'),'password'=>getenv('IGUA_TEST_DB_PASSWORD')]);
function check146mysql(bool $condition,string $message):void {
    if (!$condition)throw new RuntimeException('MariaDB v146: '.$message);
    echo 'OK MariaDB: '.$message."\n";
}
function indexes146(PDO $db):array {
    $names=[];
    foreach ($db->query('SHOW INDEX FROM articles')->fetchAll(PDO::FETCH_ASSOC) as $row)$names[$row['Key_name']]=true;
    return $names;
}
check146mysql(igua_schema_revision($db)===36,'esquema 36 instalado');
$indices=indexes146($db);
check146mysql(isset($indices['idx_public_featured'],$indices['idx_public_popular']),'ambos os índices estão presentes');
// Confirmar todas as funções ANTES de efetuar DDL auto-commit no banco isolado.
$migrationFunctions146=[
    'migrate_article_fields', 'migrate_editorial_schema', 'migrate_bus_schema',
    'migrate_ads_schema', 'migrate_news_schema', 'migrate_article_slug_schema',
    'migrate_analytics_schema', 'igua_remember_schema', 'meu_elections_schema',
    'igua_migrate_public_news_indexes',
];
foreach ($migrationFunctions146 as $function) {
    if (!function_exists($function)) throw new RuntimeException('Dependência da migração ausente antes do DDL: '.$function);
}
check146mysql(true,'dependências de migração importadas antes da alteração de índices');
// Simula atualização 35->36 no ambiente isolado, inclusive após DDL parcial.
$db->exec('ALTER TABLE articles DROP INDEX idx_public_featured');
$db->prepare('UPDATE settings SET value=? WHERE name=?')->execute(['35',IGUA_SCHEMA_REVISION_KEY]);
check146mysql(igua_migrate_schema($db) && igua_schema_revision($db)===36,'migração de 35 para 36 recompõe índice ausente');
check146mysql(isset(indexes146($db)['idx_public_featured']),'índice recomposto após atualização');
check146mysql(!igua_migrate_schema($db),'segunda migração não altera a estrutura');
$plans=igua_news_explain($db);
check146mysql(count($plans)===5 && count($plans['mais_lidas'])>=1,'EXPLAIN real de cinco padrões SQL executado');
foreach ($plans as $name=>$rows) {
    foreach ($rows as $row) {
        echo sprintf("PLANO %s: tabela=%s key=%s type=%s linhas_estimadas=%d\n",
            $name,$row['table'],$row['key']?:'(nenhum)',$row['type'],$row['estimated_rows']);
    }
}
// Insere dados fictícios somente em transação que sempre será revertida.
$db->beginTransaction();
try {
    $suffix=bin2hex(random_bytes(5));
    $add=$db->prepare("INSERT INTO articles (title,slug,summary,body,status,published_at) VALUES (?,?,?,'Texto de teste de pesquisa em MariaDB.','published',NOW())");
    $add->execute(['Relatório 50%_! teste','integracao-pesquisa-146-'.$suffix,'Relatório com símbolo de porcentagem']);
    $id=(int)$db->lastInsertId();
    $add->execute(['Relatório 50XX_ teste','integracao-outra-146-'.$suffix,'Texto sem sequência literal']);
    $public=archive_listing($db,1,'50%_!');
    check146mysql($public['total']===1 && (int)$public['rows'][0]['id']===$id,'pesquisa pública mantém sequência literal de símbolos');
    [$where,$params]=news_admin_conditions(['role'=>'admin'],'all','50%_!');
    $stmt=$db->prepare('SELECT id FROM articles a WHERE '.$where);
    $stmt->execute($params);
    $matches=$stmt->fetchAll(PDO::FETCH_COLUMN);
    check146mysql(count($matches)===1 && (int)$matches[0]===$id,'pesquisa administrativa usa mesma semântica literal');
} finally {
    if($db->inTransaction())$db->rollBack();
}
echo "Integração de índices v1.0.46 concluída no banco isolado.\n";
