<?php
declare(strict_types=1);
/** Filtros públicos do acervo. Nenhum parâmetro GET participa do SQL como identificador. */
function archive_search_term(mixed $raw): string {
    if (!is_string($raw)) return '';
    return usub(trim($raw), 0, 100);
}
function archive_page_number(mixed $raw): int {
    if (!is_scalar($raw) || !preg_match('/^[1-9][0-9]{0,5}$/D', (string)$raw)) return 1;
    return min(100000, (int)$raw);
}
function archive_period_days(mixed $raw): int {
    return is_string($raw) && in_array($raw, ['7','30','90'], true) ? (int)$raw : 0;
}
/** Slug escolhido deve existir no banco; nunca se aceita um ID externo do visitante. */
function archive_category_id(array $categories, mixed $raw): ?int {
    if (!is_string($raw) || $raw === '') return null;
    foreach ($categories as $cat) {
        if ((string)$cat['slug'] === $raw) return (int)$cat['id'];
    }
    return null;
}
function archive_category_slug(array $categories, mixed $raw): string {
    return archive_category_id($categories, $raw) === null ? '' : (string)$raw;
}
function archive_escape_like(string $term): string {
    return strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']);
}
/** Condições idênticas para COUNT e SELECT, com filtros/intervalos parametrizados. */
function archive_conditions(string $search, ?int $categoryId, int $days): array {
    $conditions = [published_filter()];
    $params = [];
    if ($categoryId !== null) {
        $conditions[] = '(a.category_id=? OR EXISTS(SELECT 1 FROM article_categories ac WHERE ac.article_id=a.id AND ac.category_id=?))';
        array_push($params, $categoryId, $categoryId);
    }
    if ($search !== '') {
        $conditions[] = "(a.title LIKE ? ESCAPE '!' OR a.summary LIKE ? ESCAPE '!')";
        $pattern = '%'.archive_escape_like($search).'%';
        array_push($params, $pattern, $pattern);
    }
    if (in_array($days, [7,30,90], true)) {
        $conditions[] = 'a.published_at >= DATE_SUB(NOW(), INTERVAL '. $days .' DAY)';
    }
    return [' WHERE '.implode(' AND ', $conditions), $params];
}
function archive_bind(PDOStatement $statement, array $params, ?int $limit=null, ?int $offset=null): void {
    $position=1;
    foreach ($params as $param) {
        $statement->bindValue($position++, $param, is_int($param) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    if ($limit !== null) $statement->bindValue($position++, $limit, PDO::PARAM_INT);
    if ($offset !== null) $statement->bindValue($position, $offset, PDO::PARAM_INT);
}
/** Busca apenas colunas necessárias para cartões. Contagem não usa JOIN que multiplique matérias. */
function archive_listing(PDO $db, int $requestedPage, string $search='', ?int $categoryId=null, int $days=0, int $pageSize=12): array {
    $pageSize=max(1,min($pageSize,30));
    [$where,$params]=archive_conditions($search,$categoryId,$days);
    $count=$db->prepare('SELECT COUNT(*) FROM articles a'.$where);
    archive_bind($count,$params);
    $count->execute();
    $total=(int)$count->fetchColumn();
    // Cálculo inteiro evita arredondamento e normaliza página pedida fora do intervalo.
    $pages=$total>0 ? intdiv($total-1,$pageSize)+1 : 1;
    $page=max(1,min($requestedPage,$pages));
    if ($total===0) return ['rows'=>[],'total'=>0,'pages'=>1,'page'=>1,'per_page'=>$pageSize];
    $select='SELECT a.id,a.slug,a.title,a.summary,a.image,a.is_demo,a.published_at,c.name AS category,c.color AS category_color'.
        ' FROM articles a LEFT JOIN categories c ON c.id=a.category_id'.$where.
        ' ORDER BY a.published_at DESC,a.id DESC LIMIT ? OFFSET ?';
    $stmt=$db->prepare($select);
    archive_bind($stmt,$params,$pageSize,($page-1)*$pageSize);
    $stmt->execute();
    return ['rows'=>$stmt->fetchAll(),'total'=>$total,'pages'=>$pages,'page'=>$page,'per_page'=>$pageSize];
}
/** Respeita links de categoria amigáveis sem descartar período ou página. */
function archive_link(string $route, int $page, string $search='', string $slug='', int $days=0): string {
    $params=[];
    if ($days>0) $params['period']=(string)$days;
    if ($page>1) $params['page']=$page;
    if ($route==='categoria') {
        $base=url(['p'=>'categoria','slug'=>$slug]);
        return $base.($params ? (str_contains($base,'?')?'&':'?').http_build_query($params) : '');
    }
    $base=['p'=>'buscar'];
    if ($search!=='') $base['q']=$search;
    if ($slug!=='') $base['cat']=$slug;
    return url($base+$params);
}
/** Mantém uma janela pequena de botões legíveis em telas estreitas. */
function archive_pages_window(int $page, int $pages): array {
    if ($pages<=1) return [1];
    $keep=[1,$pages];
    for($i=max(1,$page-1);$i<=min($pages,$page+1);$i++)$keep[]=$i;
    $keep=array_values(array_unique($keep));sort($keep,SORT_NUMERIC);
    $list=[];$last=0;
    foreach($keep as $i){ if($last>0 && $i>$last+1)$list[]=null; $list[]=$i; $last=$i; }
    return $list;
}
function archive_navigation(array $results, string $route, string $search='', string $slug='', int $days=0): void {
    if($results['pages']<=1)return;
    $page=$results['page'];$pages=$results['pages'];
    echo '<nav class="archive-pagination" aria-label="Páginas de notícias">';
    if($page>1)echo '<a class="archive-page-prev" rel="prev" href="'.h(archive_link($route,$page-1,$search,$slug,$days)).'">← Anterior</a>';
    foreach(archive_pages_window($page,$pages) as $number){
        if($number===null){echo '<span class="archive-page-gap" aria-hidden="true">…</span>';continue;}
        if($number===$page)echo '<span class="archive-page-current" aria-current="page">'.$number.'</span>';
        else echo '<a href="'.h(archive_link($route,$number,$search,$slug,$days)).'" aria-label="Página '.$number.'">'.$number.'</a>';
    }
    if($page<$pages)echo '<a class="archive-page-next" rel="next" href="'.h(archive_link($route,$page+1,$search,$slug,$days)).'">Próxima →</a>';
    echo '</nav>';
}
