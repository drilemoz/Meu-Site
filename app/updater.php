<?php
declare(strict_types=1);
/**
 * Atualizador de código do Meu Site. Aceita ZIP v2 sem assinatura após
 * reautenticação do administrador; pacotes assinados legados v1 continuam
 * autenticados pela chave pública original. SHA-256 verifica integridade, NÃO
 * a autoria de pacotes sem assinatura. Nunca executa comandos de shell.
 * A revisão do banco deve permanecer igual (migrações exigem release assistida).
 */
const IGUA_UPDATE_PUBLIC_KEY_B64 = 'zfq5ZuBvX88sMlOrIrKuvQwDV/OTYP8SQzkIogBmPAY=';
const IGUA_UPDATE_FORMAT = 'meu-site-update-v2';
const IGUA_UPDATE_LEGACY_FORMAT = 'meu-site-update-v1';
const IGUA_UPDATE_MAX_ZIP = 32 * 1024 * 1024;
const IGUA_UPDATE_MAX_TOTAL = 80 * 1024 * 1024;
const IGUA_UPDATE_MAX_FILES = 800;
const IGUA_UPDATE_DIR = IGUA_STORAGE . '/updater';

function igua_updater_version(): string {
    $v=trim((string)@file_get_contents(IGUA_ROOT.'/VERSION'));
    return preg_match('/^\d+\.\d+\.\d+$/D',$v)?$v:'0.0.0';
}
function igua_updater_folder(): string {
    if(is_link(IGUA_UPDATE_DIR) || (!is_dir(IGUA_UPDATE_DIR) && !@mkdir(IGUA_UPDATE_DIR,0700,true)))
        throw new RuntimeException('Diretório privado do atualizador indisponível.');
    if(!is_dir(IGUA_UPDATE_DIR)||!is_writable(IGUA_UPDATE_DIR))throw new RuntimeException('Atualizador sem permissão de escrita.');
    return IGUA_UPDATE_DIR;
}
function igua_updater_write_json(string $path,array $data): void {
    if(is_link($path))throw new RuntimeException('Arquivo privado inesperado.');
    $tmp=@tempnam(dirname($path),'.up-');
    if($tmp===false)throw new RuntimeException('Sem espaço para registrar atualização.');
    try{
        @chmod($tmp,0600);
        $encoded=json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
        if(file_put_contents($tmp,$encoded,LOCK_EX)!==strlen($encoded)||!@rename($tmp,$path))throw new RuntimeException('Falha ao registrar atualização.');
    }finally{if(is_file($tmp))@unlink($tmp);}
}
function igua_updater_read_json(string $path): ?array {
    if(!is_file($path)||is_link($path))return null;
    $data=json_decode((string)@file_get_contents($path),true);
    return is_array($data)?$data:null;
}
/** Consulta de progresso da atualização, vinculada ao usuário autenticado e token aleatório. */
function igua_updater_progress_file(int $userId,string $token): ?string {
    if($userId<1||!preg_match('/^[a-f0-9]{32}$/D',$token))return null;
    return igua_updater_folder().'/progress-'.hash('sha256',$userId.':'.$token).'.json';
}
function igua_updater_progress_write(int $userId,string $token,string $stage,string $message): void {
    if(!in_array($stage,['connecting','download','validating','preflight','backup','applying','complete','failed'],true))return;
    try{
        $path=igua_updater_progress_file($userId,$token);
        if($path!==null)igua_updater_write_json($path,['stage'=>$stage,'message'=>substr($message,0,150),'at'=>time()]);
    }catch(Throwable $ignored){ /* Falha no indicador nunca deve comprometer a instalação. */ }
}
function igua_updater_progress_read(int $userId,string $token): ?array {
    $path=igua_updater_progress_file($userId,$token);
    if($path===null)return null;
    $data=igua_updater_read_json($path);
    if(!$data||!is_int($data['at']??null)||$data['at']<time()-1800)return null;
    return ['stage'=>(string)($data['stage']??''),'message'=>(string)($data['message']??''),'at'=>$data['at']];
}
function igua_updater_safe_path(string $path): bool {
    if(strlen($path)<3||strlen($path)>230||str_contains($path,'\\')||str_contains($path,"\0"))return false;
    if($path==='VERSION')return true;
    if($path==='public/ads.txt')return false; // arquivo de propriedade do editor, nunca sobrescrever
    if(!str_starts_with($path,'app/')&&!str_starts_with($path,'public/'))return false;
    if(!preg_match('~^[A-Za-z0-9._/-]+$~D',$path))return false;
    $parts=explode('/',$path);
    foreach($parts as $part){if($part===''||$part==='.'||$part==='..')return false;}
    if(str_starts_with($path,'public/uploads/')||str_contains($path,'/storage/')||str_contains($path,'/backups/'))return false;
    if(preg_match('~\.(?:key|pem|env|sql|sqlite|zip|tar|gz|p12|jks)$~iD',$path))return false;
    return true;
}
function igua_updater_check_manifest(string $raw,?string $signature=null,?string $pub=null): array {
    if(strlen($raw)>140000)throw new RuntimeException('Manifesto muito grande.');
    $m=json_decode($raw,true,128,JSON_THROW_ON_ERROR);
    $format=is_array($m)?($m['format']??null):null;
    if(!in_array($format,[IGUA_UPDATE_FORMAT,IGUA_UPDATE_LEGACY_FORMAT],true))
        throw new RuntimeException('Formato de atualização não reconhecido.');
    // Um ZIP legado v1 NUNCA pode perder a assinatura por remoção de release.sig.
    if($format===IGUA_UPDATE_LEGACY_FORMAT && $signature===null)
        throw new RuntimeException('Pacote legado exige assinatura digital Ed25519.');
    if($signature!==null){
        if(!function_exists('sodium_crypto_sign_verify_detached'))
            throw new RuntimeException('Extensão Sodium indisponível para validar assinatura.');
        $key=base64_decode($pub??IGUA_UPDATE_PUBLIC_KEY_B64,true);
        if(!is_string($key)||strlen($key)!==SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES ||
           strlen($signature)!==SODIUM_CRYPTO_SIGN_BYTES ||
           !sodium_crypto_sign_verify_detached($signature,$raw,$key))
            throw new RuntimeException('Assinatura digital inválida. Pacote recusado.');
    }
    if(!is_array($m)||!is_string($m['version']??null)||
       !preg_match('/^\d+\.\d+\.\d+$/D',$m['version']) || !isset($m['files'])||!is_array($m['files'])||count($m['files'])<2||count($m['files'])>IGUA_UPDATE_MAX_FILES)
        throw new RuntimeException('Manifesto de atualização inválido.');
    if(!isset($m['schema_revision'])||!is_int($m['schema_revision'])||$m['schema_revision']<1)
        throw new RuntimeException('Revisão do banco não informada.');
    if(!isset($m['min_php'])||!is_string($m['min_php'])||!preg_match('/^\d+\.\d+\.\d+$/D',$m['min_php']))
        throw new RuntimeException('Versão mínima PHP inválida.');
    if(version_compare(PHP_VERSION,$m['min_php'],'<'))throw new RuntimeException('Atualização exige PHP '.$m['min_php'].' ou superior.');
    $total=0;$paths=[];
    foreach($m['files'] as $entry){
        if(!is_array($entry)||!is_string($entry['path']??null)||!igua_updater_safe_path($entry['path'])||
           !is_string($entry['sha256']??null)||!preg_match('/^[a-f0-9]{64}$/D',$entry['sha256'])||
           !is_int($entry['size']??null)||$entry['size']<0||$entry['size']>12*1024*1024)
            throw new RuntimeException('Manifesto contém caminho ou tamanho inválido.');
        if(isset($paths[$entry['path']]))throw new RuntimeException('Manifesto contém arquivo duplicado.');
        $paths[$entry['path']]=true;
        $total+=$entry['size'];
        if($total>IGUA_UPDATE_MAX_TOTAL)throw new RuntimeException('Pacote descompactado excede limite.');
    }
    if(!isset($paths['VERSION'],$paths['public/admin.php'],$paths['app/updater.php']))
        throw new RuntimeException('Pacote incompleto: faltam arquivos essenciais.');
    $m['signature_status']=$signature===null?'unsigned':'signed';
    return $m;
}
function igua_updater_unzip_verified(string $zipPath,string $stage): array {
    if(!class_exists('ZipArchive'))throw new RuntimeException('A extensão ZIP do PHP precisa estar ativa.');
    if(is_link($zipPath)||!is_file($zipPath)||filesize($zipPath)>IGUA_UPDATE_MAX_ZIP)
        throw new RuntimeException('Pacote ZIP inválido ou acima do limite de 32 MB.');
    $zip=new ZipArchive();
    if($zip->open($zipPath)!==true)throw new RuntimeException('Não foi possível abrir o ZIP.');
    try {
        if($zip->numFiles<3||$zip->numFiles>IGUA_UPDATE_MAX_FILES+2)throw new RuntimeException('Quantidade de arquivos inválida.');
        $raw=$zip->getFromName('release.json');$sig=$zip->getFromName('release.sig');
        if(!is_string($raw)||($sig!==false&&!is_string($sig)))
            throw new RuntimeException('Manifesto ou assinatura do ZIP inválidos.');
        $signed=is_string($sig);
        $m=igua_updater_check_manifest($raw,$signed?$sig:null);
        if(version_compare($m['version'],igua_updater_version(),'='))
            throw new RuntimeException('A versão '.$m['version'].' já está instalada. Nenhuma alteração foi necessária.');
        if(version_compare($m['version'],igua_updater_version(),'<'))
            throw new RuntimeException('Pacote antigo: a versão instalada é '.igua_updater_version().'. Nenhuma alteração foi necessária.');
        if(!defined('IGUA_SCHEMA_REVISION')||$m['schema_revision']!==IGUA_SCHEMA_REVISION)
            throw new RuntimeException('Esta atualização altera a revisão do banco. Exige instalação assistida e backup externo.');
        $expected=[];foreach($m['files'] as $f)$expected[$f['path']]=$f;
        $seen=[];
        for($i=0;$i<$zip->numFiles;$i++){
            $stat=$zip->statIndex($i);
            if(!is_array($stat))throw new RuntimeException('ZIP com entrada inválida.');
            $name=$stat['name'];
            if(isset($seen[$name]))throw new RuntimeException('ZIP contém entrada repetida.');
            $seen[$name]=true;
            if($name==='release.json'||$name==='release.sig')continue;
            if(!isset($expected[$name])||!igua_updater_safe_path($name)||$stat['size']!==$expected[$name]['size'])
                throw new RuntimeException('ZIP contém arquivo inesperado ou incorreto.');
            $external=$zip->getExternalAttributesIndex($i,$opsys,$attrs);
            if($external && $opsys===ZipArchive::OPSYS_UNIX && (($attrs>>16)&0170000)===0120000)
                throw new RuntimeException('Links simbólicos não são permitidos no ZIP.');
        }
        if(count($seen)!==count($expected)+($signed?2:1))throw new RuntimeException('Pacote incompleto.');
        if(!@mkdir($stage,0700,true))throw new RuntimeException('Não foi possível preparar área privada de atualização.');
        foreach($expected as $path=>$f){
            $target=$stage.'/'.$path;
            if(!is_dir(dirname($target))&&!@mkdir(dirname($target),0700,true))throw new RuntimeException('Falha ao preparar pacote.');
            $input=$zip->getStream($path);$output=@fopen($target,'wb');
            if(!$input||!$output){if(is_resource($input))fclose($input);if(is_resource($output))fclose($output);throw new RuntimeException('Falha ao extrair pacote.');}
            try { $written=stream_copy_to_stream($input,$output,$f['size']+1); }
            finally {fclose($input);fclose($output);}
            if($written!==$f['size'] || !hash_equals($f['sha256'],hash_file('sha256',$target)))
                throw new RuntimeException('Integridade SHA-256 divergente: '.$path);
        }
        $got=trim((string)file_get_contents($stage.'/VERSION'));
        if($got!==$m['version'])throw new RuntimeException('Versão interna do pacote divergente.');
        return $m;
    }finally{$zip->close();}
}
function igua_updater_delete_tree(string $path): void {
    if(!is_dir($path)||is_link($path))return;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $file){$p=$file->getPathname();if($file->isLink()||$file->isFile())@unlink($p);else @rmdir($p);}
    @rmdir($path);
}
function igua_updater_checked_target(string $path): string {
    if(!igua_updater_safe_path($path))throw new RuntimeException('Caminho não autorizado.');
    $target=IGUA_ROOT.'/'.$path;$dir=dirname($target);
    $base=realpath(IGUA_ROOT);
    if(!$base||is_link(IGUA_ROOT))throw new RuntimeException('Raiz de projeto insegura.');
    $relative=explode('/',dirname($path));$cursor=IGUA_ROOT;
    foreach($relative as $segment){
        if($segment==='.')continue;
        $cursor.='/'.$segment;
        if(is_link($cursor))throw new RuntimeException('Link simbólico na pasta de destino.');
        if(!file_exists($cursor)&&!@mkdir($cursor,0755))throw new RuntimeException('Não foi possível criar pasta de destino.');
        if(!is_dir($cursor))throw new RuntimeException('Pasta de destino inválida.');
    }
    if(is_link($target)|| (file_exists($target)&&!is_file($target)))throw new RuntimeException('Arquivo de destino inesperado.');
    return $target;
}
function igua_updater_replace(string $source,string $destination): void {
    $tmp=@tempnam(dirname($destination),'.deploy-');
    if($tmp===false)throw new RuntimeException('Não foi possível criar arquivo temporário no destino.');
    try{
        $in=@fopen($source,'rb');$out=@fopen($tmp,'wb');
        if(!$in||!$out)throw new RuntimeException('Falha de abertura ao atualizar arquivo.');
        try{$n=stream_copy_to_stream($in,$out);}finally{fclose($in);fclose($out);}
        if($n!==filesize($source)||!@chmod($tmp,0644)||!@rename($tmp,$destination))
            throw new RuntimeException('Falha ao substituir arquivo do portal.');
    }finally{if(is_file($tmp))@unlink($tmp);}
}
function igua_updater_backup_code(array $m,string $dir): array {
    $entries=[];
    foreach($m['files'] as $file){
        $path=$file['path'];$dest=igua_updater_checked_target($path);
        $exists=is_file($dest);
        $snap=$dir.'/old/'.$path;
        if($exists){
            if(!is_dir(dirname($snap))&&!@mkdir(dirname($snap),0700,true))throw new RuntimeException('Falha ao preparar cópia de segurança.');
            if(!@copy($dest,$snap))throw new RuntimeException('Não foi possível salvar código original.');
            @chmod($snap,0600);
            if(hash_file('sha256',$dest)!==hash_file('sha256',$snap))throw new RuntimeException('Backup de código não foi validado.');
        }
        $entries[]=['path'=>$path,'exists'=>$exists,'sha256'=>$exists?hash_file('sha256',$snap):null,'new_sha256'=>$file['sha256']];
    }
    return $entries;
}
function igua_updater_rollback(string $jobDir,array $journal): void {
    // Não desfaz alterações manuais feitas depois de uma atualização concluída.
    if(($journal['status']??'')==='complete'){
        foreach($journal['files']??[] as $entry){
            $path=igua_updater_checked_target($entry['path']);
            if(!is_file($path)||!hash_equals((string)($entry['new_sha256']??''),hash_file('sha256',$path)))
                throw new RuntimeException('Arquivos foram modificados após a atualização. Não é seguro restaurar automaticamente.');
        }
    }
    foreach(array_reverse($journal['files']??[]) as $entry){
        if(!is_array($entry)||!is_string($entry['path']??null)||!igua_updater_safe_path($entry['path']))
            throw new RuntimeException('Registro de recuperação inválido.');
        $dest=igua_updater_checked_target($entry['path']);
        if($entry['exists']??false){
            $old=$jobDir.'/old/'.$entry['path'];
            if(!is_file($old)||!is_string($entry['sha256']??null)||!hash_equals($entry['sha256'],hash_file('sha256',$old)))
                throw new RuntimeException('Arquivo de recuperação ausente ou adulterado.');
            igua_updater_replace($old,$dest);
            if(function_exists('opcache_invalidate') && str_ends_with($dest,'.php'))@opcache_invalidate($dest,true);
        }else{
            if(is_file($dest)&&!@unlink($dest))throw new RuntimeException('Falha ao retirar arquivo novo na recuperação.');
        }
    }
}
function igua_updater_maintenance(): string {return IGUA_STORAGE.'/.update-maintenance';}
function igua_updater_lock(){
    $fp=@fopen(igua_updater_folder().'/.lock','c+b');
    if(!$fp||!flock($fp,LOCK_EX|LOCK_NB))throw new RuntimeException('Outra atualização já está em execução.');
    return $fp;
}
function igua_updater_last_job(): ?array {
    $index=igua_updater_read_json(igua_updater_folder().'/last.json');
    if(!$index||!is_string($index['id']??null)||!preg_match('/^[a-f0-9]{24}$/D',$index['id']))return null;
    $journal=igua_updater_read_json(IGUA_UPDATE_DIR.'/jobs/'.$index['id'].'/state.json');
    return $journal?['dir'=>IGUA_UPDATE_DIR.'/jobs/'.$index['id'],'journal'=>$journal]:null;
}
/**
 * Analisa os caminhos de um manifesto validado sem alterar arquivos locais.
 * A comparação é informativa: "alterado" não significa que o código local foi
 * adulterado, apenas que difere da nova versão que se deseja instalar.
 */
