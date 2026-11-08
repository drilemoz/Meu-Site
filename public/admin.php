<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/editorial.php';
require __DIR__.'/../app/news_admin_view.php';
require_once __DIR__.'/../app/media_admin.php';
require_once __DIR__.'/../app/article_workflow.php';
require_once __DIR__.'/../app/seo_admin.php';
require __DIR__.'/../app/editor_examples.php';
require __DIR__.'/../app/ads_admin.php';
require_once __DIR__.'/../app/ads_examples.php';
require_once __DIR__.'/../app/schema.php';
require_once __DIR__.'/../app/schema_migrations.php';
require_once __DIR__.'/../app/auth_remember.php';
require_once __DIR__.'/../app/auth_throttle.php';
require_once __DIR__.'/../app/settings_service.php';
require_once __DIR__.'/../app/apps_legal.php';
require_once __DIR__.'/../app/bus_admin.php';
require_once __DIR__.'/../app/elections/service.php';
require_once __DIR__.'/../app/elections/repository.php';
require_once __DIR__.'/../app/elections/admin_view.php';
require_once __DIR__.'/../app/analytics_admin.php';
require_once __DIR__.'/../app/integrations.php';
require_once __DIR__.'/../app/file_manager.php';
require_once __DIR__.'/../app/updater.php';
require_once __DIR__.'/../app/admin_controllers/post_routes.php';
if (!installed()) redirect('/setup.php');
// Bloqueios manuais valem somente para rotas administrativas; jamais para leitores.
if(igua_security_blocked()) {
    igua_security_event('blocked_request');
    http_response_code(429);header('Retry-After: 900');exit('Este acesso administrativo está temporariamente bloqueado.');
}
$action=(string)($_GET['action']??'dashboard');
if(in_array($action,['updates','files','files_download','server_info'],true)){header('Cache-Control: private, no-store, max-age=0');header('Pragma: no-cache');}
// Se post_max_size for ultrapassado, PHP esvazia $_POST e $_FILES antes do CSRF.
if(request_post()&&in_array($action,['save_article','ads_save_campaign','media_upload','save_legal_app','files_save'],true)){
    $maxPost=igua_ini_bytes((string)ini_get('post_max_size'));
    $length=(int)($_SERVER['CONTENT_LENGTH']??0);
    if($maxPost>0&&$length>$maxPost){
        http_response_code(413);header('Content-Type: text/plain; charset=UTF-8');
        exit('Envio maior que o limite total do PHP (post_max_size='.ini_get('post_max_size').'). Compacte as fotos ou ajuste upload_max_filesize/post_max_size no PHP em execução (no Termux, reinicie o php -S; na hospedagem, use o painel de PHP)');
    }
}
if ($action==='logout') {
    if (!request_post()) {http_response_code(405);exit;}
    check_csrf();
    try {igua_remember_logout(db());} catch(Throwable $e) {igua_health_log('REMEMBER_LOGOUT_FAILED',$e);igua_remember_clear_cookie();}
    igua_security_event('logout');
    $_SESSION=[];session_regenerate_id(true);redirect('/admin.php?action=login');
}
// Reautenticação persistente somente em GET, antes de exigir sessão normal.
if (!admin_user() && !request_post() && isset($_COOKIE[IGUA_REMEMBER_COOKIE])) {
    try {igua_migrate_schema(db());if(igua_remember_auto_login(db()))igua_security_event('remember_success');}
    catch(Throwable $e) {igua_health_log('REMEMBER_AUTH_FAILED',$e);igua_remember_clear_cookie();}
}

