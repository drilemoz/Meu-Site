<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/pagespeed_insights.php';
function check181(bool $condition,string $message):void {if(!$condition)throw new RuntimeException('Falha PageSpeed 181: '.$message);}
$base=['seo_site_url'=>'https://www.exemplo.com.br'];
check181(igua_pagespeed_site_url($base)==='https://www.exemplo.com.br/','URL SEO HTTPS validada sem alterar host');
foreach(['http://exemplo.com.br','https://localhost','https://127.0.0.1','https://192.168.1.2','https://user:pass@example.com','https://exemplo.com.br:8080','https://exemplo.com.br/noticia','https://exemplo.com.br?url=https://evil.com','https://exemplo.com.br#fragment','https://evil.test@example.com'] as $bad){
    check181(igua_pagespeed_site_url(['seo_site_url'=>$bad])==='','URL não elegível não vai para a API: '.$bad);
}
$mock=['lighthouseResult'=>['categories'=>['performance'=>['score'=>0.87]],'audits'=>[
    'first-contentful-paint'=>['displayValue'=>'1,8 s'],
    'largest-contentful-paint'=>['displayValue'=>'2,4 s'],
    'cumulative-layout-shift'=>['displayValue'=>'0,02']
]]];
$hits=0;
$res=igua_pagespeed_check($base,static function(string $url)use(&$hits,$mock):array{
    $hits++;
    check181(str_starts_with($url,'https://pagespeedonline.googleapis.com/pagespeedonline/v5/runPagespeed?'),'endpoint fixo e HTTPS');
    parse_str((string)parse_url($url,PHP_URL_QUERY),$query);
    check181(($query['url']??'')==='https://www.exemplo.com.br/' && ($query['strategy']??'')==='mobile','consulta URL do portal e estratégia mobile');
    return ['status'=>200,'body'=>json_encode($mock,JSON_THROW_ON_ERROR)];
},1731010101);
check181($hits===1 && $res['score']===87 && count($res['metrics'])===3,'pontuação real e apenas métricas permitidas');
check181($res['checked_at']===1731010101 && $res['source']==='Lighthouse mobile','origem e horário explícitos');
check181(igua_pagespeed_last_check($base,['igua_pagespeed_last'=>$res],1731010140)!==null,'resultado recente corresponde à URL');
check181(igua_pagespeed_last_check(['seo_site_url'=>'https://outro.com'],['igua_pagespeed_last'=>$res],1731010140)===null,'não reutiliza resultado de outro domínio');
check181(igua_pagespeed_last_check($base,['igua_pagespeed_last'=>$res],1731013802)===null,'resultado expira após uma hora');
foreach([400,403,429,500] as $status){
    $failed=false;
    try{igua_pagespeed_result(['status'=>$status,'body'=>'{}'],'https://www.exemplo.com.br/');}
    catch(RuntimeException){$failed=true;}
    check181($failed,'erro HTTP não aparece como nota válida: '.$status);
}
foreach([
  ['status'=>200,'body'=>'invalid json'],
  ['status'=>200,'body'=>json_encode(['lighthouseResult'=>['categories'=>['performance'=>['score'=>null]]]])],
  ['status'=>200,'body'=>json_encode(['lighthouseResult'=>['runtimeError'=>['code'=>'FAILED'],'categories'=>['performance'=>['score'=>0.9]]]])],
  ['status'=>200,'body'=>str_repeat('x',5242881)]
] as $bad){$failed=false;try{igua_pagespeed_result($bad,'https://www.exemplo.com.br/');}catch(RuntimeException){$failed=true;}check181($failed,'resposta inválida recusada');}
$site=(string)file_get_contents(__DIR__.'/../public/admin.php');
$routes=(string)file_get_contents(__DIR__.'/../app/admin_controllers/post_routes.php');
$controller=(string)file_get_contents(__DIR__.'/../app/admin_controllers/integrations.php');
$view=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/integrations.php');
check181(str_contains($site,"'integrations_pagespeed_test'=>'manage_settings'") && str_contains($routes,"'integrations_pagespeed_test'=>'integrations'"),'rota protegida por autorização e roteador POST');
check181(str_contains($site,'check_csrf();') && str_contains($view,'data-pagespeed-test-form') && str_contains($view,'csrf_field()'),'CSRF e formulário PageSpeed presentes');
check181(str_contains($controller,'igua_pagespeed_check($cfg,null,null,$strategy)') && str_contains($controller,"'igua_pagespeed_attempts'"),'consulta manual por dispositivo sujeita a intervalo mínimo');
check181(!str_contains($view,'data-pagespeed-api-key') && !str_contains($view,'pagespeed_api_key'),'nenhuma chave ou credencial falsa apresentada');
echo 'PageSpeed 1.0.81: URLs seguras, chamada allowlisted, mocks de API, erros, expiração e rotas protegidas aprovados.'.PHP_EOL;
