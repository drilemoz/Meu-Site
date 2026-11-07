<?php
declare(strict_types=1);
/** IguaNews: serviço editorial, módulo library. Requer bootstrap.php no ponto de entrada. */

function news_library_images(PDO $db,int $limit=64): array {
    $limit=max(1,min(120,$limit));$list=[];
    try {
        $rows=$db->query("SELECT filename,caption,credit FROM (
          SELECT image AS filename,image_caption AS caption,image_credit AS credit,id AS n FROM articles WHERE image IS NOT NULL AND image<>''
          UNION ALL
          SELECT filename,caption,credit,id AS n FROM article_media WHERE kind='image'
        ) images ORDER BY n DESC LIMIT 240")->fetchAll();
        foreach($rows as $r){
            $name=(string)($r['filename']??'');
            if(isset($list[$name])||!news_library_resolve($name))continue;
            $list[$name]=['filename'=>$name,'caption'=>(string)($r['caption']??''),'credit'=>(string)($r['credit']??'')];
            if(count($list)>=$limit)break;
        }
    } catch(PDOException) { return []; }
    return array_values($list);
}

function news_library_resolve(string $filename): ?array {
    $path=media_path($filename);if(!$path)return null;
    $bytes=(int)filesize($path);if($bytes<20||$bytes>12*1048576)return null;
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($path);
    if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)||@getimagesize($path)===false)return null;
    return ['filename'=>$filename,'path'=>$path,'mime'=>$mime,'bytes'=>$bytes];
}

function news_library_attach_gallery(PDO $db,int $articleId,array $selected): int {
    if(count($selected)>10)throw new RuntimeException('Selecione até 10 fotos da biblioteca.');
    $files=array_unique(array_map('strval',$selected));$inserted=0;
    $exists=$db->prepare("SELECT id FROM article_media WHERE article_id=? AND kind='image' AND filename=? LIMIT 1");
    $metadata=$db->prepare("SELECT caption,credit FROM article_media WHERE kind='image' AND filename=? ORDER BY id DESC LIMIT 1");
    $cover=$db->prepare('SELECT image_caption,image_credit FROM articles WHERE image=? ORDER BY id DESC LIMIT 1');
    $insert=$db->prepare("INSERT INTO article_media(article_id,kind,filename,original_name,mime,bytes,caption,credit,sort_order) VALUES (?,'image',?,?,?,?,?,?,?)");
    foreach($files as $i=>$name){
        $file=news_library_resolve($name);if(!$file)throw new RuntimeException('Foto selecionada indisponível ou inválida.');
        $active=$db->prepare('SELECT id FROM media_library WHERE filename=? AND deleted_at IS NULL');$active->execute([$name]);
        if(!$active->fetchColumn())throw new RuntimeException('A imagem selecionada está na lixeira ou fora da biblioteca.');
        $exists->execute([$articleId,$name]);if($exists->fetchColumn())continue;
        $metadata->execute([$name]);$m=$metadata->fetch();
        if(!$m){$cover->execute([$name]);$r=$cover->fetch();$m=['caption'=>$r['image_caption']??'','credit'=>$r['image_credit']??''];}
        if(($m['caption']??'')==='' && ($m['credit']??'')===''){
            $catalog=$db->prepare('SELECT caption,credit FROM media_library WHERE filename=? AND deleted_at IS NULL');
            $catalog->execute([$name]);$r=$catalog->fetch();if($r)$m=$r;
        }
        $insert->execute([$articleId,$name,$name,$file['mime'],$file['bytes'],(string)$m['caption'],(string)$m['credit'],100+$i]);
        $inserted++;
    }
    return $inserted;
}