$errors=[];
if ($action==='login') {
    if (admin_user()) redirect('/admin.php');
    if (request_post()) {
        check_csrf();
        try {
            $result=igua_login_with_limit((string)($_SERVER['REMOTE_ADDR']??''),static function(): ?array {
                igua_migrate_schema(db());
                $stmt=db()->prepare('SELECT id,name,email,password_hash,role,active FROM users WHERE email=? LIMIT 1');
                $stmt->execute([trim((string)($_POST['email']??''))]);
                $u=$stmt->fetch();
                return $u && (int)$u['active'] && password_verify((string)($_POST['password']??''),$u['password_hash']) ? $u : null;
            });
            if($result['limited']){igua_security_event('login_limited');$errors[]='Muitas tentativas. Aguarde 15 minutos.';}
            elseif(!$result['user']){igua_security_event('login_failed');$errors[]='E-mail ou senha inválidos.';}
            else {
                $u=$result['user'];
                session_regenerate_id(true);
                if (!empty($_POST['remember_me'])) {
                    try {
                        if (!igua_remember_issue(db(),$u)) flash('Acesso realizado. A opção Manter conectado exige HTTPS ou acesso local.', 'warning');
                    } catch(Throwable $e) {
                        igua_health_log('REMEMBER_ISSUE_FAILED',$e);
                        flash('Acesso realizado, mas não foi possível ativar Manter conectado.', 'warning');
                    }
                }
                unset($u['password_hash']);$_SESSION['admin']=$u;
                igua_security_event('login_success');
                unset($_SESSION['login_tries'],$_SESSION['login_last']);redirect('/admin.php');
            }
        } catch(Throwable $e) {
            igua_health_log('ADMIN_LOGIN_FAILED_INTERNAL',$e);
            $errors[]='Não foi possível realizar o login. Verifique a Central de Saúde e o armazenamento privado.';
        }
    }
    require __DIR__.'/../app/admin_views/login.php';
}
require_admin();
try {igua_migrate_schema(db());} catch(Throwable $e){igua_health_log('SCHEMA_MIGRATION_FAILED',$e);if($action!=='health'){http_response_code(503);exit('Atualização do esquema pendente. Acesse /admin.php?action=health para conferir o ambiente, garanta CREATE/ALTER TABLE e faça backup antes de tentar novamente.');}}
$db=db();
$me=admin_user();
$active=$db->prepare('SELECT id,name,email,role,active FROM users WHERE id=? LIMIT 1');$active->execute([(int)$me['id']]);$fresh=$active->fetch();
if (!$fresh || !(int)$fresh['active']) {try{igua_remember_logout($db);}catch(Throwable $e){igua_health_log('REMEMBER_LOGOUT_FAILED',$e);igua_remember_clear_cookie();}$_SESSION=[];session_regenerate_id(true);redirect('/admin.php?action=login');}
$me=$fresh;$_SESSION['admin']=$fresh;
$abilityForPage=['legal_apps'=>'manage_settings','legal_app_edit'=>'manage_settings','legal_app_info'=>'manage_settings','legal_page_edit'=>'manage_settings','sitemap'=>'manage_settings','download_bundle'=>'backup','users'=>'manage_users','ads'=>'manage_settings','settings'=>'manage_settings','integrations'=>'manage_settings','integrations_save_ga4'=>'manage_settings','integrations_save_adsense'=>'manage_settings','integrations_save_verification'=>'manage_settings','integrations_save_dns'=>'manage_settings','integrations_check_dns'=>'manage_settings','integrations_save_search_console_mode'=>'manage_settings','integrations_search_console_test'=>'manage_settings','integrations_search_console_inspect'=>'manage_settings','integrations_pagespeed_test'=>'manage_settings','indexnow_setup'=>'manage_settings','indexnow_submit'=>'manage_settings','bing_save'=>'manage_settings','bing_test'=>'manage_settings','bing_remove'=>'manage_settings','integrations_ga4_credentials'=>'manage_settings','integrations_ga4_remove'=>'manage_settings','integrations_ga4_test'=>'manage_settings','integrations_ga4_report'=>'manage_settings','integrations_gemini_save'=>'manage_settings','integrations_gemini_test'=>'manage_settings','integrations_gemini_remove'=>'manage_settings','news_gemini_suggest'=>'write','integrations_adsense_credentials'=>'manage_settings','integrations_adsense_disconnect'=>'manage_settings','integrations_adsense_connect'=>'manage_settings','integrations_adsense_report'=>'manage_settings','integrations_adsense_callback'=>'manage_settings','backups'=>'backup','download'=>'backup','categories'=>'manage_taxonomy','authors'=>'manage_taxonomy','sources'=>'manage_taxonomy','bus'=>'manage_bus','bus_edit'=>'manage_bus','bus_import'=>'manage_bus','elections'=>'manage_settings','analytics'=>'manage_settings','health'=>'manage_settings','security'=>'manage_settings','security_block'=>'manage_settings','security_unblock'=>'manage_settings','health_verify'=>'backup','updates'=>'manage_settings','maintenance'=>'manage_settings','maintenance_clear_demo'=>'manage_settings','maintenance_clear_all'=>'manage_settings','maintenance_reset'=>'manage_settings','server_info'=>'manage_settings','files'=>'manage_settings','files_save'=>'manage_settings','files_download'=>'manage_settings','media'=>'write','media_list'=>'write','media_upload'=>'write','media_update'=>'write','media_download'=>'write','media_trash'=>'manage_settings','media_restore'=>'manage_settings'];
if(isset($abilityForPage[$action]))require_ability($abilityForPage[$action],$me);
if(in_array($action,['legal_apps','legal_app_edit','legal_app_info','legal_page_edit'],true) && ($me['role']??'')!=='admin'){http_response_code(403);exit('Acesso restrito a administradores.');}
if(!in_array($action,['legal_apps','legal_app_edit','legal_app_info','legal_page_edit','save_legal_app','publish_legal_app','publish_legal_all','delete_legal_app','create_legal_page','save_legal_page','publish_legal_page','unpublish_legal_page','restore_legal_revision','delete_legal_page','load_uai_legal_examples','dashboard','ads','ads_save_settings','ads_save_slot','ads_save_campaign','ads_toggle_campaign','ads_delete_campaign','ads_delete_demo','load_ad_examples','articles','toggle_article_status','new','edit','seo','sitemap','categories','authors','sources','users','settings','integrations','integrations_save_ga4','integrations_save_adsense','integrations_save_verification','integrations_save_dns','integrations_check_dns','integrations_save_search_console_mode','integrations_search_console_test','integrations_search_console_inspect','integrations_pagespeed_test','indexnow_setup','indexnow_submit','bing_save','bing_test','bing_remove','integrations_ga4_credentials','integrations_ga4_remove','integrations_ga4_test','integrations_ga4_report','integrations_gemini_save','integrations_gemini_test','integrations_gemini_remove','news_gemini_suggest','integrations_adsense_credentials','integrations_adsense_disconnect','integrations_adsense_connect','integrations_adsense_report','integrations_adsense_callback','backups','download','download_bundle','save_article','duplicate_article','restore_article_revision','delete_article','save_category','delete_category','save_settings','load_samples','load_editor_examples','create_backup','restore_backup','restore_bundle','save_user','toggle_user','save_author','delete_author','save_source','delete_source','elections','save_election','save_election_candidate','archive_election','toggle_election_candidate','toggle_elections_module','bus','bus_edit','bus_import','bus_import_teixeira','bus_stage_import','bus_approve_import','bus_ignore_import','bus_toggle_fixed','save_bus_route','save_bus_wizard','save_bus_departure','toggle_bus_departure','delete_bus_departure','delete_bus_route','import_url','autosave_news','news_create_category','news_create_source','analytics','analytics_settings','health','health_verify','security','security_block','security_unblock','updates','maintenance','maintenance_clear_demo','maintenance_clear_all','maintenance_reset','server_info','files','files_save','files_download','media','media_list','media_upload','media_update','media_download','media_trash','media_restore'],true)) {http_response_code(404);exit('Página não encontrada.');}
// API de leitura para o modal de atualizações. Sem ação de instalação por GET.
if($action==='updates' && !request_post() && (isset($_GET['updater_info'])||isset($_GET['updater_status']))){
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    if(isset($_GET['updater_status'])){
        $token=(string)($_GET['updater_status']??'');
        $state=igua_updater_progress_read((int)$me['id'],$token);
        if($state===null){http_response_code(404);echo json_encode(['ok'=>false,'message'=>'Estado ainda não disponível.']);}
        else echo json_encode(['ok'=>true,'progress'=>$state],JSON_UNESCAPED_UNICODE);
        exit;
    }
    try{
        $release=igua_updater_remote_release(true);
        $source=igua_updater_source();
        echo json_encode(['ok'=>true,'installed'=>igua_updater_version(),
            'configured'=>!empty($source['owner'])&&!empty($source['repo']),
            'release'=>$release===null?null:[
                'version'=>$release['version'],'newer'=>(bool)$release['newer'],
                'downloadable'=>!empty($release['url']),
                'notes'=>(string)($release['notes']??'')],
            'origin'=>$source['owner'].'/'.$source['repo'],
            'schema'=>IGUA_SCHEMA_REVISION],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    }catch(Throwable $e){
        igua_health_log('UPDATER_LOOKUP_FAILED',$e);
        http_response_code(502);
        echo json_encode(['ok'=>false,'message'=>'Não foi possível consultar a versão no GitHub.',
            'diagnostic'=>igua_updater_diagnostic($e)],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    }
    exit;
}
// Retorno OAuth via GET: protegido por sessão admin, ACL, state aleatório + PKCE.
if($action==='integrations_adsense_callback'){
    header('Cache-Control: private, no-store');header('Referrer-Policy: no-referrer');
    try {
        $site=setting('seo_site_url','');
        $callback=igua_adsense_callback_uri($site);
        if(PHP_SAPI!=='cli' && (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off'
            || strcasecmp((string)($_SERVER['HTTP_HOST']??''),(string)parse_url($site,PHP_URL_HOST))!==0))
            throw new RuntimeException('O OAuth AdSense exige HTTPS no domínio oficial.');
        // Mesmo quando o usuário cancela, consumir o state pendente.
        $verifier=igua_adsense_api_oauth_pending($_SESSION,$_GET['state']??null,(int)$me['id'],$callback);
        if(isset($_GET['error']))throw new RuntimeException('Autorização Google cancelada ou recusada.');
        $code=$_GET['code']??null;
        if(!is_string($code))throw new RuntimeException('Google não enviou o código de autorização.');
        $credentials=igua_adsense_api_read();
        if($credentials===null)throw new RuntimeException('Cliente OAuth não configurado.');
        $tokens=igua_adsense_api_exchange($credentials,$code,$callback,$verifier);
        $publisher=setting('adsense_publisher','');
        $account=igua_adsense_api_account($tokens['access_token'],$publisher);
        igua_adsense_api_write(['client_id'=>$credentials['client_id'],'client_secret'=>$credentials['client_secret'],
            'refresh_token'=>$tokens['refresh_token'],'account'=>$account]);
        unset($_SESSION['igua_adsense_api_last'],$_SESSION['igua_adsense_api_attempt']);
        unset($_SESSION['igua_int_diag_latest']['adsense']); // OAuth concluído não equivale a relatório testado.
        igua_security_event('admin_action','adsense_oauth_connected');
        flash('Conta Google autorizada para leitura dos relatórios AdSense.');
    }catch(Throwable $e){
        igua_int_diag_failure($_SESSION,'integrations_adsense_callback',$e);
        igua_security_event('admin_action','adsense_oauth_rejected');
        // Não gravar código OAuth, access_token ou refresh_token em logs.
        flash($e instanceof Error?'Falha interna no retorno OAuth; confira Central de Saúde.':$e->getMessage(),'warning');
    }
    redirect('/admin.php?action=integrations#google-adsense');
}
// Downloads somente após autenticação e ACL. Nenhum caminho do servidor vem da URL.
if($action==='files_save' && !request_post()){http_response_code(405);exit('Método não permitido.');}
if($action==='files_download'){
    if(request_post()){http_response_code(405);exit('Método não permitido.');}
    igua_files_project_download(is_string($_GET['file']??null)?$_GET['file']:'');
}
// Operações privadas de mídias e downloads permanecem antes do POST geral.
require __DIR__.'/../app/admin_controllers/media.php';
require __DIR__.'/../app/admin_controllers/downloads.php';
if (request_post()) {
    check_csrf();
    if(in_array($action,['save_user','toggle_user','save_settings','integrations_save_ga4','integrations_save_adsense','integrations_save_verification','integrations_save_dns','integrations_check_dns','integrations_save_search_console_mode','integrations_search_console_test','integrations_search_console_inspect','integrations_pagespeed_test','indexnow_setup','indexnow_submit','bing_save','bing_test','bing_remove','integrations_ga4_credentials','integrations_ga4_remove','integrations_ga4_test','integrations_ga4_report','integrations_gemini_save','integrations_gemini_test','integrations_gemini_remove','integrations_adsense_credentials','integrations_adsense_disconnect','integrations_adsense_connect','integrations_adsense_report','restore_bundle','restore_backup','maintenance_reset','maintenance_clear_all','updates','security_block','security_unblock'],true))
        igua_security_event('admin_action',$action);
    try {
        // Controladores por domínio. A autenticação, CSRF e tratamento de erros continuam centralizados.
        if ($action==='updates') require __DIR__.'/../app/admin_controllers/updater.php';
        elseif(in_array($action,['security_block','security_unblock'],true))require __DIR__.'/../app/admin_controllers/security.php';
        else {
            $postModule=igua_admin_post_module($action);
            if($postModule!==null)require __DIR__.'/../app/admin_controllers/'.$postModule.'.php';
        }
    } catch (Throwable $e) {
        if(in_array($action,['news_create_category','news_create_source'],true)){
            $internal=$e instanceof PDOException || $e instanceof Error;
            if($internal)igua_health_log('ADMIN_TAXONOMY_FAILED',$e);
            http_response_code($internal?500:422);
            header('Content-Type: application/json; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: no-store');
            echo json_encode(['saved'=>false,'message'=>$internal
                ? 'Erro interno ao cadastrar. Consulte a Central de Saúde.'
                : $e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        if($action==='integrations_gemini_test' && ($_POST['gemini_ajax']??'')==='1'){
            $diagnostic=igua_gemini_diagnostic($e);
            if($e instanceof Error || $e instanceof PDOException){
                igua_health_log('GEMINI_TEST_FAILED',$e);
                $diagnostic=['http_status'=>null,'message'=>'Falha interna durante o teste. Consulte a Central de Saúde.','advice'=>'Verifique o servidor.'];
            }
            $http=(int)($diagnostic['http_status']??0);
            http_response_code(in_array($http,[400,401,403,404,408,429,500,502,503,504],true)?$http:422);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: private, no-store');
            header('X-Content-Type-Options: nosniff');
            echo json_encode(['ok'=>false]+$diagnostic,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        if($action==='news_gemini_suggest'){
            $internal=$e instanceof PDOException || $e instanceof Error;
            if($internal)igua_health_log('GEMINI_EDITOR_FAILED',$e);
            http_response_code($internal?500:422);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: private, no-store');
            header('X-Content-Type-Options: nosniff');
            echo json_encode(['ok'=>false,'message'=>$internal?'Não foi possível completar a sugestão. Consulte a Central de Saúde.':$e->getMessage()],JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        if(str_starts_with($action,'integrations_')){
            $strategy=$_POST['pagespeed_strategy']??'mobile';
            igua_int_diag_failure($_SESSION,$action,$e,is_string($strategy)?$strategy:'mobile');
        }
        if($action==='updates')igua_security_event('update_failed');
        if(in_array($action,['media_upload','save_article','save_legal_app'],true))igua_security_event('upload_rejected');
        if($action==='save_bus_wizard')$_SESSION['bus_wizard_unsaved']=$_POST;
        // Erros de banco ou internos não devem vazar detalhes do servidor ao navegador.
        if($e instanceof PDOException || $e instanceof Error){
            igua_health_log('ADMIN_OPERATION_FAILED',$e);
            $errors[]='Não foi possível concluir a operação. Verifique a Central de Saúde e os registros locais.';
        } else { $errors[]=$e->getMessage(); }
    }
}
if($errors){
    if($action==='files_save' && is_string($_POST['folder']??null))$_GET['folder']=$_POST['folder'];
    $redirectOnError=['save_legal_app'=>'legal_app_edit','publish_legal_app'=>'legal_app_edit','publish_legal_all'=>'legal_app_edit','delete_legal_app'=>'legal_apps','create_legal_page'=>'legal_app_edit','save_legal_page'=>'legal_page_edit','publish_legal_page'=>'legal_page_edit','unpublish_legal_page'=>'legal_page_edit','restore_legal_revision'=>'legal_page_edit','delete_legal_page'=>'legal_page_edit','load_uai_legal_examples'=>'legal_app_edit','import_url'=>'new','autosave_news'=>'new','restore_article_revision'=>'articles','duplicate_article'=>'articles','save_article'=>'new','delete_article'=>'articles','save_category'=>'categories',
      'delete_category'=>'categories','save_author'=>'authors','delete_author'=>'authors',
      'save_source'=>'sources','delete_source'=>'sources','save_user'=>'users','toggle_user'=>'users',
      'indexnow_setup'=>'sitemap','indexnow_submit'=>'sitemap','bing_save'=>'sitemap','bing_test'=>'sitemap','bing_remove'=>'sitemap','save_bus_route'=>'bus_edit','save_bus_wizard'=>'bus_edit','save_bus_departure'=>'bus_edit','toggle_bus_departure'=>'bus','delete_bus_departure'=>'bus','delete_bus_route'=>'bus','bus_import_teixeira'=>'bus','bus_stage_import'=>'bus_import','bus_approve_import'=>'bus_import','bus_ignore_import'=>'bus_import','bus_toggle_fixed'=>'bus','analytics_settings'=>'analytics','integrations_save_ga4'=>'integrations','integrations_save_adsense'=>'integrations','integrations_save_verification'=>'integrations','integrations_save_dns'=>'integrations','integrations_check_dns'=>'integrations','integrations_save_search_console_mode'=>'integrations','integrations_search_console_test'=>'integrations','integrations_search_console_inspect'=>'integrations','integrations_pagespeed_test'=>'integrations','integrations_ga4_credentials'=>'integrations','integrations_ga4_remove'=>'integrations','integrations_ga4_test'=>'integrations','integrations_ga4_report'=>'integrations','integrations_gemini_save'=>'integrations','integrations_gemini_test'=>'integrations','integrations_gemini_remove'=>'integrations','integrations_adsense_credentials'=>'integrations','integrations_adsense_disconnect'=>'integrations','integrations_adsense_connect'=>'integrations','integrations_adsense_report'=>'integrations','ads_save_settings'=>'ads','ads_save_slot'=>'ads','ads_save_campaign'=>'ads','ads_toggle_campaign'=>'ads','ads_delete_campaign'=>'ads','ads_delete_demo'=>'ads','load_ad_examples'=>'ads','toggle_article_status'=>'articles','save_election'=>'elections','save_election_candidate'=>'elections','archive_election'=>'elections','toggle_election_candidate'=>'elections','toggle_elections_module'=>'elections','save_settings'=>'settings','create_backup'=>'backups','restore_backup'=>'backups','restore_bundle'=>'backups','load_samples'=>'articles','health_verify'=>'health','security_block'=>'security','security_unblock'=>'security','updates'=>'updates','files_save'=>'files','maintenance_clear_demo'=>'maintenance','maintenance_clear_all'=>'maintenance','maintenance_reset'=>'maintenance'];
    if(in_array($action,['save_bus_route','save_bus_wizard'],true) && (int)($_POST['id']??0)>0) $_GET['id']=(int)$_POST['id'];
    if($action==='save_bus_departure' && (int)($_POST['route_id']??0)>0) $_GET['id']=(int)$_POST['route_id'];
    if($action==='save_article' && (int)($_POST['id']??0)>0){$_GET['id']=(int)$_POST['id'];$redirectOnError['save_article']='edit';}
    if(in_array($action,['save_legal_app','publish_legal_app','publish_legal_all','delete_legal_app','create_legal_page','save_legal_page','publish_legal_page','unpublish_legal_page','restore_legal_revision','delete_legal_page','load_uai_legal_examples'],true)) {
        $_GET['id']=(int)($_POST['id']??($_POST['app_id']??0));
        if($action==='save_legal_app' && !$_GET['id'])$redirectOnError[$action]='legal_apps';
    }
    $action=$redirectOnError[$action]??$action;
}
require_once __DIR__.'/../app/admin_views/health.php';
require_once __DIR__.'/../app/admin_views/security.php';
function count_active_admins(PDO $db): int {return (int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1")->fetchColumn();}
function admin_link(string $action): string {return '/admin.php?action='.rawurlencode($action);}
function selected(string $a,string $b): string {return $a===$b?'selected':'';}
$flash=take_flash();
require __DIR__.'/../app/admin_views/layout.php';
