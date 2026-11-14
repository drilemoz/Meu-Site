<?php
declare(strict_types=1);
/** IguaNews: serviço editorial, módulo import. Requer bootstrap.php no ponto de entrada. */

function news_safe_url(string $url): array {
    if(strlen($url)>600||!filter_var($url,FILTER_VALIDATE_URL))throw new RuntimeException('URL inválida ou muito longa.');
    $parts=parse_url($url);
    if(!is_array($parts)||strtolower((string)($parts['scheme']??''))!=='https'||!isset($parts['host'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['port'])||isset($parts['fragment']))throw new RuntimeException('Use URL HTTPS pública sem porta personalizada, credenciais ou fragmentos.');
    $host=strtolower($parts['host']);
    if(filter_var($host,FILTER_VALIDATE_IP)||!preg_match('/^[a-z0-9-]+(?:\.[a-z0-9-]+)+$/D',$host)||str_ends_with($host,'.local')) throw new RuntimeException('Informe o nome público de um site de notícias.');
    return [$url,$host];
}

function news_public_ipv4(string $ip): bool {
    if(!filter_var($ip,FILTER_VALIDATE_IP,FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return false;
    $n=ip2long($ip);
    // CGNAT 100.64/10 e outras redes especiais são indevidas para scraping.
    if($n===false)return false;
    $n=(int)sprintf('%u',$n);
    foreach([['100.64.0.0',10],['192.0.0.0',24],['198.18.0.0',15]] as [$base,$prefix]){
        $mask=(~(2**(32-$prefix)-1))&0xffffffff;
        if(($n & $mask)===((int)sprintf('%u',ip2long($base))&$mask))return false;
    }
    return true;
}

function news_meta_from_html(string $html,string $host): array {
    $meta=[];
    preg_match_all('/<meta\b[^>]*>/i',$html,$matches);
    foreach($matches[0] as $tag){
        $attrs=[];
        preg_match_all('/([a-z_:.-]+)\s*=\s*(["\'])(.*?)\2/is',$tag,$tokens,PREG_SET_ORDER);
        foreach($tokens as $part)$attrs[strtolower($part[1])]=html_entity_decode($part[3],ENT_QUOTES|ENT_HTML5,'UTF-8');
        $key=strtolower($attrs['property']??$attrs['name']??'');
        if($key!==''&&isset($attrs['content'])&&!isset($meta[$key]))$meta[$key]=$attrs['content'];
    }
    preg_match('/<title\b[^>]*>(.*?)<\/title>/is',$html,$title);
    $pageTitle=trim(html_entity_decode(strip_tags($title[1]??''),ENT_QUOTES|ENT_HTML5,'UTF-8'));
    $out=['title'=>$meta['og:title']??$meta['twitter:title']??$pageTitle,
        'summary'=>$meta['og:description']??$meta['description']??'',
        'author'=>$meta['author']??'', 'published'=>$meta['article:published_time']??'',
        'source_name'=>$meta['og:site_name']??$host,'image_reference'=>$meta['og:image']??''];
    preg_match_all('/<script\b[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is',$html,$scripts);
    foreach($scripts[1] as $js){
        $tree=json_decode(html_entity_decode($js,ENT_QUOTES|ENT_HTML5,'UTF-8'),true);
        if(!is_array($tree))continue;
        $items=isset($tree['@graph'])&&is_array($tree['@graph'])?$tree['@graph']:(array_is_list($tree)?$tree:[$tree]);
        foreach($items as $item){if(!is_array($item))continue;
            $type=$item['@type']??'';$types=is_array($type)?$type:[$type];
            if(!array_intersect($types,['NewsArticle','Article','BlogPosting']))continue;
            $out['title']=is_string($item['headline']??null)?$item['headline']:$out['title'];
            $out['summary']=is_string($item['description']??null)?$item['description']:$out['summary'];
            $out['published']=is_string($item['datePublished']??null)?$item['datePublished']:$out['published'];
            $a=$item['author']??null;
            if(is_array($a))$out['author']=is_string($a['name']??null)?$a['name']:$out['author'];
            break 2;
        }
    }
    foreach(['title'=>220,'summary'=>480,'author'=>120,'source_name'=>160,'image_reference'=>600,'published'=>60] as $field=>$len)$out[$field]=usub(trim(preg_replace('/\s+/u',' ',strip_tags((string)$out[$field]))??''),0,$len);
    if($out['title']==='') throw new RuntimeException('A página não forneceu título legível. Cadastre a matéria manualmente.');
    return $out;
}

function news_import_url(string $url): array {
    [$url,$host]=news_safe_url($url);
    if(!function_exists('curl_init'))throw new RuntimeException('A extensão PHP cURL é necessária para importar URLs com segurança.');
    $ips=gethostbynamel($host);
    if(!$ips||!is_array($ips)||count($ips)>20)throw new RuntimeException('Não foi possível verificar os endereços do domínio.');
    foreach($ips as $ip)if(!news_public_ipv4($ip))throw new RuntimeException('O domínio resolve para rede não pública e foi bloqueado.');
    $html='';$tooLarge=false;$curl=curl_init($url);
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROXY=>'',CURLOPT_NOPROXY=>'*',
        CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>10,CURLOPT_USERAGENT=>'IguaNewsMetadata/1.0 (+editor assistido)',
        CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_RESOLVE=>[$host.':443:'.$ips[0]],
        CURLOPT_WRITEFUNCTION=>static function($ch,string $chunk) use (&$html,&$tooLarge): int {
            if(strlen($html)+strlen($chunk)>800000){$tooLarge=true;return 0;}$html.=$chunk;return strlen($chunk);
        }]);
    $ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);
    $mime=strtolower((string)curl_getinfo($curl,CURLINFO_CONTENT_TYPE));$error=curl_error($curl);curl_close($curl);
    if($tooLarge)throw new RuntimeException('Página muito grande para análise (limite de 800 KB).');
    if($ok===false)throw new RuntimeException('Não foi possível acessar a página: '.usub($error,0,150));
    if($status>=300&&$status<400)throw new RuntimeException('A fonte redireciona para outra URL. Cole a URL final no editor.');
    if($status!==200||!str_contains($mime,'text/html'))throw new RuntimeException('A fonte não retornou uma página HTML pública (HTTP '.$status.').');
    $data=news_meta_from_html($html,$host);$data['source_url']=$url;
    return $data;
}