function igua_updater_inspect_path(string $relative,?string $base=null): ?string {
    if(!igua_updater_safe_path($relative))return null;
    $root=$base??IGUA_ROOT;
    if(is_link($root)||!is_dir($root))return null;
    $cursor=$root;
    foreach(explode('/',$relative) as $part){
        $cursor.='/'.$part;
        if(is_link($cursor))return null;
    }
    return $cursor;
}
function igua_updater_file_changes(array $manifest,?string $base=null): array {
    $counts=['new'=>0,'changed'=>0,'identical'=>0,'unsafe'=>0];$examples=[];
    foreach(($manifest['files']??[]) as $file){
        $path=is_array($file)?($file['path']??null):null;
        $sha=is_array($file)?($file['sha256']??null):null;
        $size=is_array($file)?($file['size']??null):null;
        if(!is_string($path)||!is_string($sha)||!preg_match('/^[a-f0-9]{64}$/D',$sha)||!is_int($size)||$size<0) {
            $counts['unsafe']++;continue;
        }
        $target=igua_updater_inspect_path($path,$base);
        if($target===null || (file_exists($target)&&!is_file($target))){$type='unsafe';}
        elseif(!is_file($target)){$type='new';}
        elseif(filesize($target)===$size && hash_equals($sha,(string)@hash_file('sha256',$target))){$type='identical';}
        else{$type='changed';}
        $counts[$type]++;
        if($type!=='identical'&&count($examples)<12)$examples[]=['path'=>$path,'type'=>$type];
    }
    return ['counts'=>$counts,'examples'=>$examples,'total'=>array_sum($counts)];
}
/** Verifica os hashes que o journal marcou como instalados na última atualização. */
function igua_updater_installed_integrity(?array $journal=null,?string $base=null): array {
    if($journal===null){$last=igua_updater_last_job();$journal=$last['journal']??null;}
    if(!is_array($journal)||($journal['status']??'')!=='complete'||!is_array($journal['files']??null))
        return ['available'=>false,'status'=>'unavailable'];
    $root=$base??IGUA_ROOT;
    $version=trim((string)@file_get_contents($root.'/VERSION'));
    if($version!==($journal['to']??null))return ['available'=>false,'status'=>'version_mismatch'];
    $total=0;$verified=0;$issues=[];
    foreach($journal['files'] as $entry){
        if(++$total>IGUA_UPDATE_MAX_FILES)break;
        $path=is_array($entry)?($entry['path']??null):null;
        $sha=is_array($entry)?($entry['new_sha256']??null):null;
        if(!is_string($path)||!is_string($sha)||!preg_match('/^[a-f0-9]{64}$/D',$sha)){$status='unsafe';}
        else{
            $target=igua_updater_inspect_path($path,$root);
            if($target===null)$status='unsafe';
            elseif(!is_file($target))$status='missing';
            elseif(!hash_equals($sha,(string)@hash_file('sha256',$target)))$status='changed';
            else{$status='ok';$verified++;}
        }
        if($status!=='ok'&&count($issues)<8)$issues[]=['path'=>is_string($path)?$path:'(inválido)','status'=>$status];
    }
    if($total===0||count($journal['files'])>IGUA_UPDATE_MAX_FILES)return ['available'=>false,'status'=>'invalid_journal'];
    return ['available'=>true,'status'=>$verified===$total?'ok':'changed','total'=>$total,'verified'=>$verified,'issues'=>$issues];
}
/** Retorna as operações recentes sem expor nomes de backups ou dados privados. */
function igua_updater_history(int $limit=6,?string $jobsRoot=null): array {
    $limit=max(1,min(10,$limit));$root=$jobsRoot??IGUA_UPDATE_DIR.'/jobs';
    if(!is_dir($root)||is_link($root))return [];
    $candidates=[];$scanned=0;
    try{$iterator=new DirectoryIterator($root);}catch(Throwable $e){return [];}
    foreach($iterator as $dir){
        if(++$scanned>2000)break;
        if(!$dir->isDir()||$dir->isLink()||!preg_match('/^[a-f0-9]{24}$/D',$dir->getFilename()))continue;
        $state=$dir->getPathname().'/state.json';
        if(!is_file($state)||is_link($state)||(int)@filesize($state)>512*1024)continue;
        $candidates[]=['path'=>$state,'mtime'=>(int)@filemtime($state)];
    }
    usort($candidates,static fn($a,$b)=>$b['mtime']<=>$a['mtime']);
    $history=[];
    foreach(array_slice($candidates,0,max(25,$limit*3)) as $candidate){
        $data=igua_updater_read_json($candidate['path']);
        if(!is_array($data)||!preg_match('/^[a-f0-9]{24}$/D',(string)($data['id']??''))||
           $data['id']!==basename(dirname($candidate['path']))||
           !preg_match('/^\d+\.\d+\.\d+$/D',(string)($data['from']??''))||
           !preg_match('/^\d+\.\d+\.\d+$/D',(string)($data['to']??'')))continue;
        $state=(string)($data['status']??'');
        if(!in_array($state,['preparing','prepared','applying','complete','rolled_back','recovery_needed'],true))continue;
        $history[]=['id'=>$data['id'],'from'=>$data['from'],'to'=>$data['to'],'status'=>$state,'at'=>is_string($data['at']??null)?$data['at']:''];
        if(count($history)>=$limit)break;
    }
    return $history;
}

