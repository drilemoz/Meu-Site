<?php
$newsActions=['articles','new','edit','seo','categories','authors','sources'];
$configActions=['settings','integrations','backups','updates','maintenance'];
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><script src="/assets/js/admin-dialogs.js?v=<?=h(igua_updater_version())?>" defer></script><title>Painel <?= h(igua_brand_name()) ?></title><link rel="stylesheet" href="/assets/css/style.css?v=<?=h(igua_updater_version())?>"></head><body class="admin-body <?= in_array($action,['new','edit'],true)?'news-wizard-page':'' ?>">
<div class="admin-layout">
  <header class="admin-shell-header">
    <a href="/admin.php" class="admin-shell-brand"><span class="brand-mark" aria-hidden="true">◒</span><span><?=h(igua_brand_name())?><small>PAINEL DA REDAÇÃO</small></span></a>
    <button id="admin-nav-toggle" class="admin-icon-btn admin-nav-toggle" type="button" aria-label="Abrir menu do painel" aria-haspopup="dialog" aria-controls="admin-nav-dialog" aria-expanded="false" title="Abrir menu">☰</button>
  </header>
  <dialog id="admin-nav-dialog" class="admin-nav-drawer" aria-labelledby="admin-nav-title">
    <div class="admin-nav-heading"><strong id="admin-nav-title">Menu do painel</strong><button type="button" class="admin-icon-btn" id="admin-nav-close" aria-label="Fechar menu">×</button></div>
    <nav class="admin-menu" aria-label="Navegação administrativa">
      <a class="<?= $action==='dashboard'?'current':'' ?>" href="/admin.php" <?= $action==='dashboard'?'aria-current="page"':'' ?>>◫ Visão geral</a>
      <details class="admin-nav-group" <?=in_array($action,$newsActions,true)?'open':''?>><summary>▤ Notícias</summary><div class="admin-nav-children">
        <a class="<?= $action==='articles'?'current':'' ?>" href="<?=h(admin_link('articles'))?>">Todas as notícias</a>
        <a class="<?= in_array($action,['new','edit'],true)?'current':'' ?>" href="<?=h(admin_link('new'))?>">+ Nova notícia</a>
        <a class="<?= $action==='seo'?'current':'' ?>" href="<?=h(admin_link('seo'))?>">SEO editorial</a>
        <?php if(role_allowed($me['role'],'manage_taxonomy')): ?>
          <a class="<?= $action==='categories'?'current':'' ?>" href="<?=h(admin_link('categories'))?>">Categorias</a>
          <a class="<?= $action==='authors'?'current':'' ?>" href="<?=h(admin_link('authors'))?>">Autores</a>
          <a class="<?= $action==='sources'?'current':'' ?>" href="<?=h(admin_link('sources'))?>">Fontes</a>
        <?php endif; ?>
      </div></details>
      <?php if(($me['role']??'')==='admin'): ?><a class="<?= in_array($action,['legal_apps','legal_app_edit','legal_page_edit'],true)?'current':'' ?>" href="<?=h(admin_link('legal_apps'))?>">▣ Aplicativos e Páginas Legais</a><?php endif; ?>
      <a class="<?= $action==='media'?'current':'' ?>" href="<?=h(admin_link('media'))?>">🖼️ Mídias</a>
      <?php if(role_allowed($me['role'],'manage_bus')): ?><a class="<?= in_array($action,['bus','bus_edit','bus_import'],true)?'current':'' ?>" href="<?=h(admin_link('bus'))?>">🚌 Ônibus</a><?php endif; ?>
      <?php if(role_allowed($me['role'],'manage_settings')): ?>
        <a class="<?= $action==='elections'?'current':'' ?>" href="<?=h(admin_link('elections'))?>">🗳️ Eleições</a>
        <a class="<?= $action==='ads'?'current':'' ?>" href="<?=h(admin_link('ads'))?>">◈ Publicidade</a>
        <a class="<?= $action==='analytics'?'current':'' ?>" href="<?=h(admin_link('analytics'))?>">◷ Métricas</a>
        <a class="<?= $action==='health'?'current':'' ?>" href="<?=h(admin_link('health'))?>">✚ Saúde do sistema</a>
        <a class="<?= $action==='security'?'current':'' ?>" href="<?=h(admin_link('security'))?>">🛡 Segurança</a>
        <details class="admin-nav-group" <?=in_array($action,$configActions,true)?'open':''?>><summary>⚙ Configurações</summary><div class="admin-nav-children">
          <a class="<?= $action==='settings'?'current':'' ?>" href="<?=h(admin_link('settings'))?>">Geral e SEO</a>
          <a class="<?= $action==='integrations'?'current':'' ?>" href="<?=h(admin_link('integrations'))?>">◈ Integrações e APIs</a>
          <a class="<?= $action==='backups'?'current':'' ?>" href="<?=h(admin_link('backups'))?>">Backups e restauração</a>
          <a class="<?= $action==='updates'?'current':'' ?>" href="<?=h(admin_link('updates'))?>">Atualizações</a>
          <?php if(($me['role']??'')==='admin'): ?><a class="<?= $action==='maintenance'?'current':'' ?>" href="<?=h(admin_link('maintenance'))?>">Manutenção</a><?php endif; ?>
        </div></details>
      <?php endif; ?>
      <?php if(role_allowed($me['role'],'manage_users')): ?><a class="<?= $action==='users'?'current':'' ?>" href="<?=h(admin_link('users'))?>">♙ Usuários</a><?php endif; ?>
    </nav>
    <div class="admin-nav-footer"><strong><?=h($me['name'])?></strong><span><?=h(role_label($me['role']))?></span><a href="/index.php" target="_blank" rel="noopener">↗ Ver portal</a><form method="post" action="<?=h(admin_link('logout'))?>"><?=csrf_field()?><button type="submit" class="btn outline">Sair da conta</button></form></div>
  </dialog>
  <main class="admin-main">
    <div class="admin-top"><div><small><?=h(igua_brand_name())?> / ADMINISTRAÇÃO</small><h1><?=h(['legal_apps'=>'Aplicativos e Páginas Legais','legal_app_edit'=>'Editar aplicativo','legal_page_edit'=>'Editar página legal','dashboard'=>'Visão geral','seo'=>'Central de SEO','articles'=>'Notícias','new'=>'Nova notícia','edit'=>'Editar notícia','categories'=>'Categorias','authors'=>'Autores','sources'=>'Fontes','bus'=>'Horários de ônibus','bus_edit'=>'Assistente de horários','bus_import'=>'Importar dados oficiais','elections'=>'Resultados Eleitorais','ads'=>'Publicidade','analytics'=>'Métricas e audiência','health'=>'Saúde do sistema','security'=>'Central de Segurança','updates'=>'Atualizações do sistema','maintenance'=>'Manutenção de conteúdo','media'=>'Biblioteca de mídias','users'=>'Usuários','backups'=>'Backups e restauração','settings'=>'Configurações e SEO','integrations'=>'Integrações e serviços'][$action]??'Painel')?></h1></div><div class="admin-top-actions"><a class="admin-icon-btn" href="/index.php" target="_blank" rel="noopener" aria-label="Ver portal" title="Ver portal">↗</a></div></div>
    <?php if($flash): ?><div class="alert <?=h($flash['type'])?>"><?=h($flash['message'])?></div><?php endif; ?>
    <?php foreach($errors as $err): ?><div class="alert error"><?=h($err)?></div><?php endforeach; ?>
    <?php require __DIR__.'/pages.php'; ?>
    <?php igua_developer_footer(); ?>
  </main>
