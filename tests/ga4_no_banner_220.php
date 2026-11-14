<?php
declare(strict_types=1);
function check220(bool $ok, string $label): void {if (!$ok) throw new RuntimeException('GA4 1.0.120: '.$label);}
$layout=(string)file_get_contents(__DIR__.'/../app/public_views/layout.php');
$privacy=(string)file_get_contents(__DIR__.'/../app/public_views/routes/privacy.php');
$js=(string)file_get_contents(__DIR__.'/../public/assets/js/ga4-consent.js');
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/modules/08-analytics.css');
check220(str_contains($layout,'ga4ConsentConfig') && !str_contains($layout,'id="ga4Consent"'), 'configuração invisível e ausência de banner fixo');
check220(str_contains($layout,'href="/index.php?p=privacidade#preferencias-ga4"'), 'rodapé leva diretamente ao controle na política');
check220(!str_contains($layout,'id="ga4Allow"') && !str_contains($layout,'id="ga4Deny"'), 'não há escolhas intrusivas no HTML de todas as páginas');
check220(str_contains($privacy,'id="preferencias-ga4"') && str_contains($privacy,'id="ga4Allow"') && str_contains($privacy,'id="ga4Deny"'), 'controles disponíveis na política de privacidade');
check220(str_contains($js,"cookie(key) !== accepted") && str_contains($js,'disabledByBrowser()'), 'consentimento e GPC/DNT mantidos');
check220(!str_contains($css,'.ga4-consent{position:fixed') && str_contains($css,'.privacy-ga4 .ga4-consent-actions'), 'visual inline sem modal fixo');
check220(!str_contains($layout,'Medição externa bloqueada pela preferência de privacidade'), 'sem mensagem invasiva no portal');
echo "GA4 1.0.120: controle opcional sem aviso fixo e com consentimento protegido.\n";
