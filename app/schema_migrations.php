<?php
declare(strict_types=1);
/**
 * Registro de migração para instalações existentes, incluindo sessões persistentes (34), cadastro eleitoral (35) e índices públicos (36) e aplicativos/páginas legais (37).
 * O marcador é gravado por último, somente se TODAS as migrações forem concluídas.
 * Ao adicionar uma migração futura, aumente IGUA_SCHEMA_REVISION.
 */
const IGUA_SCHEMA_REVISION = 37;
const IGUA_SCHEMA_REVISION_KEY = 'igua_schema_revision';

function igua_schema_revision(PDO $db): int {
    $stmt=$db->prepare('SELECT value FROM settings WHERE name=? LIMIT 1');
    $stmt->execute([IGUA_SCHEMA_REVISION_KEY]);
    $value=$stmt->fetchColumn();
    if ($value===false) return 0;
    // Marcadores inválidos não devem ser silenciosamente tratados como revisão zero.
    if (!is_string($value) && !is_int($value)) throw new RuntimeException('Revisão de esquema inválida. Verifique o banco antes de migrar.');
    $marker=(string)$value;
    if (!preg_match('/^(?:0|[1-9][0-9]*)$/D', $marker)) throw new RuntimeException('Revisão de esquema inválida. Verifique o banco antes de migrar.');
    // Overflow deve falhar como revisão futura, não ser reduzido à revisão atual.
    if (strlen($marker)>strlen((string)PHP_INT_MAX) ||
        (strlen($marker)===strlen((string)PHP_INT_MAX) && strcmp($marker,(string)PHP_INT_MAX)>0)) {
        throw new RuntimeException('Revisão de esquema incompatível com este PHP.');
    }
    return (int)$marker;
}
function igua_record_schema_revision(PDO $db): void {
    $stmt=$db->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
    $stmt->execute([IGUA_SCHEMA_REVISION_KEY,(string)IGUA_SCHEMA_REVISION]);
}
/**
 * Migrações são cumulativas e idempotentes. O marcador poupa varreduras DDL
 * a cada visita; a Central de Saúde continua conferindo a estrutura real.
 */

/** Índices aditivos para ordenações públicas. DDL é auto-commit no MariaDB:
 * cada índice tem verificação de existência e pode ser repetido após falha parcial.
 * Não criar FULLTEXT aqui: LIKE '%trecho%' possui outra semântica.
 */
function igua_migrate_public_news_indexes(PDO $db): array {
    $rows=$db->query('SHOW INDEX FROM articles')->fetchAll(PDO::FETCH_ASSOC);
    $known=[];
    foreach ($rows as $row) {
        if (isset($row['Key_name'])) $known[(string)$row['Key_name']]=true;
    }
    $definitions=[
        'idx_public_featured'=>'(status,featured,published_at,id)',
        'idx_public_popular'=>'(status,views,published_at,id)',
    ];
    $added=[];
    foreach ($definitions as $name=>$columns) {
        if (isset($known[$name])) continue;
        // Nomes e colunas vêm apenas da lista literal acima, não do usuário.
        $db->exec('ALTER TABLE articles ADD INDEX `'.$name.'` '.$columns);
        $added[]=$name;
    }
    return $added;
}

function igua_migrate_schema(PDO $db): bool {
    $installed=igua_schema_revision($db);
    if ($installed>IGUA_SCHEMA_REVISION) {
        throw new RuntimeException('O banco utiliza revisão '.$installed.' do esquema, mais recente que a revisão '.IGUA_SCHEMA_REVISION.' deste código. Atualize o código antes de acessar o painel; nenhuma migração foi executada.');
    }
    if ($installed===IGUA_SCHEMA_REVISION) return false;
    migrate_article_fields($db);
    migrate_editorial_schema($db);
    migrate_bus_schema($db);
    migrate_ads_schema($db);
    migrate_news_schema($db);
    migrate_article_slug_schema($db);
    migrate_analytics_schema($db);
    require_once __DIR__.'/auth_remember.php';
    igua_remember_schema($db);
    require_once __DIR__.'/elections/schema.php';
    meu_elections_schema($db);
    // Também corrige instalações parcialmente migradas sem repetir DDL já aplicado.
    igua_migrate_public_news_indexes($db);
    require_once __DIR__.'/apps_legal.php';
    igua_migrate_apps_schema($db);
    igua_record_schema_revision($db);
    return true;
}
