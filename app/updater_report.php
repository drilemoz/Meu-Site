<?php
declare(strict_types=1);
/** Relatório de suporte: apenas campos selecionados, sem credenciais, URLs de download ou dumps. */
function igua_updater_report_event(string $operation,array $result,?Throwable $error=null): void {
    $event=['at'=>date('c'),'operation'=>$operation];
    foreach(['ok','version','package_schema','server_schema','checks','diagnostic','upload_error','size'] as $key)
        if(isset($result[$key]))$event[$key]=$result[$key];
    if($error)$event['location']=['type'=>get_class($error),'file'=>basename($error->getFile()),'line'=>$error->getLine()];
    $_SESSION['igua_update_report_events']=array_slice(array_merge($_SESSION['igua_update_report_events']??[],[$event]),-10);
}
function igua_updater_report_text(PDO $db,array $events): string {
    $lines=['Meu Site — relatório de atualizações', 'Gerado: '.date('c'),'Versão do código: '.igua_updater_version(),'Schema esperado pelo código: '.IGUA_SCHEMA_REVISION];
    try{$lines[]='Schema registrado no banco: '.igua_schema_revision($db);}catch(Throwable $e){$lines[]='Schema registrado: indisponível ('.get_class($e).')';}
    try{$lines[]='Servidor SQL: '.$db->getAttribute(PDO::ATTR_SERVER_VERSION);}catch(Throwable){$lines[]='Servidor SQL: indisponível';}
    $lines[]='PHP: '.PHP_VERSION.' / '.PHP_SAPI.' / '.PHP_OS_FAMILY;
    $lines[]='Modo VPS detectado: '.(igua_updater_vps_mode()?'sim':'não');
    foreach(['memory_limit','max_execution_time','max_input_time','upload_max_filesize','post_max_size','file_uploads'] as $key)$lines[]=$key.': '.ini_get($key);
    foreach(['zip','pdo_mysql','curl','sodium','posix','mbstring'] as $ext)$lines[]='Extensão '.$ext.': '.(extension_loaded($ext)?'sim':'não');
    $lines[]='';$lines[]='=== VISTORIA ATUAL ===';
    try{$p=igua_updater_preflight($db);foreach($p['checks'] as $c)$lines[]=$c['status'].' | '.$c['name'].' | '.$c['detail'];}catch(Throwable $e){$lines[]='Vistoria interrompida: '.get_class($e).' '.basename($e->getFile()).':'.$e->getLine();}
    $lines[]='';$lines[]='=== PERMISSÕES (visão do PHP web) ===';
    if(function_exists('posix_geteuid'))$lines[]='UID/GID do PHP: '.posix_geteuid().'/'.posix_getegid();
    foreach(['.'=>IGUA_ROOT,'VERSION'=>IGUA_ROOT.'/VERSION','app'=>IGUA_ROOT.'/app','public'=>IGUA_PUBLIC,'storage'=>IGUA_STORAGE,'backups'=>IGUA_STORAGE.'/backups','updater'=>IGUA_UPDATE_DIR,'jobs'=>IGUA_UPDATE_DIR.'/jobs','vps'=>IGUA_UPDATE_DIR.'/vps','vps/private'=>IGUA_UPDATE_DIR.'/vps/private','vps/private/.code.lock'=>IGUA_UPDATE_DIR.'/vps/private/.code.lock','vps/.dispatch.lock'=>IGUA_UPDATE_DIR.'/vps/.dispatch.lock','vps/worker.json'=>IGUA_UPDATE_DIR.'/vps/worker.json'] as $label=>$path){
        clearstatcache(true,$path);$st=@lstat($path);
        $lines[]=$label.': '.($st===false?'ausente ou inacessível':sprintf('modo=%04o uid=%d gid=%d leitura=%s escrita=%s execução/travessia=%s link=%s',$st['mode']&07777,$st['uid'],$st['gid'],is_readable($path)?'sim':'não',is_writable($path)?'sim':'não',is_executable($path)?'sim':'não',is_link($path)?'sim':'não'));
    }
    $lines[]='';$lines[]='=== SERVIÇO VPS ===';
    try{$w=igua_updater_vps_read('worker.json');$lines[]='Último sinal: '.(isset($w['at'])&&is_int($w['at'])?date('c',$w['at']):'indisponível');$lines[]='Pronto: '.(($w['ready']??false)===true?'sim':'não');foreach(['root_atomic','app_atomic','public_atomic','updater_atomic','jobs_atomic','backups_atomic','control_atomic','queue_secure','queue_lock','config_read','php_extensions','legacy_clear'] as $key)$lines[]=$key.': '.(isset($w['checks'][$key])?($w['checks'][$key]===true?'OK':'FALHA'):'não informado');
        foreach(['request.json','running.json','status.json'] as $name)$lines[]=$name.': '.(is_file(igua_updater_vps_path($name))?'presente':'ausente');
        $s=igua_updater_vps_read('status.json');$stage=$s['stage']??'';if(in_array($stage,['connecting','download','validating','preflight','backup','applying','complete','failed'],true))$lines[]='Última etapa: '.$stage;
        if(isset($s['at'])&&is_int($s['at']))$lines[]='Etapa registrada: '.date('c',$s['at']);
        if(isset($s['diagnostic'])&&is_array($s['diagnostic'])){foreach(['code','title','detail','action'] as $key)if(is_string($s['diagnostic'][$key]??null))$lines[]=$key.': '.$s['diagnostic'][$key];}
    }catch(Throwable){$lines[]='Fila VPS não disponível para leitura.';}
    $lines[]='';$lines[]='=== GITHUB ===';
    try{$src=igua_updater_source();$lines[]='Origem: '.$src['owner'].'/'.$src['repo'];$lines[]='Credencial cadastrada: '.(igua_updater_github_auth()!==null?'sim':'não');}catch(Throwable){$lines[]='Origem ou credencial indisponível.';}
    $cache=igua_updater_read_json(IGUA_UPDATE_CACHE);$v=$cache['release']['version']??null;if(is_string($v)&&preg_match('/^\d+\.\d+\.\d+$/D',$v))$lines[]='Release em cache: '.$v;
    if(is_int($cache['checked']??null))$lines[]='Consulta em cache: '.date('c',$cache['checked']);
    $lines[]='';$lines[]='=== ÚLTIMAS TENTATIVAS NESTA SESSÃO ===';
    $lines[]= $events?json_encode(array_slice($events,-10),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE):'Nenhuma tentativa registrada nesta sessão.';
    $lines[]='';$lines[]='=== HISTÓRICO DE INSTALAÇÕES ===';
    try{$lines[]=json_encode(igua_updater_history(10),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);}catch(Throwable){$lines[]='Histórico indisponível.';}
    $lines[]='';$lines[]='=== REGISTROS TÉCNICOS RECENTES (saúde do PHP) ===';
    try{foreach(igua_health_log_lines(40) as $line)if(preg_match('/^\d{4}-\d\d-\d\dT[0-9:+-]+ \| [A-Z_0-9]+ \| [a-zA-Z0-9_-]+ \| [a-zA-Z0-9._-]+ \| \d+$/D',$line))$lines[]=$line;}catch(Throwable){$lines[]='Registros indisponíveis.';}
    $lines[]='';$lines[]='Relatório limitado ao que o PHP e o worker publicam. Não inclui senhas, tokens, conteúdo SQL, logs brutos ou journal do sistema.';
    return implode("\n",$lines)."\n";
}
