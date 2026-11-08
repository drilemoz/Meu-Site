<?php
declare(strict_types=1);
// Backups lógicos portáteis, sem shell ou extensões especiais do MySQL.
const BACKUP_TABLES_V1 = ['users','categories','articles','settings','backup_logs'];
const BACKUP_TABLES_V2 = ['users','categories','author_profiles','editorial_sources','articles','settings','backup_logs'];
const BACKUP_TABLES_V3 = ['users','categories','author_profiles','editorial_sources','articles','settings','backup_logs','bus_routes','bus_stops','bus_departures'];
const BACKUP_TABLES_V5 = [...BACKUP_TABLES_V3,'bus_import_queue'];
const BACKUP_TABLES_V6 = [...BACKUP_TABLES_V5,'ad_placements','ad_campaigns'];
const BACKUP_TABLES_V7 = [...BACKUP_TABLES_V6,'article_categories','article_blocks','article_media','article_drafts','article_import_logs','article_revisions'];
const BACKUP_TABLES_V8 = [...BACKUP_TABLES_V7,'analytics_events'];
const BACKUP_TABLES_V9 = [...BACKUP_TABLES_V8,'media_library'];
const BACKUP_TABLES_V10 = [...BACKUP_TABLES_V9,'article_slug_redirects'];
const BACKUP_TABLES_V11 = [...BACKUP_TABLES_V10,'elections','election_candidates'];
const BACKUP_TABLES = [...BACKUP_TABLES_V11,'legal_apps','legal_app_pages','legal_app_revisions','legal_app_slug_redirects'];
// Limites iguais na exportação e na importação; o ZIP precisa comportar também o JSON do banco.
const MEDIA_BUNDLE_MAX_MEDIA_BYTES = 300 * 1024 * 1024;
const MEDIA_BUNDLE_MAX_ARCHIVE_BYTES = 350 * 1024 * 1024;
const MEDIA_BUNDLE_MAX_FILES = 3000;
// O mesmo limite é usado para exportar, importar e conferir o JSON.
const BACKUP_MAX_JSON_BYTES = 40 * 1024 * 1024;
function media_bundle_size_allowed(int $bytes): bool {
    return $bytes > 0 && $bytes <= MEDIA_BUNDLE_MAX_ARCHIVE_BYTES;
}
/** Grava cada fragmento do JSON com limite exato sobre os bytes descompactados. */
function backup_write_chunk($stream, string $chunk, bool $compressed, int &$total): void {
    $size=strlen($chunk);
    if($size>BACKUP_MAX_JSON_BYTES-$total) {
        throw new RuntimeException('Backup lógico maior que 40 MiB. Exporte o acervo em partes ou aumente os limites após avaliação.');
    }
    if($size>0) {
        $written=$compressed?@gzwrite($stream,$chunk):@fwrite($stream,$chunk);
        if($written!==$size)throw new RuntimeException('Falha ao gravar arquivo temporário de backup.');
    }
    $total+=$size;
}
/** Exporta as linhas diretamente no arquivo privado, sem montar a árvore completa em RAM. */
function make_backup(PDO $db, string $prefix='manual'): string {
    if (!in_array($prefix,['manual','daily','weekly'],true)) throw new RuntimeException('Prefixo inválido.');
    $folder=IGUA_STORAGE.'/backups';
    if(is_link($folder) || (!is_dir($folder) && !@mkdir($folder,0700,true) && !is_dir($folder)) || !is_writable($folder))
        throw new RuntimeException('Pasta privada de backups indisponível ou insegura.');
    $compressed=function_exists('gzopen') && function_exists('gzwrite');
    $name=$prefix.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).($compressed?'.json.gz':'.json');
    $path=$folder.'/'.$name;
    $temp=@tempnam($folder,'.backup-');
    if($temp===false)throw new RuntimeException('Não foi possível criar arquivo temporário de backup.');
    @chmod($temp,0600);
    $stream=null;
    try {
        $stream=$compressed?@gzopen($temp,'wb6'):@fopen($temp,'wb');
        if($stream===false)throw new RuntimeException('Não foi possível abrir arquivo temporário de backup.');
        $total=0;
        $write=static function(string $fragment)use($stream,$compressed,&$total):void{
            backup_write_chunk($stream,$fragment,$compressed,$total);
        };
        // Ler sob REPEATABLE READ preserva a visão InnoDB, inclusive enquanto o JSON é gravado.
        $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        $db->beginTransaction();
        try {
            $write('{"format":"iguanews-backup","version":12,"created_at":'.json_encode(date(DATE_ATOM),JSON_THROW_ON_ERROR).',"tables":{');
            foreach (BACKUP_TABLES as $index=>$table) {
                $write(($index>0?',':'').json_encode($table,JSON_THROW_ON_ERROR).':[');
                $stmt=$db->query('SELECT * FROM `'.$table.'`');
                if(!$stmt)throw new RuntimeException('Falha na consulta de backup da tabela '.$table.'.');
                $rows=0;
                try{
                    while(($row=$stmt->fetch(PDO::FETCH_ASSOC))!==false){
                        if($rows>=100000)throw new RuntimeException('Há registros demais para o backup lógico em '.$table.'.');
                        $encoded=json_encode($row,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
                        $write(($rows>0?',':'').$encoded);
                        $rows++;
                    }
                }finally{$stmt->closeCursor();}
                $write(']');
            }
            $write('}}');
            if($compressed){
                if(!@gzclose($stream))throw new RuntimeException('Falha ao finalizar GZIP de backup.');
            }else{
                if(!@fflush($stream))throw new RuntimeException('Falha ao finalizar backup.');
                if(!@fclose($stream))throw new RuntimeException('Falha ao fechar backup.');
            }
            $stream=null;
            $db->commit();
        }catch(Throwable $e){
            if($db->inTransaction())$db->rollBack();
            throw $e;
        }
        if(!@rename($temp,$path))throw new RuntimeException('Falha ao publicar arquivo de backup.');
        @chmod($path,0600);
        $size=@filesize($path);
        if($size===false)throw new RuntimeException('Não foi possível consultar tamanho do backup.');
        $stmt=$db->prepare('INSERT INTO backup_logs (filename,bytes,status) VALUES (?, ?, ?)');
        $stmt->execute([$name,$size,'ok']);
        return $name;
    }finally{
        if(is_resource($stream)){
            if($compressed)@gzclose($stream);else @fclose($stream);
        }
        if(is_file($temp))@unlink($temp);
        // Erro de registro de log não deve deixar um backup invisível fora da lista válida.
        // O arquivo final é preservado se o SQL de log falhar após a publicação.
    }
}
/** Remove somente arquivos temporários que já estavam abandonados por pelo menos 24h. */
function cleanup_backup_temporaries(?int $now=null): int {
    $folder=IGUA_STORAGE.'/backups';
    if(!is_dir($folder)||is_link($folder))return 0;
    $now??=time();$removed=0;
    foreach(['.backup-*','.bundle-*'] as $pattern){
        foreach(glob($folder.'/'.$pattern)?:[] as $path){
            $name=basename($path);
            if(!preg_match('/^\.(?:backup|bundle)-[a-zA-Z0-9]{6}$/D',$name))continue;
            if(is_link($path)||!is_file($path))continue;
            $age=@filemtime($path);
            if($age===false||$age>$now-24*3600)continue;
            // Tenta lock não bloqueante e evita remover arquivos temporários em uso que mantenham lock.
            $handle=@fopen($path,'c+b');
            if($handle===false)continue;
            try {
                if(!@flock($handle,LOCK_EX|LOCK_NB))continue;
                if(!is_link($path)&&is_file($path)&&@filemtime($path)<=$now-24*3600 && @unlink($path))$removed++;
            }finally{@flock($handle,LOCK_UN);@fclose($handle);}
        }
    }
    return $removed;
}
function list_backups(): array {
    $folder=IGUA_STORAGE.'/backups';
    if(!is_dir($folder) || is_link($folder))return [];
    $paths=glob($folder.'/*.{json,json.gz}',GLOB_BRACE) ?: [];
    $paths=array_values(array_filter($paths,static fn($p)=>is_file($p) && !is_link($p) && preg_match('/^(?:manual|daily|weekly)-\d{8}-\d{6}-[a-f0-9]{6}\.json(?:\.gz)?$/D',basename($p))));
    usort($paths,static fn($a,$b) => ((int)@filemtime($b) <=> (int)@filemtime($a)) ?: strcmp($b,$a));
    return array_values(array_map(static fn($p)=>['name'=>basename($p),'size'=>(int)filesize($p),'date'=>date('d/m/Y H:i',(int)filemtime($p))],$paths));
}
function backup_path(string $filename): ?string {
    if (!preg_match('/^(?:manual|daily|weekly)-\d{8}-\d{6}-[a-f0-9]{6}\.json(?:\.gz)?$/D',$filename)) return null;
    $folder=IGUA_STORAGE.'/backups';
    if(!is_dir($folder)||is_link($folder))return null;
    $p=$folder.'/'.$filename;
    return is_file($p)&&!is_link($p)?$p:null;
}
function parse_backup(string $raw, string $filename): array {
    if (str_ends_with($filename,'.gz')) {
        if (!function_exists('gzdecode')) throw new RuntimeException('Extensão zlib indisponível para restauração.');
        $raw=gzdecode($raw, BACKUP_MAX_JSON_BYTES);
        if ($raw===false) throw new RuntimeException('Arquivo GZIP danificado.');
    }
    if (strlen($raw)>BACKUP_MAX_JSON_BYTES) throw new RuntimeException('Backup descompactado excede 40 MiB.');
    $data=json_decode($raw,true,256,JSON_THROW_ON_ERROR);
    if (!is_array($data)||($data['format']??'')!=='iguanews-backup'||!in_array($data['version']??null,[1,2,3,4,5,6,7,8,9,10,11,12],true)) throw new RuntimeException('Formato ou versão de backup inválida.');
    $v=(int)$data['version'];
    $expected=match(true){
        $v===1=>BACKUP_TABLES_V1,$v===2=>BACKUP_TABLES_V2,$v===3=>BACKUP_TABLES_V3,
        $v<=5=>BACKUP_TABLES_V5,$v===6=>BACKUP_TABLES_V6,$v===7=>BACKUP_TABLES_V7,
        $v===8=>BACKUP_TABLES_V8,$v===9=>BACKUP_TABLES_V9,$v===10=>BACKUP_TABLES_V10,$v===11=>BACKUP_TABLES_V11,default=>BACKUP_TABLES
    };
    if (!isset($data['tables']) || !is_array($data['tables']) || array_keys($data['tables'])!==$expected) throw new RuntimeException('Estrutura de backup incompleta.');
    if ($data['version']===1) {
        $data['tables']['author_profiles']=[];
        $data['tables']['editorial_sources']=[];
        $data['tables']=array_replace(array_fill_keys(BACKUP_TABLES,[]),$data['tables']);
    }
    if (in_array($data['version'],[2,3,4,5,6,7,8,9,10,11],true)) $data['tables']=array_replace(array_fill_keys(BACKUP_TABLES,[]),$data['tables']);
    foreach (BACKUP_TABLES as $table) {
        if (!is_array($data['tables'][$table]) || count($data['tables'][$table])>100000) throw new RuntimeException('Dados inesperados na tabela '.$table);
        foreach ($data['tables'][$table] as $row) if (!is_array($row) || array_is_list($row)) throw new RuntimeException('Registro inválido em '.$table);
    }
    if (!$data['tables']['users']) throw new RuntimeException('Um backup precisa conter um administrador.');
    $hasAdmin=false;foreach($data['tables']['users'] as $u) {if(($u['role']??'admin')==='admin' && (int)($u['active']??1)===1)$hasAdmin=true;}
    if (!$hasAdmin) throw new RuntimeException('O backup precisa conter ao menos um administrador ativo.');
    return $data;
}
function restore_backup(PDO $db, array $data): void {
    // As únicas tabelas e colunas aceitas vêm do schema atual, não do arquivo.
    $columns=[];
    foreach (BACKUP_TABLES as $table) {
        $columns[$table]=array_column($db->query('SHOW COLUMNS FROM `'.$table.'`')->fetchAll(),'Field');
        foreach ($data['tables'][$table] as $row) {
            foreach (array_keys($row) as $key) if (!in_array($key,$columns[$table],true)) throw new RuntimeException('Coluna incompatível: '.$table.'.'.$key);
        }
    }
    $db->beginTransaction();
    try {
        foreach (['legal_app_slug_redirects','legal_app_revisions','legal_app_pages','legal_apps','election_candidates','elections','article_slug_redirects','media_library','analytics_events','article_revisions','article_import_logs','article_drafts','article_media','article_blocks','article_categories','ad_campaigns','ad_placements','bus_import_queue','bus_departures','bus_stops','bus_routes','articles','backup_logs','settings','editorial_sources','author_profiles','categories','users'] as $table) $db->exec('DELETE FROM `'.$table.'`');
        foreach (BACKUP_TABLES as $table) {
            foreach ($data['tables'][$table] as $row) {
                if (!$row) continue;
                $keys=array_keys($row);
                $sql='INSERT INTO `'.$table.'` ('.implode(',',array_map(static fn($k)=>'`'.$k.'`',$keys)).') VALUES ('.implode(',',array_fill(0,count($keys),'?')).')';
                $db->prepare($sql)->execute(array_values($row));
            }
        }
        $db->commit();
    } catch(Throwable $e) { if($db->inTransaction()) $db->rollBack();throw $e; }
}
function rotate_backups(): void {
    cleanup_backup_temporaries();
    foreach (['daily'=>7,'weekly'=>4] as $kind=>$keep) {
        $filtered=array_values(array_filter(list_backups(),static fn($b)=>str_starts_with($b['name'],$kind.'-')));
        foreach (array_slice($filtered,$keep) as $old) @unlink(IGUA_STORAGE.'/backups/'.$old['name']);
    }
}

