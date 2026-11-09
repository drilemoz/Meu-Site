<?php if(in_array($action,['legal_apps','legal_app_edit','legal_app_info','legal_page_edit'],true)): require __DIR__.'/routes/legal_apps.php'; ?>
<?php elseif(in_array($action,['bus','bus_edit','bus_import'],true)): bus_admin_view($db,$action); ?>
<?php elseif($action==='ads'): ad_admin_view($db); ?>
<?php elseif($action==='files'): require __DIR__.'/routes/files.php'; ?>
<?php elseif(in_array($action,['server_info','dns_diagnostic'],true)):
    // Diagnósticos opcionais nunca devem interromper o menu e o restante do painel.
    // Descartar qualquer HTML parcial quando uma função/extensão estiver indisponível.
    $diagBufferLevel=ob_get_level();ob_start();
    try {
        require __DIR__.'/routes/'.($action==='server_info'?'server_info.php':'dns_diagnostic.php');
        $diagHtml=ob_get_clean();
        echo $diagHtml;
    } catch (Throwable $e) {
        while(ob_get_level()>$diagBufferLevel)ob_end_clean();
        try { igua_health_log('ADMIN_DIAGNOSTIC_VIEW_FAILED',$e); } catch(Throwable $logError) { /* não mostrar detalhes privados */ }
        http_response_code(500);
        echo '<section class="panel" role="alert"><h2>Diagnóstico indisponível</h2>';
        echo '<p>Não foi possível carregar esta ferramenta. Verifique a Central de Saúde e os registros privados do servidor.</p>';
        echo '<p><a class="btn outline" href="/admin.php?action=health">Abrir Central de Saúde</a></p></section>';
    }
?>
<?php elseif($action==='media'): ?><?php media_admin_modal(true); ?>
<?php elseif($action==='health'): igua_health_admin_view($db); ?>
<?php elseif($action==='security'): igua_security_admin_view(); ?>
<?php elseif($action==='analytics'): analytics_admin_view($db,analytics_period($_GET),analytics_audience($_GET)); ?>
<?php elseif($action==='elections'): meu_elections_admin_view($db); ?>
<?php elseif($action==='seo'): igua_seo_admin_view($db,$me); ?>
<?php elseif($action==='sitemap'): require __DIR__.'/routes/sitemap.php'; ?>
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
<?php elseif ($action==='backup_restore'): require __DIR__.'/routes/backup_restore.php'; ?>
<?php elseif ($action==='updates'): require __DIR__.'/routes/updates.php'; ?>
<?php else: ?><div class="panel"><p>Seção não encontrada. <a href="/admin.php">Voltar ao painel.</a></p></div><?php endif; ?>
