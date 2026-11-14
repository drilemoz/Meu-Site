<?php
declare(strict_types=1);
/** Manutenção editorial Alpha. Nenhuma alteração ocorre ao consultar contagens. */
function igua_content_inventory(PDO $db): array {
    return [
        'demo'=>(int)$db->query('SELECT COUNT(*) FROM articles WHERE is_demo=1')->fetchColumn(),
        'real'=>(int)$db->query('SELECT COUNT(*) FROM articles WHERE is_demo=0')->fetchColumn(),
        'drafts'=>(int)$db->query('SELECT COUNT(*) FROM article_drafts')->fetchColumn(),
        'categories'=>(int)$db->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
        'sources'=>(int)$db->query('SELECT COUNT(*) FROM editorial_sources')->fetchColumn(),
    ];
}
/** Exclusão transacional: relações com article_id são eliminadas por ON DELETE CASCADE,
 *  salvo os rascunhos que têm chave própria e precisam ser retirados explicitamente.
 *  Fotografias e biblioteca de mídia são preservadas: também podem estar em uso por outras matérias.
 */
function igua_content_clear(PDO $db, string $scope): int {
    if (!in_array($scope,['demo','all'],true)) throw new InvalidArgumentException('Tipo de limpeza inválido.');
    $where=$scope==='demo'?' WHERE is_demo=1':'';
    $db->beginTransaction();
    try {
        if ($scope==='demo') {
            $db->exec('DELETE FROM article_drafts WHERE article_id IN (SELECT id FROM articles WHERE is_demo=1)');
        } else {
            $db->exec('DELETE FROM article_drafts');
        }
        $count=(int)$db->query('SELECT COUNT(*) FROM articles'.$where)->fetchColumn();
        $db->exec('DELETE FROM articles'.$where);
        $db->commit();
        return $count;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
/** Reset restrito à instalação completa e ao inventário fechado de 29 tabelas.
 *  DDL MariaDB não tem rollback: chamar SOMENTE depois de backup e de confirmação forte.
 *  Não remove arquivos ou backups; após remover config.php, /setup.php pode ser reaberto.
 */
function igua_content_reset_schema(PDO $db, string $storage, bool $requireConfig=true): int {
    require_once __DIR__.'/setup_inventory.php';
    $tableNames=array_map('strval',$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN));
    $known=igua_setup_known_tables();
    if (count(array_diff($tableNames,$known))>0 || count(array_diff($known,$tableNames))>0) {
        throw new DomainException('Estrutura diferente das 29 tabelas conhecidas. O banco não foi modificado.');
    }
    if (getenv('IGUANEWS_DB_NAME') !== false) {
        throw new DomainException('Reinstalação automática indisponível com credenciais fornecidas por variáveis de ambiente.');
    }
    $cfg=$storage.'/config.php';
    if (is_link($cfg) || !is_writable($storage) || ($requireConfig && (!is_file($cfg) || !is_writable($cfg))) || (!$requireConfig && is_file($cfg))) {
        throw new DomainException('Configuração privada não pode ser removida com segurança. Nenhuma tabela foi alterada.');
    }
    // Somente tabelas reconhecidas. Desabilitação de FK restringe-se a esta sessão e é restaurada sempre.
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach ($tableNames as $table) {
            // Inventário fechado e escapado. DROP TABLE é irreversível no MariaDB.
            $db->exec('DROP TABLE `'.str_replace('`','``',$table).'`');
        }
    } finally {
        $db->exec('SET FOREIGN_KEY_CHECKS=1');
    }
    if ($requireConfig && !@unlink($cfg)) {
        throw new RuntimeException('Tabelas removidas, porém o arquivo privado permaneceu. Remova storage/config.php manualmente antes de voltar ao instalador.');
    }
    return count($tableNames);
}
