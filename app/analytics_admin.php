<?php
declare(strict_types=1);
require_once __DIR__.'/analytics.php';
/** Aceita apenas os períodos publicados no painel; sem parâmetro usa 30 dias. */
function analytics_period(array $query): int|string {
    $raw=$query['days']??null;
    if(!is_string($raw) && !is_int($raw)) return 30;
    $value=(string)$raw;
    return in_array($value,['today','24h'],true)?$value:(in_array($value,['7','30','90'],true)?(int)$value:30);
}
function analytics_admin_view(PDO $db,int|string $days=30): void {
    $data=analytics_admin_data($db,$days);$s=$data['summary'];$max=1;
    foreach($data['series'] as $d)$max=max($max,(int)$d['views'],(int)$d['bots']);
    ?>
<section class="panel analytics-panel" aria-labelledby="analytics-title">
  <div class="panel-heading"><div><h2 id="analytics-title">Audiência e comportamento</h2><p class="muted">Dados locais · horário de Brasília · <?= h($data['label']) ?></p></div><nav class="analytics-periods" aria-label="Período das métricas"><?php foreach ([['today','Hoje'],['24h','24h'],['7','7d'],['30','30d'],['90','90d']] as [$key,$label]): ?><a class="<?= (string)$data['period']===$key?'current':'' ?>" href="/admin.php?action=analytics&amp;days=<?= h($key) ?>" <?= (string)$data['period']===$key?'aria-current="page"':'' ?>><?= h($label) ?></a><?php endforeach; ?></nav></div>
  <div class="compact-heading analytics-explainer"><span class="muted tiny">Solicitações registradas; separação de automações é aproximada.</span><details class="compact-help"><summary aria-label="Ajuda sobre métricas e visitantes">?</summary><div class="compact-help-content">Hoje: desde 00h no horário de Brasília. 24h: período móvel até agora. Os valores são solicitações HTTP, não pessoas comprovadas. Robôs usam User-Agent e indícios de volume; podem existir falsos positivos e negativos. Identificadores são estimados por IP e navegador, trocam por dia e podem agrupar pessoas ou duplicar uma mesma pessoa. Gráficos exibem zero em horas/dias sem registros.</div></details></div>
  <div class="analytics-kpis">
  <?php foreach ([['Visualizações não identificadas como robôs',(int)($s['views']??0)],['Identificadores/dia (estim.)',(int)($s['visitors_day']??0)],['Automação detectada',(int)($s['bots']??0)],['Cliques registrados',(int)($s['clicks']??0)]] as $k): ?>
    <div class="analytics-kpi"><span><?=h($k[0])?></span><strong><?=number_format($k[1],0,',','.')?></strong></div>
  <?php endforeach; ?></div>
  <?php if((int)($s['events']??0)===0): ?><p class="muted tiny">Sem acessos registrados neste período.</p><?php endif; ?>
  <div class="analytics-graph <?= $data['hourly']?'analytics-hourly':'' ?>" role="group" aria-label="Gráfico de <?= $data['hourly']?'acessos por hora':'acessos por dia' ?>">
    <?php foreach($data['series'] as $v):?><div class="analytics-bar-col" title="<?= h($v['title']) ?>: <?= (int)$v['views'] ?> acessos não identificados, <?= (int)$v['bots'] ?> acessos detectados como automação"><div class="analytics-bars"><i style="height:<?= (int)$v['views']>0?max(2,round((int)$v['views']/$max*100)):0 ?>%" class="humans <?= (int)$v['views']===0?'is-zero':'' ?>"></i><i style="height:<?= (int)$v['bots']>0?max(2,round((int)$v['bots']/$max*100)):0 ?>%" class="bots <?= (int)$v['bots']===0?'is-zero':'' ?>"></i></div><small><?= h($v['label']) ?></small></div><?php endforeach; ?>
  </div><p class="analytics-legend"><span class="analytics-dot human"></span> Não identificados <span class="analytics-dot robot"></span> Possível automação</p>
</section>
<div class="analytics-layout">
<?php foreach ([
  ['Páginas mais acessadas','pages'],['Cliques em links','clicks'],['Origem do tráfego','sources'],
  ['Sites que enviaram visitantes','referrers'],['Países','countries'],['Estados / regiões','regions'],
  ['Dispositivos','devices'],['Robôs identificados','robots']
] as $section):?>
  <section class="panel analytics-table"><h2><?=h($section[0])?></h2><div class="table-wrap"><table><thead><tr><th>Item</th><th class="analytics-number">Total</th></tr></thead><tbody>
  <?php if(!$data[$section[1]]):?><tr><td colspan="2" class="muted">Sem informações neste período.</td></tr><?php endif;?>
  <?php foreach($data[$section[1]] as $entry):?><tr><td title="<?=h($entry['label'])?>"><?=h($entry['label']?:'Não identificado')?></td><td class="analytics-number"><?=number_format((int)$entry['total'],0,',','.')?></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php endforeach; ?>
</div>
<section class="panel analytics-notes" id="analytics-integrations"><div class="panel-heading"><div class="compact-heading"><h2>Configurações e integrações</h2><details class="compact-help"><summary aria-label="Ajuda sobre métricas e Google Analytics">?</summary><div class="compact-help-content">As métricas locais ficam no banco do portal. O GA4 agora é configurado na Central de Integrações e requer consentimento; seus relatórios não são importados para este painel.</div></details></div></div>
  <form method="post" action="/admin.php?action=analytics_settings"><?=csrf_field()?>
    <label class="analytics-toggle"><input type="checkbox" name="enabled" value="1" <?=setting('analytics_enabled','1')==='1'?'checked':''?>> <span>Métricas internas (90 dias)</span></label>
    <div class="analytics-form-actions"><button class="btn primary" type="submit">Salvar métricas internas</button><a class="btn outline small" href="/admin.php?action=integrations#google-ga4">Gerenciar GA4 ↗</a></div>
  </form>
  <p class="muted analytics-caveat">Acessos classificados como não automatizados não são necessariamente humanos. A classificação usa indícios de navegador e volume de requisições, não verifica a identidade de cada visitante. Dados históricos não são reclassificados.</p>
  <details class="analytics-technical"><summary>Detalhes técnicos e privacidade</summary><p class="muted">A geolocalização local exige <code>storage/geoip/GeoLite2-City.mmdb</code> e <code>geoip2/geoip2</code>. Os países e estados não são inventados quando ela falta. Métricas internas respeitam GPC/DNT e o opt-out da Política de Privacidade. O GA4 exige consentimento explícito, não é equivalente ao AdSense e não compartilha os dados automaticamente com os relatórios internos.</p></details>
</section>
<?php }
