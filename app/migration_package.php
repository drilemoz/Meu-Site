<?php
declare(strict_types=1);
/** Pacote portátil para transferência entre hospedagens. Nunca inclui configuração privada. */
const IGUA_MIGRATION_FORMAT = 'iguanews-portable-migration';
const IGUA_MIGRATION_SCHEMA = 2;
const IGUA_MIGRATION_DIR = 'storage/migration';
const IGUA_MIGRATION_MAX_CODE_FILES = 800;
const IGUA_MIGRATION_MAX_EXTRA_FILES = 3000;
const IGUA_MIGRATION_MAX_EXTRA_BYTES = 300 * 1024 * 1024;
const IGUA_MIGRATION_MAX_ZIP_BYTES = 700 * 1024 * 1024;

function igua_migration_code_allowed(string $rel): bool {
    if(in_array($rel,['VERSION','public/.htaccess','public/.user.ini','public/uploads/.htaccess','public/uploads/.gitkeep'],true)) return true;
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
    foreach(['VERSION','public/setup.php','public/.htaccess','public/.user.ini','public/uploads/.htaccess','app/bootstrap.php','app/backup.php','app/migration_package.php','app/schema.php'] as $required){
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
/** Identifica imagens/anexos já embutidos no ZIP de dados, evitando duplicação no pacote portátil. */
function igua_migration_bundle_covered(string $bundle): array {
    $zip=new ZipArchive();
    if($zip->open($bundle)!==true)throw new RuntimeException('Falha ao ler backup interno para deduplicação.');
    try{
        $entry=$zip->statName('manifest.json');
        if(!$entry || ($entry['size']??0)>1024*1024)throw new RuntimeException('Manifesto interno de mídias inválido.');
        $manifest=json_decode((string)$zip->getFromName('manifest.json'),true,32,JSON_THROW_ON_ERROR);
        if(($manifest['format']??'')!=='iguanews-media-bundle'||!is_array($manifest['files']??null))throw new RuntimeException('Backup interno incompatível.');
        $covered=[];
        foreach($manifest['files'] as $key=>$sha){
            if(!is_string($key)||!is_string($sha)||!preg_match('/\A[a-f0-9]{64}\z/D',$sha))throw new RuntimeException('Entrada do backup interno inválida.');
            if(str_starts_with($key,'assets/images/'))$rel='public/uploads/'.substr($key,strlen('assets/images/'));
            elseif(str_starts_with($key,'assets/docs/'))$rel='storage/media/'.substr($key,strlen('assets/docs/'));
            else throw new RuntimeException('Pasta inesperada no backup de mídias.');
            if(!igua_migration_extra_allowed($rel))throw new RuntimeException('Nome de mídia incompatível com migração.');
            $covered[$rel]=$sha;
        }
        return $covered;
    }finally{$zip->close();}
}

/** Retorna o ZIP pronto no armazenamento privado. Caller remove depois do download. */
function igua_make_migration_package(PDO $db): string {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Ative PHP ZipArchive para criar pacote portátil.');
    $code=igua_migration_code_files();$extras=igua_migration_extra_files();
    $bundle=make_media_bundle($db); // reutiliza os checks de integridade do backup v12.
    $covered=igua_migration_bundle_covered($bundle);
    $dir=IGUA_STORAGE.'/backups';$temp=@tempnam($dir,'.bundle-');
    if($temp===false){@unlink($bundle);throw new RuntimeException('Não foi possível reservar espaço temporário.');}
    @chmod($temp,0600);$zip=new ZipArchive();$opened=false;
    try{
        if($zip->open($temp,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Não foi possível abrir ZIP portátil.');
        $opened=true;$hash=(string)hash_file('sha256',$bundle);
        $manifest=['format'=>IGUA_MIGRATION_FORMAT,'version'=>IGUA_MIGRATION_SCHEMA,'site_version'=>trim((string)file_get_contents(IGUA_ROOT.'/VERSION')),'backup'=>'storage/migration/backup.zip','backup_sha256'=>$hash,'code'=>[],'extra'=>[]];
        foreach($code as $rel=>$file){
            $sha=hash_file('sha256',$file);$bytes=filesize($file);
            if($sha===false||$bytes===false||!$zip->addFile($file,$rel))throw new RuntimeException('Falha ao adicionar código do portal.');
            $manifest['code'][$rel]=['sha256'=>$sha,'bytes'=>(int)$bytes];
        }
        // O instalador precisa das pastas privadas mesmo quando estiverem vazias.
        foreach(['storage/backups/.gitkeep','storage/media/.gitkeep','storage/security/.gitkeep','storage/logs/.gitkeep','storage/geoip/.gitkeep'] as $keep){
            if(!$zip->addFromString($keep,''))throw new RuntimeException('Não foi possível criar pastas privadas no pacote.');
        }
        if(!$zip->addFromString('storage/.htaccess',"Deny from all\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n"))throw new RuntimeException('Não foi possível proteger storage no pacote.');
        if(!$zip->addFile($bundle,'storage/migration/backup.zip'))throw new RuntimeException('Falha ao incluir o backup de dados.');
        foreach($extras as $rel=>$file){
            $extraHash=hash_file('sha256',$file);
            if($extraHash===false)throw new RuntimeException('Falha ao calcular hash da mídia.');
            if(isset($covered[$rel])){
                if(!hash_equals($covered[$rel],$extraHash))throw new RuntimeException('Mídia mudou enquanto o backup era gerado. Tente novamente.');
                continue; // A mídia já existe em storage/migration/backup.zip.
            }
            $manifest['extra'][$rel]=$extraHash;
            if(!$zip->addFile($file,'storage/migration/extras/'.$rel))throw new RuntimeException('Falha ao adicionar mídia ao pacote.');
        }
        if(!$zip->addFromString('storage/migration/manifest.json',json_encode($manifest,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR)))throw new RuntimeException('Falha ao criar manifesto de migração.');
        // Chave de bootstrap da instalação nova: fica em storage/, fora da raiz pública.
        // Nunca reutilizar a chave da hospedagem de origem.
        if(!$zip->addFromString('storage/.setup-access.key',bin2hex(random_bytes(24))."\n"))throw new RuntimeException('Falha ao adicionar chave de autorização ao pacote.');
        if(!$zip->addFromString('LEIA-ME-MIGRACAO.txt',"IguatamaOnline - migração de hospedagem\n\n1. Extraia este ZIP no diretório raiz do projeto da nova hospedagem, sem sobrescrever um site existente.\n2. Configure o document root do domínio para a subpasta public/ (não para a raiz do projeto).\n3. Ative PHP 8.1+, pdo_mysql, zip e as extensões indicadas pelo instalador.\n4. Abra /setup.php com HTTPS e informe a chave gravada em storage/.setup-access.key dentro do ZIP (via gerenciador de arquivos/terminal da NOVA hospedagem).\n5. Na etapa Banco, conecte-se a um banco vazio ou crie um banco com uma conta autorizada, separando credenciais administrativas e do portal sempre que possível. Depois escolha MIGRAR e confirme o administrador antigo.\n6. Depois de importar, entre com a conta administrativa antiga e configure domínio, DNS, SSL, cron e chaves de APIs novamente.\n7. Apague quaisquer cópias do ZIP privado da pasta pública e guarde um backup offline.\n\nATENÇÃO: contém dados pessoais e hashes de senha. Não publique nem compartilhe este ZIP.\n"))throw new RuntimeException('Falha ao incluir instruções.');
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
    if(!is_array($data)||($data['format']??'')!==IGUA_MIGRATION_FORMAT||($data['version']??null)!==IGUA_MIGRATION_SCHEMA||!is_array($data['code']??null)||count($data['code'])>IGUA_MIGRATION_MAX_CODE_FILES||($data['backup']??'')!=='storage/migration/backup.zip'||!is_array($data['extra']??null)||count($data['extra'])>IGUA_MIGRATION_MAX_EXTRA_FILES)throw new DomainException('Manifesto portátil incompatível.');
    if(!is_string($data['site_version']??null)||!hash_equals(trim((string)file_get_contents($root.'/VERSION')),$data['site_version']))throw new DomainException('Código do portal e backup portátil têm versões diferentes.');
    // Comprova a presença e integridade de cada arquivo de código ANTES de tocar no MySQL.
    $actual=igua_migration_code_files($root);
    if(array_keys($actual)!==array_keys($data['code']))throw new DomainException('Lista de arquivos do código diferente do manifesto portátil.');
    foreach($data['code'] as $rel=>$meta){
        if(!is_string($rel)||!igua_migration_code_allowed($rel)||!is_array($meta)
            ||!is_string($meta['sha256']??null)||!preg_match('/\A[a-f0-9]{64}\z/D',$meta['sha256'])
            ||!is_int($meta['bytes']??null)||$meta['bytes']<0||$meta['bytes']>16*1024*1024)
            throw new DomainException('Manifesto de código inválido.');
        $path=$actual[$rel];
        if(filesize($path)!==$meta['bytes']||!hash_equals($meta['sha256'],(string)hash_file('sha256',$path)))
            throw new DomainException('Arquivo do portal ausente ou adulterado: '.basename($rel));
    }
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

/** Lê e verifica todos os bytes do ZIP interno antes de criar qualquer tabela. */
function igua_migration_preflight(array $payload): array {
    if(!class_exists('ZipArchive'))throw new DomainException('Extensão PHP ZIP indisponível.');
    $zip=new ZipArchive();
    if($zip->open($payload['bundle'])!==true)throw new DomainException('Backup interno ilegível.');
    try{
        if($zip->numFiles>MEDIA_BUNDLE_MAX_FILES+2)throw new DomainException('Backup interno possui arquivos demais.');
        $st=$zip->statName('manifest.json');
        if(!$st || ($st['size']??-1)>1024*1024)throw new DomainException('Manifesto do backup inválido.');
        $manifest=json_decode((string)$zip->getFromName('manifest.json'),true,32,JSON_THROW_ON_ERROR);
        if(!is_array($manifest)||($manifest['format']??'')!=='iguanews-media-bundle'||!in_array(($manifest['version']??null),[1,2],true)||!is_array($manifest['files']??null))throw new DomainException('Formato de backup interno inválido.');
        $entry=$manifest['db']??'';
        if(!is_string($entry)||!preg_match('~\Adb/manual-\d{8}-\d{6}-[a-f0-9]{6}\.json(?:\.gz)?\z~D',$entry))throw new DomainException('Backup de dados inválido.');
        $meta=$zip->statName($entry);
        if(!$meta || ($meta['size']??-1)<0 || $meta['size']>45*1024*1024)throw new DomainException('Backup de dados acima do limite.');
        $raw=$zip->getFromName($entry);
        if(!is_string($raw))throw new DomainException('Backup do banco ilegível.');
        media_bundle_verify_manifest($zip,$manifest,$entry,$raw);
        $data=parse_backup($raw,basename($entry));
        unset($raw);
        $total=0;$count=0;
        foreach($manifest['files'] as $name=>$hash){
            if(!is_string($name)||!is_string($hash)||!preg_match('/\A[a-f0-9]{64}\z/D',$hash))throw new DomainException('Referência de mídia inválida.');
            $valid=str_starts_with($name,'assets/images/')?media_valid_name(substr($name,14)):
                (str_starts_with($name,'assets/docs/')&&news_attachment_storage_name_valid(substr($name,12)));
            if(!$valid)throw new DomainException('Nome de mídia inválido no backup.');
            $stat=$zip->statName($name);
            if(!$stat || !is_int($stat['size']??null)||$stat['size']<0||$stat['size']>MEDIA_BUNDLE_MAX_MEDIA_BYTES-$total)throw new DomainException('Tamanho de mídia inválido.');
            $total+=$stat['size'];$count++;
            $stream=$zip->getStream($name);if($stream===false)throw new DomainException('Não foi possível ler mídia do backup.');
            try{
                $sha=hash_init('sha256');$read=0;
                while(!feof($stream)){
                    $chunk=fread($stream,65536);
                    if($chunk===false || ($chunk===''&&!feof($stream)))throw new DomainException('Falha ao verificar mídia.');
                    if($chunk==='')break;
                    $read+=strlen($chunk);
                    if($read>$stat['size'])throw new DomainException('Mídia excede tamanho declarado.');
                    hash_update($sha,$chunk);
                }
                if($read!==$stat['size']||!hash_equals($hash,hash_final($sha)))throw new DomainException('Mídia corrompida no ZIP interno.');
            }finally{fclose($stream);}
        }
        // Confirma que o ZIP traz todos os nomes citados pelos registros do banco.
        $required=[];
        foreach($data['tables']['article_media'] as $r){
            $kind=(string)($r['kind']??'');$name=(string)($r['filename']??'');
            if(!in_array($kind,['image','attachment'],true))throw new DomainException('Referência de mídia desconhecida.');
            $required[($kind==='image'?'assets/images/':'assets/docs/').$name]=true;
        }
        foreach(['articles'=>'image','ad_campaigns'=>'image','media_library'=>'filename'] as $table=>$field){
            foreach($data['tables'][$table] as $r){$name=(string)($r[$field]??'');if(media_valid_name($name))$required['assets/images/'.$name]=true;}
        }
        foreach($required as $name=>$_)if(!array_key_exists($name,$manifest['files']))throw new DomainException('Backup incompleto: imagem ou anexo ausente.');
        return ['media_files'=>$count,'media_bytes'=>$total,'rows'=>array_sum(array_map('count',$data['tables']))];
    }finally{$zip->close();}
}

/** Pré-verifica o espaço livre observado; quotas da hospedagem ainda exigem teste real. */
function igua_migration_capacity_check(array $payload,array $preflight,string $root=IGUA_ROOT): void {
    $extrasBytes=0;
    foreach($payload['extras'] as $rel=>$hash){
        $file=$payload['dir'].'/extras/'.$rel;
        $size=@filesize($file);
        if($size===false || $size<0)throw new DomainException('Falha ao consultar tamanho de arquivo do pacote.');
        $extrasBytes+=$size;
        if($extrasBytes>IGUA_MIGRATION_MAX_EXTRA_BYTES)throw new DomainException('Arquivos extras acima do limite.');
    }
    $mediaBytes=(int)($preflight['media_bytes']??0);
    // As duas áreas podem ficar em partições diferentes; verificar sem confundir com quota do plano.
    foreach([$root.'/public/uploads',$root.'/storage/media'] as $folder){
        if(!is_dir($folder)||is_link($folder)||!is_writable($folder))throw new DomainException('Pasta de mídia não disponível para restauração na nova hospedagem.');
        $free=@disk_free_space($folder);
        if($free!==false && $free < ($mediaBytes+$extrasBytes)*1.15+16*1024*1024)
            throw new DomainException('Espaço livre insuficiente para restaurar mídias com margem de segurança.');
    }
}

/** Compensação limitada à estrutura criada nesta tentativa e que permaneceu vazia. */
function igua_migration_rollback_empty_schema(PDO $db): bool {
    require_once __DIR__.'/setup_inventory.php';
    $inventory=igua_setup_database_inventory($db);
    if($inventory['status']==='empty')return true;
    if($inventory['status']!=='partial_empty')return false; // Nunca apaga dados preenchidos.
    $tables=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    $known=igua_setup_known_tables();
    foreach($tables as $name)if(!in_array($name,$known,true))return false;
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    try{
        foreach($tables as $name)$db->exec('DROP TABLE `'.$name.'`'); // nome em lista literal fechada
    }finally{$db->exec('SET FOREIGN_KEY_CHECKS=1');}
    return igua_setup_database_inventory($db)['status']==='empty';
}

/** Atualiza somente o endereço oficial do site após restaurar os dados antigos. */
function igua_migration_site_url(string $input): string {
    $input=trim($input);
    if($input==='')return '';
    if(strlen($input)>240||!filter_var($input,FILTER_VALIDATE_URL))throw new DomainException('Informe um endereço HTTPS válido para o novo domínio.');
    $parts=parse_url($input);
    if(($parts['scheme']??'')!=='https'||!isset($parts['host'])||isset($parts['user'],$parts['pass'])||isset($parts['user'])||isset($parts['pass'])||isset($parts['query'])||isset($parts['fragment'])||!empty($parts['path'])&&$parts['path']!=='/')
        throw new DomainException('O domínio oficial precisa usar HTTPS, sem caminho, usuário ou parâmetros.');
    return rtrim($input,'/');
}

function igua_migration_update_site_url(PDO $db,string $url): void {
    if($url==='')return; // Mesmo domínio: mantém o endereço original.
    $q=$db->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
    $q->execute(['seo_site_url',$url]);
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
            if(is_link($parent)||!is_dir($parent)||(($realParent=realpath($parent))===false)||($realParent!==$baseReal&&!str_starts_with($realParent,$baseReal.'/')))throw new RuntimeException('Pasta insegura.');
            $tmp=@tempnam($parent,'.mig-');if($tmp===false)throw new RuntimeException('Espaço insuficiente para mídia.');
            try{
                if(!copy($source,$tmp)||!hash_equals($sha,(string)hash_file('sha256',$tmp)))throw new RuntimeException('Falha de integridade ao copiar mídia.');
                if(is_file($target)||is_link($target)||!rename($tmp,$target))throw new RuntimeException('Destino alterado durante a cópia de mídia.');
                $added[]=$target;@chmod($target,0640);
            }finally{if(is_file($tmp))@unlink($tmp);}
        }
        restore_media_bundle($db,$payload['bundle'],true);
    }catch(Throwable $e){foreach($added as $path)@unlink($path);throw $e;}
}
/** Apaga somente os arquivos de payload do próprio pacote depois de sucesso. */
function igua_migration_cleanup(array $payload): bool {
    $base=$payload['dir'];$allRemoved=true;
    foreach($payload['extras'] as $rel=>$sha){
        if(!igua_migration_extra_allowed($rel)){ $allRemoved=false;continue; }
        $path=$base.'/extras/'.$rel;
        if(is_link($path)){ $allRemoved=false;continue; }
        if(is_file($path)&&!@unlink($path))$allRemoved=false;
        if(file_exists($path)||is_link($path))$allRemoved=false;
    }
    foreach([$base.'/manifest.json',$base.'/backup.zip'] as $file){
        if(is_link($file)){ $allRemoved=false;continue; }
        if(is_file($file)&&!@unlink($file))$allRemoved=false;
        if(file_exists($file)||is_link($file))$allRemoved=false;
    }
    $paths=[];
    foreach(array_keys($payload['extras']) as $rel){
        $parent=dirname($base.'/extras/'.$rel);
        while(str_starts_with($parent,$base.'/extras/')){$paths[$parent]=true;$parent=dirname($parent);}
    }
    uksort($paths,static fn($a,$b)=>strlen($b)<=>strlen($a));
    foreach(array_keys($paths) as $path)if(is_dir($path)&&!is_link($path))@rmdir($path);
    if(is_dir($base.'/extras')&&!is_link($base.'/extras'))@rmdir($base.'/extras');
    if(is_dir($base)&&!is_link($base))@rmdir($base);
    // O sucesso só significa que os dados privados do pacote foram efetivamente removidos.
    return $allRemoved && !is_dir($base);
}
