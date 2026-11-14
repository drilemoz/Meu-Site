<?php
/** Ferramentas > Diagnóstico de DNS. Apenas leitura; domínio retirado da URL oficial salva. */
$dns=igua_dns_snapshot((string)setting('seo_site_url',''));
$dnsTypes=['A'=>'IPv4','AAAA'=>'IPv6','CNAME'=>'Alias','NS'=>'Servidores DNS'];
$dnsSources=['Hospedagem','Google','Cloudflare'];
$renderDnsValue=static function(array $record): string {
    if(($record['state']??'error')==='error')return 'Consulta indisponível';
    if(($record['state']??'empty')==='empty')return 'Sem registro';
    $values=$record['values']??[];
    return implode(', ',array_map('strval',array_slice(is_array($values)?$values:[],0,12)));
};
?>
<div class="dns-diag" aria-labelledby="dns-diag-heading">
 <section class="dns-diag-hero">
  <div>
   <span class="dns-diag-kicker">FERRAMENTAS · CONSULTA AUTOMÁTICA</span>
   <h2 id="dns-diag-heading">Diagnóstico de DNS</h2>
   <p>Veja para onde seu domínio está apontando, sem precisar digitá-lo.</p>
  </div>
  <div class="dns-diag-actions"><a class="btn outline small" href="<?=h(admin_link('dns_diagnostic'))?>">↻ Atualizar</a>
   <details class="dns-diag-help"><summary aria-label="Ajuda sobre diagnóstico DNS" title="Ajuda">?</summary><p>Os registros A/AAAA indicam endereços publicados no DNS. NS indica os servidores de nomes; CNAME indica aliases. Consultas podem divergir durante a propagação. Servidores com proxy ou CDN, como Cloudflare, podem ocultar o IP da hospedagem real.</p></details></div>
 </section>
 <?php if(!$dns['ok']): ?>
  <section class="dns-diag-panel" role="status"><p><?=h($dns['message'])?></p><a class="btn primary" href="<?=h(admin_link('settings'))?>">Configurar endereço oficial</a></section>
 <?php else: ?>
  <section class="dns-diag-overview" aria-label="Domínio identificado">
   <div><small>Domínio oficial identificado</small><strong><?=h($dns['host'])?></strong><span>Atualizado em <?=h($dns['collected_at'])?> · horário de Brasília</span></div>
   <span class="dns-diag-tag">Automático</span>
  </section>
  <section class="dns-diag-panel" aria-labelledby="dns-diag-public-heading">
   <div class="dns-diag-panel-heading"><div><h3 id="dns-diag-public-heading">Apontamento do domínio</h3><p>Comparação da hospedagem com dois resolvedores públicos.</p></div></div>
   <div class="dns-diag-records">
    <?php foreach($dnsTypes as $type=>$title):
      $status=$dns['comparison'][$type]??'unknown';
      $badge=$status==='same'?'Concordam':($status==='different'?'Diferentes':'Sem comparação'); ?>
     <article class="dns-diag-record">
      <div class="dns-diag-record-head"><div><strong><?=h($type)?></strong><span><?=h($title)?></span></div><span class="dns-diag-state <?=h($status)?>"><?=h($badge)?></span></div>
      <dl>
       <?php foreach($dnsSources as $source):
         $row=$source==='Hospedagem'?($dns['local'][$dns['host']][$type]??[]):($dns['public'][$source][$type]??[]);
         $isError=($row['state']??'error')==='error'; ?>
        <div><dt><?=h($source)?></dt><dd class="<?= $isError?'is-unavailable':'' ?>"><?=h($renderDnsValue($row))?></dd></div>
       <?php endforeach; ?>
      </dl>
     </article>
    <?php endforeach; ?>
   </div>
  </section>
  <?php if(count($dns['hosts'])>1): $secondary=$dns['hosts'][1]; ?>
  <section class="dns-diag-panel" aria-labelledby="dns-diag-alias-heading">
   <div class="dns-diag-panel-heading"><div><h3 id="dns-diag-alias-heading">Endereço alternativo</h3><p><?=h($secondary)?></p></div></div>
   <dl class="dns-diag-secondary">
    <?php foreach(['A','AAAA','CNAME'] as $type): ?><div><dt><?=h($type)?></dt><dd><?=h($renderDnsValue($dns['local'][$secondary][$type]??[]))?></dd></div><?php endforeach; ?>
   </dl>
  </section>
  <?php endif; ?>
  <?php if(in_array('different',$dns['comparison'],true)): ?>
   <p class="dns-diag-notice">⚠ Alguns resolvedores retornaram resultados diferentes. Pode ser propagação ou diferenças de cache; confira novamente mais tarde.</p>
  <?php endif; ?>
  <p class="dns-diag-footnote">Consultas somente de leitura ao abrir esta página. “Sem registro” é diferente de “Consulta indisponível”. O DNS não comprova onde está o servidor de origem se houver CDN ou proxy. As consultas externas usam os serviços públicos Google e Cloudflare; nenhum token é enviado.</p>
 <?php endif; ?>
</div>
