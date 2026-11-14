<?php
declare(strict_types=1);
require_once __DIR__.'/bus.php';
/** Home de transporte: saídas de Iguatama por padrão; busca livre de trechos cadastrados. */
function bus_public_page(PDO $db): void {
    $cities=[];$problem=false;
    try {$cities=bus_cities($db);} catch(PDOException){$problem=true;}
    $siteOrigin=bus_site_origin_city(setting('city','Iguatama - MG'));
    $origin=trim((string)($_GET['origem']??$siteOrigin));
    $dest=trim((string)($_GET['destino']??''));
    $valid=$origin!=='' && mb_bus_len($origin)<=100 && mb_bus_len($dest)<=100 && ($dest==='' || strcasecmp($origin,$dest)!==0);
    $routes=[];$trips=[];
    if(!$problem && $valid && $cities) {
        try {
            $routes=$dest!=='' ? bus_matching_routes($db,$origin,$dest) : bus_routes_from_city($db,$origin);
            $trips=bus_upcoming_departures($routes,bus_departures_for_routes($db,$routes),null,24);
        }catch(PDOException){$problem=true;}
    }
    $now=new DateTimeImmutable('now',new DateTimeZone('America/Sao_Paulo'));
    $todayTrips=array_values(array_filter($trips,static fn($trip)=>bus_calendar_label($trip['departs_at'],$now)==='Hoje'));
    ?>
    <div class="bus-page-intro"><div class="bus-eyebrow"><?= h(igua_brand_name()) ?> / TRANSPORTE REGIONAL</div><div class="bus-header-copy"><div><h1>🚌 Próximos ônibus</h1><p>Saídas programadas de <?=h($siteOrigin)?> e região. Veja o destino, o horário e quanto falta para a partida.</p></div><span class="bus-info-icon" aria-hidden="true">↗</span></div></div>
    <details class="bus-search-surface bus-search-collapsible" aria-label="Consultar partidas" <?=($dest!=='' || strcasecmp($origin,$siteOrigin)!==0)?'open':''?>>
    <summary class="bus-search-toggle"><span class="bus-search-magnifier" aria-hidden="true">⌕</span><span class="bus-search-toggle-copy"><strong>Buscar ônibus</strong><small>Escolha a cidade de origem e o destino</small></span><span class="bus-search-chevron" aria-hidden="true">⌄</span></summary>
    <div class="bus-search-expanded"><p class="muted bus-search-explain">Para onde você vai? Consulte apenas rotas cadastradas, sem inventar horários.</p>
    <form method="get" action="/index.php" class="bus-search-form"><input type="hidden" name="p" value="onibus">
    <label>Saindo de <input type="search" name="origem" id="busOrigin" data-city-search="1" value="<?=h($origin)?>" autocomplete="off" list="bus-origin-list" maxlength="100" required placeholder="Digite uma cidade"><datalist id="bus-origin-list"><?php foreach($cities as $city):?><option value="<?=h($city)?>"></option><?php endforeach;?></datalist></label>
    <span class="bus-arrow" aria-hidden="true">→</span><label>Destino <input type="search" name="destino" id="busDestination" data-city-search="1" value="<?=h($dest)?>" autocomplete="off" list="bus-dest-list" maxlength="100" placeholder="Digite, ex.: Uberlândia"><datalist id="bus-dest-list"><?php foreach($cities as $city):?><option value="<?=h($city)?>"></option><?php endforeach;?></datalist></label>
    <button class="btn primary" type="submit">Consultar horários →</button></form>
    <p class="bus-help">Horários informados e aprovados pela equipe do <?= h(igua_brand_name()) ?>, sem monitoramento GPS. Confira o local exato de embarque em cada linha e confirme com a empresa.</p></div></details>
    <?php if($problem):?><div class="bus-notice">A consulta ainda precisa da migração do banco. Entre no painel para atualizar o módulo.</div>
    <?php elseif(!$cities):?><section class="bus-empty-public"><span>🚌</span><h2>Aguardando os primeiros horários</h2><p>A equipe ainda não cadastrou linhas verificadas. Quando houver partidas, elas aparecerão automaticamente aqui.</p></section>
    <?php elseif(!$valid):?><div class="bus-notice">Confira a cidade de origem e o destino. A origem e o destino devem ser diferentes.</div>
    <?php elseif(!$routes):?><section class="bus-empty-public"><span>🛣️</span><h2>Nenhuma linha encontrada</h2><p>Ainda não há trajeto cadastrado saindo de <strong><?=h($origin)?></strong><?= $dest!==''?' para '.h($dest):'' ?>. Esta cidade pode existir no IBGE, mas ainda não há ônibus cadastrado no <?= h(igua_brand_name()) ?> para esse percurso. Consulte a empresa antes de viajar.</p></section>
    <?php elseif(!$trips):?><section class="bus-empty-public"><span>🕓</span><h2>Sem partidas programadas</h2><p>Existem linhas cadastradas para este trecho, mas não há horário válido nos próximos 14 dias. Verifique com a operadora.</p></section>
    <?php else:$next=$trips[0];$place=trim((string)$next['origin_label']);?>
       <section class="bus-results"><div class="bus-results-head"><div><span>QUADRO DE PARTIDAS</span><h2><?=h($origin)?> <span class="bus-direction">→</span> <?=h($dest!==''?$dest:'Todos os destinos')?></h2></div><a href="/index.php?p=onibus">Ver saídas de <?=h($siteOrigin)?> ↗</a></div>
       <?php if(!$todayTrips):?><div class="bus-today-warning">Hoje não há mais saídas cadastradas para esta consulta. A próxima partida está indicada abaixo.</div><?php endif;?>
       <div class="bus-next-card"><div class="bus-next-top"><span class="bus-next-badge">PRÓXIMA SAÍDA PROGRAMADA</span><span class="bus-next-day"><?=h(bus_calendar_label($next['departs_at'],$now))?></span></div>
       <div class="bus-next-main"><div><small>Saindo de <?=h($origin)?></small><strong><?=h($next['departs_at']->format('H:i'))?></strong><span><?=h($next['departs_at']->format('d/m/Y'))?><?= $place!==''?' · '.h($place):''?></span></div><span class="bus-next-dash" aria-hidden="true">→</span><div><small>Destino: <?=h($next['destination_city'])?></small><strong><?= $next['arrives_at']?h($next['arrives_at']->format('H:i')):'A confirmar'?></strong><span><?= $next['arrives_at']?h($next['arrives_at']->format('d/m/Y')):'Chegada não informada' ?></span></div></div>
       <div class="bus-next-duration">⏱ <?=h(bus_duration_label($next['departs_at'],$next['arrives_at']))?></div><div class="bus-next-foot"><span class="bus-countdown" data-bus-time="<?=h($next['departs_at']->format('c'))?>"><?=h(bus_relative_departure($next['departs_at'],$now))?></span><span><?=h($next['company']?:'Empresa não informada')?><?php if($next['fare_amount']!==null):?> · Tarifa total: R$ <?=h(number_format((float)$next['fare_amount'],2,',','.'))?><?php endif;?></span></div></div>
       <div class="section-heading" style="margin-top:30px"><h2>Saídas seguintes</h2><small class="muted">Até 24 partidas nos próximos 14 dias</small></div>
       <div class="bus-trips-list"><?php foreach($trips as $trip):?><article class="bus-trip-row"><div class="bus-date-pill"><small><?=h(bus_calendar_label($trip['departs_at'],$now))?></small><strong><?=h($trip['departs_at']->format('d/m'))?></strong></div><div class="bus-trip-info"><strong><?=h($trip['departs_at']->format('H:i'))?> <span>→</span> <?=h($trip['destination_city'])?></strong><small><?=h($trip['company']?:$trip['route_name'])?> · <?=h($trip['origin_label']?:'Ponto de embarque a confirmar')?></small><small>Chegada: <?= $trip['arrives_at']?h($trip['arrives_at']->format('H:i')):'a confirmar'?> · <?=h(bus_duration_label($trip['departs_at'],$trip['arrives_at']))?> · <?=h(($trip['source_type']??'manual')==='antt'?'ANTT':(($trip['source_type']??'manual')==='der_mg'?'DER-MG':'Cadastro '.igua_brand_name()))?><?php if($trip['fare_amount']!==null && $trip['origin_city']!==$trip['destination_city']):?> · Tarifa do percurso integral: R$ <?=h(number_format((float)$trip['fare_amount'],2,',','.'))?><?php endif;?></small></div><span class="bus-trip-countdown" data-bus-time="<?=h($trip['departs_at']->format('c'))?>"><?=h(bus_relative_departure($trip['departs_at'],$now))?></span></article><?php endforeach;?></div>
       <?php ad_display_slot($db,'p06'); ?>
       <div class="bus-notice"><strong>Horários programados:</strong> não são rastreamento ao vivo. Uma linha cadastrada não garante operação, especialmente em feriados ou mudanças de itinerário. Confirme a empresa, o ponto de embarque e o preço antes de viajar. Dados importados podem estar desatualizados.</div></section>
    <?php endif;
}
