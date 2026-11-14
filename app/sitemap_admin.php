<?php
declare(strict_types=1);
require_once __DIR__.'/sitemap_catalog.php';
require_once __DIR__.'/adsense_review.php';

/** A lista administrativa representa apenas o sitemap potencial, com busca local segura. */
function igua_sitemap_filter(array $items,string $kind,string $query):array {
    $valid=['all','noticia','categoria','aplicativo','site'];
    if(!in_array($kind,$valid,true))$kind='all';
    $query=trim($query);
    return array_values(array_filter($items,static function(array $item)use($kind,$query):bool{
        if($kind!=='all'&&($item['kind']??'')!==$kind)return false;
        if($query==='')return true;
        $text=(string)($item['loc']??'').' '.(string)($item['label']??'');
        return function_exists('mb_stripos')?mb_stripos($text,$query,0,'UTF-8')!==false:stripos($text,$query)!==false;
    }));
}
function igua_sitemap_kind_title(string $kind):string {
    return ['site'=>'Portal','categoria'=>'Categoria','noticia'=>'Notícia','aplicativo'=>'Aplicativo'][$kind]??'Página';
}
function igua_sitemap_admin_query(string $kind,string $query,int $page=1):string {
    return '/admin.php?'.http_build_query(['action'=>'sitemap','kind'=>$kind,'q'=>$query,'page'=>$page],'','&',PHP_QUERY_RFC3986);
}
