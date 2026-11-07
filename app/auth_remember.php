<?php
declare(strict_types=1);
/** Login persistente: tokens independentes da senha, revogáveis e limitados a sete dias. */
const IGUA_REMEMBER_COOKIE = 'meusite_remember';
const IGUA_REMEMBER_TTL = 7 * 86400;

function igua_remember_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS admin_remember_tokens (
        selector CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        validator_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        credential_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_remember_user (user_id), KEY idx_remember_expires (expires_at),
        CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function igua_remember_local_loopback(string $remote,string $server,string $sapi): bool {
    // Evita liberar cookie não-Secure se houver proxy HTTP público na frente de Apache/FPM.
    return $sapi==='cli-server' && in_array($remote,['127.0.0.1','::1'],true)
        && in_array($server,['127.0.0.1','::1','localhost'],true);
}
function igua_remember_is_secure_transport(): bool {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return true;
    return igua_remember_local_loopback((string)($_SERVER['REMOTE_ADDR']??''),(string)($_SERVER['SERVER_NAME']??''),PHP_SAPI);
}
function igua_remember_cookie_options(int $expires): array {
    return ['expires'=>$expires,'path'=>'/','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off',
        'httponly'=>true,'samesite'=>'Lax'];
}
function igua_remember_clear_cookie(): void {
    setcookie(IGUA_REMEMBER_COOKIE,'',igua_remember_cookie_options(time()-3600));
    unset($_COOKIE[IGUA_REMEMBER_COOKIE]);
}
function igua_remember_parse(string $raw): ?array {
    if (!preg_match('/^([a-f0-9]{32}):([a-f0-9]{64})$/D',$raw,$m))return null;
    return [$m[1],$m[2]];
}
function igua_remember_issue(PDO $db, array $user): bool {
    if (!igua_remember_is_secure_transport())return false;
    if (empty($user['password_hash']) || (int)($user['id']??0)<1) return false;
    $selector=bin2hex(random_bytes(16));
    $validator=bin2hex(random_bytes(32));
    $expires=time()+IGUA_REMEMBER_TTL;
    $db->prepare('DELETE FROM admin_remember_tokens WHERE expires_at<=NOW()')->execute();
    $stmt=$db->prepare('INSERT INTO admin_remember_tokens (selector,user_id,validator_hash,credential_hash,expires_at) VALUES (?,?,?,?,?)');
    $stmt->execute([$selector,(int)$user['id'],hash('sha256',$validator),hash('sha256',(string)$user['password_hash']),date('Y-m-d H:i:s',$expires)]);
    // Limitar o número de dispositivos ativos por usuário para evitar crescimento sem limite.
    $q=$db->prepare('SELECT selector FROM admin_remember_tokens WHERE user_id=? ORDER BY created_at DESC, selector DESC LIMIT 100');
    $q->execute([(int)$user['id']]);
    $old=array_slice($q->fetchAll(PDO::FETCH_COLUMN),5);
    if($old){$del=$db->prepare('DELETE FROM admin_remember_tokens WHERE selector=? AND user_id=?');
        foreach($old as $obsolete)$del->execute([(string)$obsolete,(int)$user['id']]);}
    // Se houver problema ao definir o cookie, não manter token órfão ativo.
    if (!setcookie(IGUA_REMEMBER_COOKIE,$selector.':'.$validator,igua_remember_cookie_options($expires))) {
        $db->prepare('DELETE FROM admin_remember_tokens WHERE selector=?')->execute([$selector]);
        return false;
    }
    return true;
}
function igua_remember_auto_login(PDO $db): bool {
    if (!igua_remember_is_secure_transport())return false;
    $raw=$_COOKIE[IGUA_REMEMBER_COOKIE]??null;
    if (!is_string($raw))return false;
    $pair=igua_remember_parse($raw);
    if ($pair===null){igua_remember_clear_cookie();return false;}
    [$selector,$validator]=$pair;
    $q=$db->prepare('SELECT t.validator_hash,t.credential_hash,t.expires_at,u.id,u.name,u.email,u.role,u.active,u.password_hash
        FROM admin_remember_tokens t INNER JOIN users u ON u.id=t.user_id
        WHERE t.selector=? AND t.expires_at>NOW() LIMIT 1');
    $q->execute([$selector]);$user=$q->fetch(PDO::FETCH_ASSOC);
    if(!$user || !(int)$user['active'] || !hash_equals((string)$user['validator_hash'],hash('sha256',$validator)) ||
        !hash_equals((string)$user['credential_hash'],hash('sha256',(string)$user['password_hash']))){
        // Rejeitar token expirado/revogado/alterado sem revelar causa ao navegador.
        $db->prepare('DELETE FROM admin_remember_tokens WHERE selector=?')->execute([$selector]);
        igua_remember_clear_cookie();
        return false;
    }
    // Token de uso único por reautenticação: rotação atômica contra reutilização.
    $newValidator=bin2hex(random_bytes(32));
    $update=$db->prepare('UPDATE admin_remember_tokens SET validator_hash=? WHERE selector=? AND validator_hash=?');
    $update->execute([hash('sha256',$newValidator),$selector,hash('sha256',$validator)]);
    if ($update->rowCount()!==1){igua_remember_clear_cookie();return false;}
    $cookieExpires=min((int)strtotime((string)$user['expires_at']),time()+IGUA_REMEMBER_TTL);
    // Não prolongar a validade absoluta; a expiração do banco permanece intacta.
    if (!setcookie(IGUA_REMEMBER_COOKIE,$selector.':'.$newValidator,igua_remember_cookie_options($cookieExpires)))return false;
    unset($user['validator_hash'],$user['credential_hash'],$user['password_hash'],$user['expires_at']);
    $_SESSION['admin']=$user;
    session_regenerate_id(true);
    return true;
}
function igua_remember_logout(PDO $db): void {
    $pair=igua_remember_parse(is_string($_COOKIE[IGUA_REMEMBER_COOKIE]??null)?$_COOKIE[IGUA_REMEMBER_COOKIE]:'');
    if($pair!==null)$db->prepare('DELETE FROM admin_remember_tokens WHERE selector=?')->execute([$pair[0]]);
    igua_remember_clear_cookie();
}
function igua_remember_revoke_user(PDO $db, int $userId): void {
    if($userId>0)$db->prepare('DELETE FROM admin_remember_tokens WHERE user_id=?')->execute([$userId]);
}
