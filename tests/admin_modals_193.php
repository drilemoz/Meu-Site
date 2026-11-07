<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function assert193(bool $ok,string $label):void {if(!$ok)throw new RuntimeException('193: '.$label);}
$css=file_get_contents($root.'/public/assets/css/modules/16-security.css');
$global=file_get_contents($root.'/public/assets/js/admin-dialogs.js');
$layout=file_get_contents($root.'/app/admin_views/layout.php');
$integrations=file_get_contents($root.'/app/admin_views/routes/integrations.php');
$updates=file_get_contents($root.'/app/admin_views/routes/updates.php');
$ads=file_get_contents($root.'/app/admin_views/routes/ads_dashboard.php');
$editor=file_get_contents($root.'/app/news_admin_view/details.php');
$integrationsJs=file_get_contents($root.'/public/assets/js/integrations-admin.js');
assert193(str_contains($layout,'/assets/js/admin-dialogs.js')&&strpos($layout,'/assets/js/admin-dialogs.js')<strpos($layout,'/assets/js/admin-ui.js'),'controlador comum carregado antes dos especializados');
assert193(str_contains($css,'dialog.igua-modal')&&str_contains($css,'max-height:calc(100dvh - 28px)'),'margem e altura máxima móvel');
assert193(str_contains($css,'.int-dialog-scroll')&&str_contains($css,'overflow-y:auto'),'rolagem interna');
assert193(str_contains($css,'box-sizing:border-box')&&str_contains($css,'overflow-wrap:anywhere'),'controle de overflow');
assert193(str_contains($global,'focusOrigin')&&str_contains($global,'igua-modal-open')&&str_contains($global,'updaterBusy'),'foco, rolagem e bloqueio de atualização');
assert193(str_contains($integrations,'igua-modal--medium int-dialog'),'serviços Google no padrão médio');
assert193(substr_count($updates,'<dialog id="updater-')===4&&substr_count($updates,'igua-modal--narrow updater-dialog')===4,'quatro janelas do atualizador no padrão compacto');
assert193(substr_count($ads,'igua-modal--medium ad-dialog')===5,'cinco janelas de publicidade migradas');
assert193(substr_count($editor,'igua-modal--narrow news-seo-dialog')===2,'diálogos do editor migrados');
assert193(str_contains($layout,'igua-modal--narrow admin-ui-dialog'),'confirmar/avisar migrados');
assert193(!str_contains($layout,'class="igua-modal admin-nav-drawer"'),'drawer fora do modal de conteúdo');
assert193(substr_count($integrations,'data-int-speed-tab=')===2&&substr_count($integrations,'data-int-speed-panel=')===1,'duas abas e painéis de PageSpeed');
assert193(str_contains($integrationsJs,'showSpeed(')&&str_contains($integrationsJs,"'ArrowRight'")&&str_contains($integrationsJs,'tabIndex=active?0:-1'),'troca de painel por toque e teclado');
assert193(substr_count($integrations,'name="pagespeed_strategy"')===1&&str_contains($integrations,'<?=h($strategy)?>'),'mesmo formulário dinâmico para cada estratégia');
assert193(str_contains($integrations,'csrf_field()')&&str_contains($updates,'confirm_password'),'segurança de formulários preservada');
assert193(trim(file_get_contents($root.'/VERSION'))==='1.0.94','versionamento sincronizado');
echo "1.0.93: padrões globais, integações, atualizações, publicidade, editor, PageSpeed e segurança OK.\n";
