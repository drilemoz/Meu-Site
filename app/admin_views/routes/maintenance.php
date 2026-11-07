<?php
require_once __DIR__.'/../../content_maintenance.php';
if (($me['role']??'')!=='admin'): ?>
<div class="panel"><p>A manutenção de dados está disponível somente para administradores.</p></div>
<?php else: $inventory=igua_content_inventory($db); ?>
<section class="panel maintenance-compact">
  <div class="maintenance-heading"><h2>Manutenção do conteúdo <small class="muted">Alpha</small></h2>
    <details class="maintenance-help"><summary aria-label="Ajuda sobre a limpeza editorial" title="Ajuda">?</summary><p>Remova conteúdo de testes sem afetar categorias, fontes, contas, publicidade, rotas de ônibus ou configurações. O portal cria backup lógico antes de cada exclusão e não remove as fotos automaticamente.</p></details>
  </div>
  <div class="maintenance-metrics">
    <div><strong><?= (int)$inventory['demo'] ?></strong><br><small>Matérias de demonstração</small></div>
    <div><strong><?= (int)$inventory['real'] ?></strong><br><small>Matérias reais</small></div>
    <div><strong><?= (int)$inventory['drafts'] ?></strong><br><small>Rascunhos automáticos</small></div>
    <div><strong><?= (int)$inventory['categories'] ?> categorias · <?= (int)$inventory['sources'] ?> fontes</strong><br><small>Preservadas na limpeza editorial</small></div>
  </div>
  <details class="setup-route"><summary><strong>Remover somente demonstrações</strong></summary><div class="route-detail">
    <p>Apaga somente artigos <code>is_demo=1</code> e seus blocos, revisões, galerias e vínculos. Preserva notícias reais, categorias e fontes.</p>
    <form method="post" action="/admin.php?action=maintenance_clear_demo"><?= csrf_field() ?>
      <label>Digite REMOVER EXEMPLOS<input name="confirmation" required autocomplete="off" placeholder="REMOVER EXEMPLOS"></label>
      <button class="btn outline" type="submit" data-confirm="Remover as matérias de demonstração após gerar um backup?">Limpar demonstrações</button>
    </form></div>
  </details>
  <details class="setup-route"><summary><strong>Limpar todas as notícias e rascunhos</strong></summary><div class="route-detail">
    <p>Apaga conteúdo editorial de teste e real, incluindo rascunhos e relações. Mantém categorias, fontes, usuários, configurações e os arquivos de mídia.</p>
    <form method="post" action="/admin.php?action=maintenance_clear_all"><?= csrf_field() ?>
      <label>Digite LIMPAR NOTICIAS<input name="confirmation" required autocomplete="off" placeholder="LIMPAR NOTICIAS"></label>
      <button class="btn outline" type="submit" data-confirm="Apagar TODAS as notícias e rascunhos após gerar backup?">Limpar conteúdo editorial</button>
    </form></div>
  </details>
</section>
<section class="panel maintenance-compact maintenance-danger-zone">
  <div class="maintenance-heading"><h2>Reinstalar o portal</h2><details class="maintenance-help"><summary aria-label="Ajuda sobre reinstalação" title="Ajuda">?</summary><p>Somente para testes Alpha. Reutiliza o banco atual, não cria outro MySQL. Os arquivos de mídia não são reinseridos automaticamente. Requer backup lógico.</p></details></div>
  <p class="maintenance-warning"><strong>Atenção:</strong> esta operação remove as 29 tabelas do portal. A exclusão de tabelas não pode ser desfeita por transação MariaDB.</p>
  <details class="setup-route"><summary><strong>Opções avançadas de reinstalação</strong></summary><div class="route-detail">
    <form method="post" action="/admin.php?action=maintenance_reset"><?= csrf_field() ?>
      <label>Digite REINSTALAR<input name="confirmation" required autocomplete="off" placeholder="REINSTALAR"></label>
      <label>Confirme sua senha administrativa<input name="admin_password" type="password" required autocomplete="current-password"></label>
      <button class="btn danger" type="submit" data-confirm="ATENÇÃO: todas as tabelas deste portal serão removidas, após salvar o backup. Deseja realmente reinstalar?">Fazer backup e reiniciar instalação</button>
    </form></div>
  </details>
</section>
<?php endif; ?>

<?php if(($me['role']??'')==='admin'):$adsExamples=igua_ad_demo_count($db); ?>
<section class="panel maintenance-compact">
  <div class="maintenance-heading"><h2>Publicidade de teste</h2><details class="maintenance-help"><summary aria-label="Ajuda sobre anúncios fictícios" title="Ajuda">?</summary><p>A limpeza editorial não remove anúncios. Esta ação separada preserva campanhas reais e remove somente os exemplos identificados.</p></details></div>
  <p class="muted tiny"><strong><?= $adsExamples ?></strong> anúncio(s) fictício(s)</p>
  <details class="setup-route"><summary><strong>Remover anúncios de demonstração</strong></summary><div class="route-detail">
    <form method="post" action="/admin.php?action=ads_delete_demo" class="stacked-form">
      <?=csrf_field()?><input type="hidden" name="return_to" value="maintenance">
      <label>Digite REMOVER ANUNCIOS<input name="confirmation" required autocomplete="off" placeholder="REMOVER ANUNCIOS"></label>
      <button class="btn outline" type="submit" <?=$adsExamples<1?'disabled':''?> data-confirm="Criar backup e remover somente anúncios de demonstração, preservando campanhas reais?">Remover <?= $adsExamples ?> anúncio(s) fictício(s)</button>
    </form>
  </div></details>
</section>
<?php endif; ?>
