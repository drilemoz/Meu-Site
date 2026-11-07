<?php
declare(strict_types=1);
/** Biblioteca central de fotografias IguaNews. Caminhos relativos, sem acesso a arquivos arbitrários. */
function media_valid_name(string $name): bool {
    return (bool)preg_match('~^(?:(?:20\d{2})/(?:0[1-9]|1[0-2])/)?[a-zA-Z0-9_-]{8,80}\.(?:jpe?g|png|webp)$~iD', $name);
}
function media_path(string $name): ?string {
    if(!media_valid_name($name))return null;
    $base=realpath(IGUA_PUBLIC.'/uploads');if($base===false)return null;
    $file=realpath($base.'/'.$name);
    return $file!==false && str_starts_with($file,$base.DIRECTORY_SEPARATOR) && is_file($file) ? $file:null;
}
function media_url(string $name): string {
    if(!media_valid_name($name))return '/assets/img/cidade.jpg';
    return '/uploads/'.implode('/',array_map('rawurlencode',explode('/',$name)));
}
function media_thumb(string $name): string {
    if(!media_valid_name($name))return '/assets/img/cidade.jpg';
    $thumb=dirname($name)==='.'? 'thumbs/'.pathinfo($name,PATHINFO_FILENAME).'.webp':dirname($name).'/thumbs/'.pathinfo($name,PATHINFO_FILENAME).'.webp';
    return is_file(IGUA_PUBLIC.'/uploads/'.$thumb)?media_thumb_url($thumb):media_url($name);
}
function media_thumb_url(string $thumb): string {return '/uploads/'.implode('/',array_map('rawurlencode',explode('/',$thumb)));}
function migrate_media_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS media_library (
      id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
      filename VARCHAR(150) NOT NULL, original_name VARCHAR(200) NOT NULL DEFAULT '',
      title VARCHAR(200) NOT NULL DEFAULT '', caption VARCHAR(360) NOT NULL DEFAULT '',
      credit VARCHAR(180) NOT NULL DEFAULT '', alt_text VARCHAR(220) NOT NULL DEFAULT '',
      mime VARCHAR(40) NOT NULL, bytes INT UNSIGNED NOT NULL DEFAULT 0,
      width INT UNSIGNED NOT NULL DEFAULT 0, height INT UNSIGNED NOT NULL DEFAULT 0,
      sha256 CHAR(64) NOT NULL DEFAULT '', uploaded_by INT UNSIGNED NULL,
      folder_year SMALLINT UNSIGNED NOT NULL, folder_month TINYINT UNSIGNED NOT NULL,
      deleted_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_media_filename(filename), INDEX idx_media_folder(folder_year,folder_month,deleted_at),
      INDEX idx_media_created(created_at), INDEX idx_media_hash(sha256)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    // Compatibilidade: fotos legadas permanecem nos mesmos endereços e são indexadas uma única vez.
    $has=$db->prepare('SELECT value FROM settings WHERE name=?');$has->execute(['media_legacy_indexed']);
    if($has->fetchColumn()!==false)return;
    $base=IGUA_PUBLIC.'/uploads';
    if(is_dir($base)){
        $iter=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base,FilesystemIterator::SKIP_DOTS));
        $count=0;
        foreach($iter as $file){
            if(++$count>30000)break;
            if(!$file->isFile()||str_contains(str_replace('\\','/',$file->getPathname()),'/thumbs/'))continue;
            $name=str_replace('\\','/',substr($file->getPathname(),strlen($base)+1));
            if(media_valid_name($name)) media_register($db,$name,$name,'','',null);
        }
    }
    $db->prepare('INSERT INTO settings(name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')->execute(['media_legacy_indexed','1']);
}
function media_register(PDO $db,string $filename,string $original='',string $caption='',string $credit='',?int $userId=null): ?int {
    $path=media_path($filename);if(!$path)return null;
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
    if(!in_array($mime,['image/jpeg','image/png','image/webp'],true))return null;
    $dim=@getimagesize($path);if(!$dim||$dim[0]*$dim[1]>24000000)return null;
    $size=(int)filesize($path);if($size<20||$size>12*1024*1024)return null;
    $parts=explode('/',$filename);$year=count($parts)===3?(int)$parts[0]:(int)date('Y',(int)filemtime($path));
    $month=count($parts)===3?(int)$parts[1]:(int)date('n',(int)filemtime($path));
    $original=usub(trim(str_replace(["\r","\n",'/','\\'],['_','_','_','_'],$original?:basename($filename))),0,200);
    $title=usub(pathinfo($original,PATHINFO_FILENAME),0,200);
    $sql="INSERT INTO media_library(filename,original_name,title,caption,credit,mime,bytes,width,height,sha256,uploaded_by,folder_year,folder_month) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE filename=VALUES(filename)";
    $db->prepare($sql)->execute([$filename,$original,$title,usub($caption,0,360),usub($credit,0,180),$mime,$size,(int)$dim[0],(int)$dim[1],hash_file('sha256',$path),$userId,$year,$month]);
    $s=$db->prepare('SELECT id FROM media_library WHERE filename=?');$s->execute([$filename]);return (int)$s->fetchColumn();
}
function media_optimize_to_file(string $source,string $dest,string $mime): void {
    // Formato original permanece disponível caso GD esteja ausente ou a conversão não reduza bytes.
    $size=(int)filesize($source);$dim=@getimagesize($source);
    $fn=['image/jpeg'=>'imagecreatefromjpeg','image/png'=>'imagecreatefrompng','image/webp'=>'imagecreatefromwebp'][$mime]??null;
    if(!$fn||!function_exists($fn)||!function_exists('imagewebp')||!function_exists('imagescale')||!$dim || $dim[0]*$dim[1]>14000000){
        if(!move_uploaded_file($source,$dest))throw new RuntimeException('Falha ao armazenar imagem.');return;
    }
    $image=@$fn($source);if(!$image){if(!move_uploaded_file($source,$dest))throw new RuntimeException('Falha ao armazenar imagem.');return;}
    try{
        $ratio=min(1,1920/max($dim[0],$dim[1]));
        $scaled=$ratio<1?@imagescale($image,max(1,(int)round($dim[0]*$ratio)),max(1,(int)round($dim[1]*$ratio)),IMG_BILINEAR_FIXED):$image;
        if(!$scaled){if(!move_uploaded_file($source,$dest))throw new RuntimeException('Falha ao salvar imagem.');return;}
        imagealphablending($scaled,false);imagesavealpha($scaled,true);
        $temporary=$dest.'.tmp';$ok=@imagewebp($scaled,$temporary,85);
        if($scaled!==$image)imagedestroy($scaled);
        if($ok && is_file($temporary) && filesize($temporary)<$size*0.98 && filesize($temporary)>100){
            // A extensão do arquivo é decidida pelo chamador; somente codificar WebP em destino .webp.
            if(str_ends_with($dest,'.webp')){rename($temporary,$dest);return;}
        }
        @unlink($temporary);
        if(!move_uploaded_file($source,$dest))throw new RuntimeException('Falha ao armazenar imagem.');
    }finally{imagedestroy($image);}
}
/** Somente caminhos criados nesta operação, informados pelo servidor (nunca pelo POST).
 * Remove também miniaturas criadas para fotos novas, sem tocar em imagens reutilizadas.
 */
