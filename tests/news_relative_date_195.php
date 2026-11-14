<?php
declare(strict_types=1);
require_once __DIR__.'/../app/admin_views/partials/news_cards.php';
function check195(bool $condition, string $description): void {
    if (!$condition) throw new RuntimeException('1.0.95: '.$description);
}
$tz=new DateTimeZone('America/Sao_Paulo');
$now=new DateTimeImmutable('2026-11-07 12:00:00',$tz);
$article=['status'=>'published','published_at'=>'2026-11-07 08:00:00'];
check195(igua_news_relative_date($article,$now)==='Hoje','data publicada válida');
$article['published_at']='2026-11-06 22:00:00';
check195(igua_news_relative_date($article,$now)==='Ontem','dia anterior válido');
$article['published_at']='2026-11-04 09:00:00';
check195(igua_news_relative_date($article,$now)==='Há 3 dias','três dias decorridos');
$article['published_at']='2026-11-08 09:00:00';
check195(igua_news_relative_date($article,$now)==='Amanhã','agendamento futuro');
foreach (['invalid', '2026-02-30 08:00:00', '2026-13-01 08:00:00', "\0", ''] as $invalid) {
    $article['published_at']=$invalid;
    check195(igua_news_relative_date($article,$now)==='Data indisponível','data publicada inválida: '.bin2hex($invalid));
}
$article=['status'=>'draft','created_at'=>'2026-11-03 08:00:00'];
check195(igua_news_relative_date($article,$now)==='Criado há 4 dias','data de criação');
$article['created_at']='invalid';
check195(igua_news_relative_date($article,$now)==='Data indisponível','data inválida de rascunho');
$article['created_at']=null;
check195(igua_news_relative_date($article,$now)==='Rascunho','data ausente de rascunho');
echo "1.0.95: datas válidas e inválidas sem exceção aprovadas.\n";
