<?php
declare(strict_types=1);
/** Executar somente em banco igua_ci_* APÓS o ensaio de restauração 134. */
if(PHP_SAPI!=='cli'||getenv('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB'||
   !preg_match('/^igua_ci_[a-z0-9_]{3,40}$/D',(string)getenv('IGUA_TEST_DB_NAME')) ||
   !in_array((string)getenv('IGUA_TEST_DB_HOST'),['127.0.0.1','localhost','::1'],true) ||
   !extension_loaded('pdo_mysql')){
    fwrite(STDERR,"Teste bloqueado: exige MariaDB local e banco isolado igua_ci_*.\n");exit(2);
}
require_once __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/schema.php';
require_once __DIR__.'/../../app/schema_migrations.php';
require_once __DIR__.'/../../app/backup.php';
require_once __DIR__.'/../../app/auth_remember.php';
$db=connection(['host'=>getenv('IGUA_TEST_DB_HOST'),'port'=>getenv('IGUA_TEST_DB_PORT')?:3306,
    'database'=>getenv('IGUA_TEST_DB_NAME'),'username'=>getenv('IGUA_TEST_DB_USER'),'password'=>getenv('IGUA_TEST_DB_PASSWORD')]);
function check140(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);echo "OK MariaDB: $message\n";}
check140(igua_schema_revision($db)===IGUA_SCHEMA_REVISION,'migração atual registrada');
$q=$db->query("SHOW TABLES LIKE 'admin_remember_tokens'");check140((bool)$q->fetchColumn(),'tabela real criada');
$email='ig140-'.bin2hex(random_bytes(6)).'@example.invalid';
$pass=password_hash('senha-segura-140',PASSWORD_DEFAULT);
$uid=0;
try {
    $db->prepare("INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,'admin',1)")->execute(['Teste 140',$email,$pass]);
    $uid=(int)$db->lastInsertId();
    $_SERVER['REMOTE_ADDR']='127.0.0.1';$_SERVER['SERVER_NAME']='127.0.0.1';
    $_SESSION=[];
    check140(igua_remember_issue($db,['id'=>$uid,'password_hash'=>$pass]),'cookie de sessão emitido e hash no banco');
    $q=$db->prepare('SELECT selector,validator_hash FROM admin_remember_tokens WHERE user_id=?');$q->execute([$uid]);$token=$q->fetch();
    check140(is_array($token) && strlen($token['validator_hash'])===64,'hash persistido');
    igua_remember_revoke_user($db,$uid);
    $q->execute([$uid]);check140($q->fetch()===false,'logout/alteração revoga token real');
    check140(!in_array('admin_remember_tokens',BACKUP_TABLES,true),'segredos fora do backup');
} finally {if($uid>0)$db->prepare('DELETE FROM users WHERE id=?')->execute([$uid]);}
