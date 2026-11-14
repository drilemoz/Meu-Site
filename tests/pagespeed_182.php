<?php
declare(strict_types=1);
require_once __DIR__.'/support/integrations_source.php';
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/pagespeed_insights.php';
function check182(bool $condition,string $message):void {if(!$condition)throw new RuntimeException('Falha PageSpeed 182: '.$message);}
$cfg=['seo_site_url'=>'https://iguatama.online'];
$mock=['lighthouseResult'=>['categories'=>['performance'=>['score'=>0.74]],'audits'=>[
  'largest-contentful-paint'=>['displayValue'=>'3,2 s'],
  'cumulative-layout-shift'=>['displayValue'=>'0,06'],
]]];
$seen=[];
$transport=static function(string $url)use(&$seen,$mock):array {
    check182(str_starts_with($url,'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?'),'somente endpoint Google');
    parse_str((string)parse_url($url,PHP_URL_QUERY),$query);
    preg_match_all('/(?:[?&])category=([^&]+)/',$url,$matches);
    check182(($query['url']??'')==='https://iguatama.online/' && array_map('rawurldecode',$matches[1])===['performance','accessibility','best-practices','seo'],'URL fixa e quatro categorias oficiais');
    $seen[]=$query['strategy']??'';
    return ['status'=>200,'body'=>json_encode($mock,JSON_THROW_ON_ERROR)];
};
$mobile=igua_pagespeed_check($cfg,$transport,1731010101,'mobile');
$desktop=igua_pagespeed_check($cfg,$transport,1731010161,'desktop');
check182($seen===['mobile','desktop'],'consulta com estratégias oficiais separadas');
check182($mobile['strategy']==='mobile' && $desktop['strategy']==='desktop','resultado carrega estratégia');
check182($mobile['source']==='Lighthouse mobile' && $desktop['source']==='Lighthouse desktop','origem explícita');
$session=['igua_pagespeed_results'=>['mobile'=>$mobile,'desktop'=>$desktop]];
check182(igua_pagespeed_last_check($cfg,$session,1731010180,'mobile')['checked_at']===1731010101,'cache mobile independente');
check182(igua_pagespeed_last_check($cfg,$session,1731010180,'desktop')['checked_at']===1731010161,'cache desktop independente');
check182(igua_pagespeed_last_check($cfg,['igua_pagespeed_results'=>['desktop'=>$desktop]],1731010180,'mobile')===null,'desktop não finge mobile');
check182(igua_pagespeed_last_check($cfg,['igua_pagespeed_results'=>['mobile'=>$mobile]],1731010180,'desktop')===null,'mobile não finge desktop');
check182(igua_pagespeed_last_check(['seo_site_url'=>'https://example.com'],$session,1731010180,'desktop')===null,'cache não atravessa URL');
check182(igua_pagespeed_last_check($cfg,$session,1731013800,'desktop')===null,'desktop expira após 1 hora');
check182(igua_pagespeed_last_check($cfg,['igua_pagespeed_last'=>$mobile],1731010180,'mobile')!==null,'sessão mobile antiga legível');
check182(igua_pagespeed_last_check($cfg,['igua_pagespeed_last'=>$mobile],1731010180,'desktop')===null,'sessão antiga não vira desktop');
foreach(['','tablet','MOBILE','desktop\n'] as $bad){
    $failed=false;try{igua_pagespeed_check($cfg,$transport,1731010101,$bad);}catch(InvalidArgumentException){$failed=true;}
    check182($failed,'estratégia inválida bloqueada');
}
$view=(string)igua_test_integrations_source();
$controller=(string)file_get_contents(__DIR__.'/../app/admin_controllers/integrations.php');
$js=(string)file_get_contents(__DIR__.'/../public/assets/js/integrations-admin.js');
check182(str_contains($view,"['mobile'=>'Celular','desktop'=>'Computador']") && str_contains($view,'name="pagespeed_strategy"'),'dois formulários e campo de estratégia na interface');
check182(str_contains($view,'csrf_field()') && str_contains($controller,'igua_pagespeed_strategy($strategy)'),'CSRF e whitelist no servidor');
check182(str_contains($controller,"'igua_pagespeed_attempts'") && str_contains($controller,"'igua_pagespeed_results'"),'tentativas/resultados independentes');
check182(str_contains($js,'hub.querySelectorAll(selector)'),'JavaScript atualiza os dois formulários');
echo 'PageSpeed 1.0.82: dispositivos, cache isolado, retrocompatibilidade, whitelist, interface e segurança aprovados.'.PHP_EOL;
