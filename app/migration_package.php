<?php
declare(strict_types=1);
/** Pacote portátil para transferência entre hospedagens. Nunca inclui configuração privada. */
const IGUA_MIGRATION_FORMAT = 'iguanews-portable-migration';
const IGUA_MIGRATION_DIR = 'storage/migration';
const IGUA_MIGRATION_MAX_CODE_FILES = 800;
const IGUA_MIGRATION_MAX_EXTRA_FILES = 3000;
const IGUA_MIGRATION_MAX_EXTRA_BYTES = 300 * 1024 * 1024;
const IGUA_MIGRATION_MAX_ZIP_BYTES = 700 * 1024 * 1024;

function igua_migration_code_allowed(string $rel): bool {
    if($rel==='VERSION' || $rel==='public/uploads/.htaccess' || $rel==='public/uploads/.gitkeep') return true;
    $parts=explode('/',$rel);
    if(count($parts)<2 || !in_array($parts[0],['app','public','bin'],true)) return false;
    if(in_array($parts[1],['uploads','src','vendor','node_modules'],true) || str_starts_with($parts[1],'.'))return false;
    if(in_array($rel,['app/config.php','app/.setup.lock'],true))return false;
    if($parts[0]==='app' && strtolower(pathinfo($rel,PATHINFO_EXTENSION))==='json' && !in_array($rel,['app/sample_news.json','app/update-origin.json'],true))return false;
    if(in_array($parts[0],['app','public'],true)){
        $allowed=['php','js','css','json','txt','md','svg','png','jpg','jpeg','gif','webp','ico','woff','woff2','ttf','otf','xml','html','htm','pdf','map','htaccess'];
    } else $allowed=['php','sh'];
    $ext=strtolower(pathinfo($rel,PATHINFO_EXTENSION));
    if(!in_array($ext,$allowed,true) && basename($rel)!=='.htaccess')return false;
    foreach($parts as $part)if($part==='' || $part==='.' || $part==='..' || str_starts_with($part,'.') && $part!=='.htaccess' || !preg_match('/\A[a-zA-Z0-9_.-]+\z/D',$part))return false;
    return true;
}
/** Recusa arquivos especiais, redirecionados e diretórios privados. */
function igua_migration_code_files(string $root=IGUA_ROOT): array {
    $files=[];
    foreach(['app','public','bin'] as $top){
        $base=$root.'/'.$top;if(!is_dir($base)||is_link($base))continue;
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY);
        foreach($it as $node){
            $path=$node->getPathname();$rel=substr($path,strlen($root)+1);
            if($node->isLink() || !$node->isFile() || !igua_migration_code_allowed($rel))continue;
            // Diretórios que contêm código externo não são aceitos como fonte do pacote.
            if($node->getSize()>16*1024*1024)throw new RuntimeException('Arquivo de código grande demais para empacotamento: '.basename($rel));
            $files[$rel]=$path;
            if(count($files)>IGUA_MIGRATION_MAX_CODE_FILES)throw new RuntimeException('Código excede limite do pacote de migração.');
        }
    }
    foreach(['VERSION','public/setup.php','app/bootstrap.php','app/backup.php','app/migration_package.php','app/schema.php'] as $required){
        $path=$root.'/'.$required;
        if(!igua_migration_code_allowed($required) || !is_file($path)||is_link($path))throw new RuntimeException('Arquivo necessário à migração não encontrado: '.$required);
        $files[$required]=$path;
    }
    ksort($files,SORT_STRING);
    return $files;
}
function igua_migration_extra_allowed(string $rel): bool {
    if(!preg_match('~\A(?:public/uploads|storage/media)/(?:[a-zA-Z0-9_-]+/){0,8}[a-zA-Z0-9][a-zA-Z0-9_.-]{0,180}\z~D',$rel))return false;
    $ext=strtolower(pathinfo($rel,PATHINFO_EXTENSION));
    return in_array($ext,['jpg','jpeg','png','webp','gif','pdf','txt','csv','json','rtf','docx','xlsx','pptx','odt','ods','odp','mp3','wav','mp4','zip'],true);
}
/** Inclui também mídias não referenciadas, dentro das áreas seguras, sem logs e segredos. */
function igua_migration_extra_files(string $root=IGUA_ROOT): array {
    $files=[];$bytes=0;
    foreach(['public/uploads','storage/media'] as $top){
        $base=$root.'/'.$top;
        if(!is_dir($base)||is_link($base))continue;
        $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::LEAVES_ONLY);
        foreach($it as $node){
            $path=$node->getPathname();$rel=substr($path,strlen($root)+1);
            if($node->isLink() || !$node->isFile() || !igua_migration_extra_allowed($rel))continue;
            // Não seguir links simbólicos nem em diretórios intermediários.
            $current=dirname($path);
            while($current!==$base && str_starts_with($current,$base.'/')){if(is_link($current))throw new RuntimeException('Link simbólico encontrado nas mídias.');$current=dirname($current);}
            $size=$node->getSize();
            if($size<0 || $size>MEDIA_BUNDLE_MAX_MEDIA_BYTES)throw new RuntimeException('Arquivo de mídia muito grande.');
            $bytes+=$size;
            if($bytes>IGUA_MIGRATION_MAX_EXTRA_BYTES || count($files)>=IGUA_MIGRATION_MAX_EXTRA_FILES)throw new RuntimeException('Acervo de arquivos excede limite do pacote portátil.');
            $files[$rel]=$path;
        }
    }
    ksort($files,SORT_STRING);return $files;
}
/** Retorna o ZIP pronto no armazenamento privado. Caller remove depois do download. */
function igua_make_migration_package(PDO $db): string {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Ative PHP ZipArchive para criar pacote portátil.');
    $code=igua_migration_code_files();$extras=igua_migration_extra_files();
    $bundle=make_media_bundle($db); // reutiliza os checks de integridade do backup v12.
    $dir=IGUA_STORAGE.'/backups';$temp=@tempnam($dir,'.bundle-');
    if($temp===false){@unlink($bundle);throw new RuntimeException('Não foi possível reservar espaço temporário.');}
    @chmod($temp,0600);$zip=new ZipArchive();$opened=false;
    try{
        if($zip->open($temp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Não foi possível abrir ZIP portátil.');
        $opened=true;$hash=(string)hash_file('sha256',$bundle);
        $manifest=['format'=>IGUA_MIGRATION_FORMAT,'version'=>1,'site_version'=>trim((string)file_get_contents(IGUA_ROOT.'/VERSION')),'backup'=>'storage/migration/backup.zip','backup_sha256'=>$hash,'extra'=>[]];
        foreach($code as $rel=>$file)if(!$zip->addFile($file,$rel))throw new RuntimeException('Falha ao adicionar código do portal.');
        // O instalador precisa das pastas privadas mesmo quando estiverem vazias.
        foreach(['storage/backups/.gitkeep','storage/media/.gitkeep','storage/security/.gitkeep','storage/logs/.gitkeep','storage/geoip/.gitkeep'] as $keep){
            if(!$zip->addFromString($keep,''))throw new RuntimeException('Não foi possível criar pastas privadas no pacote.');
        }
        if(!$zip->addFromString('storage/.htaccess',"Deny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"))throw new RuntimeException('Não foi possível proteger storage no pacote.');
        if(!$zip->addFile($bundle,'storage/migration/backup.zip'))throw new RuntimeException('Falha ao incluir o backup de dados.');
        foreach($extras as $rel=>$file){
            $manifest['extra'][$rel]=hash_file('sha256',$file);
            if(!$zip->addFile($file,'storage/migration/extras/'.$rel))throw new RuntimeException('Falha ao adicionar mídia ao pacote.');
        }
        if(!$zip->addFromString('storage/migration/manifest.json',json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)))throw new RuntimeException('Falha ao criar manifesto de migração.');
        if(!$zip->addFromString('LEIA-ME-MIGRACAO.txt',"IguatamaOnline - migração de hospedagem\n\n1. Extraia este ZIP no diretório raiz do projeto da nova hospedagem, sem sobrescrever um site existente.\n2. Configure o document root do domínio para a subpasta public/ (não para a raiz do projeto).\n3. Ative PHP 8.1+, pdo_mysql, zip e as extensões indicadas pelo instalador.\n4. Crie um banco MySQL vazio no novo provedor.\n5. Use HTTPS e abra /setup.php; escolha MIGRAR, informe as credenciais novas do banco e confirme.\n6. Depois de importar, entre com a conta administrativa antiga e configure domínio, DNS, SSL, cron e chaves de APIs novamente.\n7. Apague quaisquer cópias do ZIP privado da pasta pública e guarde um backup offline.\n\nATENÇÃO: contém dados pessoais e hashes de senha. Não publique nem compartilhe este ZIP.\n"))throw new RuntimeException('Falha ao incluir instruções.');
        if(!$zip->close())throw new RuntimeException('Falha ao fechar ZIP portátil.');$opened=false;
        if((int)filesize($temp)>IGUA_MIGRATION_MAX_ZIP_BYTES)throw new RuntimeException('Pacote portátil ultrapassou 700 MiB.');
        $dest=$dir.'/iguatama-migracao-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.zip';
        if(!@rename($temp,$dest))throw new RuntimeException('Falha ao concluir pacote portátil.');
        @chmod($dest,0600);return $dest;
    }finally{
        if($opened)$zip->close();
        if(is_file($temp))@unlink($temp);
        if(is_file($bundle))@unlink($bundle);
    }
}
/** Detecta o payload local após extração do pacote, sem varrer diretórios arbitrários. */
function igua_migration_payload(string $root=IGUA_ROOT): ?array {
    $dir=$root.'/storage/migration';$manifestPath=$dir.'/manifest.json';$bundle=$dir.'/backup.zip';
    if(!is_file($manifestPath)||!is_file($bundle))return null;
    if(is_link($dir)||is_link($dir.'/extras')||is_link($manifestPath)||is_link($bundle))throw new DomainException('Pacote de migração contém links não permitidos.');
    if(filesize($manifestPath)>1024*1024 || filesize($bundle)>MEDIA_BUNDLE_MAX_ARCHIVE_BYTES)throw new DomainException('Pacote de migração acima dos limites.');
    $data=json_decode((string)file_get_contents($manifestPath),true,20,JSON_THROW_ON_ERROR);
    if(!is_array($data)||($data['format']??'')!==IGUA_MIGRATION_FORMAT||($data['version']??null)!==1||($data['backup']??'')!=='storage/migration/backup.zip'||!is_array($data['extra']??null)||count($data['extra'])>IGUA_MIGRATION_MAX_EXTRA_FILES)throw new DomainException('Manifesto portátil incompatível.');
    if(!is_string($data['site_version']??null)||!hash_equals(trim((string)file_get_contents($root.'/VERSION')),$data['site_version']))throw new DomainException('Código do portal e backup portátil têm versões diferentes.');
    $hash=$data['backup_sha256']??'';
    if(!is_string($hash)||!preg_match('/\A[a-f0-9]{64}\z/D',$hash)||!hash_equals($hash,(string)hash_file('sha256',$bundle)))throw new DomainException('Integridade do backup de banco e mídias inválida.');
    $total=0;
    foreach($data['extra'] as $rel=>$sha){
        if(!is_string($rel)||!igua_migration_extra_allowed($rel)||!is_string($sha)||!preg_match('/\A[a-f0-9]{64}\z/D',$sha))throw new DomainException('Arquivo extra inválido.');
        $path=$dir.'/extras/'.$rel;
        $top=$dir.'/extras';$current=$path;
        while($current!==$top){if(is_link($current))throw new DomainException('Link simbólico em mídia do pacote.');$current=dirname($current);if(strlen($current)<strlen($top))throw new DomainException('Caminho inválido.');}
        if(!is_file($path)||is_link($path)||!hash_equals($sha,(string)hash_file('sha256',$path)))throw new DomainException('Mídia do pacote ausente ou alterada.');
        $total+=(int)filesize($path);
        if($total>IGUA_MIGRATION_MAX_EXTRA_BYTES)throw new DomainException('Arquivos de mídia excedem o limite de migração.');
    }
    return ['bundle'=>$bundle,'extras'=>$data['extra'],'dir'=>$dir];
}
/** Exige a identidade de um administrador antigo antes de permitir ler/importar o pacote no MySQL novo. */
function igua_migration_authorized(array $payload,string $email,string $password): bool {
    if(!class_exists('ZipArchive')||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>254||$password===''||strlen($password)>1024)return false;
    $zip=new ZipArchive();if($zip->open($payload['bundle'])!==true)throw new DomainException('Backup portátil inválido.');
    try{
        if($zip->numFiles>MEDIA_BUNDLE_MAX_FILES+2)throw new DomainException('Backup portátil possui entradas demais.');
        $ms=$zip->statName('manifest.json');
        if(!$ms||($ms['size']??0)>1024*1024)throw new DomainException('Manifesto inválido.');
        $manifest=json_decode((string)$zip->getFromName('manifest.json'),true,50,JSON_THROW_ON_ERROR);
        if(!is_array($manifest)||($manifest['format']??'')!=='iguanews-media-bundle'||!in_array($manifest['version']??null,[1,2],true)||!is_array($manifest['files']??null))throw new DomainException('Backup incompatível.');
        $dbEntry=(string)($manifest['db']??'');
        if(!preg_match('~\Adb/manual-\d{8}-\d{6}-[a-f0-9]{6}\.json(?:\.gz)?\z~D',$dbEntry))throw new DomainException('Backup de dados inválido.');
        $stat=$zip->statName($dbEntry);
        if(!$stat||($stat['size']??0)>45*1024*1024)throw new DomainException('Backup de dados inválido.');
        $raw=$zip->getFromName($dbEntry);
        if(!is_string($raw)||strlen($raw)>45*1024*1024)throw new DomainException('Backup de dados inválido.');
        media_bundle_verify_manifest($zip,$manifest,$dbEntry,$raw);
        $data=parse_backup($raw,basename($dbEntry));
        $email=strtolower(trim($email));
        foreach($data['tables']['users'] as $user){
            if(($user['role']??'')==='admin' && (int)($user['active']??0)===1
                && strtolower((string)($user['email']??''))===$email
                && password_verify($password,(string)($user['password_hash']??'')))return true;
        }
        return false;
    }finally{$zip->close();}
}

/** Só promove arquivos novos, dentro de diretórios permitidos. Se falhar, desfaz os novos. */
function igua_migration_restore(PDO $db,array $payload,string $root=IGUA_ROOT): void {
    $added=[];
    try{
        foreach($payload['extras'] as $rel=>$sha){
            if(!igua_migration_extra_allowed($rel))throw new RuntimeException('Destino de mídia inválido.');
            $target=$root.'/'.$rel;$source=$payload['dir'].'/extras/'.$rel;
            if(is_link($source)||!is_file($source)||!hash_equals($sha,(string)hash_file('sha256',$source)))throw new RuntimeException('Fonte de mídia alterada durante a migração.');
            $parent=dirname($target);$base=str_starts_with($rel,'public/uploads/')?$root.'/public/uploads':$root.'/storage/media';
            if(!is_dir($base)||is_link($base))throw new RuntimeException('Pasta de mídia não disponível.');
            $ancestor=$parent;
            while(!is_dir($ancestor)&&dirname($ancestor)!==$ancestor)$ancestor=dirname($ancestor);
            $ancestorReal=realpath($ancestor);$baseReal=realpath($base);
            if($ancestorReal===false||$baseReal===false||($ancestorReal!==$baseReal&&!str_starts_with($ancestorReal,$baseReal.'/')))throw new RuntimeException('Destino fora da pasta de mídia.');
            if(is_link($target))throw new RuntimeException('Destino de mídia redirecionado.');
            if(is_file($target)){
                if(!hash_equals($sha,(string)hash_file('sha256',$target)))throw new RuntimeException('Arquivo existente diferente do backup: '.basename($target));
                continue;
            }
            if(!is_dir($parent)&&!mkdir($parent,0755,true)&&!is_dir($parent))throw new RuntimeException('Falha ao criar subpasta de mídia.');
            if(is_link($parent)||!is_dir($parent))throw new RuntimeException('Pasta insegura.');
            $tmp=@tempnam($parent,'.mig-');if($tmp===false)throw new RuntimeException('Espaço insuficiente para mídia.');
            try{
                if(!copy($source,$tmp)||!hash_equals($sha,(string)hash_file('sha256',$tmp)))throw new RuntimeException('Falha de integridade ao copiar mídia.');
                if(is_file($target)||is_link($target)||!rename($tmp,$target))throw new RuntimeException('Destino alterado durante a cópia de mídia.');
                $added[]=$target;@chmod($target,0640);
            }finally{if(is_file($tmp))@unlink($tmp);}
        }
        restore_media_bundle($db,$payload['bundle']);
    }catch(Throwable $e){foreach($added as $path)@unlink($path);throw $e;}
}
/** Apaga somente os arquivos de payload do próprio pacote depois de sucesso. */
function igua_migration_cleanup(array $payload): void {
    $base=$payload['dir'];
    foreach($payload['extras'] as $rel=>$sha){$path=$base.'/extras/'.$rel;if(is_file($path)&&!is_link($path))@unlink($path);}
    foreach([$base.'/manifest.json',$base.'/backup.zip'] as $file)if(is_file($file)&&!is_link($file))@unlink($file);
    // Subpastas vazias apenas, nunca executa exclusão recursiva sobre diretórios arbitrários.
    $paths=[];foreach(array_keys($payload['extras']) as $rel){$parent=dirname($base.'/extras/'.$rel);while(str_starts_with($parent,$base.'/extras')){$paths[$parent]=true;$parent=dirname($parent);}}
    uksort($paths,static fn($a,$b)=>strlen($b)<=>strlen($a));foreach(array_keys($paths) as $path)@rmdir($path);
    @rmdir($base.'/extras');@rmdir($base);
}
