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
/** Categorias oficiais do Lighthouse solicitadas na mesma análise para cada dispositivo. */
function igua_pagespeed_categories(): array {
    return ['performance'=>'Desempenho','accessibility'=>'Acessibilidade',
        'best-practices'=>'Boas práticas','seo'=>'SEO'];
}
/** Mostra somente recomendações cuja auditoria efetivamente reprovou no relatório. */
function igua_pagespeed_recommendations(array $report): array {
    // Dicas estáveis em português, condicionadas ao ID e ao resultado real do Lighthouse.
    $tips=[
        'render-blocking-resources'=>'Adie scripts não essenciais e carregue CSS crítico primeiro.',
        'render-blocking-insight'=>'Reduza recursos que bloqueiam a primeira renderização.',
        'unused-css-rules'=>'Remova CSS que não é usado pela página inicial.',
        'unused-javascript'=>'Remova scripts desnecessários ou carregue-os sob demanda.',
        'unminified-javascript'=>'Minifique os arquivos JavaScript servidos em produção.',
        'modern-image-formats'=>'Converta imagens grandes para WebP/AVIF quando possível.',
        'uses-optimized-images'=>'Comprima imagens sem perder legibilidade.',
        'uses-responsive-images'=>'Sirva imagens do tamanho adequado para cada tela.',
        'offscreen-images'=>'Use carregamento preguiçoso para imagens fora da tela.',
        'image-delivery-insight'=>'Revise tamanho, formato e compactação das imagens.',
        'server-response-time'=>'Revise cache e tempo de resposta da hospedagem.',
        'document-latency-insight'=>'Investigue a latência da resposta HTML e o cache do servidor.',
        'font-display'=>'Use font-display para evitar texto invisível durante o carregamento.',
        'image-alt'=>'Adicione texto alternativo útil às imagens informativas.',
        'color-contrast'=>'Aumente o contraste de textos e elementos com leitura difícil.',
        'label'=>'Associe rótulos claros a todos os campos de formulário.',
        'tap-targets'=>'Aumente o espaço entre alvos de toque próximos no celular.',
        'document-title'=>'Defina um título descritivo e único para a página.',
        'meta-description'=>'Inclua uma descrição de pesquisa adequada à página.',
        'is-on-https'=>'Verifique se todos os recursos públicos usam HTTPS.',
        'canonical'=>'Revise a URL canônica exibida no HTML.'
    ];
    $audits=is_array($report['audits']??null)?$report['audits']:[];
    $categories=is_array($report['categories']??null)?$report['categories']:[];
    $found=[];
    foreach(igua_pagespeed_categories() as $key=>$categoryName){
        $refs=$categories[$key]['auditRefs']??[];
        if(!is_array($refs))continue;
        foreach($refs as $ref){
            if(!is_array($ref))continue;
            $id=$ref['id']??null;
            if(!is_string($id)||!isset($tips[$id])||isset($found[$id]))continue;
            $audit=$audits[$id]??null;
            if(!is_array($audit))continue;
            $score=$audit['score']??null;
            if(!is_numeric($score)||!is_finite((float)$score)||$score<0||$score>=0.9)continue;
            $mode=(string)($audit['scoreDisplayMode']??'numeric');
            if(!in_array($mode,['numeric','binary'],true))continue;
            $title=$audit['title']??null;
            if(!is_string($title))continue;
            $title=trim(preg_replace('/[\x00-\x1F\x7F]/u',' ',$title)??'');
            if($title===''||strlen($title)>160)continue;
            $weight=$ref['weight']??0;
            $found[$id]=[
                'category'=>$categoryName,'title'=>$title,
                'tip'=>$tips[$id], 'score'=>(int)round((float)$score*100),
                'priority'=>(1-(float)$score)*100+max(0,min(10,is_numeric($weight)?(float)$weight:0))
            ];
        }
    }
    usort($found,static fn($a,$b)=>$b['priority']<=>$a['priority']);
    return array_map(static fn($x)=>array_diff_key($x,['priority'=>true]),array_slice($found,0,4));
}
/** As duas estratégias aceitas oficialmente; nunca enviar valores livres à API. */
function igua_pagespeed_strategy(string $strategy): string {
    if(!in_array($strategy,['mobile','desktop'],true))throw new InvalidArgumentException('Estratégia PageSpeed inválida.');
    return $strategy;
}
function igua_pagespeed_result(array $response,string $target,?int $now=null,string $strategy='mobile'): array {
    igua_pagespeed_strategy($strategy);
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
    $scores=[];
    foreach(igua_pagespeed_categories() as $key=>$label){
        $rawScore=$report['categories'][$key]['score']??null;
        $scores[$key]=is_numeric($rawScore) && is_finite((float)$rawScore) && (float)$rawScore>=0 && (float)$rawScore<=1
            ? (int)round((float)$rawScore*100) : null;
    }
    $score=$scores['performance'];
    if($score===null)throw new RuntimeException('A resposta não contém uma pontuação de desempenho válida.');
    $audits=is_array($report['audits']??null)?$report['audits']:[];
    $metrics=[];
    foreach(['first-contentful-paint'=>'Primeira exibição','largest-contentful-paint'=>'Maior conteúdo','cumulative-layout-shift'=>'Estabilidade visual'] as $key=>$label){
        $value=$audits[$key]['displayValue']??null;
        if(!is_string($value))continue;
        $value=trim(preg_replace('/[\x00-\x1F\x7F]/u',' ',$value)??'');
        if($value!=='' && strlen($value)<=75)$metrics[]=['label'=>$label,'value'=>$value];
    }
    return ['url'=>$target,'score'=>$score,'scores'=>$scores,'recommendations'=>igua_pagespeed_recommendations($report),
        'metrics'=>$metrics,'checked_at'=>$now??time(),'source'=>'Lighthouse '.$strategy,'strategy'=>$strategy];
}
function igua_pagespeed_check(array $cfg,?callable $transport=null,?int $now=null,string $strategy='mobile'): array {
    igua_pagespeed_strategy($strategy);
    $target=igua_pagespeed_site_url($cfg);
    if($target==='')throw new InvalidArgumentException('Configure uma URL oficial HTTPS pública (sem porta ou caminho) em Geral e SEO.');
    // O parâmetro category é repetido (category=performance&category=seo...), não category[0].
    $endpoint='https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?'.http_build_query(
        ['url'=>$target,'strategy'=>$strategy,'locale'=>'pt_BR'],'','&',PHP_QUERY_RFC3986);
    foreach(array_keys(igua_pagespeed_categories()) as $category)$endpoint.='&category='.rawurlencode($category);
    $response=($transport??'igua_pagespeed_http_get')($endpoint);
    if(!is_array($response))throw new RuntimeException('Resposta inesperada do PageSpeed.');
    return igua_pagespeed_result($response,$target,$now,$strategy);
}
/** Mantém somente resultado resumido na sessão do admin; nunca salva JSON integral da API. */
function igua_pagespeed_last_check(array $cfg,array $session,?int $now=null,string $strategy='mobile'): ?array {
    igua_pagespeed_strategy($strategy);
    // Compatibilidade com resultado mobile de sessão criado antes da v1.0.82.
    $last=$session['igua_pagespeed_results'][$strategy]??null;
    if(!is_array($last) && $strategy==='mobile')$last=$session['igua_pagespeed_last']??null;
    $now=$now??time();
    if(!is_array($last)||!is_int($last['checked_at']??null)||$now<$last['checked_at']||$now-$last['checked_at']>3600)return null;
    if(($last['url']??null)!==igua_pagespeed_site_url($cfg))return null;
    if(isset($last['strategy']) && $last['strategy']!==$strategy)return null;
    if(!is_int($last['score']??null) || $last['score']<0 || $last['score']>100)return null;
    return $last;
}
