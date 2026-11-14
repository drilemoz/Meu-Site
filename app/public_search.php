<?php
declare(strict_types=1);
require_once __DIR__.'/news_archive.php';

/** Sugestões públicas, restritas a matérias publicadas, categorias e linhas ativas. */
function igua_public_search_suggestions(PDO $db, mixed $raw, int $limit=6): array {
    if (!is_string($raw)) return [];
    $query=trim($raw);
    $length=function_exists('mb_strlen') ? mb_strlen($query,'UTF-8') : strlen($query);
    if ($length<2 || $length>100) return [];
    $limit=max(1,min($limit,6));
    $pattern='%'.archive_escape_like($query).'%';
    $items=[];

    $stmt=$db->prepare('SELECT a.title,a.slug FROM articles a WHERE '.published_filter()." AND (a.title LIKE ? ESCAPE '!' OR a.summary LIKE ? ESCAPE '!') ORDER BY a.published_at DESC,a.id DESC LIMIT 3");
    $stmt->execute([$pattern,$pattern]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $items[]=['type'=>'Notícia','label'=>(string)$row['title'],'url'=>url(['p'=>'noticia','slug'=>(string)$row['slug']])];
        if (count($items)>=$limit) return $items;
    }
    $stmt=$db->prepare("SELECT name,slug FROM categories WHERE name LIKE ? ESCAPE '!' ORDER BY sort_order,name LIMIT 2");
    $stmt->execute([$pattern]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $items[]=['type'=>'Editoria','label'=>(string)$row['name'],'url'=>url(['p'=>'categoria','slug'=>(string)$row['slug']])];
        if (count($items)>=$limit) return $items;
    }
    // Só sugerir percursos que realmente existem entre duas paradas de linha ativa.
    try {
        $stmt=$db->prepare("SELECT DISTINCT a.city AS origin_city,b.city AS destination_city FROM bus_stops a INNER JOIN bus_routes r ON r.id=a.route_id INNER JOIN bus_stops b ON b.route_id=r.id AND b.stop_order>a.stop_order WHERE r.active=1 AND (a.city LIKE ? ESCAPE '!' OR b.city LIKE ? ESCAPE '!') ORDER BY a.city,b.city LIMIT 2");
        $stmt->execute([$pattern,$pattern]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $from=(string)$row['origin_city'];$to=(string)$row['destination_city'];
            $items[]=['type'=>'Ônibus','label'=>$from.' → '.$to,'url'=>url(['p'=>'onibus','origem'=>$from,'destino'=>$to])];
            if (count($items)>=$limit) break;
        }
    } catch (PDOException) {
        // Em instalações antigas sem tabelas de ônibus, a busca de notícias continua.
    }
    return $items;
}
