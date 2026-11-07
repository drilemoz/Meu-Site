<?php if(in_array($action,['bus','bus_edit','bus_import'],true)): bus_admin_view($db,$action); ?>
<?php elseif($action==='ads'): ad_admin_view($db); ?>
<?php elseif($action==='media'): ?><?php media_admin_modal(true); ?>
<?php elseif($action==='health'): igua_health_admin_view($db); ?>
<?php elseif($action==='security'): igua_security_admin_view(); ?>
<?php elseif($action==='analytics'): analytics_admin_view($db,analytics_period($_GET)); ?>
<?php elseif($action==='elections'): meu_elections_admin_view($db); ?>
<?php elseif($action==='seo'): igua_seo_admin_view($db,$me); ?>
<?php elseif ($action==='dashboard'): require __DIR__.'/routes/dashboard.php'; ?>
<?php elseif ($action==='articles'): require __DIR__.'/routes/articles.php'; ?>
<?php elseif ($action==='new'||$action==='edit'): require __DIR__.'/routes/editor.php'; ?>
<?php elseif ($action==='categories'): require __DIR__.'/routes/categories.php'; ?>
<?php elseif ($action==='authors'): require __DIR__.'/routes/authors.php'; ?>
<?php elseif ($action==='sources'): require __DIR__.'/routes/sources.php'; ?>
<?php elseif ($action==='users'): require __DIR__.'/routes/users.php'; ?>
<?php elseif ($action==='settings'): require __DIR__.'/routes/settings.php'; ?>
<?php elseif ($action==='integrations'): igua_integrations_admin_view($db); ?>
<?php elseif ($action==='maintenance'): require __DIR__.'/routes/maintenance.php'; ?>
<?php elseif ($action==='backups'): require __DIR__.'/routes/backups.php'; ?>
<?php elseif ($action==='updates'): require __DIR__.'/routes/updates.php'; ?>
<?php else: ?><div class="panel"><p>Seção não encontrada. <a href="/admin.php">Voltar ao painel.</a></p></div><?php endif; ?>
