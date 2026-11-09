<?php
declare(strict_types=1);
/** Fila estritamente limitada para deploy na VPS. Nenhuma execução de shell via HTTP. */
function igua_updater_vps_mode(): bool { return getenv('IGUA_DEPLOY_VPS')==='1'; }
function igua_updater_vps_dir(): string {
    $dir=IGUA_UPDATE_DIR.'/vps';
    if(is_link($dir)||!is_dir($dir))throw new RuntimeException('Fila de deploy não configurada. Consulte docs/AWS_EC2_DEPLOY.md.');
    return $dir;
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
        if(is_file(igua_updater_vps_path('request.json'))||is_file(igua_updater_vps_path('running.json')))
            throw new RuntimeException('Já existe uma atualização pendente. Não reenvie a solicitação.');
        $worker=igua_updater_vps_read('worker.json');
        if(!is_array($worker)||!is_int($worker['at']??null)||$worker['at']<time()-180)
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
    if(PHP_SAPI!=='cli'||!igua_updater_vps_mode()||(function_exists('posix_geteuid')&&posix_geteuid()===0))
        throw new RuntimeException('Deploy exige PHP CLI como conta não-root, fora do processo web.');
    igua_updater_vps_write('worker.json',['at'=>time()]);
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
        @unlink(igua_updater_vps_path('running.json'));
    }
}
