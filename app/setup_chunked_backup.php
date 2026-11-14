<?php
declare(strict_types=1);
/**
 * Importação segmentada durante a primeira instalação.
 * Nenhum arquivo é armazenado na pasta pública; sessão e CSRF vinculam os blocos.
 * Não eleva limites PHP do provedor, apenas utiliza requisições menores.
 */
const IGUA_SETUP_CHUNK_TTL = 3600;
const IGUA_SETUP_CHUNK_MAX_BYTES = 524288; // 512 KiB por requisição

function igua_setup_chunk_size(string $uploadIni, string $postIni): int {
    $upload=igua_php_size($uploadIni);
    $post=igua_php_size($postIni);
    if ($upload<262144 || $post<262144) return 0;
    // Margem de 50% para metadados multipart e eventuais limites intermediários.
    return max(0,min(IGUA_SETUP_CHUNK_MAX_BYTES,intdiv(min($upload,$post),2)));
}
function igua_setup_chunk_enabled(): bool {
    return filter_var((string)ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN)
        && igua_setup_chunk_size((string)ini_get('upload_max_filesize'),(string)ini_get('post_max_size'))>=131072;
}
/** Pasta privada, sem aceitar links simbólicos. */
function igua_setup_chunks_directory(): string {
    $dir=IGUA_STORAGE.'/setup-imports';
    if (is_link(IGUA_STORAGE) || !is_dir(IGUA_STORAGE) || is_link($dir)) {
        throw new RuntimeException('Área privada de uploads não está disponível.');
    }
    if (!is_dir($dir) && !@mkdir($dir,0700) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível criar a área privada de importação.');
    }
    @chmod($dir,0700);
    if (is_link($dir) || !is_writable($dir)) throw new RuntimeException('Área privada de importação não é gravável.');
    return $dir;
}
function igua_setup_chunk_path(string $id): string {
    if (!preg_match('/\A[a-f0-9]{32}\z/D',$id)) throw new DomainException('Identificador de upload inválido.');
    return igua_setup_chunks_directory().'/'.$id.'.part';
}
function igua_setup_clear_chunk(): void {
    $staged=$_SESSION['iguanews_setup_chunk']??null;
    if (is_array($staged) && isset($staged['id']) && preg_match('/\A[a-f0-9]{32}\z/D',(string)$staged['id'])) {
        try {
            $p=igua_setup_chunk_path((string)$staged['id']);
            if (is_file($p) && !is_link($p)) @unlink($p);
        } catch (Throwable) { /* falha não deve comprometer encerramento da sessão */ }
    }
    unset($_SESSION['iguanews_setup_chunk']);
}
/** Remove apenas blocos antigos com nome gerado pelo próprio instalador. */
function igua_setup_cleanup_stale_chunks(): void {
    $dir=igua_setup_chunks_directory();
    $items=@scandir($dir);
    if (!is_array($items)) return;
    $now=time();
    foreach ($items as $entry) {
        if (!preg_match('/\A[a-f0-9]{32}\.part\z/D',$entry)) continue;
        $path=$dir.'/'.$entry;
        if (!is_file($path) || is_link($path)) continue;
        $modified=@filemtime($path);
        if ($modified!==false && $modified<$now-IGUA_SETUP_CHUNK_TTL) @unlink($path);
    }
}
/** Processa um único bloco. Chamado exclusivamente pela ação HTTP protegida do instalador. */
function igua_setup_receive_chunk(array $upload, string $name, int $total, int $offset, string $id): array {
    if (!igua_setup_chunk_enabled()) throw new DomainException('Envio segmentado indisponível neste servidor PHP.');
    if ($total<1 || $total>IGUA_BACKUP_IMPORT_MAX_BYTES || !preg_match('~\A[\p{L}\p{N}._ ()-]{1,180}\.json(?:\.gz)?\z~iuD',$name)) {
        throw new DomainException('Escolha um backup .json ou .json.gz de até 15 MiB.');
    }
    $chunkSize=(int)($upload['size']??0);
    $maxChunk=igua_setup_chunk_size((string)ini_get('upload_max_filesize'),(string)ini_get('post_max_size'));
    if ((int)($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || $chunkSize<1 || $chunkSize>$maxChunk || $offset<0 || $offset+$chunkSize>$total) {
        throw new DomainException('Bloco inválido ou acima do limite de envio do servidor.');
    }
    $tmp=(string)($upload['tmp_name']??'');
    if (!is_uploaded_file($tmp)) throw new DomainException('O bloco não foi recebido como upload HTTP válido.');
    if ($offset===0 && $id==='') {
        igua_setup_clear_chunk();
        igua_setup_cleanup_stale_chunks();
        $id=bin2hex(random_bytes(16));
        $path=igua_setup_chunk_path($id);
        $new=@fopen($path,'x+b');
        if (!$new) throw new RuntimeException('Não foi possível iniciar a importação privada.');
        @chmod($path,0600);
        fclose($new);
        $_SESSION['iguanews_setup_chunk']=['id'=>$id,'name'=>$name,'total'=>$total,'offset'=>0,'updated'=>time()];
    }
    $state=$_SESSION['iguanews_setup_chunk']??null;
    if (!is_array($state) || !hash_equals((string)($state['id']??''),$id) || $state['name']!==$name || (int)$state['total']!==$total || time()-(int)$state['updated']>IGUA_SETUP_CHUNK_TTL) {
        throw new DomainException('Envio expirado ou pertencente a outra sessão. Inicie novamente.');
    }
    $path=igua_setup_chunk_path($id);
    if (is_link($path) || !is_file($path)) throw new DomainException('Arquivo temporário de importação indisponível.');
    $destination=@fopen($path,'c+b');
    if (!$destination || !flock($destination,LOCK_EX)) {
        if (is_resource($destination)) fclose($destination);
        throw new RuntimeException('Arquivo de importação ocupado.');
    }
    try {
        $size=(int)(fstat($destination)['size']??-1);
        if ($size!==$offset || $size!==(int)$state['offset']) throw new DomainException('Sequência de blocos inválida. Reinicie a importação.');
        if (fseek($destination,0,SEEK_END)!==0) throw new RuntimeException('Falha ao posicionar arquivo de importação.');
        $source=@fopen($tmp,'rb');
        if (!$source) throw new RuntimeException('Falha ao ler bloco recebido.');
        $written=0;
        try {
            while (!feof($source)) {
                $buffer=fread($source,65536);
                if ($buffer===false) throw new RuntimeException('Falha de leitura do bloco.');
                if ($buffer==='') break;
                $length=strlen($buffer);
                $current=0;
                while ($current<$length) {
                    $n=fwrite($destination,substr($buffer,$current));
                    if ($n===false || $n===0) throw new RuntimeException('Sem espaço para gravar o backup.');
                    $current+=$n;
                }
                $written+=$length;
            }
        } finally { fclose($source); }
        if ($written!==$chunkSize || !fflush($destination)) throw new RuntimeException('Bloco recebido incompleto.');
        $_SESSION['iguanews_setup_chunk']['offset']=$offset+$written;
        $_SESSION['iguanews_setup_chunk']['updated']=time();
        @touch($path);
    } catch (Throwable $e) {
        @ftruncate($destination,$offset);
        throw $e;
    } finally {
        flock($destination,LOCK_UN);
        fclose($destination);
    }
    return ['ok'=>true,'upload_token'=>$id,'next_offset'=>$offset+$chunkSize,'complete'=>$offset+$chunkSize===$total];
}
/** O backup só é processado após a confirmação POST normal, no mesmo cookie de sessão. */
function igua_setup_read_staged_backup(string $id): array {
    $state=$_SESSION['iguanews_setup_chunk']??null;
    if (!is_array($state) || !preg_match('/\A[a-f0-9]{32}\z/D',$id) || !hash_equals((string)($state['id']??''),$id)
        || time()-(int)($state['updated']??0)>IGUA_SETUP_CHUNK_TTL) {
        throw new DomainException('O envio temporário expirou. Selecione o backup novamente.');
    }
    $path=igua_setup_chunk_path($id);
    $total=(int)$state['total'];
    if (is_link($path) || !is_file($path) || $total<1 || $total>IGUA_BACKUP_IMPORT_MAX_BYTES
        || (int)$state['offset']!==$total || filesize($path)!==$total) {
        throw new DomainException('Backup incompleto. Selecione o arquivo novamente.');
    }
    $raw=@file_get_contents($path);
    if ($raw===false || strlen($raw)!==$total) throw new RuntimeException('Falha ao ler backup temporário.');
    try { return parse_backup($raw,(string)$state['name']); }
    catch (Throwable $e) { throw new InvalidArgumentException('Arquivo de backup inválido ou incompatível.',0,$e); }
}
