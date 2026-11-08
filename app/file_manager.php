<?php
declare(strict_types=1);
/** Gerenciador restrito. Nunca monta caminhos a partir da URL fora de public/uploads/arquivos. */
const IGUA_FILES_MAX_BYTES = 8 * 1024 * 1024;
const IGUA_FILES_TEXT_MAX = 128 * 1024;
const IGUA_FILES_EXTENSIONS = ['jpg','jpeg','png','webp','pdf','txt','csv'];

function igua_files_root(): string { return IGUA_PUBLIC.'/uploads/arquivos'; }
function igua_files_name(string $name): bool {
    return strlen($name)>0 && strlen($name)<=100 &&
      (bool)preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\z/D',$name) &&
      $name!=='.' && $name!=='..' && !str_starts_with($name,'.') &&
      !str_contains($name,'..');
}
function igua_files_relative(string $relative): string {
    if(strlen($relative)>400 || str_contains($relative,"\0") || str_contains($relative,'\\'))
        throw new InvalidArgumentException('Caminho inválido.');
    if($relative==='')return '';
    foreach(explode('/',$relative) as $segment){
        if(!igua_files_name($segment))throw new InvalidArgumentException('Nome de pasta ou arquivo inválido.');
    }
    return $relative;
}
function igua_files_extension(string $name): string { return strtolower(pathinfo($name,PATHINFO_EXTENSION)); }
function igua_files_allowed(string $name): bool {
    return igua_files_name($name) && in_array(igua_files_extension($name),IGUA_FILES_EXTENSIONS,true)
        && !preg_match('/(?:^|\.)(?:php[0-9]*|phtml|phar|cgi|pl|sh|html?|js|svg)(?:\.|$)/i',$name);
}
function igua_files_is_below(string $candidate,string $root): bool {
    return $candidate===$root || str_starts_with($candidate,$root.DIRECTORY_SEPARATOR);
}
function igua_files_root_ready(bool $create=false): ?string {
    $uploads=realpath(IGUA_PUBLIC.'/uploads');
    $public=realpath(IGUA_PUBLIC);
    if($uploads===false || $public===false || !igua_files_is_below($uploads,$public) || is_link(IGUA_PUBLIC.'/uploads'))
        throw new RuntimeException('Pasta pública de uploads indisponível.');
    $folder=IGUA_PUBLIC.'/uploads/arquivos';
    if(is_link($folder))throw new InvalidArgumentException('Pasta protegida contra links simbólicos.');
    if(!is_dir($folder) && $create){
        if(!@mkdir($folder,0755) && !is_dir($folder))throw new RuntimeException('Sem permissão para criar a pasta de arquivos.');
    }
    if(!is_dir($folder))return null;
    $resolved=realpath($folder);
    if($resolved===false || !igua_files_is_below($resolved,$uploads))throw new InvalidArgumentException('Pasta fora da área autorizada.');
    return $resolved;
}
/** Retorna arquivo/diretório existente, sem atravessar symlink em qualquer segmento. */
function igua_files_resolve(string $relative,bool $directory=false): ?string {
    $relative=igua_files_relative($relative);
    $root=igua_files_root_ready(false);
    if($root===null)return null;
    $path=$root;
    foreach($relative===''?[]:explode('/',$relative) as $part){
        $path.='/'.$part;
        if(is_link($path))throw new InvalidArgumentException('Links simbólicos não são permitidos.');
    }
    $resolved=realpath($path);
    if($resolved===false)return null;
    if(!igua_files_is_below($resolved,$root))throw new InvalidArgumentException('Arquivo fora da área autorizada.');
    if($directory){if(!is_dir($resolved))return null;}
    elseif(!is_file($resolved) || !igua_files_allowed(basename($resolved)))return null;
    return $resolved;
}
function igua_files_prepare_dir(string $relative): string {
    $relative=igua_files_relative($relative);
    $root=igua_files_root_ready(true);
    if($root===null)throw new RuntimeException('Pasta indisponível.');
    $dir=igua_files_resolve($relative,true);
    if($dir===null)throw new InvalidArgumentException('Pasta não encontrada.');
    return $dir;
}
function igua_files_special(string $filename): ?string {
    if(!in_array($filename,['ads.txt','robots.txt'],true))return null;
    $root=realpath(IGUA_PUBLIC);
    if($root===false)return null;
    $path=IGUA_PUBLIC.'/'.$filename;
    if(is_link($path) || (file_exists($path) && !is_file($path)))throw new InvalidArgumentException('Arquivo especial inválido.');
    if(is_file($path) && realpath($path)!==$root.'/'.$filename)throw new InvalidArgumentException('Arquivo especial fora da raiz.');
    return $path;
}
function igua_files_list(string $relative): array {
    $relative=igua_files_relative($relative);
    $dir=igua_files_resolve($relative,true);
    if($dir===null)return [];
    $result=[];
    foreach(new DirectoryIterator($dir) as $item){
        if($item->isDot() || $item->isLink())continue;
        $name=$item->getFilename();
        if(!igua_files_name($name))continue;
        if(!$item->isDir() && (!$item->isFile() || !igua_files_allowed($name)))continue;
        $result[]=['name'=>$name,'dir'=>$item->isDir(),'size'=>$item->isFile()?$item->getSize():null,
          'modified'=>$item->getMTime()];
        if(count($result)>=500)break;
    }
    usort($result,static fn(array $a,array $b)=>($b['dir']<=>$a['dir']) ?: strnatcasecmp($a['name'],$b['name']));
    return $result;
}
function igua_files_text_read(string $path): string {
    if(!is_file($path) || filesize($path)>IGUA_FILES_TEXT_MAX)throw new InvalidArgumentException('Arquivo de texto maior que o limite de 128 KiB.');
    $content=file_get_contents($path);
    if($content===false || str_contains($content,"\0") || !preg_match('//u',$content))throw new InvalidArgumentException('O arquivo não contém texto UTF-8 válido.');
    return $content;
}
function igua_files_write_text(string $path,string $content,?string $expectedHash): void {
    if(strlen($content)>IGUA_FILES_TEXT_MAX || str_contains($content,"\0") || !preg_match('//u',$content))
        throw new InvalidArgumentException('O texto deve ser UTF-8 e ter até 128 KiB.');
    if(is_link($path) || (file_exists($path) && !is_file($path)))throw new InvalidArgumentException('Destino inválido.');
    $current=is_file($path)?hash_file('sha256',$path):'missing';
    if(!is_string($current) || $expectedHash===null || !hash_equals($current,$expectedHash))
        throw new InvalidArgumentException('O arquivo foi modificado desde a abertura. Reabra antes de salvar.');
    $dir=dirname($path);
    if(!is_dir($dir) || !is_writable($dir))throw new RuntimeException('A pasta não permite escrita.');
    $temp=tempnam($dir,'.igua-');
    if($temp===false)throw new RuntimeException('Falha ao preparar gravação.');
    try {
        if(file_put_contents($temp,$content,LOCK_EX)!==strlen($content))throw new RuntimeException('Falha ao gravar texto.');
        @chmod($temp,0644);
        if(!@rename($temp,$path))throw new RuntimeException('Não foi possível salvar o arquivo.');
    } finally {if(is_file($temp))@unlink($temp);}
}
function igua_files_upload(array $upload,string $relative): string {
    $dir=igua_files_prepare_dir($relative);
    if(($upload['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK || !is_string($upload['tmp_name']??null) || !is_uploaded_file($upload['tmp_name']))
        throw new InvalidArgumentException('Selecione um arquivo válido no celular.');
    $name=(string)($upload['name']??'');
    $bytes=(int)($upload['size']??0);
    if(!igua_files_allowed($name) || $bytes<=0 || $bytes>IGUA_FILES_MAX_BYTES)
        throw new InvalidArgumentException('Envie imagem JPG/PNG/WebP, PDF, TXT ou CSV com até 8 MiB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($upload['tmp_name']);
    $ext=igua_files_extension($name);
    $expected=['jpg'=>['image/jpeg'],'jpeg'=>['image/jpeg'],'png'=>['image/png'],'webp'=>['image/webp'],
      'pdf'=>['application/pdf'],'txt'=>['text/plain'],'csv'=>['text/plain','text/csv','application/csv','application/vnd.ms-excel']];
    if(!in_array($mime,$expected[$ext]??[],true))throw new InvalidArgumentException('Tipo real do arquivo não corresponde à extensão.');
    if(in_array($ext,['jpg','jpeg','png','webp'],true) && !@getimagesize($upload['tmp_name']))throw new InvalidArgumentException('Imagem inválida.');
    if($ext==='pdf' && file_get_contents($upload['tmp_name'],false,null,0,5)!=='%PDF-')throw new InvalidArgumentException('PDF inválido.');
    if(in_array($ext,['txt','csv'],true))igua_files_text_read($upload['tmp_name']);
    $destination=$dir.'/'.$name;
    if(file_exists($destination) || is_link($destination))throw new InvalidArgumentException('Já existe um arquivo com esse nome.');
    // x: evita sobrescrever uma criação concorrente e limita a um arquivo novo.
    $target=@fopen($destination,'x');
    if($target===false)throw new RuntimeException('Não foi possível criar arquivo. Verifique permissões e nome.');
    try {
        $input=@fopen($upload['tmp_name'],'rb');
        if($input===false)throw new RuntimeException('Falha ao abrir arquivo enviado.');
        try {$copied=stream_copy_to_stream($input,$target,IGUA_FILES_MAX_BYTES+1);}finally{fclose($input);}
        if($copied!==$bytes)throw new RuntimeException('Envio incompleto.');
    }catch(Throwable $e){fclose($target);@unlink($destination);throw $e;}
    fclose($target);@chmod($destination,0644);
    return $name;
}
function igua_files_new_folder(string $relative,string $name): void {
    if(!igua_files_name($name) || str_contains($name,'.'))throw new InvalidArgumentException('Use nome de pasta sem ponto (letras, números, traço ou sublinhado).');
    $dir=igua_files_prepare_dir($relative);
    if(!@mkdir($dir.'/'.$name,0755))throw new InvalidArgumentException('Não foi possível criar a pasta; verifique se já existe.');
}
function igua_files_rename(string $relative,string $newName): void {
    $relative=igua_files_relative($relative);
    if($relative==='' || !igua_files_name($newName))throw new InvalidArgumentException('Nome inválido.');
    $parent=dirname($relative);if($parent==='.')$parent='';
    $old=igua_files_resolve($relative,true) ?? igua_files_resolve($relative,false);
    if($old===null)throw new InvalidArgumentException('Item não encontrado.');
    if(is_dir($old) ? str_contains($newName,'.') : (!igua_files_allowed($newName) || igua_files_extension($relative)!==igua_files_extension($newName)))throw new InvalidArgumentException('O novo nome ou formato não é permitido. A extensão deve ser preservada.');
    $dest=igua_files_prepare_dir($parent).'/'.$newName;
    if(file_exists($dest)||is_link($dest))throw new InvalidArgumentException('O nome já está em uso.');
    if(!@rename($old,$dest))throw new RuntimeException('Não foi possível renomear.');
}
function igua_files_delete(string $relative): void {
    $relative=igua_files_relative($relative);
    if($relative==='')throw new InvalidArgumentException('A pasta principal não pode ser removida.');
    $file=igua_files_resolve($relative,false);
    $folder=$file===null?igua_files_resolve($relative,true):null;
    if($file!==null){if(!@unlink($file))throw new RuntimeException('Não foi possível excluir.');return;}
    if($folder!==null){if(!@rmdir($folder))throw new InvalidArgumentException('Só é possível excluir pastas vazias.');return;}
    throw new InvalidArgumentException('Item inexistente ou não permitido.');
}
function igua_files_download(string $scope,string $relative): void {
    try {$path=$scope==='special'?igua_files_special($relative):igua_files_resolve($relative,false);}
    catch(InvalidArgumentException $e){http_response_code(404);exit('Arquivo indisponível.');}
    if($path===null || !is_file($path)) {http_response_code(404);exit('Arquivo indisponível.');}
    $size=filesize($path);
    if($size===false||$size>IGUA_FILES_MAX_BYTES){http_response_code(413);exit('Arquivo muito grande para este recurso.');}
    header('Cache-Control: private, no-store');header('X-Content-Type-Options: nosniff');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.basename($path).'"');
    header('Content-Length: '.$size);
    readfile($path);exit;
}
