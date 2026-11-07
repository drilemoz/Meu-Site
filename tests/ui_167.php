<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$read=static fn(string $name):string=>(string)file_get_contents($root.'/'.$name);
function ui167(bool $ok,string $label):void {if(!$ok)throw new RuntimeException('1.0.67: '.$label);echo 'OK: '.$label.PHP_EOL;}
ui167(version_compare(trim($read('VERSION')),'1.0.67','>='),'versão sequencial 1.0.67');
$view=$read('app/admin_views/routes/updates.php');
$controller=$read('app/admin_controllers/updater.php');
$js=$read('public/assets/js/updater-progress.js');
ui167(substr_count($view,'data-updater-progress-form')===3,'upload, simulação e instalação remota com progresso');
ui167(str_contains($view,'updater-progress.js?v=') && str_contains($view,"igua_updater_version()"),'script de progresso versionado');
ui167(str_contains($js,'xhr.upload.addEventListener(\'progress\'') && str_contains($js,'progress.loaded / progress.total'),'percentual calculado pelo progresso real do upload');
ui167(str_contains($js,'progress.lengthComputable')===false ? str_contains($js,'progress.lengthComputable'):str_contains($js,'progress.lengthComputable'),'sinalização de progresso não mensurável');
ui167(str_contains($js,'bar.removeAttribute(\'value\')') && str_contains($js,"'Processando…'"),'sem porcentagem falsa durante a instalação');
ui167(str_contains($js,'form.checkValidity()') && str_contains($js,'new FormData(form)'),'confirmação/senha/CSRF continuam enviados');
ui167(str_contains($js,'form.dataset.updaterSending') && str_contains($js,'xhr.ontimeout'),'envio duplicado bloqueado e timeout sem retry automático');
ui167(str_contains($js,'admin-main > .alert.error') && str_contains($js,'Resposta precisa de verificação'),'falhas exibidas sem declarar instalação concluída');
ui167(str_contains($controller,"if((\$_POST['confirm_install']??'')!=='yes')") && str_contains($controller,"password_verify(\$pwd,\$hash)"),'servidor exige senha e confirmação');
ui167(str_contains($controller,"IGUA_UPDATE_MAX_ZIP") && str_contains($controller,"igua_updater_install_zip"),'validação e instalação existentes preservadas');
$maint=$read('app/admin_views/routes/maintenance.php');
ui167(str_contains($maint,'maintenance-warning') && str_contains($maint,'25 tabelas'),'aviso destrutivo permanece visível');
ui167(substr_count($maint,'csrf_field()')>=4 && str_contains($maint,'data-confirm'),'formulários destrutivos com CSRF e confirmação');
$bus=$read('app/bus_wizard.php');
ui167(str_contains($bus,'bus-wizard-step-info') && str_contains($bus,'id="busWizardForm"'),'wizard mantém campos com explicação contextual');
$ads=$read('app/admin_views/routes/ads_dashboard.php');
ui167(str_contains($ads,'ad-quick-help') && str_contains($ads,'data-ad-open'),'publicidade com ajuda e controles intactos');
$css=$read('public/assets/css/modules/15-archive.css');
ui167(str_contains($css,'.updater-progress progress') && str_contains($read('public/assets/css/style.css'),'.updater-progress progress'),'progresso com CSS consolidado');
echo '1.0.67: testes estruturais concluídos; HTTP real e instalação PHP-FPM ainda pendentes.'.PHP_EOL;
