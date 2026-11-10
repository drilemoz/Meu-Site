<?php
/** Verificações locais do resumo de partida única v1.0.146. */
declare(strict_types=1);
function check246(bool $value,string $description): void {if (!$value) throw new RuntimeException('FALHA: '.$description);echo "OK: $description\n";}
$home=file_get_contents(__DIR__.'/../app/public_views/routes/home.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/modules/05-site-layout-contact.css');
check246(str_contains($home, "bus_home_summary($".'db'.",setting('city','Iguatama - MG'),1)"), 'home consulta somente uma saída');
check246(str_contains($home,"$".'trip'."=$".'homeBuses'."['trips'][0]"), 'home renderiza primeira saída');
check246(!str_contains($home,'foreach ($homeBuses'), 'sem repetição de múltiplas partidas');
check246(str_contains($home,'Próximo ônibus') && str_contains($home,'Ver horários'), 'título e acesso aos horários');
check246(str_contains($home,'destination_city') && str_contains($home,"format('H:i')") && str_contains($home,'data-bus-time') && str_contains($home,'bus_calendar_label'), 'destino, hora, dia e contagem');
foreach(['TRANSPORTE REGIONAL','Saídas programadas','Horários cadastrados, sem localização em tempo real','Ponto a confirmar',"$".'trip'."['company']",'home-bus-disclaimer'] as $removed){check246(!str_contains($home,$removed),'informação dispensável removida: '.$removed);}
check246(str_contains($home,"'unavailable'") && str_contains($home,"'no_departures'"), 'fallback de consulta preservado');
check246(!str_contains($home,'categories-strip') && strpos($home,'home-bus') < strpos($home,'headline-heading'), 'posição correta e sem categorias');
check246(str_contains($css,'.home-bus-departure') && !str_contains($css,'.home-bus-disclaimer'), 'estilo de card único, sem aviso');
check246(str_contains(file_get_contents(__DIR__.'/../app/public_views/layout.php'),'search-suggest.js'), 'busca automática preservada');
echo "OK: card único v1.0.146\n";
