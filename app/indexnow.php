<?php
declare(strict_types=1);
/** IndexNow: configuração explícita e notificações manuais só para URLs publicadas do sitemap.
 * Esta integração NÃO consulta o Google e NÃO garante indexação. */
require_once __DIR__.'/sitemap_catalog.php';

function igua_indexnow_file(string $storage): string { return rtrim($storage,'/').'/integrations/indexnow.json'; }
function igua_indexnow_key_valid(string $key): bool { return preg_match('/^[A-Za-z0-9-]{8,128}$/D',$key)===1; }
function igua_indexnow_read(string $storage): ?array {
    $path=igua_indexnow_file($storage);
    if(!is_file($path)||is_link($path))return null;
    $raw=@file_get_contents($path);
    if(!is_string($raw)||strlen($raw)>4096)return null;
    $data=json_decode($raw,true);
    if(!is_array($data)||!igua_indexnow_key_valid((string)($data['key']??'')))return null;
    try{$base=igua_sitemap_base((string)($data['site']??''));}catch(Throwable){return null;}
    return ['key'=>$data['key'],'site'=>$base];
}
function igua_indexnow_key_path(string $public,string $key): string {
    if(!igua_indexnow_key_valid($key))throw new InvalidArgumentException('Chave IndexNow inválida.');
    return rtrim($public,'/').'/'.$key.'.txt';
}
function igua_indexnow_key_url(array $cfg): string {
    return $cfg['site'].'/'.rawurlencode($cfg['key']).'.txt';
}
function igua_indexnow_file_ready(array $cfg,string $public): bool {
    // Na VPS o Nginx serve a chave por um endpoint PHP específico. Não requer
    // escrita em public/. A resposta HTTPS real ainda precisa ser testada.
    if(getenv('IGUA_INDEXNOW_VIRTUAL')==='1')
        return igua_indexnow_key_valid((string)($cfg['key']??''))&&is_string($cfg['site']??null);
    $path=igua_indexnow_key_path($public,$cfg['key']);
    return is_file($path)&&!is_link($path)&&is_string($value=@file_get_contents($path))&&trim($value)===$cfg['key'];
}
/** Ativação gera a chave aleatória no servidor; nunca aceitar chave do navegador.
 * O arquivo público comprova domínio conforme protocolo IndexNow. */
