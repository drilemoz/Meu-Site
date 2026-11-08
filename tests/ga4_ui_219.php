<?php
declare(strict_types=1);
// Teste isolado do HTML administrativo GA4; sem banco, rede ou credenciais reais.
function h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function csrf_field(): string { return '<input type="hidden" name="csrf" value="TESTE">'; }
function check219(bool $ok, string $reason): void { if (!$ok) throw new RuntimeException('GA4 UI 219: '.$reason); }
function render219(bool $configured, bool $serviceAccount, ?array $live=null): string {
    $cfg=['ga4_measurement_id'=>'G-TESTE1234', 'ga4_property_id'=>'123456789'];
    $gaOn=$configured;
    $ga4CredentialsReady=$serviceAccount;
    $ga4Live=$live;
    $ga4Audience=null;
    ob_start();
    require __DIR__.'/../app/admin_views/routes/integrations/ga4.php';
    return (string)ob_get_clean();
}
$html=render219(true, false);
foreach (['integrations_save_ga4', 'integrations_ga4_test','integrations_ga4_report', 'integrations_ga4_credentials',
          'int-ga-verify-guide', 'int-ga-api-details', 'int-ga-collection-state',
          'Recebimento ainda não verificado', 'requer conta de serviço', 'O painel administrativo não dispara visualizações GA4',
          'Como saber se o GA4 está recebendo visitas?', 'Tag Assistant', 'O ID numérico do fluxo não é o ID da propriedade'] as $value) {
    check219(stripos($html, $value)!==false, 'faltando mensagem/contrato: '.$value);
}
check219(stripos($html,'requer conta de serviço')!==false && substr_count($html,'disabled')>=2, 'consultas API desabilitadas sem credencial');
check219(strpos($html,'int-ga-verify-guide') < strpos($html,'int-ga-api-details'), 'verificação do rastreamento antes da API');
check219(strpos($html,'int-ga-api-details') < strpos($html,'int-live-panel'), 'consultas avançadas no bloco opcional');
check219(!str_contains($html,'private_key'), 'chaves privadas não são expostas');
check219(substr_count($html,'name="csrf"')>=4, 'CSRF nos formulários');
$confirmed=render219(true,true,['status'=>'realtime','realtime_events'=>2,'realtime_users'=>1,'recent_events'=>3,'last_event_date'=>'','checked_at'=>'Agora']);
check219(str_contains($confirmed,'Eventos retornados pela API') && str_contains($confirmed,'is-confirmed'), 'resultado comprovado por consulta');
check219(!str_contains($confirmed,'Sem credencial para a API'), 'não inventa falta de credencial');
$off=render219(false,false);
check219(str_contains($off,'Rastreamento desativado no portal'), 'rótulo quando desligado');
check219(str_contains($off,'Recebimento ainda não verificado'), 'não presume recebimento quando desligado');
echo "GA4 UI 1.0.119: 3 estados renderizados, formulário preservado, ajuda e relatório opcional verificados.\n";
