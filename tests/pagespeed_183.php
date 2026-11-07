<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/pagespeed_insights.php';
function check183(bool $value,string $msg):void {if(!$value)throw new RuntimeException('PageSpeed 183: '.$msg);}
$cfg=['seo_site_url'=>'https://iguatama.online'];
$seen=[];
$fixture=['lighthouseResult'=>[
    'categories'=>[
        'performance'=>['score'=>0.43,'auditRefs'=>[['id'=>'render-blocking-resources','weight'=>7],['id'=>'unused-javascript','weight'=>4]]],
        'accessibility'=>['score'=>0.83,'auditRefs'=>[['id'=>'image-alt','weight'=>3],['id'=>'color-contrast','weight'=>2]]],
        'best-practices'=>['score'=>0.97,'auditRefs'=>[['id'=>'is-on-https','weight'=>1]]],
        'seo'=>['score'=>1.0,'auditRefs'=>[['id'=>'meta-description','weight'=>1]]],
    ],
    'audits'=>[
        'render-blocking-resources'=>['score'=>0,'title'=>'Recursos que bloqueiam a renderização','scoreDisplayMode'=>'numeric'],
        'unused-javascript'=>['score'=>0.8,'title'=>'JavaScript não utilizado','scoreDisplayMode'=>'numeric'],
        'image-alt'=>['score'=>0.1,'title'=>'Imagem sem texto alternativo','scoreDisplayMode'=>'binary'],
        'color-contrast'=>['score'=>1,'title'=>'Contraste aprovado','scoreDisplayMode'=>'binary'],
        'is-on-https'=>['score'=>1,'title'=>'HTTPS aprovado','scoreDisplayMode'=>'binary'],
        'meta-description'=>['score'=>1,'title'=>'Descrição SEO adequada','scoreDisplayMode'=>'binary'],
        'fake-unknown'=>['score'=>0,'title'=>'Não existe no ref'],
        'largest-contentful-paint'=>['displayValue'=>'4,1 s'],
    ],
]];
$transport=static function(string $url)use(&$seen,$fixture):array {
    $u=parse_url($url);
    check183(($u['host']??'')==='pagespeedonline.googleapis.com','host fixo Google');
    parse_str((string)($u['query']??''),$args);
    check183(($args['url']??'')==='https://iguatama.online/' && ($args['locale']??'')==='pt_BR','URL pública e localidade');
    preg_match_all('/(?:^|&)category=([^&]+)/',(string)($u['query']??''),$matches);
    check183(array_map('rawurldecode',$matches[1])===array_keys(igua_pagespeed_categories()),'categorias repetidas na ordem correta');
    $seen[]=$args['strategy']??'';
    return ['status'=>200,'body'=>json_encode($fixture,JSON_THROW_ON_ERROR)];
};
$a=igua_pagespeed_check($cfg,$transport,1731010101,'mobile');
$b=igua_pagespeed_check($cfg,$transport,1731010161,'desktop');
check183($seen===['mobile','desktop'],'consultas distintas por dispositivo');
check183($a['score']===43 && $a['scores']===['performance'=>43,'accessibility'=>83,'best-practices'=>97,'seo'=>100],'quatro notas reais arredondadas');
check183(count($a['recommendations'])===3 && $a['recommendations'][0]['category']==='Desempenho','somente auditorias reprovadas e conhecidas');
check183($a['recommendations'][1]['category']==='Acessibilidade','prioridade por grau da reprovação');
check183(!str_contains(json_encode($a['recommendations']), 'fake-unknown'),'não inclui auditoria inexistente no ref');
check183(!isset($a['recommendations'][0]['priority']),'não vaza prioridade interna');
check183(count($a['metrics'])===1,'métricas existentes, sem inventar as ausentes');
check183(igua_pagespeed_last_check($cfg,['igua_pagespeed_results'=>['mobile'=>$a,'desktop'=>$b]],1731010190,'desktop')['scores']['seo']===100,'cache íntegro');
$old=['lighthouseResult'=>['categories'=>['performance'=>['score'=>0.72]]]];
$c=igua_pagespeed_result(['status'=>200,'body'=>json_encode($old)],'https://iguatama.online/',1731010101);
check183($c['score']===72 && $c['scores']['seo']===null && $c['recommendations']===[],'categorias ausentes são indisponíveis');
$invalid=['lighthouseResult'=>['categories'=>['performance'=>['score'=>0.88],'accessibility'=>['score'=>7],'seo'=>['score'=>'invalid']]]];
$d=igua_pagespeed_result(['status'=>200,'body'=>json_encode($invalid)],'https://iguatama.online/');
check183($d['scores']['accessibility']===null && $d['scores']['seo']===null,'pontuações não numéricas ou fora da faixa recusadas');
$harm=$fixture;
$harm['lighthouseResult']['audits']['image-alt']['title']='<script>alert(1)</script>';
$e=igua_pagespeed_result(['status'=>200,'body'=>json_encode($harm)],'https://iguatama.online/');
check183($e['recommendations'][1]['title']==='<script>alert(1)</script>','resultado não é HTML confiável e requer escaping na view');
$view=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/integrations.php');
check183(str_contains($view,'h((string)$recommendation[\'title\'])') && str_contains($view,'h((string)$recommendation[\'tip\'])'),'escaping de títulos e dicas de auditoria');
check183(str_contains($view,'igua_pagespeed_categories()') && str_contains($view,'int-speed-categories') && str_contains($view,'int-speed-recommendations'),'quatro notas e sugestões acessíveis na UI');
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/modules/16-security.css');
check183(str_contains($css,'.int-speed-category') && str_contains($css,'.int-speed-fix'),'layout para telas pequenas');
check183(!str_contains($view,'pagespeed_api_key'),'não exige chave privada');
echo 'PageSpeed 1.0.83: API oficial, quatro notas, sugestões condicionadas, cache, HTML escapado e mobile-first aprovados.'.PHP_EOL;
