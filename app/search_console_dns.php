<?php
declare(strict_types=1);
/** Verificação DNS TXT sob demanda; resultados locais não provam a posse da propriedade no Google. */
function igua_search_dns_expected(string $value): string {
    $value=trim($value);
    if($value==='')return '';
    if(strlen($value)>300 || !preg_match('~^google-site-verification=[A-Za-z0-9_+/=-]{8,260}$~D',$value))
        throw new InvalidArgumentException('Cole o valor TXT completo fornecido pelo Search Console (google-site-verification=...).');
    return $value;
}
/** Só usa o domínio da URL canônica HTTPS salva no servidor; nunca consulta hosts vindos de GET/POST. */
function igua_search_dns_domain(array $cfg): string {
    try {$url=safe_seo_url((string)($cfg['seo_site_url']??''));}
    catch(Throwable $e){return '';}
    if($url==='')return '';
    $parts=parse_url($url);
    if(!is_array($parts))return '';
    $domain=strtolower(rtrim((string)($parts['host']??''),'.'));
    if(strlen($domain)>253 || strlen($domain)<4 || filter_var($domain,FILTER_VALIDATE_IP) || !str_contains($domain,'.'))return '';
    if(!preg_match('~^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$~D',$domain))return '';
    return $domain;
}
/** Lookup real restrito a DNS_TXT; $resolver existe apenas para testes controlados. */
function igua_search_dns_check(array $cfg,?callable $resolver=null): array {
    $domain=igua_search_dns_domain($cfg);
    $base=['domain'=>$domain,'status'=>'unavailable','count'=>0,'checked_at'=>time(),
           'detail'=>'Não foi possível consultar o DNS. Confira a configuração do servidor.'];
    if($domain==='')return array_replace($base,['status'=>'no_domain','detail'=>'Configure primeiro a URL HTTPS oficial em Geral e SEO.']);
    $expected='';
    try {$expected=igua_search_dns_expected((string)($cfg['google_dns_verification_txt']??''));}
    catch(Throwable $e){return array_replace($base,['status'=>'invalid_config','detail'=>'O TXT esperado salvo é inválido. Salve um valor válido e tente novamente.']);}
    if($resolver===null && (!function_exists('dns_get_record') || !defined('DNS_TXT')))
        return array_replace($base,['detail'=>'A hospedagem não disponibiliza a consulta DNS TXT no PHP.']);
    try {$records=$resolver!==null?$resolver($domain):@dns_get_record($domain,DNS_TXT);}
    catch(Throwable $e){$records=false;}
    if(!is_array($records))return $base;
    $found=[];
    foreach(array_slice($records,0,200) as $record){
        if(!is_array($record))continue;
        $txt=(string)($record['txt']??'');
        if($txt==='' && is_array($record['entries']??null))$txt=implode('',array_filter($record['entries'],'is_string'));
        $txt=trim($txt,'"');
        if(strlen($txt)>300)continue;
        try{$token=igua_search_dns_expected($txt);}catch(InvalidArgumentException){continue;}
        if($token!=='')$found[$token]=true;
    }
    $count=count($found);
    if($expected!==''){
        if(isset($found[$expected]))return array_replace($base,['status'=>'matched','count'=>$count,'detail'=>'TXT esperado encontrado no DNS público. Confirme a propriedade no Google Search Console.']);
        return array_replace($base,['status'=>'mismatch','count'=>$count,'detail'=>$count?'Há registro Google, mas não corresponde ao TXT esperado.':'O TXT esperado ainda não aparece na consulta DNS. A propagação pode demorar.']);
    }
    return array_replace($base,['status'=>$count?'found':'missing','count'=>$count,
        'detail'=>$count?'Registro TXT do Google encontrado. Sem o token esperado não é possível associá-lo à sua conta; confirme no Google.':'Nenhum registro google-site-verification foi encontrado nesse domínio.']);
}
/** Dados de sessão expiram e não são usados como prova permanente de propriedade. */
function igua_search_dns_last_check(array $cfg,array $session): ?array {
    $r=$session['igua_search_dns_last_check']??null;
    if(!is_array($r) || !is_int($r['checked_at']??null) || abs(time()-$r['checked_at'])>900)return null;
    if(($r['domain']??null)!==igua_search_dns_domain($cfg))return null;
    $expected='';
    try{$expected=igua_search_dns_expected((string)($cfg['google_dns_verification_txt']??''));}catch(Throwable){return null;}
    if(($r['expected_hash']??null)!==hash('sha256',$expected))return null;
    return $r;
}
