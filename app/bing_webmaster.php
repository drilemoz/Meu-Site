<?php
declare(strict_types=1);
/** Bing Webmaster: leitura autenticada dos sites verificados e feeds/sitemaps.
 * O IndexNow já cuida do envio de URLs. Não enviar URL nem chamar API automaticamente.
 * A API legada JSON do Bing poderá mudar; diagnosticar falha sem prometer cobertura/indexação.
 */
function igua_bing_key(string $value): string {
    $value=trim($value);
    if(!preg_match('/^[A-Za-z0-9_-]{12,256}$/D',$value))throw new InvalidArgumentException('Chave Bing inválida. Confira o valor em Bing Webmaster Tools → Configurações → API Access.');
    return $value;
}
function igua_bing_file(string $storage): string {return rtrim($storage,'/').'/integrations/bing-webmaster.json';}
function igua_bing_read(string $storage): ?array {
    $path=igua_bing_file($storage);
    if(!is_file($path)||is_link($path))return null;
    $raw=@file_get_contents($path);
    if(!is_string($raw)||strlen($raw)>4096)return null;
    $parsed=json_decode($raw,true);
    if(!is_array($parsed)||!is_string($parsed['key']??null))return null;
    try{$key=igua_bing_key($parsed['key']);}catch(Throwable){return null;}
    return ['key'=>$key];
}
function igua_bing_store(string $storage,string $key): void {
    $key=igua_bing_key($key);
    $dir=rtrim($storage,'/').'/integrations';
    if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new RuntimeException('Não foi possível criar armazenamento privado.');
    if(is_link($dir)||!is_writable($dir))throw new RuntimeException('Armazenamento privado sem permissão de escrita.');
    $path=igua_bing_file($storage);
    if(is_link($path))throw new RuntimeException('Destino de credencial inválido.');
    $data=json_encode(['key'=>$key],JSON_THROW_ON_ERROR);
    $tmp=$path.'.'.bin2hex(random_bytes(8)).'.tmp';
    try{
        if(@file_put_contents($tmp,$data,LOCK_EX)!==strlen($data))throw new RuntimeException('Falha ao salvar chave Bing.');
        @chmod($tmp,0600);
        if(!@rename($tmp,$path))throw new RuntimeException('Não foi possível finalizar a gravação da chave.');
    }catch(Throwable $e){@unlink($tmp);throw $e;}
}
function igua_bing_remove(string $storage): void {
    $path=igua_bing_file($storage);
    if(is_link($path))throw new RuntimeException('Credencial inválida.');
    if(is_file($path)&&!@unlink($path))throw new RuntimeException('Não foi possível remover a chave Bing.');
}
function igua_bing_http(string $method,string $key,?string $site=null): array {
    if(!in_array($method,['GetUserSites','GetFeeds'],true))throw new InvalidArgumentException('Método Bing inválido.');
    if(!function_exists('curl_init'))throw new RuntimeException('Extensão PHP cURL indisponível.');
    $query=['apikey'=>igua_bing_key($key)];
    if($method==='GetFeeds'){
        if($site===null || !filter_var($site,FILTER_VALIDATE_URL) || !in_array(parse_url($site,PHP_URL_SCHEME),['https','http'],true))throw new InvalidArgumentException('Propriedade Bing inválida.');
        $query['siteUrl']=$site;
    }
    // Host e método fixos: sem proxy nem URLs fornecidas pelo usuário.
    $url='https://ssl.bing.com/webmaster/api.svc/json/'.$method.'?'.http_build_query($query,'','&',PHP_QUERY_RFC3986);
    $curl=curl_init($url);
    if(!$curl)throw new RuntimeException('Não foi possível iniciar a consulta ao Bing.');
    $body='';
    curl_setopt_array($curl,[CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,
        CURLOPT_CONNECTTIMEOUT=>7,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_HTTPHEADER=>['Accept: application/json'],
        CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk)use(&$body):int{
            if(strlen($body)+strlen($chunk)>65536)return 0;
            $body.=$chunk;return strlen($chunk);
        }]);
    $ok=curl_exec($curl);$status=(int)curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);
    if($ok===false)throw new RuntimeException('Não foi possível acessar o Bing via HTTPS. Verifique DNS, TLS, saída de rede ou limite da resposta.');
    return ['status'=>$status,'body'=>$body];
}
/** Aceita transporte injetável para testes sem rede; retorna só campos públicos resumidos. */
function igua_bing_request(string $method,string $key,?string $site=null,?callable $transport=null): array {
    $key=igua_bing_key($key);
    if(!in_array($method,['GetUserSites','GetFeeds'],true))throw new InvalidArgumentException('Consulta não permitida.');
    $res=($transport??'igua_bing_http')($method,$key,$site);
    $status=(int)($res['status']??0);
    if($status!==200){
        $reason=match($status){400=>'Chave inválida ou parâmetros recusados.',401,403=>'Acesso negado. Confira a chave e a propriedade verificada.',404=>'Método não encontrado. A API do Bing pode ter mudado.',429=>'Cota ou limite de consultas atingido.',500,502,503,504=>'Serviço temporariamente indisponível.',default=>'Confira API Access e conectividade da hospedagem.'};
        throw new RuntimeException('Bing HTTP '.$status.': '.$reason);
    }
    $raw=$res['body']??null;
    if(!is_string($raw)||strlen($raw)>65536)throw new RuntimeException('Resposta Bing inválida ou grande demais.');
    $payload=json_decode($raw,true);
    if(!is_array($payload)||!array_key_exists('d',$payload)||!is_array($payload['d']))throw new RuntimeException('Formato de resposta Bing inesperado. A API pode ter mudado.');
    return $payload['d'];
}
/** Normaliza somente origin raiz; não aceita URL pública arbitrária. */
function igua_bing_origin(string $site): string {
    $url=parse_url(trim($site));
    if(!is_array($url)||($url['scheme']??'')!=='https'||!is_string($url['host']??null)||!preg_match('/^[a-z0-9.-]+$/i',$url['host'])||isset($url['user'])||isset($url['pass'])||isset($url['port'])||($url['path']??'/')!=='/'&&($url['path']??'')!==''||isset($url['query'])||isset($url['fragment']))throw new InvalidArgumentException('Configure o endereço HTTPS oficial no SEO.');
    return 'https://'.strtolower($url['host']);
}
function igua_bing_check(string $key,string $configuredSite,?callable $transport=null): array {
    $expected=igua_bing_origin($configuredSite);
    $entries=igua_bing_request('GetUserSites',$key,null,$transport);
    $match=null;
    foreach($entries as $entry){
        if(!is_array($entry)||!is_string($entry['Url']??null))continue;
        if(strcasecmp(rtrim($entry['Url'],'/'),$expected)===0){$match=$entry;break;}
    }
    if($match===null)return ['site'=>$expected,'verified'=>false,'feeds'=>[],'message'=>'O Bing respondeu, mas esta propriedade HTTPS não está cadastrada na conta. Adicione e verifique o domínio no Bing Webmaster Tools.'];
    if(empty($match['IsVerified']))return ['site'=>$expected,'verified'=>false,'feeds'=>[],'message'=>'A propriedade foi encontrada, mas ainda não está verificada no Bing Webmaster Tools.'];
    $feeds=igua_bing_request('GetFeeds',$key,(string)$match['Url'],$transport);
    $summary=[];
    foreach($feeds as $feed){
        if(!is_array($feed))continue;
        $url=$feed['Url']??null;
        if(!is_string($url)||strlen($url)>2048||!filter_var($url,FILTER_VALIDATE_URL))continue;
        $summary[]=['url'=>$url,'status'=>substr((string)($feed['Status']??'Não informado'),0,64),'count'=>max(0,(int)($feed['UrlCount']??0))];
        if(count($summary)>=30)break;
    }
    return ['site'=>$expected,'verified'=>true,'feeds'=>$summary,'message'=>'Consulta realizada. Estes dados são do Bing; sitemap recebido não equivale a página indexada.'];
}
