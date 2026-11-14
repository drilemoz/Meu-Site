<?php
declare(strict_types=1);
// POST centralizado: admin autenticado, CSRF validado anteriormente.
require_ability('manage_settings',$me);
if(($me['role']??'')!=='admin')throw new RuntimeException('Somente o administrador pode gerenciar bloqueios.');
$ip=is_string($_POST['ip']??null)?trim($_POST['ip']):'';
$minutes=$action==='security_unblock'?0:(int)($_POST['minutes']??0);
if(in_array($action,['security_block','security_unblock'],true)){
    $st=$db->prepare('SELECT password_hash FROM users WHERE id=? AND active=1 LIMIT 1');
    $st->execute([(int)$me['id']]);
    if(!password_verify((string)($_POST['confirm_password']??''),(string)$st->fetchColumn()))
        throw new RuntimeException('Senha administrativa inválida.');
}
igua_security_change_block($ip,$minutes,(int)$me['id']);
flash($minutes>0?'IP bloqueado temporariamente no painel.':'Bloqueio de IP removido.');
redirect('/admin.php?action=security');
