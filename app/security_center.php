<?php
declare(strict_types=1);
/**
 * Central de Segurança: eventos de aplicação, em armazenamento privado (sem MySQL).
 * NÃO é um IDS/WAF do servidor: somente registra requisições que executaram PHP.
 * Nunca armazena corpo, senhas, cookies, tokens, URLs, e-mail ou User-Agent.
 */
const IGUA_SECURITY_RETENTION_DAYS = 30;
const IGUA_SECURITY_DAILY_BYTES = 1048576;
const IGUA_SECURITY_BLOCK_LIMIT = 150;

function igua_security_types(): array {
    return [
        'login_failed'=>['label'=>'Senha incorreta no login','level'=>'warning'],
        'login_limited'=>['label'=>'Limite de tentativas de login','level'=>'critical'],
        'login_success'=>['label'=>'Login administrativo','level'=>'info'],
        'remember_success'=>['label'=>'Sessão persistente revalidada','level'=>'info'],
        'logout'=>['label'=>'Saída do painel','level'=>'info'],
        'csrf_rejected'=>['label'=>'Requisição sem CSRF válido','level'=>'warning'],
        'permission_denied'=>['label'=>'Acesso não autorizado','level'=>'warning'],
        'admin_action'=>['label'=>'Operação administrativa solicitada','level'=>'info'],
        'user_changed'=>['label'=>'Cadastro de usuário alterado','level'=>'warning'],
        'user_access_changed'=>['label'=>'Acesso de usuário alterado','level'=>'warning'],
        'settings_changed'=>['label'=>'Configurações alteradas','level'=>'info'],
        'update_installed'=>['label'=>'Atualização instalada','level'=>'warning'],
        'update_failed'=>['label'=>'Falha em operação de atualização','level'=>'warning'],
        'backup_restored'=>['label'=>'Restauração de backup concluída','level'=>'critical'],
        'probe_path'=>['label'=>'Caminho suspeito solicitado','level'=>'warning'],
        'ip_blocked'=>['label'=>'Bloqueio de IP aplicado','level'=>'warning'],
        'ip_unblocked'=>['label'=>'Bloqueio de IP removido','level'=>'info'],
        'blocked_request'=>['label'=>'Requisição bloqueada no admin','level'=>'warning'],
        'upload_rejected'=>['label'=>'Envio administrativo recusado','level'=>'warning'],
    ];
}
function igua_security_ip(?string $address=null): string {
    $address ??= (string)($_SERVER['REMOTE_ADDR']??'');
    $packed=@inet_pton($address);
    return $packed===false ? '' : (string)inet_ntop($packed);
}
function igua_security_root(?string $root=null): ?string {
    $root ??= IGUA_STORAGE.'/security/monitor';
    if(is_link($root))return null;
    if(!is_dir($root)&&!@mkdir($root,0700,true)&&!is_dir($root))return null;
    return is_dir($root)&&is_writable($root)&&!is_link($root)?$root:null;
}
/** Retorno false sinaliza armazenamento indisponível. Metadados vêm de enumerações internas. */
function igua_security_event(string $type, ?string $target=null, ?string $ip=null, ?int $now=null, ?string $root=null): bool {
    $types=igua_security_types();
    if(!isset($types[$type]))return false;
    $now ??= time();
    $dir=igua_security_root($root);
    if($dir===null)return false;
    $ip=igua_security_ip($ip);
    $target=is_string($target)&&preg_match('/^[a-z0-9_]{1,48}$/D',$target)?$target:'';
    $actor=function_exists('admin_user') ? (int)(admin_user()['id']??0) : 0;
    $entry=['at'=>date('c',$now),'type'=>$type,'level'=>$types[$type]['level'],
        'ip'=>$ip,'actor'=>$actor,'target'=>$target];
    $line=json_encode($entry,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    if(!is_string($line))return false;
    $path=$dir.'/'.date('Y-m-d',$now).'.jsonl';
    if(is_link($path))return false;
    $file=@fopen($path,'c+');
    if($file===false)return false;
    try {
        if(!@flock($file,LOCK_EX))return false;
        @chmod($path,0600);
        $size=fstat($file)['size']??IGUA_SECURITY_DAILY_BYTES;
        if($size+strlen($line)+1>IGUA_SECURITY_DAILY_BYTES)return false;
        fseek($file,0,SEEK_END);
        $bytes=$line."\n";
        $ok=fwrite($file,$bytes)===strlen($bytes)&&fflush($file);
    } finally {flock($file,LOCK_UN);fclose($file);}
    // A primeira gravação do dia executa retenção sem cron, sem varrer os logs a cada request.
    $gc=$dir.'/.last-cleanup';
    if(!is_link($gc)&&(!is_file($gc)||(int)@filemtime($gc)<$now-86400)){
        igua_security_cleanup($dir,$now);
        @touch($gc,$now);@chmod($gc,0600);
    }
    return $ok;
}
function igua_security_cleanup(string $dir,int $now): void {
    $cutoff=date('Y-m-d',$now-(IGUA_SECURITY_RETENTION_DAYS-1)*86400);
    foreach(glob($dir.'/????-??-??.jsonl')?:[] as $p){
        if(is_link($p)||!preg_match('~/([0-9]{4}-[0-9]{2}-[0-9]{2})\.jsonl$~D',$p,$m))continue;
        if($m[1]<$cutoff)@unlink($p);
    }
}
/** Consulta restrita e limitada. A contagem é dos eventos efetivamente armazenados. */
function igua_security_history(int $days=7,string $level='all',string $kind='all',?string $ip=null,int $limit=100,?int $now=null,?string $root=null): array {
    $days=in_array($days,[1,7,30],true)?$days:7;
    $types=igua_security_types();
    if(!in_array($level,['all','info','warning','critical'],true))$level='all';
    if($kind!=='all'&&!isset($types[$kind]))$kind='all';
    $ip=$ip===null||$ip===''?'':igua_security_ip($ip);
    $now ??= time();$start=$now-$days*86400;
    $dir=$root??IGUA_STORAGE.'/security/monitor';
    $found=[];$summary=['total'=>0,'warning'=>0,'critical'=>0,'login_failed'=>0,'login_limited'=>0,'blocked_request'=>0];
    if(!is_dir($dir)||is_link($dir))return ['rows'=>[],'summary'=>$summary,'matched'=>0];
    $matched=0;
    for($d=0;$d<=$days;$d++){
        $day=date('Y-m-d',$now-$d*86400);
        $path=$dir.'/'.$day.'.jsonl';
        if(!is_file($path)||is_link($path)||filesize($path)>IGUA_SECURITY_DAILY_BYTES)continue;
        $lines=@file($path,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES);
        if(!is_array($lines))continue;
        for($i=count($lines)-1;$i>=0;$i--){
            $r=json_decode($lines[$i],true);
            if(!is_array($r)||!isset($types[$r['type']??''])||!is_string($r['at']??null))continue;
            $stamp=strtotime($r['at']);
            if($stamp===false||$stamp<$start||$stamp>$now+60)continue;
            $typ=$r['type'];$sev=$types[$typ]['level'];
            $summary['total']++;
            if(isset($summary[$sev]))$summary[$sev]++;
            if(isset($summary[$typ]))$summary[$typ]++;
            if($level!=='all'&&$level!==$sev)continue;
            if($kind!=='all'&&$kind!==$typ)continue;
            if($ip!==''&&($r['ip']??'')!==$ip)continue;
            $matched++;
            if(count($found)<max(1,min(200,$limit))){
                $found[]=['at'=>date('d/m/Y H:i:s',$stamp),'time'=>$stamp,'type'=>$typ,'level'=>$sev,
                    'ip'=>igua_security_ip(is_string($r['ip']??null)?$r['ip']:''),'actor'=>max(0,(int)($r['actor']??0)),
                    'target'=>preg_match('/^[a-z0-9_]{1,48}$/D',(string)($r['target']??''))?(string)$r['target']:''];
            }
        }
    }
    usort($found,static fn($a,$b)=>$b['time']<=>$a['time']);
    return ['rows'=>$found,'summary'=>$summary,'matched'=>$matched];
}
function igua_security_blocks_read(?string $root=null,?int $now=null): array {
    $dir=$root??IGUA_STORAGE.'/security/monitor';
    $path=$dir.'/blocks.json';
    if(!is_file($path)||is_link($path)||filesize($path)>65536)return [];
    $json=@file_get_contents($path);
    $data=is_string($json)?json_decode($json,true):null;
    if(!is_array($data))return [];
    $now ??= time();$rows=[];
    foreach($data as $ip=>$record){
        if(!is_string($ip)||igua_security_ip($ip)!==$ip||!is_array($record))continue;
        $until=(int)($record['until']??0);
        if($until<=$now||$until>$now+86400*2)continue;
        $rows[$ip]=['until'=>$until,'by'=>max(0,(int)($record['by']??0))];
    }
    return $rows;
}
function igua_security_blocked(?string $ip=null,?int $now=null,?string $root=null): bool {
    $ip=igua_security_ip($ip);
    return $ip!==''&&isset(igua_security_blocks_read($root,$now)[$ip]);
}
/** Escrita transacional de lista de IPs sob lock privado. Sem bloqueios por padrão. */
function igua_security_change_block(string $ip,int $minutes,int $actor,?int $now=null,?string $root=null): void {
    $canonical=igua_security_ip($ip);
    if($canonical===''||$canonical!==$ip)throw new InvalidArgumentException('Endereço IP inválido.');
    if(!in_array($minutes,[0,15,60,360,1440],true))throw new InvalidArgumentException('Prazo de bloqueio inválido.');
    if($minutes>0&&$canonical===igua_security_ip())throw new RuntimeException('Não é permitido bloquear o IP do próprio acesso.');
    // Evita bloquear proxies internos/loopback e criar bloqueio permanente por engano.
    if($minutes>0&&!filter_var($canonical,FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))
        throw new RuntimeException('Bloqueie apenas IPs públicos válidos.');
    $now ??= time();
    $dir=igua_security_root($root);
    if($dir===null)throw new RuntimeException('Armazenamento privado de segurança indisponível.');
    $lockPath=$dir.'/.blocks.lock';$path=$dir.'/blocks.json';
    if(is_link($lockPath)||is_link($path))throw new RuntimeException('Arquivo de segurança inválido.');
    $lock=@fopen($lockPath,'c');
    if($lock===false)throw new RuntimeException('Não foi possível bloquear a lista de IPs.');
    try{
        if(!flock($lock,LOCK_EX))throw new RuntimeException('Lista de IPs indisponível.');
        @chmod($lockPath,0600);
        $blocks=igua_security_blocks_read($dir,$now);
        if($minutes===0)unset($blocks[$canonical]);
        else $blocks[$canonical]=['until'=>$now+$minutes*60,'by'=>$actor];
        if(count($blocks)>IGUA_SECURITY_BLOCK_LIMIT)throw new RuntimeException('Limite de bloqueios ativos atingido.');
        $encoded=json_encode($blocks,JSON_THROW_ON_ERROR);
        $tmp=@tempnam($dir,'.blocks-');
        if($tmp===false)throw new RuntimeException('Sem espaço para registrar bloqueio.');
        try {
            @chmod($tmp,0600);
            if(file_put_contents($tmp,$encoded)!==strlen($encoded)||!@rename($tmp,$path))
                throw new RuntimeException('Não foi possível salvar o bloqueio.');
        }finally{if(is_file($tmp))@unlink($tmp);}
    }finally{flock($lock,LOCK_UN);fclose($lock);}
    igua_security_event($minutes>0?'ip_blocked':'ip_unblocked',null,$canonical,$now,$dir);
}
/** Somente padrões explícitos, nada de inspecionar POST/query strings. */
function igua_security_probe_request(?string $uri=null): bool {
    if(PHP_SAPI==='cli')return false;
    $uri ??= (string)($_SERVER['REQUEST_URI']??'');
    $path=parse_url(substr($uri,0,2048),PHP_URL_PATH);
    if(!is_string($path))return false;
    $path=rawurldecode($path);
    if(preg_match('~(?:^|/)(?:\.env(?:\.|$)|\.git(?:/|$)|wp-admin(?:/|$)|wp-login\.php|xmlrpc\.php|phpmyadmin(?:/|$)|vendor/phpunit(?:/|$)|cgi-bin(?:/|$)|\.aws(?:/|$)|server-status(?:/|$))~i',$path)){
        igua_security_event('probe_path');return true;
    }
    return false;
}
