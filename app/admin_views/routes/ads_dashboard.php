<?php
/** Painel visual de publicidade. Autenticação/CSRF ficam em public/admin.php. */
$bySlot=[];
foreach($campaigns as $c)$bySlot[(string)$c['placement_slug']][]=$c;
$activeNow=0;
$free=0;
$today=date('Y-m-d');
foreach($slots as $slot){
    $code=(string)$slot['slug'];
    $running=array_filter($bySlot[$code]??[],static fn(array $c)=>(int)$c['active']===1&&(string)$c['start_date']<=date('Y-m-d')&&($c['end_date']===null||$c['end_date']>=date('Y-m-d')));
    if((int)$slot['enabled']&&($slot['provider']==='adsense'||($slot['provider']==='local'&&$running)))$activeNow++;
    else $free++;
}
?>
<section class="panel ad-center" id="ad-center">
  <div class="ad-center-heading">
    <div><h2>Central de publicidade</h2></div>
    <div class="ad-center-toolbar">
      <button class="btn primary" type="button" data-ad-open="ad-type" aria-haspopup="dialog">+ Nova campanha</button>
      <button class="btn outline ad-settings-button" type="button" data-ad-open="ad-settings" aria-label="Configurações da publicidade" title="Configurações" aria-haspopup="dialog">⚙</button>
    </div>
  </div>
  <div class="ad-metrics" aria-label="Resumo da publicidade">
    <div><strong><?=count($slots)?></strong><small>Posições</small></div>
    <div><strong><?=$activeNow?></strong><small>Em uso agora</small></div>
    <div><strong><?=$free?></strong><small>Sem exibição</small></div>
    <div><strong><?=$total?></strong><small>Campanhas</small></div>
  </div>
  <div class="ad-center-info"><span class="ad-admin-chip <?= $adsOn?'on':'off' ?>"><?= $adsOn?'● Publicidade geral ativa':'○ Publicidade geral desativada' ?></span><?php if($demoTotal):?><span><?= $demoTotal ?> exemplo(s) · <button type="button" class="ad-inline-action" data-ad-open="ad-settings">Gerenciar</button></span><?php endif;?></div>
  <div class="ad-section-heading"><h3>Posições do site</h3><span class="muted tiny">P01 a P06</span></div>
  <div class="ad-positions-compact" id="slots">
    <?php foreach($slots as $slot):$slug=(string)$slot['slug'];if(!ad_valid_slot($slug))continue;
      $running=array_filter($bySlot[$slug]??[],static fn(array $c)=>(int)$c['active']===1&&(string)$c['start_date']<=date('Y-m-d')&&($c['end_date']===null||$c['end_date']>=date('Y-m-d')));
      $state=!(int)$slot['enabled']||$slot['provider']==='off'?'Desativada':($slot['provider']==='adsense'?'AdSense':(count($running)?'Local em exibição':'Sem campanha ativa'));
    ?>
    <button class="ad-position-line" type="button" data-ad-open="ad-slot-<?=h($slug)?>" aria-haspopup="dialog" aria-label="Configurar <?=h(strtoupper($slug))?> · <?=h(AD_SLOTS[$slug][0])?>">
      <span class="ad-code"><?=h(strtoupper($slug))?></span>
      <span class="ad-position-text"><strong><?=h(AD_SLOTS[$slug][0])?></strong><small><?=h($state)?> · <?=count($bySlot[$slug]??[])?> campanha(s) cadastrada(s)</small></span>
      <span aria-hidden="true">›</span>
    </button>
    <?php endforeach; ?>
  </div>
