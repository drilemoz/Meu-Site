<?php
declare(strict_types=1);
/** Planos representativos, somente leitura; não prometem uso obrigatório de um índice.
 * A decisão depende de estatísticas, cardinalidade, versões e volume de registros.
 */
function igua_news_explain_cases(): array {
    // Reutiliza a mesma função de filtro da busca pública, preservando escape literal.
    [$searchWhere,$searchParams]=archive_conditions('50%_!',null,0);
    [$categoryWhere,$categoryParams]=archive_conditions('',0,0);
    $published=" WHERE a.status = 'published' AND a.published_at <= NOW()";
    return [
        'recentes'=>['SELECT a.id FROM articles a'.$published.' ORDER BY a.published_at DESC,a.id DESC LIMIT 12',[]],
        'destaque'=>['SELECT a.id FROM articles a'.$published.' AND a.featured=1 ORDER BY a.published_at DESC,a.id DESC LIMIT 1',[]],
        'mais_lidas'=>['SELECT a.id FROM articles a'.$published.' ORDER BY a.views DESC,a.published_at DESC,a.id DESC LIMIT 5',[]],
        'busca_trecho'=>['SELECT a.id FROM articles a'.$searchWhere.' ORDER BY a.published_at DESC,a.id DESC LIMIT 12',$searchParams],
        'categoria'=>['SELECT a.id FROM articles a'.$categoryWhere.' ORDER BY a.published_at DESC,a.id DESC LIMIT 12',$categoryParams],
    ];
}
function igua_news_explain(PDO $db): array {
    $report=[];
    foreach (igua_news_explain_cases() as $name=>[$sql,$params]) {
        // EXPLAIN não executa a consulta de leitura, apenas produz o plano estimado.
        $statement=$db->prepare('EXPLAIN '.$sql);
        $statement->execute($params);
        $plans=$statement->fetchAll(PDO::FETCH_ASSOC);
        $report[$name]=array_map(static fn(array $plan):array => [
            'table'=>(string)($plan['table']??''),
            'type'=>(string)($plan['type']??''),
            'possible_keys'=>(string)($plan['possible_keys']??''),
            'key'=>(string)($plan['key']??''),
            'estimated_rows'=>(int)($plan['rows']??0),
            'extra'=>(string)($plan['Extra']??''),
        ],$plans);
    }
    return $report;
}
