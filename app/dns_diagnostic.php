<?php
declare(strict_types=1);
/** Diagnóstico DNS somente leitura, com domínio fixado à configuração SEO. */

function igua_dns_valid_host(string $input): bool {
    if(strlen($input)<4 || strlen($input)>253 || filter_var($input,FILTER_VALIDATE_IP))return false;
    if(!preg_match('/^[a-z0-9.-]+$/D',$input) || str_contains($input,'..') || str_ends_with($input,'.'))return false;
    $labels=explode('.',$input);
    if(count($labels)<2 || !preg_match('/^[a-z]{2,63}$/D',end($labels)))return false;
    if(in_array(end($labels),['local','localhost','internal','invalid','example','test'],true))return false;
    foreach($labels as $label){
        if(strlen($label)>63 || !preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/D',$label))return false;
    }
    return true;
}

function igua_dns_site_hosts(string $siteUrl): array {
    $url=trim($siteUrl);
    if(!preg_match('~^https://~i',$url))return [];
    $parts=parse_url($url);
    if(!is_array($parts) || isset($parts['user'],$parts['pass']) || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment']) || (isset($parts['port']) && $parts['port']!==443)
        || !in_array($parts['path']??'',['','/'],true))return [];
    $host=$parts['host']??null;
    if(!is_string($host))return [];
    $host=strtolower($host);
    if(!igua_dns_valid_host($host))return [];
    $alt=str_starts_with($host,'www.')?substr($host,4):'www.'.$host;
    return array_values(array_unique(array_filter([$host,igua_dns_valid_host($alt)?$alt:''])));
}

function igua_dns_types(): array {return ['A'=>DNS_A,'AAAA'=>DNS_AAAA,'CNAME'=>DNS_CNAME,'NS'=>DNS_NS];}

function igua_dns_normalize_value(string $type, string $value): ?string {
    $value=trim($value);
    if($type==='A' || $type==='AAAA'){
        if(!filter_var($value,FILTER_VALIDATE_IP,$type==='A'?FILTER_FLAG_IPV4:FILTER_FLAG_IPV6))return null;
        return strtolower($value);
    }
    if($type==='CNAME'||$type==='NS'){
        $value=strtolower(rtrim($value,'.'));
        return igua_dns_valid_host($value)?$value:null;
    }
    return null;
}

function igua_dns_local_records(string $host, ?callable $resolver=null): array {
    $result=[];
    foreach(igua_dns_types() as $type=>$flag){
        try{
            $raw=$resolver?$resolver($host,$flag):@dns_get_record($host,$flag);
            if(!is_array($raw)){$result[$type]=['state'=>'error','values'=>[]];continue;}
            $values=[];
            foreach($raw as $entry){
                if(!is_array($entry) || strtoupper((string)($entry['type']??''))!==$type)continue;
                $value=$entry[$type==='A'?'ip':($type==='AAAA'?'ipv6':'target')]??'';
                if(!is_string($value))continue;
                $normalized=igua_dns_normalize_value($type,$value);
                if($normalized!==null)$values[]=$normalized;
            }
            $values=array_values(array_unique($values));sort($values,SORT_STRING);
            $result[$type]=['state'=>$values?'ok':'empty','values'=>$values];
        }catch(Throwable){$result[$type]=['state'=>'error','values'=>[]];}
    }
    return $result;
}

function igua_dns_doh_decode(string $body, string $host, string $type): array {
    $failed=['state'=>'error','values'=>[]];
    if(strlen($body)>65536)return $failed;
    try{$json=json_decode($body,true,24,JSON_THROW_ON_ERROR);}catch(Throwable){return $failed;}
    if(!is_array($json) || !isset($json['Status']) || !is_int($json['Status']))return $failed;
    if(!in_array($json['Status'],[0,3],true))return $failed;
    $numbers=['A'=>1,'NS'=>2,'CNAME'=>5,'AAAA'=>28];
    if(!isset($numbers[$type]))return $failed;
    $answers=$json['Answer']??[];
    if(!is_array($answers))return $failed;
    $values=[];
    foreach($answers as $entry){
        if(!is_array($entry) || ($entry['type']??null)!==$numbers[$type] || !is_string($entry['data']??null))continue;
        // Respostas da cadeia CNAME podem conter nomes diferentes, mas cada valor é validado.
        $value=igua_dns_normalize_value($type,$entry['data']);
        if($value!==null)$values[]=$value;
    }
    $values=array_values(array_unique($values));sort($values,SORT_STRING);
    return ['state'=>$values?'ok':'empty','values'=>$values];
}

/** DoH usa apenas duas URLs HTTPS fixas e um nome previamente validado. Limites totais de ~4 s. */
function igua_dns_public_resolvers(string $host): array {
    $sources=['Google'=>'https://dns.google/resolve','Cloudflare'=>'https://cloudflare-dns.com/dns-query'];
    $types=['A','AAAA','NS','CNAME'];
    $result=[];
    foreach($sources as $label=>$_){foreach($types as $type)$result[$label][$type]=['state'=>'error','values'=>[]];}
    if(!igua_dns_valid_host($host) || !function_exists('curl_multi_init'))return $result;
    $multi=curl_multi_init();$handles=[];$buffers=[];
    foreach($sources as $label=>$base){
        foreach($types as $type){
            $url=$base.'?'.http_build_query(['name'=>$host,'type'=>$type], '', '&', PHP_QUERY_RFC3986);
            $ch=curl_init($url);
            if($ch===false)continue;
            curl_setopt_array($ch,[
                CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>false,
                CURLOPT_CONNECTTIMEOUT=>2,CURLOPT_TIMEOUT=>4,
                CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
                CURLOPT_HTTPHEADER=>['Accept: application/dns-json'],
                CURLOPT_USERAGENT=>'IguatamaOnline-DNS-Diagnostic/1.0',
            ]);
            // Não permitir redirecionamentos, proxy arbitrário ou resposta sem limite em memória.
            if(defined('CURLOPT_PROTOCOLS_STR'))curl_setopt($ch,CURLOPT_PROTOCOLS_STR,'https');
            elseif(defined('CURLOPT_PROTOCOLS'))curl_setopt($ch,CURLOPT_PROTOCOLS,CURLPROTO_HTTPS);
            curl_setopt($ch,CURLOPT_WRITEFUNCTION,static function($curl,string $chunk) use (&$buffers,$label,$type): int {
                $key=$label.'|'.$type;
                $buffers[$key]=($buffers[$key]??'').$chunk;
                return strlen($buffers[$key])<=65536?strlen($chunk):0;
            });
            $handles[]=['handle'=>$ch,'label'=>$label,'type'=>$type];
            curl_multi_add_handle($multi,$ch);
        }
    }
    $running=0;$started=microtime(true);
    do {
        $status=curl_multi_exec($multi,$running);
        if($status!==CURLM_OK)break;
        if($running>0){$selected=curl_multi_select($multi,0.25);if($selected===-1)usleep(10000);}
    }while($running>0 && microtime(true)-$started<4.5);
    foreach($handles as $info){
        $ch=$info['handle'];$label=$info['label'];$type=$info['type'];
        $key=$label.'|'.$type;
        $body=$buffers[$key]??'';
        if(curl_errno($ch)===0 && curl_getinfo($ch,CURLINFO_HTTP_CODE)===200 && $body!==''){
            $result[$label][$type]=igua_dns_doh_decode($body,$host,$type);
        }
        curl_multi_remove_handle($multi,$ch);curl_close($ch);
    }
    curl_multi_close($multi);
    return $result;
}

function igua_dns_compare(array $providers, string $type): string {
    $known=[];
    foreach($providers as $records){
        $record=$records[$type]??null;
        if(!is_array($record) || ($record['state']??'')==='error')continue;
        $known[]=($record['values']??[]);
    }
    if(count($known)<2)return 'unknown';
    foreach($known as $values){if($values!==$known[0])return 'different';}
    return 'same';
}

function igua_dns_snapshot(string $configuredUrl, ?callable $localResolver=null, ?callable $publicResolver=null): array {
    $hosts=igua_dns_site_hosts($configuredUrl);
    if(!$hosts)return ['ok'=>false,'message'=>'Configure o endereço HTTPS oficial em Configurações → Geral e SEO.'];
    $primary=$hosts[0];
    $local=[];
    foreach($hosts as $host)$local[$host]=igua_dns_local_records($host,$localResolver);
    $public=$publicResolver?$publicResolver($primary):igua_dns_public_resolvers($primary);
    $providers=['Hospedagem'=>$local[$primary]];
    foreach(['Google','Cloudflare'] as $name)$providers[$name]=$public[$name]??[];
    $comparison=[];
    foreach(array_keys(igua_dns_types()) as $type)$comparison[$type]=igua_dns_compare($providers,$type);
    return ['ok'=>true,'host'=>$primary,'hosts'=>$hosts,'local'=>$local,'public'=>$public,
        'comparison'=>$comparison,'collected_at'=>date('d/m/Y H:i:s')];
}