</section>
<section class="panel ad-center" id="campaigns">
  <div class="ad-section-heading"><h2>Campanhas</h2><button class="btn outline small" type="button" data-ad-open="ad-type">+ Nova</button></div>
  <?php if(!$campaigns):?><p class="muted">Nenhuma campanha cadastrada. Use “+ Nova campanha” para começar.</p><?php endif; ?>
  <div class="ad-campaign-list">
  <?php foreach($campaigns as $c): ?>
    <details class="ad-campaign-row">
      <summary><span class="ad-campaign-summary"><strong><?=h($c['title'])?></strong><small><?=h(strtoupper((string)$c['placement_slug']))?> · <?=h($c['advertiser'])?> · <?= $c['active']?'Ativa':'Pausada' ?><?=ad_is_demo_campaign($c)?' · Exemplo':''?></small></span><span class="ad-campaign-expander" aria-hidden="true">⌄</span></summary>
      <div class="ad-campaign-detail">
        <p class="muted tiny">Período: <?=h($c['start_date'])?> até <?=h($c['end_date']??'sem prazo')?> · Cliques aproximados: <?= (int)$c['clicks'] ?></p>
        <div class="ad-center-toolbar">
          <button type="button" class="btn outline small" data-ad-edit="<?= (int)$c['id'] ?>">Editar</button>
          <form method="post" action="/admin.php?action=ads_toggle_campaign"><?=csrf_field()?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn outline small" type="submit"><?= $c['active']?'Pausar':'Ativar' ?></button></form>
          <form method="post" action="/admin.php?action=ads_delete_campaign"><?=csrf_field()?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn outline small" type="submit" data-confirm="Excluir esta campanha? A imagem original será preservada.">Excluir</button></form>
        </div>
      </div>
    </details>
  <?php endforeach;?>
  </div>
  <?php if($total>count($campaigns)):?><p class="muted tiny">Mostrando as 150 campanhas mais recentes de <?= $total ?> cadastradas.</p><?php endif; ?>
  <details class="ad-quick-help"><summary aria-label="Ajuda sobre métricas de campanhas" title="Ajuda">?</summary><p>Cliques locais são estimativas agregadas, não faturamento. Receitas do AdSense devem ser consultadas no Google.</p></details>
</section>

<dialog class="ad-dialog" id="ad-type" aria-labelledby="ad-type-title"><div class="ad-dialog-inner">
  <div class="ad-dialog-top"><h2 id="ad-type-title">Nova publicidade</h2><button type="button" class="ad-dialog-close" data-ad-close aria-label="Fechar">✕</button></div>
  <button type="button" class="ad-type-choice" data-ad-new="local"><strong>🏪 Anúncio local</strong><small>Empresa, banner, link, período e posição.</small></button>
  <button type="button" class="ad-type-choice" data-ad-new="adsense"><strong>◈ Bloco Google AdSense</strong><small>Associe uma unidade de anúncio do Google a uma das posições.</small></button>
  <details class="ad-quick-help"><summary aria-label="Ajuda sobre formatos de publicidade" title="Ajuda">?</summary><p>O Google seleciona os anúncios AdSense. Para empresas locais, use o cadastro de anúncio local.</p></details>
</div></dialog>

<dialog class="ad-dialog" id="ad-local" aria-labelledby="ad-local-title"><div class="ad-dialog-inner">
  <div class="ad-dialog-top"><h2 id="ad-local-title">Novo anúncio local</h2><button type="button" class="ad-dialog-close" data-ad-close aria-label="Fechar">✕</button></div>
  <form method="post" id="ad-local-form" action="/admin.php?action=ads_save_campaign" enctype="multipart/form-data" class="stacked-form">
    <?=csrf_field()?><input type="hidden" name="id" value="0">
    <div class="ad-modal-grid">
      <label>Empresa anunciante<input name="advertiser" required maxlength="130" placeholder="Nome do comércio"></label>
      <label>Título do anúncio<input name="title" required maxlength="130" placeholder="Ofertas da semana"></label>
      <label>Posição<select name="placement_slug" required><?php foreach(AD_SLOTS as $slug=>$info):?><option value="<?=h($slug)?>"><?=h(strtoupper($slug).' · '.$info[0])?></option><?php endforeach;?></select></label>
      <label>Destino HTTPS<input type="url" name="target_url" maxlength="700" required placeholder="https://empresa.example"></label>
      <label>Primeiro dia<input type="date" name="start_date" value="<?=h($today)?>" required></label>
      <label>Último dia (opcional)<input type="date" name="end_date"></label>
    </div>
    <div id="ad-overlap-warning" class="ad-context-alert" role="status" hidden></div>
    <label class="ad-check-line" id="ad-slot-prepare-line"><input type="checkbox" name="prepare_local_slot" value="1"><span>Preparar esta posição para anúncios locais (caso esteja desativada)</span></label>
    <label class="ad-check-line" id="ad-overlap-line" hidden><input type="checkbox" name="allow_overlap" value="1"><span>Confirmo o revezamento com outras campanhas no mesmo período. A maior prioridade prevalece.</span></label>
    <label class="ad-check-line"><input type="checkbox" name="active" value="1" checked><span>Campanha ativa (respeita datas e posição)</span></label>
    <details class="ad-advanced"><summary>Imagem, descrição e prioridade (opcional)</summary>
      <div class="ad-advanced-fields">
        <label>Prioridade (0 a 100)<input type="number" name="priority" min="0" max="100" value="0"></label>
        <label>Descrição curta<textarea name="description" maxlength="220" rows="2"></textarea></label>
        <label>Banner JPG/PNG/WebP<input type="file" name="campaign_image" accept="image/jpeg,image/png,image/webp"><input type="hidden" name="library_campaign" id="library-campaign"><button type="button" class="btn outline small" data-media-pick="campaign">🖼️ Biblioteca de mídias</button><small id="campaign-image-selected"></small><small id="ad-current-image"></small></label>
      </div>
    </details>
    <div class="ad-dialog-footer"><button class="btn primary" type="submit">Salvar campanha</button></div>
  </form>