/**
 * Diagnóstico sem escrita: o painel não muda arquivos nem o banco ao vistoriar.
 * O status "aviso" descreve limites não verificáveis (ex.: cota da hospedagem),
 * e nunca deve ser mostrado como um teste real de backup ou deploy bem-sucedido.
 */
function igua_updater_preflight(?PDO $db=null, ?array $manifest=null): array {
    $checks=[];
    $add=static function(string $name,string $status,string $detail)use(&$checks):void {
        $checks[]=['name'=>$name,'status'=>$status,'detail'=>$detail];
    };
    $add('PHP 8.1+',version_compare(PHP_VERSION,'8.1.0','>=')?'ok':'falha','Versão detectada: '.PHP_VERSION);
    foreach(['zip'=>'Leitura e validação de ZIP','pdo_mysql'=>'Conexão PDO MySQL'] as $ext=>$label){
        $add($label,extension_loaded($ext)?'ok':'falha',$ext.' '.(extension_loaded($ext)?'disponível':'não disponível'));
    }
    $add('Ed25519 legado',extension_loaded('sodium')?'ok':'aviso',extension_loaded('sodium')?'Pacotes antigos assinados podem ser verificados':'Pacotes antigos assinados exigem Sodium; pacotes v2 sem assinatura continuam disponíveis');
    $add('cURL para GitHub',extension_loaded('curl')?'ok':'aviso',extension_loaded('curl')?'Download direto disponível':'Sem cURL: use somente o envio manual de ZIP');
    $storage=realpath(IGUA_STORAGE);
    $public=realpath(IGUA_PUBLIC);
    $safeStorage=$storage!==false&&$public!==false&&$storage!==$public&&!str_starts_with($storage,$public.'/');
    $add('Dados privados fora da web',$safeStorage?'ok':'falha',$safeStorage?'storage está fora da raiz pública':'Não foi possível comprovar isolamento de storage');
    $layout=realpath(IGUA_ROOT.'/public');
    $supported=$layout!==false&&$public!==false&&$layout===$public;
    $add('Layout de publicação',$supported?'ok':'falha',$supported?'Código público em public/':'O motor atual usa IGUA_ROOT/public; outro layout exige atualização assistida');
    $root=realpath(IGUA_ROOT);
    $add('Pasta do código',$root!==false&&!is_link(IGUA_ROOT)?'ok':'falha',$root!==false?'Diretório existente':'Raiz do projeto ausente ou por link simbólico');
    foreach(['storage'=>IGUA_STORAGE,'backups'=>IGUA_STORAGE.'/backups','atualizador'=>IGUA_UPDATE_DIR] as $label=>$path){
        $valid=!is_link($path)&&(is_dir($path)?is_writable($path):(is_dir(dirname($path))&&!is_link(dirname($path))&&is_writable(dirname($path))));
        $add('Escrita em '.$label,$valid?'ok':'falha',$valid?'Permissão aparente adequada':'Pasta ausente sem possibilidade aparente de criação/escrita');
    }
    if($db!==null){
        try{$ok=$db->query('SELECT 1')->fetchColumn()!==false;$add('Leitura do MySQL',$ok?'ok':'falha',$ok?'SELECT 1 executado':'Consulta não retornou resultado');}
        catch(Throwable $e){$add('Leitura do MySQL','falha','Não foi possível consultar o banco; verifique a conexão no painel');}
    }else{$add('Leitura do MySQL','aviso','Banco não testado nesta vistoria');}
    if(is_file(igua_updater_maintenance()))$add('Atualização pendente','falha','Modo de manutenção ativo: conclua a recuperação antes de instalar');
    else $add('Atualização pendente','ok','Nenhuma manutenção ativa');
    $paths=$manifest['files']??[['path'=>'VERSION'],['path'=>'app/updater.php'],['path'=>'public/admin.php']];
    $bad=[];$examined=0;
    foreach($paths as $entry){
        $rel=$entry['path']??null;
        if(!is_string($rel)||!igua_updater_safe_path($rel)){$bad[]='caminho inválido';continue;}
        $examined++;
        $target=IGUA_ROOT.'/'.$rel;$parent=dirname($target);$cursor=$parent;$unsafe=false;
        // Sem mkdir neste diagnóstico: verifica o ancestral existente.
        for($i=0;$i<32;$i++){
            if(is_link($cursor)){$unsafe=true;break;}
            if(file_exists($cursor))break;
            $next=dirname($cursor);
            if($next===$cursor){$unsafe=true;break;}
            $cursor=$next;
        }
        if($unsafe||!is_dir($cursor)||is_link($target)||
           (file_exists($target)&&(!is_file($target)||!is_writable($target)))||
           !is_writable($cursor)){
            if(count($bad)<5)$bad[]=$rel;
        }
    }
    $add('Escrita do código',$bad?'falha':'ok',$bad?'Sem permissão ou caminho inseguro: '.implode(', ',$bad):'Permissões aparentes para '.$examined.' arquivo(s)');
    $disk=@disk_free_space(IGUA_STORAGE);
    if($disk===false)$add('Espaço disponível','aviso','Não foi possível consultar o espaço; a cota contratual deve ser verificada no Plesk');
    else {
        $mib=(int)floor($disk/(1024*1024));
        $add('Espaço disponível',$mib>=160?'ok':'aviso',$mib.' MiB livres informados pelo sistema; isso não garante a cota real da hospedagem');
    }
    $memory=ini_get('memory_limit');$seconds=(int)ini_get('max_execution_time');
    $memoryBytes=igua_updater_ini_bytes((string)$memory);
    $add('Limites de execução',($memoryBytes===-1||$memoryBytes>=128*1024*1024)&&($seconds===0||$seconds>=60)?'ok':'aviso',
        'memory_limit='.($memory!==false?$memory:'desconhecido').'; max_execution_time='.$seconds.' s. O provedor pode encerrar processos antes disso');
    $fails=count(array_filter($checks,static fn($c)=>$c['status']==='falha'));
    $warnings=count(array_filter($checks,static fn($c)=>$c['status']==='aviso'));
    return ['ok'=>$fails===0,'failures'=>$fails,'warnings'=>$warnings,'checks'=>$checks];
}
function igua_updater_ini_bytes(string $value): int {
    $value=trim($value);
    if($value==='-1')return -1;
    if(!preg_match('/^(\d+)([KMG]?)$/iD',$value,$m))return 0;
    $n=(int)$m[1];$factor=[''=>1,'K'=>1024,'M'=>1048576,'G'=>1073741824];
    return $n*$factor[strtoupper($m[2])];
}
/** Simula uma atualização sem substituir código e sem gravar no MySQL. */
function igua_updater_preview_zip(string $zipPath, ?PDO $db=null): array {
    $dir=igua_updater_folder().'/preview-'.bin2hex(random_bytes(12));
    try {
        $m=igua_updater_unzip_verified($zipPath,$dir);
        $report=igua_updater_preflight($db,$m);
        return ['version'=>$m['version'],'files'=>count($m['files']),'signature_status'=>$m['signature_status'],'preflight'=>$report,'changes'=>igua_updater_file_changes($m)];
    } finally {igua_updater_delete_tree($dir);}
}
function igua_updater_install_zip(string $zipPath,PDO $db,?callable $onStage=null): array {
    $lock=igua_updater_lock();$jobDir='';$journal=[];$maintenance=false;
    try{
        if(is_file(igua_updater_maintenance()))throw new RuntimeException('Instalação anterior interrompida. Faça a recuperação antes de atualizar novamente.');
        $id=bin2hex(random_bytes(12));$jobDir=IGUA_UPDATE_DIR.'/jobs/'.$id;
        if(!@mkdir($jobDir,0700,true))throw new RuntimeException('Não foi possível preparar cópia privada.');
        $onStage && $onStage('validating','Validando manifesto, caminhos e integridade do ZIP.');
        $m=igua_updater_unzip_verified($zipPath,$jobDir.'/new');
        $onStage && $onStage('preflight','Conferindo compatibilidade e permissões do servidor.');
        $preflight=igua_updater_preflight($db,$m);
        if(!$preflight['ok'])throw new RuntimeException('Pré-atualização bloqueada: corrija '.implode(', ',array_column(array_values(array_filter($preflight['checks'],static fn($check)=>$check['status']==='falha')),'name')).'.');
        $journal=['id'=>$id,'from'=>igua_updater_version(),'to'=>$m['version'],'status'=>'preparing','at'=>date(DATE_ATOM),'files'=>[],'db_backup'=>null,'signature_status'=>$m['signature_status']];
        $onStage && $onStage('backup','Criando cópias do código e do banco antes de instalar.');
        $entries=igua_updater_backup_code($m,$jobDir);
        $journal['files']=$entries;
        // Banco é copiado antes de qualquer alteração do código.
        $backup=make_backup($db,'manual');
        $journal['db_backup']=$backup;
        $journal['status']='prepared';
        igua_updater_write_json($jobDir.'/state.json',$journal);
        igua_updater_write_json(IGUA_UPDATE_DIR.'/last.json',['id'=>$id]);
        if(file_put_contents(igua_updater_maintenance(),$id,LOCK_EX)!==strlen($id)){@unlink(igua_updater_maintenance());throw new RuntimeException('Falha ao ativar modo de manutenção.');}
        @chmod(igua_updater_maintenance(),0600);$maintenance=true;
        $journal['status']='applying';igua_updater_write_json($jobDir.'/state.json',$journal);
        $onStage && $onStage('applying','Substituindo arquivos verificados do portal.');
        foreach($entries as $entry){
            if($entry['path']==='VERSION')continue;
            $path=$entry['path'];igua_updater_replace($jobDir.'/new/'.$path,igua_updater_checked_target($path));
            if(function_exists('opcache_invalidate') && str_ends_with($path,'.php'))@opcache_invalidate(IGUA_ROOT.'/'.$path,true);
        }
        igua_updater_replace($jobDir.'/new/VERSION',igua_updater_checked_target('VERSION'));
        $journal['status']='complete';igua_updater_write_json($jobDir.'/state.json',$journal);
        @unlink(igua_updater_maintenance());$maintenance=false;
        igua_updater_delete_tree($jobDir.'/new');
        @unlink(IGUA_UPDATE_DIR.'/release-cache.json');
        $onStage && $onStage('complete','Atualização instalada e registrada com sucesso.');
        return ['version'=>$m['version'],'backup'=>$backup,'id'=>$id];
    } catch(Throwable $e){
        if($jobDir!=='' && ($journal['status']??'')==='applying'){
            try{igua_updater_rollback($jobDir,$journal);$journal['status']='rolled_back';igua_updater_write_json($jobDir.'/state.json',$journal);if($maintenance)@unlink(igua_updater_maintenance());}
            catch(Throwable $rollbackError){$journal['status']='recovery_needed';igua_updater_write_json($jobDir.'/state.json',$journal);throw new RuntimeException('Atualização interrompida e recuperação pendente. Reabra Atualizações no painel e restaure o código.');}
        }
        throw $e;
    } finally {flock($lock,LOCK_UN);fclose($lock);}
}
function igua_updater_manual_rollback(): void {
    $lock=igua_updater_lock();
    try{
        $job=igua_updater_last_job();
        if(!$job || !in_array(($job['journal']['status']??''),['prepared','complete','recovery_needed','applying'],true))
            throw new RuntimeException('Nenhuma atualização aplicável para recuperar.');
        igua_updater_rollback($job['dir'],$job['journal']);
        $job['journal']['status']='rolled_back';igua_updater_write_json($job['dir'].'/state.json',$job['journal']);
        @unlink(igua_updater_maintenance());
    }finally{flock($lock,LOCK_UN);fclose($lock);}
}
/**
 * Fonte de releases: configuração do administrador tem prioridade. Quando o
 * pacote foi criado com uma origem oficial identificada, podemos ler o arquivo
 * app/update-origin.json, assinado como parte do pacote do portal. Nunca
 * identificar um repositório a partir de github-manager.json: neste projeto
 * ele pode pertencer a outro aplicativo que compartilha o mesmo ZIP.
 */
