<?php
declare(strict_types=1);
/**
 * Proteção de bootstrap antes da primeira instalação.
 * A chave somente pode ser criada por quem tem acesso ao filesystem/CLI.
 * Nunca é gerada e revelada em uma requisição HTTP pública.
 */
function igua_setup_access_file(string $storage=IGUA_STORAGE): string {
    return $storage.'/.setup-access.key';
}
function igua_setup_access_read(string $storage=IGUA_STORAGE): ?string {
    $dir=realpath($storage);
    $path=igua_setup_access_file($storage);
    if($dir===false || !is_dir($storage) || is_link($storage) || !is_file($path) || is_link($path))return null;
    $bytes=filesize($path);
    if($bytes===false || $bytes<48 || $bytes>64)return null;
    $secret=trim((string)file_get_contents($path));
    return preg_match('/\A[a-f0-9]{48}\z/D',$secret) ? $secret : null;
}
/** CLI gerencia a chave, nunca a interface web. */
function igua_setup_access_create(string $storage=IGUA_STORAGE): string {
    if(!is_dir($storage)||is_link($storage))throw new RuntimeException('Pasta storage indisponível.');
    $secret=bin2hex(random_bytes(24));
    $path=igua_setup_access_file($storage);
    $file=@fopen($path,'x');
    if($file===false)throw new RuntimeException('Já existe uma chave ou faltam permissões de escrita.');
    try{
        @chmod($path,0600);
        $ok=fwrite($file,$secret."\n")===49 && fflush($file);
    }finally{fclose($file);}
    if(!$ok){@unlink($path);throw new RuntimeException('Não foi possível salvar a chave.');}
    return $secret;
}
function igua_setup_access_valid(?string $secret, array $session, int $now): bool {
    $record=$session['igua_setup_authorization']??null;
    if(!is_string($secret)||!is_array($record)||!is_string($record['digest']??null)
        ||!is_int($record['at']??null)||$record['at']>$now||$now-$record['at']>1800)return false;
    return hash_equals(hash('sha256',$secret),$record['digest']);
}
function igua_setup_access_unlock(string $attempt, ?string $secret, array &$session, int $now): bool {
    if(!is_string($secret)||!preg_match('/\A[a-f0-9]{48}\z/D',$attempt)||!hash_equals($secret,$attempt)){
        $session['igua_setup_access_failures']=min(10,(int)($session['igua_setup_access_failures']??0)+1);
        return false;
    }
    $session['igua_setup_authorization']=['digest'=>hash('sha256',$secret),'at'=>$now];
    unset($session['igua_setup_access_failures']);
    return true;
}