</div></dialog>

<dialog class="ad-dialog" id="ad-new-adsense" aria-labelledby="ad-new-adsense-title"><div class="ad-dialog-inner">
  <div class="ad-dialog-top"><h2 id="ad-new-adsense-title">Novo bloco AdSense</h2><button type="button" class="ad-dialog-close" data-ad-close aria-label="Fechar">✕</button></div>
  <p class="muted tiny">Configure o ID de editor <code>ca-pub</code> na engrenagem antes de ativar um bloco.</p>
  <form method="post" action="/admin.php?action=ads_save_slot" class="stacked-form">
    <?=csrf_field()?><input type="hidden" name="provider" value="adsense"><input type="hidden" name="enabled" value="1">
    <label>Onde exibir<select name="slot" required><?php foreach(AD_SLOTS as $slug=>$info):?><option value="<?=h($slug)?>"><?=h(strtoupper($slug).' · '.$info[0])?></option><?php endforeach;?></select></label>
    <label>ID do bloco AdSense<input name="adsense_slot" inputmode="numeric" pattern="[0-9]{6,32}" maxlength="32" required placeholder="1234567890"></label>
    <div class="ad-context-alert" id="ad-adsense-warning" role="status" hidden></div>
    <div class="ad-dialog-footer"><button type="submit" class="btn primary">Ativar bloco</button></div>
  </form>
</div></dialog>

<?php foreach($slots as $slot):$slug=(string)$slot['slug'];if(!ad_valid_slot($slug))continue; ?>
<dialog class="ad-dialog" id="ad-slot-<?=h($slug)?>" aria-labelledby="ad-slot-title-<?=h($slug)?>"><div class="ad-dialog-inner">
  <div class="ad-dialog-top"><h2 id="ad-slot-title-<?=h($slug)?>"><?=h(strtoupper($slug).' · '.AD_SLOTS[$slug][0])?></h2><button type="button" class="ad-dialog-close" data-ad-close aria-label="Fechar">✕</button></div>
  <p class="muted tiny"><?=h(AD_SLOTS[$slug][1])?></p>
  <form method="post" action="/admin.php?action=ads_save_slot" class="stacked-form">
    <?=csrf_field()?><input type="hidden" name="slot" value="<?=h($slug)?>">
    <label class="ad-check-line"><input type="checkbox" name="enabled" value="1" <?= $slot['enabled']?'checked':'' ?>><span>Habilitar posição</span></label>
    <label>Exibição<select name="provider"><option value="off" <?= $slot['provider']==='off'?'selected':'' ?>>Desativado</option><option value="local" <?= $slot['provider']==='local'?'selected':'' ?>>Anúncios locais</option><option value="adsense" <?= $slot['provider']==='adsense'?'selected':'' ?>>Google AdSense</option><?php if($slot['provider']==='external'):?><option value="external" selected>Outro provedor (sem adaptador)</option><?php endif;?></select></label>
    <label>ID do bloco AdSense<input name="adsense_slot" inputmode="numeric" maxlength="32" value="<?=h($slot['adsense_slot'])?>" placeholder="1234567890"></label>
    <p class="muted tiny"><?=count($bySlot[$slug]??[])?> campanha(s) nesta posição. Não é possível ativar AdSense com campanhas locais ativas.</p>
    <div class="ad-dialog-footer"><button class="btn primary" type="submit">Salvar posição</button></div>
  </form>
  <div class="ad-dialog-secondary"><button class="btn outline small" type="button" data-ad-new="local" data-ad-position="<?=h($slug)?>">+ Anúncio local nesta posição</button></div>
