<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function check219(bool $ok,string $msg): void {if(!$ok)throw new RuntimeException('GA4 UI 219: '.$msg);}
function render219(bool $hasCred,bool $hasLast=false): string {
    $cfg=['ga4_measurement_id'=>'G-ABCD123456','ga4_property_id'=>'123456789'];
    $gaOn=true; $ga4CredentialsReady=$hasCred; $ga4Live=$hasLast?['status'=>'realtime','realtime_events'=>2,'realtime_users'=>1,'recent_events'=>7,'checked_at'=>'08/11/2026 01:00:00']:null;
    $ga4Audience=null;
    ob_start();require __DIR__.'/../app/admin_views/routes/integrations/ga4.php';return (string)ob_get_clean();
}
$noCredential=render219(false);
check219(str_contains($noCredential,'Rastreamento habilitado no portal') && str_contains($noCredential,'Isso não confirma que o Google já recebeu visitas'),'status não promete coleta confirmada');
check219(str_contains($noCredential,'não exige a conta de serviço') && str_contains($noCredential,'https://analytics.google.com/'),'consulta Tempo real independente da API');
check219(str_contains($noCredential,'Não é necessário para o rastreamento público') && str_contains($noCredential,'Consulta pela API ainda não configurada'),'clareza sobre ID e credencial');
check219((bool)preg_match('~<details class="int-ga-api-tools"\s*>~',$noCredential),'consulta opcional começa recolhida');
check219((bool)preg_match('~action="/admin\.php\?action=integrations_ga4_test"[^>]*>\s*<input[^>]*>\s*<button[^>]*disabled~s',$noCredential),'teste da API desabilitado quando sem credencial');
check219(substr_count($noCredential,'<details')===substr_count($noCredential,'</details>'),'elementos details balanceados');
check219(substr_count($noCredential,'csrf_field')===0 && substr_count($noCredential,'name="csrf"')>=4,'CSRF renderizado nos formulários');
$ready=render219(true,true);
check219(str_contains($ready,'Conta de serviço cadastrada') && str_contains($ready,'int-ga-api-tools" open'),'última consulta abre resultados');
check219(str_contains($ready,'<strong>2</strong>') && str_contains($ready,'<strong>7</strong>'),'resultado de teste é apresentado');
check219(!str_contains($ready,'disabled>↗ Testar recebimento') && str_contains($ready,'Testar recebimento'),'teste habilitado com credencial');
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/modules/16-security.css');
check219(str_contains($css,'.int-ga .int-live-panel .btn:disabled') && str_contains($css,'.int-ga-api-tools>summary'),'estado dos botões e resumo em CSS');
echo "GA4 1.0.119: estado, consentimento explicado, painel opcional, CSRF, resultados, disabled e CSS OK.\n";