function igua_discard_new_uploads(array $files): void {
    $uploads=IGUA_PUBLIC.'/uploads/';
    $attachments=IGUA_STORAGE.'/media/';
    foreach(array_reverse($files) as $file){
        if(!is_string($file))continue;
        if(str_starts_with($file,$uploads) && preg_match('~^(?:20\d{2}/(?:0[1-9]|1[0-2])/)?[a-zA-Z0-9_-]{8,80}\.(?:jpe?g|png|webp)$~iD',substr($file,strlen($uploads)))){
            $thumb=dirname($file).'/thumbs/'.pathinfo($file,PATHINFO_FILENAME).'.webp';
            if(is_file($thumb))@unlink($thumb);
            if(is_file($file))@unlink($file);
        } elseif (str_starts_with($file,$attachments) && preg_match('/^[a-f0-9]{32}\.(?:pdf|txt|csv)$/D',substr($file,strlen($attachments)))){
            if(is_file($file))@unlink($file);
        }
    }
}

/** Grava upload em AAAA/MM, preservando a transparência e validando conteúdo real. */
function media_upload(PDO $db,array $file,?int $userId=null,?callable $onCreated=null): array {
    $error=(int)($file['error']??UPLOAD_ERR_NO_FILE);
    if($error!==UPLOAD_ERR_OK)throw new RuntimeException(igua_upload_error_message($error));
    $tmp=(string)($file['tmp_name']??'');$size=(int)($file['size']??0);
    if($size<20||$size>8*1048576||!is_uploaded_file($tmp))throw new RuntimeException('Imagem inválida ou superior a 8 MB.');
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
    $dim=$ext?@getimagesize($tmp):false;
    if(!$ext||!$dim||$dim[0]<16||$dim[1]<16||$dim[0]*$dim[1]>24000000)throw new RuntimeException('Imagem inválida. JPG, PNG ou WebP até 24 megapixels.');
    $y=date('Y');$m=date('m');$dir=IGUA_PUBLIC.'/uploads/'.$y.'/'.$m;
    if(!is_dir($dir)&&!@mkdir($dir,0755,true))throw new RuntimeException('Falha ao criar pasta de imagens.');
    $hash=bin2hex(random_bytes(16));
    // WebP só é selecionado se o motor tiver suporte real e possibilidade de otimizar.
    $useWebp=function_exists('imagewebp')&&function_exists('imagescale')&&function_exists(['image/jpeg'=>'imagecreatefromjpeg','image/png'=>'imagecreatefrompng','image/webp'=>'imagecreatefromwebp'][$mime]);
    $name=$y.'/'.$m.'/'.$hash.'.'.($useWebp?'webp':$ext);$target=IGUA_PUBLIC.'/uploads/'.$name;
    try {
        if($useWebp){
            media_optimize_to_file($tmp,$target,$mime);
            // Fallback da otimização preserva bytes originais; nunca entregar JPG com extensão WebP.
            if((new finfo(FILEINFO_MIME_TYPE))->file($target)!=='image/webp'){
                $correct=substr($target,0,-4).$ext;
                if(!@rename($target,$correct))throw new RuntimeException('Não foi possível concluir a conversão da foto.');
                $name=$y.'/'.$m.'/'.$hash.'.'.$ext;$target=$correct;
            }
        }elseif(!move_uploaded_file($tmp,$target))throw new RuntimeException('Não foi possível armazenar a foto.');
    }catch(Throwable $e){
        // Mesmo falhas durante conversão e renomeação não deixam arquivos parciais.
        @unlink($dir.'/'.$hash.'.webp');
        @unlink($dir.'/'.$hash.'.webp.tmp');
        @unlink($dir.'/'.$hash.'.'.$ext);
        throw $e;
    }
    @chmod($target,0644);
    try {
        // Registra o caminho novo imediatamente, antes da escrita SQL.
        // A transação que chama o serviço poderá removê-lo em caso de rollback.
        if($onCreated!==null)$onCreated($target);
        $id=media_register($db,$name,(string)($file['name']??basename($name)),'','',$userId);
        if(!$id)throw new RuntimeException('Imagem rejeitada após validação.');
        if(function_exists('news_try_thumbnail'))news_try_thumbnail($target,$name,(new finfo(FILEINFO_MIME_TYPE))->file($target));
        return ['id'=>$id,'filename'=>$name,'url'=>media_url($name),'thumb'=>media_thumb($name),'bytes'=>(int)filesize($target)];
    }catch(Throwable $e){
        igua_discard_new_uploads([$target]);
        throw $e;
    }
}
function media_link_count(PDO $db,string $filename): int {
    $total=0;
    foreach(['SELECT COUNT(*) FROM articles WHERE image=?','SELECT COUNT(*) FROM article_media WHERE kind=\'image\' AND filename=?','SELECT COUNT(*) FROM ad_campaigns WHERE image=?'] as $sql){$s=$db->prepare($sql);$s->execute([$filename]);$total+=(int)$s->fetchColumn();}
    return $total;
}
function media_browse(PDO $db,array $params): array {
    $year=filter_var($params['year']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>2000,'max_range'=>2100]])?:0;
    $month=filter_var($params['month']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1,'max_range'=>12]])?:0;
    $trash=($params['trash']??'')==='1';$search=trim((string)($params['q']??''));
    if(ulen($search)>100)$search=usub($search,0,100);
    $where=['deleted_at IS '.($trash?'NOT NULL':'NULL')];$args=[];
    if($year){$where[]='folder_year=?';$args[]=$year;}
    if($month&&$year){$where[]='folder_month=?';$args[]=$month;}
    if($search!==''){$where[]='(original_name LIKE ? OR title LIKE ? OR caption LIKE ? OR credit LIKE ?)';$term='%'.str_replace(['%','_'],['\\%','\\_'],$search).'%';array_push($args,$term,$term,$term,$term);}
    $condition=implode(' AND ',$where);
    $count=$db->prepare('SELECT COUNT(*) FROM media_library WHERE '.$condition);$count->execute($args);
    $total=(int)$count->fetchColumn();$page=max(1,min(10000,(int)($params['page']??1)));$offset=($page-1)*48;
    $stmt=$db->prepare('SELECT id,filename,title,caption,credit,alt_text,original_name,bytes,width,height,folder_year,folder_month,created_at,deleted_at FROM media_library WHERE '.$condition.' ORDER BY id DESC LIMIT 48 OFFSET '.$offset);
    $stmt->execute($args);$rows=$stmt->fetchAll();
    foreach($rows as &$item){$item['url']=media_url($item['filename']);$item['thumb']=media_thumb($item['filename']);$item['missing']=media_path((string)$item['filename'])===null;}unset($item);
    $folders=$db->query('SELECT folder_year,folder_month,COUNT(*) AS total FROM media_library WHERE deleted_at IS NULL GROUP BY folder_year,folder_month ORDER BY folder_year DESC,folder_month DESC')->fetchAll();
    return ['items'=>$rows,'total'=>$total,'page'=>$page,'pages'=>max(1,(int)ceil($total/48)),'folders'=>$folders];
}