</div></dialog>
<?php endforeach; ?>

<dialog class="ad-dialog" id="ad-settings" aria-labelledby="ad-settings-title"><div class="ad-dialog-inner">
  <div class="ad-dialog-top"><h2 id="ad-settings-title">⚙ Configurações de publicidade</h2><button type="button" class="ad-dialog-close" data-ad-close aria-label="Fechar">✕</button></div>
  <details class="ad-advanced" open><summary>Configuração geral</summary><div class="ad-advanced-fields">
    <form method="post" action="/admin.php?action=ads_save_settings" class="stacked-form">
      <?=csrf_field()?><label class="ad-check-line"><input type="checkbox" name="ads_enabled" <?=$adsOn?'checked':''?>><span>Ativar publicidade no portal</span></label>
      <div class="compact-heading"><span class="muted tiny">Conta AdSense: <?=h($publisher!==''?'configurada':'não configurada')?>.</span><details class="compact-help"><summary aria-label="Ajuda sobre a conta Google">?</summary><div class="compact-help-content">O ID de editor é gerenciado em Configurações → Integrações. Ativar publicidade aqui não equivale à aprovação de anúncios ou ao consentimento do visitante.</div></details></div>
      <a class="btn outline small" href="<?=h(admin_link('integrations'))?>#google-adsense">Gerenciar conta AdSense ↗</a>
      <button class="btn primary" type="submit">Salvar configurações</button>
    </form>
  </div></details>
  <details class="ad-advanced" id="adsense-review"><summary>Preparação para AdSense (<?=$passed?>/<?=count($review)?>)</summary><div class="ad-advanced-fields">
    <p class="muted tiny">Diagnóstico local, não é aprovação do Google. <?= $adsTxtReady?'ads.txt corresponde ao editor':'ads.txt ainda não corresponde ao editor' ?>.</p>
    <ul class="adsense-review-list"><?php foreach($review as $check):?><li><strong><?= $check['ok']?'✓':'!' ?> <?=h($check['title'])?></strong><p class="muted"><?=h($check['detail'])?></p></li><?php endforeach;?></ul>
    <div class="ad-center-toolbar"><a class="btn outline small" href="https://adsense.google.com/" target="_blank" rel="noopener noreferrer">Abrir AdSense ↗</a><a class="btn outline small" href="/ads.txt" target="_blank" rel="noopener">Conferir ads.txt ↗</a></div>
    <form method="post" action="/admin.php?action=ads_prepare_ads_txt"><?=csrf_field()?><button class="btn outline small" type="submit" <?=$publisher===''?'disabled':''?>>Publicar ads.txt</button></form>
    <p class="muted tiny">Confira fontes, autoria, direitos das imagens, qualidade do conteúdo e páginas institucionais antes da revisão do Google.</p>
  </div></details>
  <details class="ad-advanced"><summary>Testes e demonstrações</summary><div class="ad-advanced-fields">
    <p class="muted tiny">Existem <strong><?= $demoTotal ?></strong> campanhas de exemplo. Elas são independentes das notícias demonstrativas.</p>
    <form method="post" action="/admin.php?action=load_ad_examples" class="stacked-form">
      <?=csrf_field()?><label>Confirme sua senha para gerar exemplos<input type="password" name="confirm_password" autocomplete="current-password" required></label>
      <button class="btn outline" type="submit" data-confirm="Criar seis anúncios fictícios? Pode reconfigurar posições; verifique anúncios reais antes de continuar.">Gerar seis exemplos</button>
    </form>
    <form method="post" action="/admin.php?action=ads_delete_demo" class="stacked-form">
      <?=csrf_field()?><label>Para remover, digite REMOVER ANUNCIOS<input name="confirmation" required autocomplete="off" placeholder="REMOVER ANUNCIOS"></label>
      <button class="btn outline" type="submit" <?=$demoTotal<1?'disabled':''?> data-confirm="Criar backup e excluir APENAS os anúncios fictícios? Campanhas reais serão preservadas.">Remover <?= $demoTotal ?> anúncio(s) de demonstração</button>
    </form>
  </div></details>
</div></dialog>
<script type="application/json" id="ad-dashboard-data"><?=json_encode(['slots'=>array_column($slots,null,'slug'),'campaigns'=>$campaigns,'draft'=>$draft],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE)?></script>