</div>
<?php if($action!=='media')media_admin_modal(); ?>
<dialog id="admin-confirm-dialog" class="igua-modal igua-modal--narrow admin-ui-dialog admin-confirm-dialog" aria-labelledby="admin-confirm-title"><form method="dialog"><div class="admin-ui-dialog-head"><h2 id="admin-confirm-title">Confirmar ação</h2><button type="submit" value="cancel" aria-label="Fechar" class="admin-ui-close">×</button></div><p id="admin-confirm-message"></p><div class="admin-ui-dialog-actions"><button type="submit" value="cancel" class="btn outline">Cancelar</button><button type="submit" value="confirm" id="admin-confirm-accept" class="btn primary">Confirmar</button></div></form></dialog>
<dialog id="admin-message-dialog" class="igua-modal igua-modal--narrow admin-ui-dialog" aria-labelledby="admin-message-title"><div class="admin-ui-dialog-head"><h2 id="admin-message-title">Aviso</h2><button type="button" class="admin-ui-close" data-admin-message-close aria-label="Fechar">×</button></div><p id="admin-message-text"></p><div class="admin-ui-dialog-actions"><button type="button" class="btn primary" data-admin-message-close>Entendi</button></div></dialog>
<script src="/assets/js/main.js?v=<?=h(igua_updater_version())?>" defer></script><script src="/assets/js/admin-ui.js?v=<?=h(igua_updater_version())?>" defer></script><script src="/assets/js/media-state.js" defer></script><script src="/assets/js/media-library.js?v=<?=h(igua_updater_version())?>" defer></script><script src="/assets/js/file-inputs.js" defer></script><?php if($action==='integrations'): ?><script src="/assets/js/integrations-admin.js?v=<?=h(igua_updater_version())?>" defer></script><?php endif; ?><?php if($action==='ads'): ?><script src="/assets/js/ads-admin.js?v=<?=h(igua_updater_version())?>" defer></script><?php endif; ?></body></html>