/** Recusa incluir arquivos apontando para fora das áreas previstas do portal. */
function media_bundle_safe_source(string $key, string $file): string {
    $base=str_starts_with($key,'assets/images/') ? IGUA_PUBLIC.'/uploads' : IGUA_STORAGE.'/media';
    $root=realpath($base);$real=realpath($file);
    if($root===false || $real===false || !is_file($real) || is_link($file) || !str_starts_with($real,$root.DIRECTORY_SEPARATOR)) {
        throw new RuntimeException('Mídia não encontrada ou fora do diretório permitido: '.basename($file));
    }
    return $real;
}
/** Exporta banco + fotos + anexos em um único ZIP, se ext-zip estiver disponível. */
function make_media_bundle(PDO $db): string {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Extensão PHP zip indisponível. Ative ext-zip para backup único; a exportação JSON continua disponível.');
    $backup=make_backup($db);
    $folder=IGUA_STORAGE.'/backups';
    $zipName='iguanews-completo-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.zip';
    $destination=$folder.'/'.$zipName;
    $temporary=@tempnam($folder,'.bundle-');
    if($temporary===false)throw new RuntimeException('Não foi possível criar arquivo temporário para pacote completo.');
    @chmod($temporary,0600);
    $zip=new ZipArchive();$opened=false;
    try{
        if($zip->open($temporary,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Não foi possível criar pacote de mídia.');
        $opened=true;
        $backupPath=$folder.'/'.$backup;
        $dbHash=hash_file('sha256',$backupPath);
        if($dbHash===false)throw new RuntimeException('Falha ao calcular integridade do banco do pacote.');
        $manifest=['format'=>'iguanews-media-bundle','version'=>2,'db'=>'db/'.$backup,'db_sha256'=>$dbHash,'files'=>[]];
        if(!$zip->addFile($backupPath,'db/'.$backup))throw new RuntimeException('Falha ao adicionar o banco ao ZIP.');
        $sources=[];
        // Apenas metadados/nomes de mídias, sem conteúdo dos arquivos na memória.
        $stmt=$db->query('SELECT kind,filename FROM article_media');
        try{
            while(($v=$stmt->fetch(PDO::FETCH_ASSOC))!==false){
                $filename=(string)$v['filename'];
                if($v['kind']==='image' && media_valid_name($filename))$sources['assets/images/'.$filename]=IGUA_PUBLIC.'/uploads/'.$filename;
                if($v['kind']==='attachment' && news_attachment_storage_name_valid($filename))$sources['assets/docs/'.$filename]=IGUA_STORAGE.'/media/'.$filename;
            }
        }finally{$stmt->closeCursor();}
        foreach(['articles'=>'image','ad_campaigns'=>'image','media_library'=>'filename'] as $table=>$field){
            $stmt=$db->query('SELECT `'.$field.'` FROM `'.$table.'` WHERE `'.$field.'` IS NOT NULL');
            try{
                while(($v=$stmt->fetch(PDO::FETCH_ASSOC))!==false){
                    $filename=(string)$v[$field];
                    if(media_valid_name($filename))$sources['assets/images/'.$filename]=IGUA_PUBLIC.'/uploads/'.$filename;
                }
            }finally{$stmt->closeCursor();}
        }
        $total=0;
        if(count($sources)>MEDIA_BUNDLE_MAX_FILES)throw new RuntimeException('Há mídias demais para um único pacote. Divida a migração.');
        foreach($sources as $key=>$file){
            $file=media_bundle_safe_source($key,$file);
            $size=(int)filesize($file);$total+=$size;
            if($total>MEDIA_BUNDLE_MAX_MEDIA_BYTES)throw new RuntimeException('Mídias acima de 300 MiB. Faça backup externo dos arquivos e exporte o banco separadamente.');
            $hash=hash_file('sha256',$file);
            if($hash===false || !$zip->addFile($file,$key))throw new RuntimeException('Não foi possível adicionar a mídia ao pacote.');
            $manifest['files'][$key]=$hash;
        }
        if(!$zip->addFromString('manifest.json',json_encode($manifest,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)))throw new RuntimeException('Falha ao gravar o manifesto do pacote.');
        if(!$zip->close())throw new RuntimeException('Falha ao finalizar ZIP de mídia.');
        $opened=false;
        if(!media_bundle_size_allowed((int)filesize($temporary)))throw new RuntimeException('ZIP maior que o limite de 350 MiB. Exporte o banco e as mídias separadamente.');
        if(!@rename($temporary,$destination))throw new RuntimeException('Falha ao publicar ZIP de mídia.');
        @chmod($destination,0600);
        return $destination;
    }finally{
        if($opened)$zip->close();
        if(is_file($temporary))@unlink($temporary);
    }
}

/**
 * Verifica a entrada sem carregar a fotografia inteira na RAM. Arquivos novos
 * ficam temporários, no mesmo filesystem do destino, até a validação completa.
 * Retorna null quando o destino já existe e tem o hash esperado.
 */
function media_bundle_stage_entry(ZipArchive $zip, string $entry, string $checksum, string $target, int $declaredBytes, ?string $allowedRoot=null): ?string {
    if($declaredBytes<0 || $declaredBytes>MEDIA_BUNDLE_MAX_MEDIA_BYTES)throw new RuntimeException('Tamanho de mídia inválido.');
    if($allowedRoot!==null){
        $root=realpath($allowedRoot);$ancestor=dirname($target);
        while(!is_dir($ancestor)&&dirname($ancestor)!==$ancestor)$ancestor=dirname($ancestor);
        $ancestorReal=realpath($ancestor);
        if($root===false||$ancestorReal===false||($ancestorReal!==$root&&!str_starts_with($ancestorReal,$root.DIRECTORY_SEPARATOR)))throw new RuntimeException('Destino fora da pasta de mídia.');
    }
    if(is_link($target))throw new RuntimeException('Destino de mídia é um link simbólico.');
    $existing=is_file($target);
    if($existing && !hash_equals($checksum,(string)hash_file('sha256',$target)))throw new RuntimeException('Há um arquivo com mesmo nome e conteúdo diferente: '.basename($target));
    if(!$existing){
        $folder=dirname($target);
        if(!is_dir($folder) && !mkdir($folder,0755,true) && !is_dir($folder))throw new RuntimeException('Falha ao criar subpasta de mídia.');
        if(!is_dir($folder)||!is_writable($folder)||is_link($folder))throw new RuntimeException('Diretório de mídia não gravável ou inseguro.');
        if($allowedRoot!==null){
            $realFolder=realpath($folder);
            if($realFolder===false||($realFolder!==$root&&!str_starts_with($realFolder,$root.DIRECTORY_SEPARATOR)))throw new RuntimeException('Subpasta de mídia fora da área permitida.');
        }
        $temporary=@tempnam($folder,'.iguanews-restore-');
        if($temporary===false)throw new RuntimeException('Espaço ou permissão insuficiente para preparar mídia.');
    }else{$temporary=null;}
    $reader=$zip->getStream($entry);
    if($reader===false){if($temporary!==null)@unlink($temporary);throw new RuntimeException('Mídia ilegível no ZIP: '.$entry);}
    $writer=null;
    try{
        if($temporary!==null){
            $writer=@fopen($temporary,'wb');
            if($writer===false)throw new RuntimeException('Não foi possível preparar arquivo de mídia.');
        }
        $hash=hash_init('sha256');$copied=0;
        while(!feof($reader)){
            $chunk=fread($reader,65536);
            if($chunk===false || ($chunk===''&&!feof($reader)))throw new RuntimeException('Falha ao ler mídia no ZIP.');
            if($chunk==='')break;
            $copied+=strlen($chunk);
            if($copied>$declaredBytes)throw new RuntimeException('Mídia excede tamanho declarado.');
            hash_update($hash,$chunk);
            if($writer!==null && fwrite($writer,$chunk)!==strlen($chunk))throw new RuntimeException('Armazenamento insuficiente durante restauração.');
        }
        if($copied!==$declaredBytes || !hash_equals($checksum,hash_final($hash)))throw new RuntimeException('Mídia corrompida: '.$entry);
        if($writer!==null && !fflush($writer))throw new RuntimeException('Não foi possível finalizar arquivo temporário.');
    }catch(Throwable $e){
        if($temporary!==null)@unlink($temporary);
        throw $e;
    }finally{
        fclose($reader);
        if(is_resource($writer))fclose($writer);
    }
    return $temporary;
}

/**
 * Pacotes v1/v2 antigos podem não conter db_sha256. Quando presente, ele é obrigatório e validado.
 * Recusa entradas extras, nomes duplicados e diretórios suspeitos antes de gravar qualquer arquivo.
 */
function media_bundle_verify_manifest(ZipArchive $zip,array $manifest,string $dbEntry,string $dbRaw): void {
    if(array_key_exists('db_sha256',$manifest)) {
        $hash=$manifest['db_sha256'];
        if(!is_string($hash)||!preg_match('/^[a-f0-9]{64}$/D',$hash)||!hash_equals($hash,hash('sha256',$dbRaw)))
            throw new RuntimeException('Integridade do banco do pacote não confere (SHA-256).');
    }
    $expected=['manifest.json'=>true,$dbEntry=>true];
    foreach($manifest['files'] as $name=>$sha){
        if(!is_string($name) || isset($expected[$name]))throw new RuntimeException('Manifesto contém entrada inválida ou repetida.');
        $expected[$name]=true;
    }
    $seen=[];
    for($i=0;$i<$zip->numFiles;$i++){
        $stat=$zip->statIndex($i);
        $name=$stat['name']??null;
        if(!is_string($name)||!isset($expected[$name])||isset($seen[$name]))
            throw new RuntimeException('ZIP contém arquivo inesperado ou repetido.');
        $seen[$name]=true;
    }
    if(count($seen)!==count($expected))throw new RuntimeException('ZIP incompleto ou incompatível com o manifesto.');
}

/** Restaura pacote completo, verificando tudo antes de substituir o banco. */
function restore_media_bundle(PDO $db,string $path,bool $skipPreventiveBackup=false): void {
    if(!class_exists('ZipArchive'))throw new RuntimeException('Ative a extensão PHP zip para importar este pacote.');
    if(!is_file($path)||!media_bundle_size_allowed((int)filesize($path)))throw new RuntimeException('Arquivo ZIP inválido ou acima de 350 MiB.');
    $zip=new ZipArchive();if($zip->open($path)!==true)throw new RuntimeException('Não foi possível abrir ZIP.');
    $staged=[];$promoted=[];
    try{
        if($zip->numFiles>MEDIA_BUNDLE_MAX_FILES+2)throw new RuntimeException('Pacote contém arquivos demais.');
        $manifestStat=$zip->statName('manifest.json');
        if(!$manifestStat || ($manifestStat['size']??0)>1024*1024)throw new RuntimeException('Manifesto ausente ou excessivamente grande.');
        $manifest=json_decode((string)$zip->getFromName('manifest.json'),true,50,JSON_THROW_ON_ERROR);
        if(($manifest['format']??'')!=='iguanews-media-bundle'||!in_array(($manifest['version']??0),[1,2],true)||!is_array($manifest['files']??null))throw new RuntimeException('Manifesto de migração inválido.');
        if(count($manifest['files'])>MEDIA_BUNDLE_MAX_FILES)throw new RuntimeException('Pacote contém mídias demais.');
        $dbEntry=(string)($manifest['db']??'');
        if(!preg_match('~^db/manual-\d{8}-\d{6}-[a-f0-9]{6}\.json(?:\.gz)?$~D',$dbEntry))throw new RuntimeException('Banco de dados do pacote inválido.');
        $dbStat=$zip->statName($dbEntry);
        if(!$dbStat||($dbStat['size']??0)>45*1024*1024)throw new RuntimeException('Backup do banco não encontrado ou grande demais.');
        $raw=$zip->getFromName($dbEntry);
        if($raw===false||strlen($raw)>45*1024*1024)throw new RuntimeException('Backup do banco não encontrado.');
        media_bundle_verify_manifest($zip,$manifest,$dbEntry,$raw);
        $data=parse_backup($raw,basename($dbEntry));unset($raw);
        // Recusar pacotes que omitem mídias referenciadas no banco.
        $required=[];
        foreach($data['tables']['article_media'] as $ref){
            $kind=(string)($ref['kind']??'');$fname=(string)($ref['filename']??'');
            if(!in_array($kind,['image','attachment'],true))throw new RuntimeException('Tipo de mídia no banco inválido.');
            $required[($kind==='image'?'assets/images/':'assets/docs/').$fname]=true;
        }
        foreach($data['tables']['articles'] as $ref){
            $fname=(string)($ref['image']??'');
            if(media_valid_name($fname))$required['assets/images/'.$fname]=true;
        }
        foreach($data['tables']['ad_campaigns'] as $ref){
            $fname=(string)($ref['image']??'');if(media_valid_name($fname))$required['assets/images/'.$fname]=true;
        }
        foreach($data['tables']['media_library'] as $ref){
            $fname=(string)($ref['filename']??'');if(media_valid_name($fname))$required['assets/images/'.$fname]=true;
        }
        foreach($required as $key=>$present)if(!array_key_exists($key,$manifest['files']))throw new RuntimeException('Pacote incompleto: imagem ou anexo ausente.');
        $total=0;
        foreach($manifest['files'] as $name=>$checksum){
            if(!is_string($name)||!(str_starts_with($name,'assets/images/')?media_valid_name(substr($name,strlen('assets/images/'))):(str_starts_with($name,'assets/docs/')&&news_attachment_storage_name_valid(substr($name,strlen('assets/docs/')))))||!is_string($checksum)||!preg_match('/^[a-f0-9]{64}$/D',$checksum))throw new RuntimeException('Nome de arquivo ou hash inválido no pacote.');
            $idx=$zip->locateName($name);if($idx===false)throw new RuntimeException('Arquivo ausente no pacote: '.$name);
            $stat=$zip->statIndex($idx);$size=$stat['size']??-1;
            if(!is_int($size)||$size<0||$size>MEDIA_BUNDLE_MAX_MEDIA_BYTES-$total)throw new RuntimeException('Pacote descompactado muito grande.');
            $total+=$size;
            $target=str_starts_with($name,'assets/images/') ? IGUA_PUBLIC.'/uploads/'.substr($name,strlen('assets/images/')) : IGUA_STORAGE.'/media/'.basename($name);
            $root=str_starts_with($name,'assets/images/')?IGUA_PUBLIC.'/uploads':IGUA_STORAGE.'/media';
            $temp=media_bundle_stage_entry($zip,$name,$checksum,$target,$size,$root);
            if($temp!==null)$staged[$target]=$temp;
        }
        // Antes de tocar no banco, confirme que todas as mídias cabem no disco.
        if(!$skipPreventiveBackup)make_backup($db); // Migração usa banco novo vazio; não grava log preventivo desnecessário.
        try{
            foreach($staged as $target=>$temp){
                if(file_exists($target)||is_link($target))throw new RuntimeException('Destino mudou durante restauração: '.basename($target));
                if(!@rename($temp,$target))throw new RuntimeException('Falha ao gravar mídia: '.basename($target));
                $promoted[]=$target;
                @chmod($target,0640);
            }
            restore_backup($db,$data); // transação MySQL; se falhar, retira apenas arquivos novos.
        }catch(Throwable $e){
            foreach($promoted as $target)@unlink($target);
            throw $e;
        }
    }finally{
        foreach($staged as $temp)if(is_file($temp))@unlink($temp);
        $zip->close();
    }
}
