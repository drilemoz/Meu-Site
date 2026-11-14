<?php
declare(strict_types=1);
/** Fila estritamente limitada para deploy na VPS. Nenhuma execução de shell via HTTP. */
function igua_updater_vps_mode(): bool { return getenv('IGUA_DEPLOY_VPS')==='1'; }
function igua_updater_vps_dir(): string {
    $dir=IGUA_UPDATE_DIR.'/vps';
    if(is_link($dir)||!is_dir($dir))throw new RuntimeException('Fila de deploy não configurada. Consulte docs/AWS_EC2_DEPLOY.md.');
    return $dir;
}
/** Controle do worker: www-data pode ler o marcador, mas nunca criá-lo/removê-lo. */
function igua_updater_vps_private_dir(): string {
    $dir=igua_updater_vps_dir().'/private';
    if(is_link($dir)||!is_dir($dir))throw new RuntimeException('Controle privado do deploy não configurado.');
    return $dir;
}
function igua_updater_vps_private_path(string $name): string {
    if(!in_array($name,['maintenance','.code.lock'],true))throw new InvalidArgumentException('Controle privado não permitido.');
    return igua_updater_vps_private_dir().'/'.$name;
}
/** A verificação explícita impede que um PHP CLI iniciado como www-data se faça passar pelo worker. */
function igua_updater_vps_assert_worker(): void {
    if(PHP_SAPI!=='cli'||!igua_updater_vps_mode()||!function_exists('posix_geteuid'))
        throw new RuntimeException('Worker dedicado exige PHP CLI e POSIX.');
    $uid=posix_geteuid();
    if($uid===0||fileowner(igua_updater_vps_private_dir())!==$uid||fileowner(IGUA_ROOT)!==$uid)
        throw new RuntimeException('Processo sem identidade da conta dedicada de implantação.');
}
/** Testa CRIAÇÃO, RENOMEAÇÃO ATÔMICA e REMOÇÃO em um diretório, sem alterar arquivos existentes. */
function igua_updater_vps_probe_rename(string $dir): bool {
    if(is_link($dir)||!is_dir($dir))return false;
    try {$rand=bin2hex(random_bytes(10));}catch(Throwable){return false;}
    $first=$dir.'/.igua-probe-'.$rand;$second=$dir.'/.igua-probe-'.$rand.'-renamed';
    $ok=false;$f=@fopen($first,'x');
    if(!$f)return false;
    try {
        $ok=fwrite($f,'ready')===5;
    }finally{fclose($f);}
    try {
        if($ok && @rename($first,$second))$ok=is_file($second) && (string)@file_get_contents($second)==='ready';
        else $ok=false;
    }finally{
        if(is_file($first))@unlink($first);
        if(is_file($second) && !@unlink($second))$ok=false;
    }
    return $ok;
}
/** Diagnóstico real rodado somente na conta dedicada, publicado como sinalização de prontidão. */
function igua_updater_vps_worker_probe(): array {
    igua_updater_vps_assert_worker();
    $root=IGUA_ROOT;$queue=igua_updater_vps_dir();$private=igua_updater_vps_private_dir();
    $uid=function_exists('posix_geteuid')?posix_geteuid():null;
    $owned=static function(string $dir,bool $allowGroupWrite=false)use($uid): bool {
        if(is_link($dir)||!is_dir($dir))return false;
        $mode=(int)fileperms($dir);
        if(($mode&0002)!==0||(!$allowGroupWrite && ($mode&0020)!==0))return false;
        return $uid===null||fileowner($dir)===$uid;
    };
    $checks=[
        'root_atomic'=>$owned($root)&&igua_updater_vps_probe_rename($root),
        'app_atomic'=>$owned($root.'/app')&&igua_updater_vps_probe_rename($root.'/app'),
        'public_atomic'=>$owned($root.'/public')&&igua_updater_vps_probe_rename($root.'/public'),
        'updater_atomic'=>$owned(IGUA_UPDATE_DIR,true)&&igua_updater_vps_probe_rename(IGUA_UPDATE_DIR),
        'jobs_atomic'=>$owned(IGUA_UPDATE_DIR.'/jobs')&&igua_updater_vps_probe_rename(IGUA_UPDATE_DIR.'/jobs'),
        'backups_atomic'=>igua_updater_vps_probe_rename(IGUA_STORAGE.'/backups'),
        'control_atomic'=>$owned($private)&&igua_updater_vps_probe_rename($private),
        'queue_secure'=>$owned($queue,true)&&(((int)fileperms($queue)&01000)!==0),
        'queue_lock'=>is_file($queue.'/.dispatch.lock')&&!is_link($queue.'/.dispatch.lock')&&is_writable($queue.'/.dispatch.lock'),
        'config_read'=>is_file(IGUA_STORAGE.'/config.php')&&!is_link(IGUA_STORAGE.'/config.php')&&is_readable(IGUA_STORAGE.'/config.php'),
        'php_extensions'=>extension_loaded('zip')&&extension_loaded('pdo_mysql')&&extension_loaded('curl'),
        'legacy_clear'=>!is_file(IGUA_STORAGE.'/.update-maintenance')&&!is_link(IGUA_STORAGE.'/.update-maintenance'),
    ];
    return ['ready'=>!in_array(false,$checks,true),'checks'=>$checks];
}
function igua_updater_vps_path(string $name): string {
    if(!in_array($name,['request.json','running.json','status.json','worker.json','.dispatch.lock'],true))throw new InvalidArgumentException('Arquivo de fila não permitido.');
    return igua_updater_vps_dir().'/'.$name;
}
function igua_updater_vps_read(string $name): ?array {
    $path=igua_updater_vps_path($name);
    if(is_link($path)||!is_file($path)||filesize($path)>4096)return null;
    $data=json_decode((string)@file_get_contents($path),true);
    return is_array($data)?$data:null;
}
/** Escrita atômica via pasta setgid compartilhada, sem segredos. */
function igua_updater_vps_write(string $name,array $data): void {
    $path=igua_updater_vps_path($name);
    if(is_link($path))throw new RuntimeException('Fila de deploy insegura.');
    $dir=dirname($path);$tmp=tempnam($dir,'.queue-');
    if($tmp===false)throw new RuntimeException('Não foi possível registrar a solicitação de deploy.');
    try{
        $raw=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
        if(strlen($raw)>4096||file_put_contents($tmp,$raw,LOCK_EX)!==strlen($raw)||!@chmod($tmp,0640)||!@rename($tmp,$path))
            throw new RuntimeException('Falha ao gravar estado de deploy.');
    }finally{if(is_file($tmp))@unlink($tmp);}
}
function igua_updater_vps_lock() {
    $path=igua_updater_vps_path('.dispatch.lock');
    if(is_link($path)||!is_file($path))throw new RuntimeException('Lock de deploy ausente. Execute a preparação da VPS.');
    $f=@fopen($path,'c+');
    if(!$f||!@flock($f,LOCK_EX|LOCK_NB))throw new RuntimeException('Fila de deploy ocupada.');
    return $f;
}
/** Operações deliberadamente limitadas; URL, caminho e comando não vêm do navegador. */
function igua_updater_vps_enqueue(string $operation,int $userId,string $token): void {
    if(!igua_updater_vps_mode() || $operation!=='install_remote'
       ||$userId<1||!preg_match('/^[a-f0-9]{32}$/D',$token))throw new RuntimeException('Solicitação de deploy inválida.');
    $lock=igua_updater_vps_lock();
    try {
        if(function_exists('igua_updater_maintenance_present') && igua_updater_maintenance_present())
            throw new RuntimeException('Existe uma atualização interrompida. Recupere antes de solicitar outra.');
        if(is_file(igua_updater_vps_path('request.json'))||is_file(igua_updater_vps_path('running.json')))
            throw new RuntimeException('Já existe uma atualização pendente. Não reenvie a solicitação.');
        $worker=igua_updater_vps_read('worker.json');
        if(!is_array($worker)||!is_int($worker['at']??null)||$worker['at']<time()-180||($worker['ready']??false)!==true)
            throw new RuntimeException('Serviço externo de implantação não respondeu recentemente. Verifique systemd.');
        igua_updater_vps_write('request.json',['operation'=>$operation,'user_id'=>$userId,'token'=>$token,'created'=>time()]);
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
function igua_updater_vps_status(int $userId,string $token): ?array {
    if($userId<1||!preg_match('/^[a-f0-9]{32}$/D',$token))return null;
    try{$status=igua_updater_vps_read('status.json');}catch(Throwable){return null;}
    if(($status['user_id']??null)!==$userId||($status['token']??null)!==$token||
       !is_int($status['at']??null)||$status['at']<time()-86400)return null;
    return ['stage'=>(string)($status['stage']??''),'message'=>(string)($status['message']??''),'at'=>$status['at']];
}
function igua_updater_vps_status_update(array $request,string $stage,string $message): void {
    igua_updater_vps_write('status.json',['user_id'=>$request['user_id'],'token'=>$request['token'],
        'stage'=>$stage,'message'=>substr($message,0,150),'at'=>time()]);
}
function igua_updater_vps_take(): ?array {
    $lock=igua_updater_vps_lock();
    try{
        if(is_file(igua_updater_vps_path('running.json')))return null; // Falha abrupta: recuperação assistida obrigatória.
        $request=igua_updater_vps_read('request.json');
        if($request===null)return null;
        if(($request['operation']??null)!=='install_remote'||
           !is_int($request['user_id']??null)||$request['user_id']<1||
           !is_string($request['token']??null)||!preg_match('/^[a-f0-9]{32}$/D',$request['token'])||
           !is_int($request['created']??null)||abs(time()-$request['created'])>1800)
            throw new RuntimeException('Solicitação expirada ou adulterada. Intervenção administrativa necessária.');
        // Arquivo running é criado pelo worker (não por www-data).
        // Com pasta sticky 3770, PHP-FPM não consegue remover este marcador.
        igua_updater_vps_write('running.json',$request);
        if(!@unlink(igua_updater_vps_path('request.json')))
            throw new RuntimeException('Não foi possível consumir a solicitação.');
        return $request;
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
/** Invocado somente por bin/aws-deploy-worker.php, como usuário de implantação. */
function igua_updater_vps_run(): void {
    igua_updater_vps_assert_worker();
    $health=igua_updater_vps_worker_probe();
    igua_updater_vps_write('worker.json',['at'=>time()]+$health);
    if(!$health['ready']){error_log('IGUA VPS DEPLOY: vistoria da conta dedicada falhou. Consulte bin/aws-deploy-check.php.');return;}
    $request=igua_updater_vps_take();if($request===null)return;
    $zip=null;
    try{
        $notify=static function(string $stage,string $label)use($request):void {igua_updater_vps_status_update($request,$stage,$label);};
        // Fonte IMUTÁVEL do projeto; nunca aceita URL, branch, repo ou ZIP da fila/web.
        $source=igua_updater_source();
        if(strcasecmp((string)$source['owner'].'/'.(string)$source['repo'],'drilemoz/Meu-Site')!==0)
            throw new RuntimeException('A origem da VPS deve ser o repositório oficial do portal.');
        $notify('connecting','Conferindo a última release oficial do GitHub.');
        $release=igua_updater_remote_release(true);
        if(!$release||empty($release['newer'])||empty($release['url']))throw new RuntimeException('Sem nova release oficial válida.');
        $notify('download','Baixando o ZIP oficial na pasta privada.');
        $zip=igua_updater_download_release($release['url']);
        $result=igua_updater_install_zip($zip,db(),$notify);
        $notify('complete','Versão '.$result['version'].' instalada; backup '. $result['backup'].'.');
    }catch(Throwable $error){
        // Erro genérico ao navegador. Detalhes somente no journalctl do serviço.
        error_log('IGUA VPS DEPLOY: '.get_class($error).': '.$error->getMessage());
        igua_updater_vps_status_update($request,'failed','Implantação não confirmada. Verifique o journalctl e o histórico privado antes de tentar novamente.');
    }finally{
        if(is_string($zip)&&is_file($zip))@unlink($zip);
        // Se processo morreu abruptamente, running.json permanece e impede repetição.
        // A morte após substituição de arquivos exige recuperação assistida.
        if(!function_exists('igua_updater_maintenance_present')||!igua_updater_maintenance_present())
            @unlink(igua_updater_vps_path('running.json'));
    }
}