function igua_indexnow_setup(string $configuredSite,string $public,string $storage): array {
    $site=igua_sitemap_base($configuredSite);
    $existing=igua_indexnow_read($storage);
    if($existing!==null){
        if($existing['site']!==$site)throw new RuntimeException('O domínio HTTPS mudou. Revise o arquivo de verificação antes de reconfigurar o IndexNow.');
        if(!igua_indexnow_file_ready($existing,$public))throw new RuntimeException('O arquivo de verificação não existe ou está incorreto. Confira permissões de escrita na raiz pública.');
        return $existing;
    }
    $virtual=getenv('IGUA_INDEXNOW_VIRTUAL')==='1';
    if(!$virtual && (!is_dir($public)||!is_writable($public)))throw new RuntimeException('A raiz pública não permite criar o arquivo de verificação IndexNow. Confira as permissões da hospedagem.');
    $folder=rtrim($storage,'/').'/integrations';
    if(!is_dir($folder)&&!@mkdir($folder,0700,true)&&!is_dir($folder))throw new RuntimeException('Não foi possível criar a pasta privada de integrações.');
    if(is_link($folder)||!is_writable($folder))throw new RuntimeException('Pasta privada de integrações sem escrita segura.');
    $key=bin2hex(random_bytes(16));
    $pubPath=igua_indexnow_key_path($public,$key);
    if(file_exists($pubPath)||is_link($pubPath))throw new RuntimeException('Arquivo IndexNow já existe.');
    $data=json_encode(['key'=>$key,'site'=>$site],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $private=igua_indexnow_file($storage);
    if(file_exists($private)||is_link($private))throw new RuntimeException('Uma configuração IndexNow já existe. Confira-a antes de ativar novamente.');
    // Exclusivo evita sobrepor arquivos de usuário ou de outras instalações.
    $handle=$virtual?null:@fopen($pubPath,'x');
    if(!$virtual){
        if(!$handle)throw new RuntimeException('Não foi possível gerar o arquivo público IndexNow.');
        try {
            if(fwrite($handle,$key)!==strlen($key))throw new RuntimeException('Falha ao escrever verificação IndexNow.');
        } catch(Throwable $e){fclose($handle);@unlink($pubPath);throw $e;}
        fclose($handle);@chmod($pubPath,0644);
    }
    $tmp=$private.'.'.bin2hex(random_bytes(6)).'.tmp';
    try {
        if(@file_put_contents($tmp,$data,LOCK_EX)!==strlen($data))throw new RuntimeException('Não foi possível salvar IndexNow em storage privado.');
        @chmod($tmp,0600);
        if(!@rename($tmp,$private))throw new RuntimeException('Não foi possível finalizar a configuração IndexNow.');
    }catch(Throwable $e){@unlink($tmp);if(!$virtual)@unlink($pubPath);throw $e;}
    return ['site'=>$site,'key'=>$key];
}
/** Seleciona apenas URLs emitidas pela fonte única do sitemap; não aceita URL arbitrária. */
function igua_indexnow_select_urls(array $catalog,string $configuredSite,string $path='',int $limit=10): array {
    $base=igua_sitemap_base($configuredSite);
    if($limit<1||$limit>20)throw new InvalidArgumentException('Limite IndexNow inválido.');
    if($path!=='' && (!str_starts_with($path,'/')||strlen($path)>1600||str_starts_with($path,'//')||str_contains($path,'\\')))
        throw new InvalidArgumentException('Caminho IndexNow inválido.');
    $items=[];
    foreach($catalog as $item){
        if(!is_array($item)||!in_array($item['kind']??'',['noticia','aplicativo','categoria','site'],true))continue;
        $p=(string)($item['path']??'');$url=(string)($item['loc']??'');
        if($url!==$base.$p||!str_starts_with($p,'/')||str_starts_with($p,'//'))continue;
        if($path!==''&&$path!==$p)continue;
        if($path===''&&!in_array($item['kind'],['noticia','aplicativo'],true))continue;
        $items[]=$url;
        if(count($items)>=$limit)break;
    }
    if($path!==''&&!$items)throw new InvalidArgumentException('A URL não está publicada ou não pertence ao sitemap atual.');
    return array_values(array_unique($items));
}
function igua_indexnow_http_post(string $endpoint,string $json): array {
    if(!function_exists('curl_init'))throw new RuntimeException('A extensão cURL do PHP não está disponível.');
    $handle=curl_init($endpoint);
    if(!$handle)throw new RuntimeException('Não foi possível preparar a conexão com IndexNow.');
    $response='';
    curl_setopt_array($handle,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$json,CURLOPT_RETURNTRANSFER=>false,
        CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_TIMEOUT=>18,
        CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_HTTPHEADER=>['Content-Type: application/json; charset=utf-8','Accept: application/json'],
        CURLOPT_WRITEFUNCTION=>static function($ch,string $chunk)use(&$response):int{
            if(strlen($response)+strlen($chunk)>8192)return 0;
            $response.=$chunk;return strlen($chunk);
        }]);
    $ok=curl_exec($handle);$http=(int)curl_getinfo($handle,CURLINFO_HTTP_CODE);
    $error=curl_error($handle);curl_close($handle);
    if($ok===false)throw new RuntimeException('Falha de conexão TLS com IndexNow: '.($error!==''?'verifique DNS, certificado e acesso externo da hospedagem.':'tente novamente mais tarde.'));
    return ['status'=>$http]; // Nunca retornar corpo bruto.
}
function igua_indexnow_submit(array $cfg,array $urls,string $public,?callable $transport=null): array {
    $site=igua_sitemap_base((string)($cfg['site']??''));
    $key=(string)($cfg['key']??'');if(!igua_indexnow_key_valid($key))throw new InvalidArgumentException('Chave IndexNow ausente.');
    if(!igua_indexnow_file_ready($cfg,$public))throw new RuntimeException('Arquivo de verificação IndexNow ausente ou divergente.');
    if(!$urls||count($urls)>20)throw new InvalidArgumentException('Selecione até 20 URLs publicadas.');
    $host=(string)parse_url($site,PHP_URL_HOST);
    foreach($urls as $url){
        if(!is_string($url)||strlen($url)>2048||!str_starts_with($url,$site.'/')||str_contains($url,'#')||str_contains($url,'\\')||!filter_var($url,FILTER_VALIDATE_URL))
            throw new InvalidArgumentException('URL fora do domínio canônico.');
        if(strcasecmp((string)parse_url($url,PHP_URL_HOST),$host)!==0||parse_url($url,PHP_URL_SCHEME)!=='https')
            throw new InvalidArgumentException('URL não pertence ao domínio autorizado.');
    }
    $payload=json_encode(['host'=>$host,'key'=>$key,'keyLocation'=>igua_indexnow_key_url($cfg),'urlList'=>array_values(array_unique($urls))],JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    $res=($transport??'igua_indexnow_http_post')('https://api.indexnow.org/indexnow',$payload);
    $http=(int)($res['status']??0);
    if($http===200||$http===202)return ['status'=>$http,'count'=>count(array_unique($urls))];
    $explanation=match($http){400=>'Solicitação inválida.',403=>'O buscador recusou a chave. Verifique se o arquivo público está acessível.',422=>'URL ou domínio incompatível com a chave.',429=>'Limite de envios atingido. Aguarde antes de tentar novamente.',default=>'Serviço indisponível. Consulte a hospedagem e tente mais tarde.'};
    throw new RuntimeException('IndexNow HTTP '.$http.': '.$explanation);
}
