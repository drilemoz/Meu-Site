<?php
declare(strict_types=1);
/**
 * Dados reais da Search Console API sob demanda. A conta de serviço Google já
 * armazenada para GA4 é reutilizada, mas precisa ser adicionada separadamente
 * como usuário autorizado da propriedade Search Console.
 * Nenhuma URL arbitrária é aceita; ambas as propriedades vêm de Geral e SEO.
 */
function igua_search_console_property_mode(string $mode): string {
    if(!in_array($mode,['domain','prefix'],true))throw new InvalidArgumentException('Tipo de propriedade Search Console inválido.');
    return $mode;
}
/** @return array{domain:string,prefix:string} */
function igua_search_console_properties(array $cfg): array {
    $empty=['domain'=>'','prefix'=>''];
    $domain=igua_search_dns_domain($cfg);
    if($domain==='')return $empty;
    try{$canonical=safe_seo_url((string)($cfg['seo_site_url']??''));}
    catch(Throwable){return $empty;}
    $parts=parse_url($canonical);
    if(!is_array($parts) || ($parts['scheme']??'')!=='https' || isset($parts['port']) ||
       !in_array((string)($parts['path']??''),['','/'],true))return $empty;
    // A propriedade de domínio normalmente fica no ápice, sem o prefixo www.
    $root=preg_replace('/^www\./','',$domain);
    return ['domain'=>'sc-domain:'.$root,'prefix'=>'https://'.$domain.'/'];
}
function igua_search_console_property(array $cfg,string $mode): string {
    return igua_search_console_properties($cfg)[igua_search_console_property_mode($mode)];
}
function igua_search_console_response(array $response,string $stage): array {
    $status=(int)($response['status']??0);
    if($status===401)throw new RuntimeException('Autenticação Google recusada (401). Atualize a credencial de serviço.');
    if($status===403)throw new RuntimeException('Search Console recusou o acesso (403). Ative a Search Console API e dê permissão de leitura à conta de serviço na propriedade.');
    if($status===404)throw new RuntimeException('Propriedade Search Console não encontrada (404). Confira o domínio e o tipo de propriedade.');
    if($status===429)throw new RuntimeException('Limite de consultas do Search Console atingido. Tente mais tarde.');
    if($status<200 || $status>=300)throw new RuntimeException('Consulta Search Console indisponível (HTTP '.$status.', etapa '.$stage.').');
    $body=$response['body']??null;
    if(!is_string($body) || strlen($body)>262144)throw new RuntimeException('Resposta da API Google inválida ou excessiva.');
    $data=json_decode($body,true,32);
    if(!is_array($data))throw new RuntimeException('Search Console não retornou um JSON válido.');
    return $data;
}
/** Não aceita destinos nem ações fora das operações Google autorizadas. */
function igua_search_console_endpoint_allowed(string $method,string $url,bool $form): bool {
    return ($method==='POST' && $form && $url==='https://oauth2.googleapis.com/token')
        || ($method==='GET' && !$form && $url==='https://www.googleapis.com/webmasters/v3/sites')
        || ($method==='POST' && !$form && $url==='https://searchconsole.googleapis.com/v1/urlInspection/index:inspect')
        || ($method==='POST' && !$form && preg_match('~^https://www\.googleapis\.com/webmasters/v3/sites/(?:sc-domain%3A[a-z0-9.-]{4,253}|https%3A%2F%2F[a-z0-9.-]{4,253}%2F)/searchAnalytics/query$~D',$url)===1);
}
/** Transporte com endpoints fixos, HTTPS validado, sem redirecionamentos ou URL do usuário. */
function igua_search_console_http(string $method,string $url,array $headers,array $body,bool $form=false): array {
    if(!igua_search_console_endpoint_allowed($method,$url,$form))throw new RuntimeException('Endpoint da Search Console API não permitido.');
    if(!function_exists('curl_init'))throw new RuntimeException('O PHP da hospedagem precisa da extensão cURL para consultar a Search Console API.');
    $payload=$form?http_build_query($body,'','&',PHP_QUERY_RFC3986):json_encode($body,JSON_THROW_ON_ERROR);
    $ch=curl_init($url);
    if($ch===false)throw new RuntimeException('Falha ao iniciar conexão Google.');
    $data='';$overflow=false;
    $options=[CURLOPT_HTTPHEADER=>array_merge(['Accept: application/json'], $method==='POST'?[$form?'Content-Type: application/x-www-form-urlencoded':'Content-Type: application/json']:[], $headers),
        CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>15,
        CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_WRITEFUNCTION=>static function($c,string $chunk)use(&$data,&$overflow):int{
            if(strlen($data)+strlen($chunk)>262144){$overflow=true;return 0;}
            $data.=$chunk;return strlen($chunk);
        }];
    if($method==='POST'){$options[CURLOPT_POST]=true;$options[CURLOPT_POSTFIELDS]=$payload;}
    curl_setopt_array($ch,$options);
    $ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    if($overflow)throw new RuntimeException('Google retornou uma resposta acima do limite seguro.');
    if($ok===false)throw new RuntimeException('Falha HTTPS ao consultar Google. Confira rede e certificados do servidor.');
    return ['status'=>$status,'body'=>$data];
}
function igua_search_console_metric($raw,float $max): ?float {
    if((!is_int($raw) && !is_float($raw)) || !is_finite((float)$raw))return null;
    $v=(float)$raw;
    if($v<0 || $v>$max)return null;
    return $v;
}
/** Nunca exibe nem mantém JSON completo retornado pela API. */
function igua_search_console_total(array $api): ?array {
    $rows=$api['rows']??[];
    if(!is_array($rows)||$rows===[])return null;
    $row=$rows[0]??null;
    if(!is_array($row))return null;
    $clicks=igua_search_console_metric($row['clicks']??null,1e12);
    $impressions=igua_search_console_metric($row['impressions']??null,1e12);
    $ctr=igua_search_console_metric($row['ctr']??null,1);
    $position=igua_search_console_metric($row['position']??null,100000);
    if($clicks===null||$impressions===null||$ctr===null||$position===null)return null;
    return ['clicks'=>(int)round($clicks),'impressions'=>(int)round($impressions),
        'ctr'=>round($ctr*100,1),'position'=>round($position,1)];
}
/** @return list<array{label:string,clicks:int,impressions:int}> */
function igua_search_console_ranked(array $api,string $kind): array {
    if(!in_array($kind,['query','page'],true))throw new InvalidArgumentException('Dimensão desconhecida.');
    $out=[];
    foreach(array_slice(is_array($api['rows']??null)?$api['rows']:[],0,8) as $row){
        if(!is_array($row)||!is_array($row['keys']??null))continue;
        $name=$row['keys'][0]??null;
        if(!is_string($name)||$name===''||strlen($name)>1024)continue;
        $name=trim(preg_replace('/[\x00-\x1F\x7F]/u',' ',$name)??'');
        if($name===''||(function_exists('mb_check_encoding')&&!mb_check_encoding($name,'UTF-8')))continue;
        if($kind==='page' && !preg_match('~^https://[a-zA-Z0-9.-]+(?:/|$)~D',$name))continue;
        $clicks=igua_search_console_metric($row['clicks']??null,1e12);
        $impressions=igua_search_console_metric($row['impressions']??null,1e12);
        if($clicks===null||$impressions===null)continue;
        $out[]=['label'=>function_exists('mb_strimwidth')?mb_strimwidth($name,0,160,'…','UTF-8'):substr($name,0,160),'clicks'=>(int)round($clicks),'impressions'=>(int)round($impressions)];
    }
    return $out;
}
/** Compartilha OAuth de leitura e checagem de acesso da propriedade sem cachear tokens.
 * @return array{property:string,permission:string,headers:array}
 */
