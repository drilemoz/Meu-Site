<?php
declare(strict_types=1);
require_once __DIR__.'/news_queries.php';

/** Preferências na tabela settings existente. IDs mantêm a escolha ao renomear categorias. */
function igua_home_preferences(array $categories,string $rawIds='',string $rawRegion=''): array {
    $available=[];$defaults=[];$defaultRegion=0;
    foreach ($categories as $c) {
        $id=(int)$c['id'];$available[$id]=true;
        if (in_array($c['slug'],['cidade','politica','eventos'],true)) $defaults[]=$id;
        if (!$defaultRegion && in_array($c['slug'],['regiao','regional','noticias-da-regiao'],true)) $defaultRegion=$id;
    }
    $decoded=json_decode($rawIds,true);
    $ids=is_array($decoded)&&array_is_list($decoded)?array_values(array_unique(array_filter($decoded,static fn($id):bool=>is_int($id)&&isset($available[$id])))):$defaults;
    // O valor '0' de setting() é tratado como ausência; persistir 'none' para desativar explicitamente.
    $region=$rawRegion===''?$defaultRegion:(ctype_digit($rawRegion)?(int)$rawRegion:0);
    return ['ids'=>$ids,'region'=>isset($available[$region])?$region:0];
}
function igua_home_validate_preferences(array $categories,array $post): array {
    $known=array_fill_keys(array_map(static fn($c)=>(int)$c['id'],$categories),true);
    $raw=$post['home_category_ids']??[];
    if (!is_array($raw) || !array_is_list($raw) || count($raw)>count($known)) throw new InvalidArgumentException('Seleção de editorias inválida.');
    $ids=[];
    foreach ($raw as $id) {
        if (!is_string($id)||!ctype_digit($id)||!isset($known[(int)$id])) throw new InvalidArgumentException('Editoria inválida ou removida. Recarregue as configurações.');
        $ids[]=(int)$id;
    }
    $region=$post['home_region_category']??'none';
    if (!is_string($region)||($region!=='none'&&(!ctype_digit($region)||!isset($known[(int)$region])))) throw new InvalidArgumentException('Categoria regional inválida.');
    return ['home_category_ids'=>json_encode(array_values(array_unique($ids)),JSON_THROW_ON_ERROR),'home_region_category'=>$region];
}
/** Um único registro de IDs por renderização. Nenhum componente consulta notícias por conta própria. */
function igua_home_news(PDO $db,array $categories,array $prefs): array {
    $seen=[];
    $take=static function(array $rows,int $limit) use (&$seen):array {
        $out=[];
        foreach ($rows as $row) {
            $id=(int)$row['id'];
            if ($id<=0 || isset($seen[$id])) continue;
            $seen[$id]=true;$out[]=$row;
            if(count($out)>=$limit)break;
        }
        return $out;
    };
    $featured=fetch_featured_news($db);
    $featured=$featured?($take([$featured],1)[0]??false):false;
    $recent=$take(fetch_news($db,4,null,null,array_keys($seen)),4);
    $sections=[];$ordered=[];
    // Cidade tem precedência. A categoria regional explícita vem a seguir, depois a ordem do ADM.
    foreach ($categories as $c) if ($c['slug']==='cidade'&&(int)$c['id']!==$prefs['region']&&in_array((int)$c['id'],$prefs['ids'],true)) $ordered[(int)$c['id']]=$c;
    foreach ($categories as $c) if ((int)$c['id']===$prefs['region']) $ordered[(int)$c['id']]=$c;
    foreach ($categories as $c) if (in_array((int)$c['id'],$prefs['ids'],true)) $ordered[(int)$c['id']]=$c;
    foreach ($ordered as $id=>$c) {
        $rows=$take(fetch_news($db,3,$id,null,array_keys($seen)),3);
        if (!$rows) continue;
        $sections[]=['category'=>$c,'title'=>$id===$prefs['region']?'Acontece na região':$c['name'],'rows'=>$rows];
    }
    $popular=$take(fetch_popular_news($db,array_keys($seen)),5);
    return compact('featured','recent','sections','popular');
}
