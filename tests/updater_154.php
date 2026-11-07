<?php
/** V1.0.54: fluxo de sessão, detecção segura e UI compacta. */
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function verify154(bool $ok,string $message): void {
    if(!$ok)throw new RuntimeException('FALHOU: '.$message);
    echo "OK: $message\n";
}
verify154(version_compare(igua_updater_version(),'1.0.60','>='),'versão sequencial');
verify154(igua_updater_parse_repository('https://github.com/exemplo/site.git')===['owner'=>'exemplo','repo'=>'site'],'URL de origem válida');
verify154(igua_updater_parse_repository('conta/portal')===['owner'=>'conta','repo'=>'portal'],'nome de repositório válido');
foreach(['', 'https://evil.com/conta/portal','../../etc/passwd','admin//repo','usuario/repo/extra','conta/repo..'] as $bad)
    verify154(igua_updater_parse_repository($bad)===null,'origem suspeita recusada');
verify154(igua_updater_release_asset_name('Atualizacao-v1.0.54.zip','1.0.54'),'aceita novo padrão de nome');
verify154(igua_updater_release_asset_name('Meu-Site-update-v1.0.54.zip','1.0.54'),'aceita release legado');
verify154(!igua_updater_release_asset_name('Meu-Site-v1.0.54-hospedagem.zip','1.0.54'),'rejeita ZIP completo como atualização');
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');
$admin=file_get_contents(__DIR__.'/../public/admin.php');
verify154(str_contains($controller, "(\$_POST['confirm_install']??'')!=='yes'"),'confirmação explícita obrigatória');
verify154(str_contains($controller,"if(\$operation==='rollback')")&&str_contains($controller,'password_verify'),'senha exigida para restauração');
verify154(substr_count($view,'name="confirm_password"')===3,'instalações e restauração pedem senha');
verify154(str_contains($controller,'igua_update_auth_failures'),'reautenticação com limitação de tentativas');
verify154(substr_count($view,'name="confirm_install"')===2,'instalações remota e manual confirmadas');
verify154(str_contains($view,'<details')&&str_contains($view,'updater-help'),'explicações recolhidas');
verify154(str_contains($view,'originLabel')&&str_contains($view,'updater-source-form'),'origem com fallback de configuração');
verify154(str_contains($admin,"header('Cache-Control: private, no-store, max-age=0')"),'página de atualizações sem cache HTTP');
verify154(str_contains($admin,'check_csrf()')&&str_contains($admin,"'updates'=>'manage_settings'"),'CSRF e autorização admin preservados');
echo 'Teste estático aprovado. Fluxo real continua pendente na hospedagem.\n';
