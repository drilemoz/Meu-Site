<?php
declare(strict_types=1);
/** PageSpeed Insights v5: diagnóstico somente sob demanda, HTTPS fixo, sem chave obrigatória. */
function igua_pagespeed_site_url(array $cfg): string {
    try {$url=safe_seo_url((string)($cfg['seo_site_url']??''));}
    catch(Throwable){return '';}
    if($url==='')return '';
    $parts=parse_url($url);
    if(!is_array($parts) || strtolower((string)($parts['scheme']??''))!=='https')return '';
    if(isset($parts['user'],$parts['pass']) || isset($parts['user']) || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment']))return '';
    $host=strtolower((string)($parts['host']??''));
    if(strlen($host)>253 || !str_contains($host,'.') || filter_var($host,FILTER_VALIDATE_IP))return '';
    if(!preg_match('~^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z](?:[a-z0-9-]{0,61}[a-z0-9])?$~D',$host))return '';
    // A URL de SEO corresponde à origem pública do portal; não há campos de URL livre no POST.
    $path=(string)($parts['path']??'/');
    if($path!=='' && $path!=='/')return '';
    return 'https://'.$host.'/';
}
/** Transporte injetável em testes. URL HTTPS é SEMPRE construída internamente e fixa no Google. */
function igua_pagespeed_http_get(string $url): array {
    if(!str_starts_with($url,'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?'))
        throw new InvalidArgumentException('Endpoint PageSpeed não permitido.');
    if(!function_exists('curl_init'))throw new RuntimeException('A extensão PHP cURL não está disponível na hospedagem.');
    $ch=curl_init($url);if($ch===false)throw new RuntimeException('Não foi possível preparar a conexão PageSpeed.');
    $body='';$overflow=false;
    curl_setopt_array($ch,[CURLOPT_HTTPGET=>true,CURLOPT_HTTPHEADER=>['Accept: application/json'],
      CURLOPT_RETURNTRANSFER=>false,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_CONNECTTIMEOUT=>7,
      CURLOPT_TIMEOUT=>25,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,
      CURLOPT_WRITEFUNCTION=>static function($handle,string $chunk)use(&$body,&$overflow):int {
          if(strlen($body)+strlen($chunk)>5242880){$overflow=true;return 0;}
          $body.=$chunk;return strlen($chunk);
      }]);
    $ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);curl_close($ch);
    if($overflow)throw new RuntimeException('A resposta do PageSpeed excedeu 5 MiB. Use o site oficial para consultar o relatório.');
    if($ok===false)throw new RuntimeException('Não foi possível consultar o Google via HTTPS. Confira cURL e a conexão do servidor.');
    return ['status'=>$status,'body'=>$body];
}
function igua_pagespeed_result(array $response,string $target,?int $now=null): array {
    $code=(int)($response['status']??0);
    if($code===429)throw new RuntimeException('PageSpeed atingiu o limite de consultas. Tente mais tarde.');
    if($code===400)throw new RuntimeException('A API recusou a URL pública configurada. Confira Geral e SEO.');
    if($code===403)throw new RuntimeException('PageSpeed recusou a consulta (403). Confira os limites do serviço Google.');
    if($code<200||$code>=300)throw new RuntimeException('PageSpeed indisponível (HTTP '.$code.'). Tente novamente depois.');
    $body=$response['body']??'';
    if(!is_string($body)||strlen($body)>5242880)throw new RuntimeException('Resposta PageSpeed inválida ou excessiva.');
    $data=json_decode($body,true,64);
    if(!is_array($data))throw new RuntimeException('A API PageSpeed retornou um JSON inválido.');
    $report=$data['lighthouseResult']??null;
    if(!is_array($report))throw new RuntimeException('A API não retornou uma análise Lighthouse concluída.');
    if(is_array($report['runtimeError']??null))throw new RuntimeException('O Lighthouse não conseguiu concluir a análise desta página. Confira se a página pública está acessível.');
    $score=$report['categories']['performance']['score']??null;
    if(!is_numeric($score) || (float)$score<0 || (float)$score>1)throw new RuntimeException('A resposta não contém uma pontuação de desempenho válida.');
    $audits=is_array($report['audits']??null)?$report['audits']:[];
    $metrics=[];
    foreach(['first-contentful-paint'=>'Primeira exibição','largest-contentful-paint'=>'Maior conteúdo','cumulative-layout-shift'=>'Estabilidade visual'] as $key=>$label){
        $value=$audits[$key]['displayValue']??null;
        if(!is_string($value))continue;
        $value=trim(preg_replace('/[\x00-\x1F\x7F]/u',' ',$value)??'');
        if($value!=='' && strlen($value)<=75)$metrics[]=['label'=>$label,'value'=>$value];
    }
    return ['url'=>$target,'score'=>(int)round((float)$score*100),'metrics'=>$metrics,
        'checked_at'=>$now??time(),'source'=>'Lighthouse mobile'];
}
function igua_pagespeed_check(array $cfg,?callable $transport=null,?int $now=null): array {
    $target=igua_pagespeed_site_url($cfg);
    if($target==='')throw new InvalidArgumentException('Configure uma URL oficial HTTPS pública (sem porta ou caminho) em Geral e SEO.');
    $endpoint='https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?'.http_build_query(
        ['url'=>$target,'strategy'=>'mobile','category'=>'performance','locale'=>'pt_BR'],'','&',PHP_QUERY_RFC3986);
    $response=($transport??'igua_pagespeed_http_get')($endpoint);
    if(!is_array($response))throw new RuntimeException('Resposta inesperada do PageSpeed.');
    return igua_pagespeed_result($response,$target,$now);
}
/** Mantém somente resultado resumido na sessão do admin; nunca salva JSON integral da API. */
function igua_pagespeed_last_check(array $cfg,array $session,?int $now=null): ?array {
    $last=$session['igua_pagespeed_last']??null;
    $now=$now??time();
    if(!is_array($last)||!is_int($last['checked_at']??null)||$now<$last['checked_at']||$now-$last['checked_at']>3600)return null;
    if(($last['url']??null)!==igua_pagespeed_site_url($cfg))return null;
    if(!is_int($last['score']??null) || $last['score']<0 || $last['score']>100)return null;
    return $last;
}
