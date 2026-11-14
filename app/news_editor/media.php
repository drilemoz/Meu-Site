<?php
declare(strict_types=1);
/** IguaNews: serviço editorial, módulo media. Requer bootstrap.php no ponto de entrada. */

function article_media(PDO $db,int $articleId): array {
    try{$s=$db->prepare('SELECT * FROM article_media WHERE article_id=? ORDER BY sort_order,id');$s->execute([$articleId]);return $s->fetchAll();}catch(PDOException){return [];}
}

function news_media_upload(PDO $db,int $articleId,array $files,string $kind,array $captions=[],array $credits=[],?callable $onCreated=null): int {
    if(empty($files['name'])||!is_array($files['name']))return 0;
    $n=count($files['name']);if($n>10)throw new RuntimeException('Máximo de 10 arquivos por envio.');
    $count=0;$pdo=$db->prepare('INSERT INTO article_media(article_id,kind,filename,original_name,mime,bytes,caption,credit,sort_order) VALUES (?,?,?,?,?,?,?,?,?)');
    $destDir=$kind==='image'?IGUA_PUBLIC.'/uploads':IGUA_STORAGE.'/media';
    if(!is_dir($destDir)&&!mkdir($destDir,0700,true))throw new RuntimeException('Não foi possível criar o diretório de mídias.');
    foreach($files['name'] as $i=>$name){
        if((string)$name==='')continue;
        $error=(int)($files['error'][$i]??UPLOAD_ERR_NO_FILE);
        if($error!==UPLOAD_ERR_OK)throw new RuntimeException(igua_upload_error_message($error,$kind==='image'?'Foto da galeria':'Anexo'));
        $tmp=(string)($files['tmp_name'][$i]??'');$size=(int)($files['size'][$i]??0);
        $limit=$kind==='image'?8*1024*1024:12*1024*1024;
        if($size<20||$size>$limit||!is_uploaded_file($tmp))throw new RuntimeException('Arquivo inválido ou acima do limite permitido.');
        $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $ext=$kind==='image'?(['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null):news_attachment_extension((string)$name,(string)$mime,$tmp);
        if(!$ext)throw new RuntimeException('Formato de arquivo inválido ou não permitido. Anexos aceitam documentos, imagens, áudio, MP4 e ZIP válidos; scripts executáveis são bloqueados.');
        if($kind==='image'){
            $dimensions=@getimagesize($tmp);
            if(!$dimensions||(int)$dimensions[0]<100||(int)$dimensions[1]<100||(int)$dimensions[0]*(int)$dimensions[1]>24000000)throw new RuntimeException('Imagem inválida ou com resolução excessiva (máximo 24 MP).');
        }
        if($kind==='image'){
            $saved=media_upload($db,['name'=>$name,'error'=>$error,'tmp_name'=>$tmp,'size'=>$size],(int)(admin_user()['id']??0)?:null,$onCreated);
            $filename=$saved['filename'];$size=$saved['bytes'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file(media_path($filename));
        }else{
            $filename=bin2hex(random_bytes(16)).'.'.$ext;
            if(!move_uploaded_file($tmp,$destDir.'/'.$filename))throw new RuntimeException('Erro ao gravar anexo.');
            @chmod($destDir.'/'.$filename,0600);
            if($onCreated!==null)$onCreated($destDir.'/'.$filename);
        }
        $caption=usub(trim((string)($captions[$i]??'')),0,360);
        $credit=usub(trim((string)($credits[$i]??'')),0,180);
        $original=usub(trim(str_replace(["\r","\n",'/','\\'],['_','_','_','_'],(string)$name)),0,200);
        $pdo->execute([$articleId,$kind,$filename,$original,$mime,$size,$caption,$credit,$i]);$count++;
    }
    return $count;
}

function news_media_update(PDO $db,int $articleId,array $post): void {
    $media=article_media($db,$articleId);$upd=$db->prepare('UPDATE article_media SET caption=?,credit=?,sort_order=? WHERE id=? AND article_id=?');
    $del=$db->prepare('DELETE FROM article_media WHERE id=? AND article_id=?');
    foreach($media as $item){$id=(int)$item['id'];
        if(in_array((string)$id,$post['remove_media']??[],true)){$del->execute([$id,$articleId]);continue;}
        $caption=trim((string)($post['media_caption'][$id]??$item['caption']));
        $credit=trim((string)($post['media_credit'][$id]??$item['credit']));
        $sort=(int)($post['media_order'][$id]??$item['sort_order']);
        if(ulen($caption)>360||ulen($credit)>180)throw new RuntimeException('Legenda ou crédito longo demais.');
        $upd->execute([$caption,$credit,max(0,min($sort,1000)),$id,$articleId]);
    }
}

function news_try_thumbnail(string $file,string $name,string $mime): void {
    if(!function_exists('imagewebp')||!function_exists('imagescale'))return;
    $dims=@getimagesize($file);if(!$dims||$dims[0]*$dims[1]>14000000)return;
    $read=['image/jpeg'=>'imagecreatefromjpeg','image/png'=>'imagecreatefrompng','image/webp'=>'imagecreatefromwebp'][$mime]??null;
    if(!$read||!function_exists($read))return;
    try {
        $image=@$read($file);if(!$image)return;
        $ratio=min(1,700/max(imagesx($image),imagesy($image)));
        $small=@imagescale($image,max(1,(int)round(imagesx($image)*$ratio)),max(1,(int)round(imagesy($image)*$ratio)),IMG_BILINEAR_FIXED);
        imagedestroy($image);if(!$small)return;
        $relative=dirname($name);$folder=IGUA_PUBLIC.'/uploads/'.($relative==='.'?'':$relative.'/').'thumbs';
        if(!is_dir($folder))@mkdir($folder,0755,true);
        if(is_dir($folder))@imagewebp($small,$folder.'/'.pathinfo($name,PATHINFO_FILENAME).'.webp',78);
        imagedestroy($small);
    }catch(Throwable){}
}

function news_media_thumbnail(string $filename): string {return media_thumb($filename);}

