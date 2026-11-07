<?php
declare(strict_types=1);
// Autenticado no front controller, protegido por token CSRF.
require_ability('manage_settings',$me);
if (($me['role']??'')!=='admin') throw new DomainException('Somente o administrador pode executar limpeza.');
require_once __DIR__.'/../content_maintenance.php';
require_once __DIR__.'/../backup.php';
if ($action==='maintenance_clear_demo' || $action==='maintenance_clear_all') {
    $scope=$action==='maintenance_clear_demo'?'demo':'all';
    $expected=$scope==='demo'?'REMOVER EXEMPLOS':'LIMPAR NOTICIAS';
    if (!hash_equals($expected,trim((string)($_POST['confirmation']??''))))
        throw new DomainException('Confirmação incorreta. Nenhum dado foi excluído.');
    $backup=make_backup($db,'manual');
    $deleted=igua_content_clear($db,$scope);
    flash('Backup salvo: '.$backup.'. '.$deleted.' matéria(s) removida(s). Categorias, fontes, usuários, configurações e arquivos físicos preservados.');
    redirect('/admin.php?action=maintenance');
}
if ($action==='maintenance_reset') {
    if (!hash_equals('REINSTALAR',trim((string)($_POST['confirmation']??''))))
        throw new DomainException('Digite REINSTALAR exatamente para continuar.');
    $pass=(string)($_POST['admin_password']??'');
    $stmt=$db->prepare('SELECT password_hash FROM users WHERE id=? AND role=? AND active=1 LIMIT 1');
    $stmt->execute([(int)$me['id'],'admin']);
    $hash=$stmt->fetchColumn();
    if (!is_string($hash) || !password_verify($pass,$hash)) throw new DomainException('Senha administrativa incorreta.');
    require_once __DIR__.'/../setup_inventory.php';
    $inventory=igua_setup_database_inventory($db);
    if (!in_array($inventory['status'],['recoverable','occupied'],true))
        throw new DomainException('Banco não reconhecido para reinicialização.');
    // Validações não destrutivas antes da criação de backup.
    $names=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    if (count(array_diff(igua_setup_known_tables(),$names))>0 || count(array_diff($names,igua_setup_known_tables()))>0)
        throw new DomainException('A estrutura não é exatamente a esperada. Banco preservado.');
    $lock=@fopen(IGUA_STORAGE.'/.setup.lock','c');
    if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) throw new DomainException('Há outra operação de instalação em execução.');
    try {
        // NUNCA limpar o banco se o backup falhar.
        $backup=make_backup($db,'manual');
        $removed=igua_content_reset_schema($db,IGUA_STORAGE);
    } finally {
        flock($lock,LOCK_UN);fclose($lock);
    }
    // A sessão administrativa antiga não pode ser reutilizada depois da reinicialização.
    $_SESSION=[];
    session_regenerate_id(true);
    redirect('/setup.php?step=1');
}
throw new DomainException('Ação de manutenção desconhecida.');
