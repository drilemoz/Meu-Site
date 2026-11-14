<?php
declare(strict_types=1);
/** Limite de autenticação por IP de origem do servidor, compartilhado entre sessões PHP.
 * Não confiar em X-Forwarded-For sem um proxy explicitamente configurado.
 * Não gravar IP, e-mail, senha ou tokens nos arquivos de estado.
 */
function igua_login_with_limit(string $remoteAddress, callable $authenticate, ?int $now=null, ?string $root=null): array {
    $now ??= time();
    $root ??= IGUA_STORAGE.'/security/login';
    if (!is_dir($root) && !@mkdir($root,0700,true) && !is_dir($root)) {
        throw new RuntimeException('Não foi possível proteger as tentativas de login.');
    }
    $ip=filter_var($remoteAddress,FILTER_VALIDATE_IP) ? $remoteAddress : 'unknown';
    $filename=$root.'/'.hash('sha256','login-ip:'.$ip).'.json';
    $handle=@fopen($filename,'c+');
    if ($handle===false)throw new RuntimeException('Armazenamento de limite de login indisponível.');
    try {
        if(!flock($handle,LOCK_EX))throw new RuntimeException('Bloqueio de login indisponível.');
        @chmod($filename,0600);
        rewind($handle);
        $raw=stream_get_contents($handle,1024);
        if($raw===false)throw new RuntimeException('Erro de leitura do limite de login.');
        $state=json_decode($raw,true);
        if(!is_array($state))$state=[];
        $failures=max(0,min(100,(int)($state['failures']??0)));
        $first=(int)($state['first']??0);
        $blocked=(int)($state['blocked_until']??0);
        if(($blocked>0 && $now >= $blocked) || ($blocked<=0 && ($first<=0 || $first>$now || $now-$first>=900))){
            $failures=0;$first=0;$blocked=0;
        }
        if($blocked>$now)return ['limited'=>true,'user'=>null];
        // Consulta e atualização são feitas sob o mesmo flock: requisições concorrentes
        // da mesma origem não ultrapassam o limite por corrida de gravação.
        $user=$authenticate();
        if($user){$failures=0;$first=0;$blocked=0;}
        else {
            if($first===0)$first=$now;
            $failures++;
            if($failures>=5)$blocked=$now+900;
        }
        $next=json_encode(['failures'=>$failures,'first'=>$first,'blocked_until'=>$blocked],JSON_THROW_ON_ERROR);
        rewind($handle);
        if(!ftruncate($handle,0) || fwrite($handle,$next)!==strlen($next) || !fflush($handle)){
            throw new RuntimeException('Não foi possível atualizar o limite de login.');
        }
        return ['limited'=>false,'user'=>$user?:null];
    } finally {
        flock($handle,LOCK_UN);
        fclose($handle);
    }
}