function igua_search_console_authorize(array $credentials,array $cfg,string $mode,?callable $transport=null,?int $now=null): array {
    $property=igua_search_console_property($cfg,$mode);
    if($property==='')throw new InvalidArgumentException('Configure a URL HTTPS oficial sem porta/caminho em Geral e SEO.');
    $creds=igua_ga4_decode_service_account(json_encode($credentials,JSON_THROW_ON_ERROR));
    $transport=$transport??'igua_search_console_http';
    $now=$now??time();
    $head=rtrim(strtr(base64_encode(json_encode(['alg'=>'RS256','typ'=>'JWT'],JSON_THROW_ON_ERROR)),'+/','-_'),'=');
    $claims=['iss'=>$creds['client_email'],'scope'=>'https://www.googleapis.com/auth/webmasters.readonly',
        'aud'=>'https://oauth2.googleapis.com/token','iat'=>$now-30,'exp'=>$now+3300];
    $body=rtrim(strtr(base64_encode(json_encode($claims,JSON_THROW_ON_ERROR)),'+/','-_'),'=');
    $payload=$head.'.'.$body;$sig='';
    if(!openssl_sign($payload,$sig,$creds['private_key'],OPENSSL_ALGO_SHA256))throw new RuntimeException('Falha ao assinar a autenticação Google.');
    $jwt=$payload.'.'.rtrim(strtr(base64_encode($sig),'+/','-_'),'=');
    $auth=igua_search_console_response($transport('POST','https://oauth2.googleapis.com/token',[],
        ['grant_type'=>'urn:ietf:params:oauth:grant-type:jwt-bearer','assertion'=>$jwt],true),'autenticação');
    $token=$auth['access_token']??null;
    if(!is_string($token)||!preg_match('/^[A-Za-z0-9._~+\/-]{10,4096}$/D',$token))throw new RuntimeException('O Google não forneceu token de acesso válido.');
    $headers=['Authorization: Bearer '.$token];
    // Valida a permissão da propriedade de fato. DNS TXT sozinho não autoriza a API.
    $sites=igua_search_console_response($transport('GET','https://www.googleapis.com/webmasters/v3/sites',$headers,[],false),'propriedades');
    $permission='';
    foreach(array_slice(is_array($sites['siteEntry']??null)?$sites['siteEntry']:[],0,1000) as $entry){
        if(is_array($entry) && ($entry['siteUrl']??null)===$property){
            $permission=(string)($entry['permissionLevel']??'');break;
        }
    }
    if(!in_array($permission,['siteOwner','siteFullUser','siteRestrictedUser'],true))
        throw new RuntimeException('A conta de serviço não tem acesso à propriedade selecionada. Adicione o e-mail dela como usuário no Search Console, com permissão de leitura, e confirme se escolheu Domínio ou Prefixo de URL.');
    return ['property'=>$property,'permission'=>$permission,'headers'=>$headers];
}
/** Um teste real, só acionado pelo administrador; transporte injetável para CI offline. */
function igua_search_console_query(array $credentials,array $cfg,string $mode,?callable $transport=null,?int $now=null): array {
    $now=$now??time();
    $transport=$transport??'igua_search_console_http';
    $access=igua_search_console_authorize($credentials,$cfg,$mode,$transport,$now);
    $property=$access['property'];$permission=$access['permission'];$headers=$access['headers'];
    $date=(new DateTimeImmutable('@'.$now))->setTimezone(new DateTimeZone('America/Los_Angeles'));
    $start=$date->modify('-30 days')->format('Y-m-d');
    $end=$date->modify('-3 days')->format('Y-m-d');
    $url='https://www.googleapis.com/webmasters/v3/sites/'.rawurlencode($property).'/searchAnalytics/query';
    $base=['startDate'=>$start,'endDate'=>$end,'type'=>'web'];
    $total=igua_search_console_response($transport('POST',$url,$headers,$base+['rowLimit'=>1],false),'resumo');
    $query=igua_search_console_response($transport('POST',$url,$headers,$base+['dimensions'=>['query'],'rowLimit'=>8],false),'pesquisas');
    $pages=igua_search_console_response($transport('POST',$url,$headers,$base+['dimensions'=>['page'],'rowLimit'=>8],false),'páginas');
    $summary=igua_search_console_total($total);
    return ['property'=>$property,'mode'=>$mode,'permission'=>$permission,'start'=>$start,'end'=>$end,
        'summary'=>$summary,'queries'=>igua_search_console_ranked($query,'query'),
        'pages'=>igua_search_console_ranked($pages,'page'),'checked_at'=>$now];
}
function igua_search_console_last(array $cfg,array $session,?int $now=null): ?array {
    $now=$now??time();
    $result=$session['igua_search_console_api_last']??null;
    if(!is_array($result)||!is_int($result['checked_at']??null)||$now<$result['checked_at']||$now-$result['checked_at']>3600)return null;
    $mode=(string)($cfg['search_console_property_mode']??'domain');
    try{$property=igua_search_console_property($cfg,$mode);}catch(Throwable){return null;}
    if($property===''||($result['property']??'')!==$property||($result['mode']??'')!==$mode)return null;
    return $result;
}
