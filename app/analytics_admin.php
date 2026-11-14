<?php
declare(strict_types=1);
require_once __DIR__.'/analytics.php';

function analytics_period(array $query): int|string {
    $value=$query['days']??'30';
    if(!is_string($value) && !is_int($value))return 30;
    $value=(string)$value;
    return in_array($value,['today','24h'],true)?$value:(in_array($value,['7','30','90'],true)?(int)$value:30);
}
function analytics_audience(array $query): string {
    $value=$query['group']??'human';
    return is_string($value)?analytics_group($value):'human';
}
function analytics_admin_view(PDO $db,int|string $days=30,string $group='human'): void {
    $data=analytics_admin_data($db,$days,null,$group);
    $s=$data['summary'];$period=(string)$data['period'];$group=$data['group'];
    $humans=(int)($s['views']??0);$bots=(int)($s['bots']??0);$suspect=(int)($s['suspect']??0);
    $pageviews=$humans+$bots+$suspect;$clicks=(int)($s['clicks']??0);
    $ratio=static fn(int $value):float=>$pageviews>0?round($value*100/$pageviews,1):0.0;
    $max=1;foreach($data['series'] as $d)$max=max($max,(int)$d['views'],(int)$d['bots'],(int)$d['suspect']);
    $types=['human'=>['Público provável','Visualizações sem indícios de automação'],
            'bot'=>['Robôs declarados','Agentes que se identificam como rastreadores'],
            'suspect'=>['Suspeitos','Ferramentas automatizadas, UA vazio e tráfego em rajada']];
    $labels=[['Público provável',$humans,'human'],['Robôs declarados',$bots,'bot'],['Suspeitos',$suspect,'suspect']];
    ?>
<section class="panel analytics-panel" aria-labelledby="analytics-title">
  <div class="panel-heading">
    <div><h2 id="analytics-title">Audiência do portal</h2><p class="muted">Métricas locais · horário de Brasília · <?=h($data['label'])?></p></div>
    <nav class="analytics-periods" aria-label="Período das métricas">
      <?php foreach ([['today','Hoje'],['24h','24h'],['7','7d'],['30','30d'],['90','90d']] as [$key,$label]):?>
        <a href="/admin.php?action=analytics&amp;days=<?=h($key)?>&amp;group=<?=h($group)?>" class="<?= $period===$key?'current':'' ?>" <?= $period===$key?'aria-current="page"':'' ?>><?=h($label)?></a>
      <?php endforeach;?>
    </nav>
  </div>
  <div class="compact-heading analytics-explainer">
    <span class="muted tiny">Três grupos de requisições, sem identificar pessoas individualmente.</span>
    <details class="compact-help"><summary aria-label="Como interpretar humanos e bots">?</summary>
      <div class="compact-help-content">“Público provável” significa somente que não detectamos automação. “Robôs declarados” são identificados pelo User-Agent, não há verificação oficial de IP. “Suspeitos” incluem ferramentas HTTP, navegadores headless, agentes vazios e rajadas. Os sinais podem ser falsificados, com falsos positivos e negativos. Histórico anterior é preservado sem reclassificação retroativa.</div>
    </details>
  </div>
  <div class="analytics-kpis analytics-kpis-audience">
    <?php foreach($labels as [$title,$count,$name]):?>
      <div class="analytics-kpi analytics-kpi-<?=h($name)?>"><span><?=h($title)?></span><strong><?=number_format($count,0,',','.')?></strong><small><?=number_format($ratio($count),1,',','.')?>% das visualizações</small></div>
    <?php endforeach;?>
    <div class="analytics-kpi"><span>Identificadores/dia · público provável</span><strong><?=number_format((int)($s['visitors_day']??0),0,',','.')?></strong><small>Não são pessoas únicas no período</small></div>
  </div>
  <div class="analytics-share" aria-label="Distribuição do tráfego por categoria">
    <div class="analytics-share-track" role="img" aria-label="<?=number_format($ratio($humans),1,',','.')?>% público provável, <?=number_format($ratio($bots),1,',','.')?>% bots e <?=number_format($ratio($suspect),1,',','.')?>% suspeitos">
      <span class="human" style="width:<?=$ratio($humans)?>%"></span><span class="robot" style="width:<?=$ratio($bots)?>%"></span><span class="suspect" style="width:<?=$ratio($suspect)?>%"></span>
    </div>
    <p class="analytics-legend"><span class="analytics-dot human"></span>Público provável <span class="analytics-dot robot"></span>Robôs <span class="analytics-dot suspect"></span>Suspeitos</p>
  </div>
  <div class="analytics-graph <?= $data['hourly']?'analytics-hourly':'' ?>" role="group" aria-label="Comparação de acessos <?= $data['hourly']?'por hora':'por dia' ?>">
    <?php foreach($data['series'] as $v):?>
      <div class="analytics-bar-col" title="<?=h($v['title'])?>: <?=$v['views']?> acessos prováveis, <?=$v['bots']?> bots, <?=$v['suspect']?> suspeitos">
        <div class="analytics-bars"><i class="humans <?= $v['views']===0?'is-zero':'' ?>" style="height:<?=$v['views']?max(2,round($v['views']/$max*100)):0?>%"></i><i class="bots <?= $v['bots']===0?'is-zero':'' ?>" style="height:<?=$v['bots']?max(2,round($v['bots']/$max*100)):0?>%"></i><i class="suspects <?= $v['suspect']===0?'is-zero':'' ?>" style="height:<?=$v['suspect']?max(2,round($v['suspect']/$max*100)):0?>%"></i></div>
        <small><?=h($v['label'])?></small>
      </div>
    <?php endforeach;?>
  </div>
  <?php if($pageviews===0):?><p class="muted tiny">Nenhuma visualização registrada no período escolhido.</p><?php endif;?>
  <div class="analytics-interactions"><div><strong><?=number_format($clicks,0,',','.')?></strong><span> Cliques em links (JavaScript)</span></div><div><strong><?=number_format((int)($s['engaged_ids']??0),0,',','.')?></strong><span> Identificadores/dia com clique</span></div></div>
  <p class="muted tiny">Cliques podem ser bloqueados por privacidade e não comprovam interação humana. Os valores não são equivalentes ao GA4.</p>
</section>
<section class="panel analytics-audience-panel" aria-labelledby="analytics-audience-title">
  <div class="panel-heading"><div><h2 id="analytics-audience-title">Explorar o tráfego</h2><p class="muted tiny"><?=h($types[$group][1])?></p></div></div>
  <nav class="analytics-audience-tabs" aria-label="Tipo de tráfego">
    <?php foreach($types as $key=>$item):?><a href="/admin.php?action=analytics&amp;days=<?=h($period)?>&amp;group=<?=h($key)?>#analytics-audience-title" class="<?= $group===$key?'current':'' ?>" <?= $group===$key?'aria-current="page"':'' ?>><?=h($item[0])?></a><?php endforeach;?>
  </nav>
  <p class="muted tiny">Tabelas filtradas por <?=h($types[$group][0])?>. Os gráficos acima sempre comparam as três categorias.</p>
</section>
<div class="analytics-layout">
  <?php
  $tables=[['Páginas mais acessadas','pages'],['Origem do tráfego','sources'],['Sites de referência','referrers'],['Países','countries'],['Estados / regiões','regions']];
  if($group==='human'){
      $tables[]=['Dispositivos','devices'];$tables[]=['Campanhas identificadas','campaigns'];$tables[]=['Links clicados','clicks'];
  }else{
      $tables[]=[($group==='suspect'?'Indícios detectados':'Agentes declarados'),'robots'];
  }
  foreach($tables as [$heading,$key]):?>
    <section class="panel analytics-table"><h2><?=h($heading)?></h2><div class="table-wrap"><table><thead><tr><th>Item</th><th class="analytics-number">Total</th></tr></thead><tbody>
    <?php
      $rows=$data[$key];
      if($key==='campaigns')$rows=array_values(array_filter($rows,static fn(array $r):bool=>(string)($r['label']??'')!==''));
      if(!$rows):?><tr><td colspan="2" class="muted">Sem dados nesta categoria e período.</td></tr><?php endif;
      foreach($rows as $entry):?><tr><td title="<?=h((string)$entry['label'])?>"><?=h($entry['label']?:'Não identificado')?></td><td class="analytics-number"><?=number_format((int)$entry['total'],0,',','.')?></td></tr><?php endforeach;?>
    </tbody></table></div></section>
  <?php endforeach;?>
</div>
<section class="panel analytics-notes" id="analytics-integrations">
  <div class="panel-heading"><div class="compact-heading"><h2>Configurações</h2><details class="compact-help"><summary aria-label="Ajuda sobre métricas e Google Analytics">?</summary><div class="compact-help-content">As métricas locais ficam no banco do portal. O GA4 é configurado separadamente em Integrações e precisa do consentimento do visitante; os relatórios GA4 não são importados para este painel.</div></details></div></div>
  <form method="post" action="/admin.php?action=analytics_settings"><?=csrf_field()?>
    <label class="analytics-toggle"><input type="checkbox" name="enabled" value="1" <?=setting('analytics_enabled','1')==='1'?'checked':''?>> <span>Coletar métricas internas (retidas por 90 dias)</span></label>
    <div class="analytics-form-actions"><button class="btn primary" type="submit">Salvar preferência</button><a class="btn outline small" href="/admin.php?action=integrations#google-ga4">Gerenciar GA4 ↗</a></div>
  </form>
  <p class="muted analytics-caveat">Classificação aproximada: acessos do público provável não são necessariamente humanos. Navegadores maliciosos podem imitar pessoas ou robôs, e acessos legítimos podem parecer suspeitos. Os identificadores usam IP e User-Agent com HMAC renovado por dia, sem armazenamento de IP bruto.</p>
  <details class="analytics-technical"><summary>Privacidade, horários e limites</summary><p class="muted">GPC, DNT e a preferência de exclusão são respeitados. Novos eventos são datados pelo relógio PHP em America/Sao_Paulo, sem alterar registros antigos. País e região só aparecem quando há GeoLite2 offline instalada. O banco não registra visitas bloqueadas por cache externo ou CDN. Uma visita sem JavaScript pode gerar visualização, mas não clique. Dados históricos anteriores à classificação em três grupos são interpretados a partir dos nomes que já estavam gravados, sem alteração da tabela ou do backup.</p></details>
</section>
<?php }
