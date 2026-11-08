<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_controller_source.php';
require_once __DIR__.'/../app/admin_controllers/post_routes.php';
require_once __DIR__.'/../app/editorial.php';
function route130(bool $cond,string $message):void {if(!$cond)throw new RuntimeException('Falhou: '.$message);echo "OK: {$message}\n";}
$expect=[
 'news'=>['autosave_news','import_url','restore_article_revision','save_article','duplicate_article','delete_article','toggle_article_status'],
 'editorial'=>['save_category','delete_category','load_editor_examples','load_samples','save_author','delete_author','save_source','delete_source'],
 'system'=>['health_verify','analytics_settings','save_settings'],
 'bus'=>['bus_stage_import','bus_approve_import','bus_ignore_import','bus_toggle_fixed','save_bus_route','save_bus_wizard','save_bus_departure','toggle_bus_departure','delete_bus_departure','delete_bus_route'],
 'ads'=>['ads_save_settings','ads_save_slot','ads_save_campaign','ads_toggle_campaign','ads_delete_campaign','ads_delete_demo','load_ad_examples'],
 'users'=>['save_user','toggle_user'],
 'backups'=>['restore_bundle','create_backup','restore_backup']
];
$seen=[];
foreach($expect as $module=>$actions){
    route130(is_file(__DIR__.'/../app/admin_controllers/'.$module.'.php'),'controlador privado existe: '.$module);
    foreach($actions as $action){
        route130(igua_admin_post_module($action)===$module,'rota preservada: '.$action);
        route130(!isset($seen[$action]),'ação registrada uma única vez: '.$action);
        $seen[$action]=true;
    }
}
foreach(['../storage/config.php','/etc/passwd','../../x','unknown','logout','login','media_upload'] as $unsafe){
    route130(igua_admin_post_module($unsafe)===null,'ação externa não escolhe arquivo privado: '.$unsafe);
}
$admin=file_get_contents(__DIR__.'/../public/admin.php');
route130(str_contains($admin,'if (request_post())') && str_contains($admin,'check_csrf();') && str_contains($admin,'igua_admin_post_module($action)'), 'dispatcher exige POST e CSRF antes do controlador');
route130(str_contains($admin,'require_admin();')&&str_contains($admin,"$".'me=admin_user()'), 'roteamento executado depois de autenticação');
route130(str_contains($admin,'catch (Throwable $e)'), 'erros continuam tratados pela entrada administrativa');
route130(str_contains($admin,"'health'=>'manage_settings'")&&str_contains($admin,"'media_trash'=>'manage_settings'"), 'proteção de páginas e mídias preservada');
route130(!role_allowed('author','manage_users')&&!role_allowed('author','publish')&&role_allowed('editor','publish'),'permissões por papel mantidas');
route130(str_contains(file_get_contents(__DIR__.'/../app/admin_controllers/media.php'),'media_link_count'), 'proteção de vínculo antes da lixeira preservada');
route130(str_contains(igua_test_news_controller_source(),'article_record_slug_change')&&str_contains(igua_test_news_controller_source(),'$db->beginTransaction()'), 'mudança de slug continua dentro da transação');
echo "Rotas privadas 1.0.30 verificadas sem banco; integração MySQL e sessão real pendentes.\n";
