<?php
declare(strict_types=1);
/**
 * Consultas leves para cartões e ranking público.
 * A consulta de notícia individual continua carregando body e campos editoriais completos.
 * Não usar SELECT a.* em listagens: body é MEDIUMTEXT e não aparece nos cartões.
 */
function has_published_demo_news(PDO $db): bool {
    return $db->query("SELECT 1 FROM articles WHERE is_demo=1 AND status='published' LIMIT 1")->fetchColumn() !== false;
}
function fetch_news(PDO $db, int $limit=8, ?int $category=null, ?int $exclude=null): array {
    $sql='SELECT a.id,a.slug,a.title,a.summary,a.image,a.is_demo,a.published_at,'.
        'c.name AS category,c.slug AS category_slug,c.color AS category_color '.
        'FROM articles a LEFT JOIN categories c ON c.id=a.category_id WHERE '.published_filter();
    $params=[];
    if ($category!==null) {
        $sql.=' AND (a.category_id=? OR EXISTS(SELECT 1 FROM article_categories ac WHERE ac.article_id=a.id AND ac.category_id=?))';
        $params[]=$category;
        $params[]=$category;
    }
    if ($exclude!==null) {
        $sql.=' AND a.id<>?';
        $params[]=$exclude;
    }
    $sql.=' ORDER BY a.published_at DESC,a.id DESC LIMIT '.max(1,min($limit,30));
    $stmt=$db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}
function fetch_featured_news(PDO $db): array|false {
    $sql='SELECT a.id,a.slug,a.title,a.summary,a.image,a.is_demo,a.published_at,'.
        'c.name AS category,c.color AS category_color,c.slug AS category_slug '.
        'FROM articles a LEFT JOIN categories c ON c.id=a.category_id WHERE '.published_filter().
        ' AND a.featured=1 ORDER BY a.published_at DESC,a.id DESC LIMIT 1';
    return $db->query($sql)->fetch();
}
function fetch_popular_news(PDO $db): array {
    $sql='SELECT a.id,a.title,a.slug,a.published_at,a.views FROM articles a WHERE '.published_filter().
        ' ORDER BY a.views DESC,a.published_at DESC,a.id DESC LIMIT 5';
    return $db->query($sql)->fetchAll();
}
