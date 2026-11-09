<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/error_diagnostics.php';
$count=0;
function ck241(bool $ok,string $label):void {global $count;if(!$ok)throw new RuntimeException('141: '.$label);$count++;}
$root=dirname(__DIR__);
$view=file_get_contents($root.'/app/news_admin_view/media.php');
ck241(substr_count($view,'data-media-pick="choose"')===1,'biblioteca única');
ck241(str_contains($view,'name="gallery_files[]"')&&str_contains($view,'name="image"')&&str_contains($view,'name="photo_zip"'),'upload direto preservado');
$seo=file_get_contents($root.'/app/news_admin_view/details.php');
ck241(substr_count($seo,'id="news-seo-dialog-open"')===1,'IDs de diagnóstico únicos');
ck241(str_contains($seo,'Gemini IA')&&str_contains($seo,'Automático'),'ações de SEO explícitas');
$footer=file_get_contents($root.'/app/news_admin_view/footer.php');
ck241(strpos($footer,'news-publish-choice')<strpos($footer,'news-review-card'),'publicação antes da revisão');
foreach(['draft','now','scheduled'] as $mode)ck241(str_contains($footer,'value="'.$mode.'"'),'sem alterar modo de publicação '.$mode);
$admin=file_get_contents($root.'/public/admin.php');
ck241(str_contains($admin,"'error_diagnostics'=>'manage_settings'"),'diagnóstico restrito');
ck241(str_contains($admin,"'admin_error_event'=>'write'"),'endpoint restrito');
ck241(str_contains($admin,'check_csrf();')&&str_contains($admin,"igua_error_diagnostics_client_code"),'POST validado');
ck241(str_contains($admin,'igua_health_log($code)'),'logger privado');
ck241(is_file($root.'/app/admin_views/routes/error_diagnostics.php'),'tela privada presente');
ck241(str_contains(file_get_contents($root.'/app/admin_views/layout.php'),'Diagnóstico de erros'),'item no menu Ferramentas');
foreach(['MEDIA_TIMEOUT','MEDIA_HTTP','GEMINI_TIMEOUT','GEMINI_HTTP','JS_RUNTIME','JS_PROMISE'] as $valid)
    ck241(igua_error_diagnostics_client_code($valid)===$valid,'código permitido '.$valid);
foreach(['','foo','PHP_FATAL','../config.php',"MEDIA_HTTP\nTOKEN=123",str_repeat('A',500)] as $invalid)
    ck241(igua_error_diagnostics_client_code($invalid)===null,'código suspeito bloqueado');
ck241(!str_contains(file_get_contents($root.'/public/assets/js/admin-error-monitor.js'),'location.href'),'nunca enviar URL no monitor');
ck241(!str_contains(file_get_contents($root.'/public/assets/js/admin-error-monitor.js'),'error.message'),'não registrar mensagens sensíveis');
ck241(str_contains(file_get_contents($root.'/public/assets/js/news-gemini.js'),'38000'),'Gemini tem prazo máximo');
ck241(str_contains(file_get_contents($root.'/public/assets/js/media-library.js'),'18000'),'mídia tem prazo máximo');
ck241(str_contains(file_get_contents($root.'/public/assets/js/media-library.js'),'data-media-retry'),'biblioteca oferece retry');
echo "OK editor_diagnostics_241: {$count} verificações.\n";
