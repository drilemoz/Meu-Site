<?php
/** Testes de interface modal, origem oficial verificada e CSS móvel 1.0.56. */
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function check156(bool $ok,string $label):void {
    if(!$ok)throw new RuntimeException('FALHOU: '.$label);
    echo "OK: $label\n";
}
check156(version_compare(igua_updater_version(),'1.0.60','>='),'versão sincronizada');
$origin=json_decode((string)file_get_contents(__DIR__.'/../app/update-origin.json'),true);
check156(is_array($origin)&&$origin['repository']==='drilemoz/Meu-Site','repo oficial conhecido e validado');
check156(igua_updater_parse_repository($origin['repository'])===['owner'=>'drilemoz','repo'=>'Meu-Site'],'repo validado pelo parser');
$view=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');
$controller=(string)file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
$js=(string)file_get_contents(__DIR__.'/../public/assets/js/updater-dialogs.js');
$css=(string)file_get_contents(__DIR__.'/../public/assets/css/modules/15-archive.css');
check156(preg_match('~<dialog id="updater-source-dialog" class="[^"]*updater-dialog"~',$view)===1 && preg_match('~<dialog id="updater-check-dialog" class="[^"]*updater-dialog"~',$view)===1,'duas janelas nativas de origem e vistoria');
check156(str_contains($view,'data-updater-open="updater-source-dialog"')&&str_contains($view,'data-updater-open="updater-check-dialog"'),'atalhos compactos de origem e vistoria');
check156(str_contains($view,'class="updater-source-form"') && str_contains($view,'name="operation" value="source"'),'edição de origem preservada');
check156(str_contains($view,'value="source_reset"')&&str_contains($controller,"$"."operation==='source_reset'"),'restauração segura da origem oficial');
check156(str_contains($js,'showModal')&&str_contains($js,'closeDialog')&&str_contains($js,'data-updater-close'),'modal com fechamento e backdrop');
check156(str_contains($css,'.site-footer{padding-bottom:calc(68px') && str_contains($css,'.footer-new{padding:23px 0 0}'),'rodapé móvel sem padding duplicado');
check156(substr_count($view,'name="confirm_password"')===3,'senha de recuperação preservada');
check156(substr_count($view,'name="confirm_install"')===2,'confirmações de instalação preservadas');
$updater=(string)file_get_contents(__DIR__.'/../app/updater.php');
check156(str_contains($updater,"HTTP 404")&&str_contains($updater,"'release'=>null"),'repo sem Releases não produz alerta falso');
echo 'Testes estáticos aprovados; validar layout e atualização em hospedagem real.\n';