function igua_updater_parse_repository(string $value): ?array {
    $value=trim($value);
    if(preg_match('~^https://github\.com/([A-Za-z0-9-]{1,39})/([A-Za-z0-9._-]{1,100})(?:\.git)?/?$~D',$value,$m)){
        $value=$m[1].'/'.preg_replace('/\.git$/i','',$m[2]);
    }
    if(!preg_match('~^([A-Za-z0-9-]{1,39})/([A-Za-z0-9._-]{1,100})$~D',$value,$m))return null;
    if(str_contains($m[2],'..')||$m[2]==='.'||$m[2]==='..'||str_ends_with($m[2],'.git'))return null;
    return ['owner'=>$m[1],'repo'=>$m[2]];
}
function igua_updater_source(): array {
    $stored=igua_updater_read_json(IGUA_UPDATE_DIR.'/source.json');
    if(is_array($stored)){
        $source=igua_updater_parse_repository((string)($stored['owner']??'').'/'.(string)($stored['repo']??''));
        if($source!==null)return $source+['origin'=>'manual'];
    }
    // Quando houver metadados específicos do portal, a fonte aparece
    // automaticamente sem recolher credenciais nem consultar repos aleatórios.
    $metadata=igua_updater_read_json(__DIR__.'/update-origin.json');
    if(is_array($metadata)){
        $source=igua_updater_parse_repository((string)($metadata['repository']??''));
        if($source!==null)return $source+['origin'=>'metadata'];
    }
    $environment=getenv('IGUA_UPDATE_REPOSITORY');
    if(is_string($environment)){
        $source=igua_updater_parse_repository($environment);
        if($source!==null)return $source+['origin'=>'environment'];
    }
    return ['owner'=>'','repo'=>'','origin'=>'unconfigured'];
}
function igua_updater_release_asset_name(string $name,string $version): bool {
    return $name==='Atualizacao-v'.$version.'.zip' || $name==='Meu-Site-update-v'.$version.'.zip';
}
function igua_updater_set_source(string $owner,string $repo): void {
    foreach([$owner,$repo] as $part){if(!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]{0,99}$/D',$part)||str_contains($part,'..'))throw new RuntimeException('Nome de conta ou repositório inválido.');}
    igua_updater_write_json(igua_updater_folder().'/source.json',['owner'=>$owner,'repo'=>$repo]);
    @unlink(IGUA_UPDATE_DIR.'/release-cache.json');
}
function igua_updater_reset_source(): void {
    $metadata=igua_updater_read_json(__DIR__.'/update-origin.json');
    if(!is_array($metadata)||igua_updater_parse_repository((string)($metadata['repository']??''))===null)
        throw new RuntimeException('Sem origem oficial válida no pacote para restaurar.');
    $file=igua_updater_folder().'/source.json';
    if(is_file($file)&&!@unlink($file))throw new RuntimeException('Não foi possível remover a origem personalizada.');
    @unlink(IGUA_UPDATE_DIR.'/release-cache.json');
}
function igua_updater_http(string $url,int $max): string {
    if(!function_exists('curl_init'))throw new RuntimeException('Extensão cURL indisponível.');
    $allowed=['api.github.com','github.com','release-assets.githubusercontent.com','objects.githubusercontent.com'];
    for($jump=0;$jump<6;$jump++){
        $parts=parse_url($url);
        if(!$parts||strtolower($parts['scheme']??'')!=='https'||!in_array(strtolower($parts['host']??''),$allowed,true)||isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment']) || isset($parts['port']))
            throw new RuntimeException('Destino de download não autorizado.');
        $ch=curl_init($url);$buffer='';$location='';$overLimit=false;
        curl_setopt_array($ch,[CURLOPT_FOLLOWLOCATION=>false,CURLOPT_RETURNTRANSFER=>false,CURLOPT_TIMEOUT=>25,CURLOPT_CONNECTTIMEOUT=>6,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'MeuSiteUpdater/1.0 (+https://github.com)',CURLOPT_HTTPHEADER=>['Accept: application/vnd.github+json'],CURLOPT_HEADERFUNCTION=>static function($ch,$header)use(&$location){if(stripos($header,'Location:')===0)$location=trim(substr($header,9));return strlen($header);},CURLOPT_WRITEFUNCTION=>static function($ch,$chunk)use(&$buffer,$max,&$overLimit){if(strlen($buffer)+strlen($chunk)>$max){$overLimit=true;return 0;}$buffer.=$chunk;return strlen($chunk);}]);
        $ok=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_RESPONSE_CODE);$err=curl_error($ch);curl_close($ch);
        if($overLimit)throw new RuntimeException('Resposta remota excede limite.');
        if($ok===false)throw new RuntimeException('Falha na consulta HTTPS ao GitHub: '.$err);
        if($status>=300&&$status<400&&$location!==''){$url=$location;continue;}
        if($status!==200)throw new RuntimeException('Consulta à versão no GitHub retornou HTTP '.$status.'.');
        return $buffer;
    }
    throw new RuntimeException('Redirecionamentos demais no download.');
}
function igua_updater_remote_release(bool $force=false): ?array {
    $dir=igua_updater_folder();$source=igua_updater_source();
    if(empty($source['owner'])||empty($source['repo']))return null;
    $cache=igua_updater_read_json($dir.'/release-cache.json');
    if(!$force && $cache && ($cache['checked']??0)>time()-1800){
        $saved=$cache['release']??null;
        if(is_array($saved))$saved['newer']=version_compare((string)($saved['version']??'0.0.0'),igua_updater_version(),'>');
        return $saved;
    }
    $url='https://api.github.com/repos/'.rawurlencode($source['owner']).'/'.rawurlencode($source['repo']).'/releases/latest';
    // Um repositório recém-criado pode ainda não ter releases; isso não é falha de SSL.
    try {$response=igua_updater_http($url,384*1024);}
    catch(RuntimeException $exc) {
        if(str_contains($exc->getMessage(),'HTTP 404')){
            igua_updater_write_json($dir.'/release-cache.json',['checked'=>time(),'release'=>null]);
            return null;
        }
        throw $exc;
    }
    $json=json_decode($response,true);
    if(!is_array($json))throw new RuntimeException('Resposta inválida do GitHub.');
    $tag=ltrim((string)($json['tag_name']??''),'vV');
    if(!preg_match('/^\d+\.\d+\.\d+$/D',$tag))throw new RuntimeException('Release sem versão semântica válida.');
    $assetUrl=null;
    foreach(($json['assets']??[]) as $asset){
        if(!is_array($asset))continue;
        if(igua_updater_release_asset_name((string)($asset['name']??''),$tag) && (int)($asset['size']??0)>0 && (int)$asset['size']<=IGUA_UPDATE_MAX_ZIP){
            $assetUrl=(string)($asset['browser_download_url']??'');break;
        }
    }
    $release=['version'=>$tag,'notes'=>substr((string)($json['body']??''),0,1800),'url'=>$assetUrl,'newer'=>version_compare($tag,igua_updater_version(),'>')];
    igua_updater_write_json($dir.'/release-cache.json',['checked'=>time(),'release'=>$release]);
    return $release;
}
function igua_updater_download_release(string $url): string {
    $dir=igua_updater_folder();$bytes=igua_updater_http($url,IGUA_UPDATE_MAX_ZIP);
    $file=@tempnam($dir,'.download-');if(!$file)throw new RuntimeException('Espaço privado indisponível para download.');
    @chmod($file,0600);
    if(file_put_contents($file,$bytes,LOCK_EX)!==strlen($bytes)){@unlink($file);throw new RuntimeException('Falha ao salvar ZIP recebido.');}
    return $file;
}
