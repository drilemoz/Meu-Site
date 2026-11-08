<?php
declare(strict_types=1);
$root = dirname(__DIR__);
require_once $root.'/app/bootstrap.php';
require_once $root.'/app/analytics.php';
function check211(bool $ok, string $label): void {
    if (!$ok) throw new RuntimeException('Refatoração 211: '.$label);
    echo 'OK '.$label.PHP_EOL;
}
check211(function_exists('analytics_log') && function_exists('analytics_page') && function_exists('analytics_bot'), 'API legada continua disponível por analytics.php');
check211(is_file($root.'/app/analytics_helpers.php'), 'auxiliares separados em arquivo privado');
check211(analytics_page('/noticia/fato-em-iguatama') === '/noticia/fato-em-iguatama', 'rota de notícia preservada');
check211(analytics_page('/categoria/esportes/') === '/categoria/esportes', 'rota de categoria preservada');
check211(analytics_page('/uaicortes/termos', ['p'=>'app','app_slug'=>'uaicortes','app_page'=>'termos']) === '/uaicortes/termos', 'página legal com contexto validado');
check211(analytics_page('/segredo/valor?token=privado') === '/index.php', 'rota privada não é registrada');
check211(analytics_page('/index.php?p=noticia&slug=teste&token=privado') === '/index.php?p=noticia&slug=teste', 'parâmetro não autorizado descartado');
check211(analytics_bot('Mozilla/5.0 (Linux; Android) AppleWebKit') === '', 'navegador comum não vira bot');
check211(analytics_bot('curl/8.0') === 'curl', 'cliente automatizado classificado');
check211(analytics_device('Mozilla/5.0 (iPhone; Mobile Safari)') === 'celular', 'dispositivo móvel reconhecido');
check211(analytics_referrer('https://www.google.com/search?q=segredo', ['HTTP_HOST'=>'iguatama.online']) === ['busca','www.google.com'], 'origem da busca sem consulta persistida');
check211(analytics_opted_out(['HTTP_SEC_GPC'=>'1'], []) && analytics_opted_out(['HTTP_DNT'=>'1'], []), 'GPC e DNT preservados');
check211(analytics_visitor_hash('1.2.3.4','UA','2026-11-07','chave') !== analytics_visitor_hash('1.2.3.4','UA','2026-11-08','chave'), 'identificador permanece diário');
check211(analytics_ga4_measurement_id('g-test123') === 'G-TEST123', 'funções de GA4 continuam disponíveis');
echo 'Refatoração PHP 1.0.111 aprovada.'.PHP_EOL;
