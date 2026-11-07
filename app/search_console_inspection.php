<?php
declare(strict_types=1);
/** Inspeção do estado conhecido do índice do Google para notícia publicada.
 * Nunca recebe URL remota livre nem executa indexação ou teste de URL ao vivo.
 */
function igua_search_inspection_target(array $subject,array $cfg): array {
    if(($subject['kind']??'')!=='article' || !is_array($subject['data']??null))
        throw new InvalidArgumentException('Selecione uma notícia publicada para consultar o Google.');
    $article=$subject['data'];
    $id=(int)($article['id']??0);
    if($id<=0 || ($article['status']??'')!=='published' || (int)($article['is_demo']??0)!==0)
        throw new InvalidArgumentException('A inspeção aceita somente notícias publicadas reais.');
    if(($cfg['seo_index']??'0')!=='1')
        throw new InvalidArgumentException('A indexação global do site está desativada em Geral e SEO.');
    $base=safe_seo_url((string)($cfg['seo_site_url']??''));
    if($base==='' || igua_search_console_property($cfg,'domain')==='' || !str_starts_with($base,'https://'))
        throw new InvalidArgumentException('Configure a URL pública HTTPS em Geral e SEO.');
    $relative=igua_page_inspect_relative($subject,($cfg['seo_pretty_urls']??'0')==='1');
    if($relative==='' || strlen($relative)>250)
        throw new InvalidArgumentException('A notícia não possui um slug válido para a inspeção.');
    $url=$base.$relative;
    if(strlen($url)>500 || !preg_match('~^https://[a-z0-9.-]+/(?:noticia/[a-z0-9-]+|index\.php\?p=noticia&slug=[a-z0-9-]+)$~D',$url))
        throw new InvalidArgumentException('URL canônica da notícia inválida para a inspeção.');
    return ['article_id'=>$id,'title'=>(string)($article['title']??''),'url'=>$url];
}
/** Texto externo é reduzido e escapado no HTML. Não preserva a resposta integral. */
function igua_search_inspection_text(mixed $input,int $limit=260): string {
    if(!is_string($input)||strlen($input)>4096)return '';
    $input=trim(preg_replace('/[\x00-\x1F\x7F]/u',' ',$input)??'');
    if(function_exists('mb_check_encoding')&&!mb_check_encoding($input,'UTF-8'))return '';
    return function_exists('mb_strimwidth')?mb_strimwidth($input,0,$limit,'…','UTF-8'):substr($input,0,$limit);
}
function igua_search_inspection_summary(array $api,array $target,string $property,string $mode,int $now): array {
    $status=$api['inspectionResult']['indexStatusResult']??null;
    if(!is_array($status))throw new RuntimeException('O Google não retornou o estado da indexação desta URL.');
    $verdict=$status['verdict']??'VERDICT_UNSPECIFIED';
    if(!in_array($verdict,['PASS','NEUTRAL','FAIL','PARTIAL','VERDICT_UNSPECIFIED'],true))$verdict='VERDICT_UNSPECIFIED';
    $state=match($verdict){'PASS'=>'indexed','NEUTRAL'=>'excluded','FAIL'=>'issue',default=>'unknown'};
    $date='';$rawDate=$status['lastCrawlTime']??null;
    if(is_string($rawDate) && strlen($rawDate)<=45 && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d{1,9})?Z$/D',$rawDate)) {
        try{$date=(new DateTimeImmutable($rawDate))->setTimezone(new DateTimeZone('America/Sao_Paulo'))->format('d/m/Y H:i');}catch(Throwable){}
    }
    $robots=in_array(($status['robotsTxtState']??''),['ALLOWED','DISALLOWED'],true)?$status['robotsTxtState']:'UNKNOWN';
    $indexing=in_array(($status['indexingState']??''),['INDEXING_ALLOWED','BLOCKED_BY_META_TAG','BLOCKED_BY_HTTP_HEADER'],true)?$status['indexingState']:'UNKNOWN';
    $fetch=in_array(($status['pageFetchState']??''),['SUCCESSFUL','SOFT_404','BLOCKED_ROBOTS_TXT','NOT_FOUND','ACCESS_DENIED','SERVER_ERROR','REDIRECT_ERROR','ACCESS_FORBIDDEN','BLOCKED_4XX','INTERNAL_CRAWL_ERROR','INVALID_URL'],true)?$status['pageFetchState']:'UNKNOWN';
    $recommendations=[];
    if($robots==='DISALLOWED')$recommendations[]='Confira as regras do robots.txt para esta URL.';
    if($indexing==='BLOCKED_BY_META_TAG' || $indexing==='BLOCKED_BY_HTTP_HEADER')$recommendations[]='Revise a diretiva noindex da notícia ou dos cabeçalhos HTTP.';
    if($fetch==='NOT_FOUND')$recommendations[]='Confira se a notícia responde sem erro 404.';
    if(in_array($fetch,['SERVER_ERROR','ACCESS_FORBIDDEN','ACCESS_DENIED','BLOCKED_4XX'],true))$recommendations[]='Confira o código HTTP e as permissões da página pública.';
    $googleCanonical=igua_search_inspection_text($status['googleCanonical']??null,450);
    $userCanonical=igua_search_inspection_text($status['userCanonical']??null,450);
    if($googleCanonical!=='' && $googleCanonical!==$target['url'])$recommendations[]='O Google selecionou uma URL canônica diferente. Confira redirecionamentos e a tag canonical.';
    return [
        'article_id'=>$target['article_id'],'title'=>igua_search_inspection_text($target['title'],150),
        'url'=>$target['url'],'property'=>$property,'mode'=>$mode,'checked_at'=>$now,
        'state'=>$state,'coverage'=>igua_search_inspection_text($status['coverageState']??null),
        'robots'=>$robots,'indexing'=>$indexing,'fetch'=>$fetch,'last_crawl'=>$date,
        'google_canonical'=>$googleCanonical,'user_canonical'=>$userCanonical,
        'recommendations'=>array_slice($recommendations,0,4),
    ];
}
/** Consulta manual, com OAuth somente leitura, URL obtida do banco e transporte testável. */
function igua_search_inspection_query(array $credentials,array $cfg,string $mode,array $target,?callable $transport=null,?int $now=null): array {
    $now=$now??time();
    $transport=$transport??'igua_search_console_http';
    // Mesmo na chamada direta, nunca permitir trocar a URL pela enviada no POST.
    if(!isset($target['article_id'],$target['url']) || !is_int($target['article_id']) || $target['article_id']<=0 || !is_string($target['url']) || strlen($target['url'])>500)
        throw new InvalidArgumentException('Alvo de inspeção inválido.');
    $site=safe_seo_url((string)($cfg['seo_site_url']??''));
    if($site==='' || igua_search_console_property($cfg,'domain')==='' || !str_starts_with($target['url'],$site.'/') || !preg_match('~^https://[a-z0-9.-]+/(?:noticia/[a-z0-9-]+|index\.php\?p=noticia&slug=[a-z0-9-]+)$~D',$target['url']))
        throw new InvalidArgumentException('A URL deve pertencer ao portal configurado.');
    $access=igua_search_console_authorize($credentials,$cfg,$mode,$transport,$now);
    $data=igua_search_console_response($transport('POST','https://searchconsole.googleapis.com/v1/urlInspection/index:inspect',$access['headers'],
        ['inspectionUrl'=>$target['url'],'siteUrl'=>$access['property'],'languageCode'=>'pt-BR'],false),'inspeção');
    return igua_search_inspection_summary($data,$target,$access['property'],$mode,$now);
}
/** Resultados mínimos da sessão, vinculados à notícia/URL/propriedade, sem token. */
function igua_search_inspection_last(array $cfg,array $target,array $session,?int $now=null): ?array {
    $now=$now??time();
    $last=$session['igua_search_inspection_last']??null;
    if(!is_array($last)||!is_int($last['checked_at']??null)||$last['checked_at']>$now||$now-$last['checked_at']>3600)return null;
    $mode=(string)($cfg['search_console_property_mode']??'domain');
    try{$property=igua_search_console_property($cfg,$mode);}catch(Throwable){return null;}
    if($property==='' || ($last['property']??'')!==$property || ($last['mode']??'')!==$mode ||
       ($last['article_id']??0)!==($target['article_id']??0) || ($last['url']??'')!==($target['url']??''))return null;
    return $last;
}
