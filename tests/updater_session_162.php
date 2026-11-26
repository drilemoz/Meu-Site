<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/updater.php';
function verify162session(bool $ok): void {if(!$ok)throw new RuntimeException('Falha na regressão de autorização/diagnóstico.');}
$root=sys_get_temp_dir().'/igua-layout-'.bin2hex(random_bytes(8));
mkdir($root.'/storage/updater/vps',0700,true);
try {
    verify162session(!igua_updater_vps_layout($root));
    mkdir($root.'/storage/updater/vps/private',0700);
    verify162session(igua_updater_vps_layout($root));
    rmdir($root.'/storage/updater/vps/private');
    symlink($root,$root.'/storage/updater/vps/private');
    verify162session(!igua_updater_vps_layout($root));
}finally{igua_updater_delete_tree($root);}
$c=file_get_contents(__DIR__.'/../app/admin_controllers/updater.php');
$v=file_get_contents(__DIR__.'/../app/admin_views/routes/updates.php');
$a=file_get_contents(__DIR__.'/../public/admin.php');
verify162session(substr_count($c,'password_verify(')===1);
verify162session(substr_count($v,'name="confirm_password"')===1);
verify162session(str_contains($c,"Somente administradores podem instalar atualizações."));
verify162session(str_contains($c,"Confirme a instalação antes de atualizar o código."));
verify162session(str_contains($a,'check_csrf()')&&str_contains($a,'require_admin()'));
verify162session(igua_updater_diagnostic(new RuntimeException('Não foi possível preparar cópia privada.'))['code']==='deploy_unavailable');
verify162session(igua_updater_diagnostic(new RuntimeException('Esta atualização altera a revisão do banco.'))['code']==='schema_migration');
echo "OK: sessão, confirmação, reconhecimento VPS e diagnóstico.\n";
