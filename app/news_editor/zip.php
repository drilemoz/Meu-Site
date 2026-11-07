<?php
declare(strict_types=1);
/** Importa apenas fotografias de um ZIP enviado por formulário autenticado.
 * Sem extrair caminhos da entrada: cada imagem vai para um nome aleatório.
 * Requer ZipArchive; não altera schema nem armazena o ZIP original.
 */
function news_photo_zip_entries(ZipArchive $zip): array {
    if($zip->numFiles<1||$zip->numFiles>40)throw new RuntimeException('ZIP vazio ou com arquivos demais (máximo 40 entradas).');
    $images=[];$total=0;
    for($i=0;$i<$zip->numFiles;$i++){
        $stat=$zip->statIndex($i);if(!$stat)throw new RuntimeException('Não foi possível inspecionar o ZIP.');
        $name=(string)$stat['name'];
        if(strlen($name)>240||str_contains($name,"\0")||str_contains($name,'\\')||str_starts_with($name,'/')||preg_match('~(^|/)\.\.?(/|$)~',$name))throw new RuntimeException('ZIP com caminho inseguro.');
        if(str_ends_with($name,'/'))continue;
        if(str_starts_with($name,'__MACOSX/')||str_starts_with(basename($name),'._')||basename($name)==='.DS_Store')continue;
        // Proibir links simbólicos e arquivos especiais independentemente do sistema hospedeiro.
        $ops=0;$attrs=0;
        if($zip->getExternalAttributesIndex($i,$ops,$attrs) && $ops===ZipArchive::OPSYS_UNIX){
            $type=($attrs>>16)&0170000;if($type!==0&&$type!==0100000)throw new RuntimeException('ZIP contém link ou arquivo especial.');
        }
        if(!preg_match('/\.(?:jpe?g|png|webp)$/iD',$name))throw new RuntimeException('ZIP aceita somente JPG, PNG e WebP (sem outros arquivos ou ZIP aninhado).');
        $size=(int)($stat['size']??0);
        if($size<20||$size>8*1048576)throw new RuntimeException('Fotografia do ZIP excede 8 MB.');
        $total+=$size;if($total>64*1048576)throw new RuntimeException('ZIP excede 64 MB descompactados.');
        $images[]=['index'=>$i,'name'=>basename($name),'size'=>$size];
        if(count($images)>10)throw new RuntimeException('ZIP aceita no máximo 10 fotografias.');
    }
    if(!$images)throw new RuntimeException('O ZIP não contém fotografias.');
    return $images;
}
function news_photo_zip_import(PDO $db,int $articleId,?array $upload,?callable $onCreated=null): int {
    if(!$upload||($upload['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return 0;
    $error=(int)($upload['error']??UPLOAD_ERR_NO_FILE);
    if($error!==UPLOAD_ERR_OK)throw new RuntimeException(igua_upload_error_message($error,'ZIP de fotografias'));
    if(!class_exists(ZipArchive::class))throw new RuntimeException('O servidor precisa da extensão PHP ZipArchive para importar ZIP.');
    $tmp=(string)($upload['tmp_name']??'');$bytes=(int)($upload['size']??0);
    if($bytes<20||$bytes>25*1048576||!is_uploaded_file($tmp))throw new RuntimeException('ZIP inválido ou acima de 25 MB.');
    $zip=new ZipArchive();if($zip->open($tmp)!==true)throw new RuntimeException('Não foi possível abrir o ZIP.');
    try{
        $entries=news_photo_zip_entries($zip);$y=date('Y');$m=date('m');$dir=IGUA_PUBLIC.'/uploads/'.$y.'/'.$m;
        if(!is_dir($dir)&&!@mkdir($dir,0755,true))throw new RuntimeException('Falha ao criar pasta de imagens.');
        $insert=$db->prepare("INSERT INTO article_media(article_id,kind,filename,original_name,mime,bytes,caption,credit,sort_order) VALUES (?,'image',?,?,?,?,?,?,?)");
        $created=0;
        foreach($entries as $item){
            $stream=$zip->getStream($zip->getNameIndex($item['index']));
            if(!$stream)throw new RuntimeException('Falha ao ler uma foto dentro do ZIP.');
            $temp=tmpfile();if(!$temp){fclose($stream);throw new RuntimeException('Sem espaço temporário para verificar a foto.');}
            try{
                $written=stream_copy_to_stream($stream,$temp,$item['size']+1);fclose($stream);
                if($written!==$item['size'])throw new RuntimeException('Tamanho da foto no ZIP divergente.');
                $tempPath=stream_get_meta_data($temp)['uri'];$mime=(new finfo(FILEINFO_MIME_TYPE))->file($tempPath);
                $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
                $dim=$ext?@getimagesize($tempPath):false;
                if(!$ext||!$dim||(int)$dim[0]<100||(int)$dim[1]<100||(int)$dim[0]*(int)$dim[1]>24000000)throw new RuntimeException('Imagem inválida encontrada no ZIP.');
                $filename=$y.'/'.$m.'/'.bin2hex(random_bytes(16)).'.'.$ext;
                $dest=IGUA_PUBLIC.'/uploads/'.$filename;
                $from=fopen($tempPath,'rb');$to=@fopen($dest,'xb');
                if(!$from||!$to){if(is_resource($from))fclose($from);if(is_resource($to))fclose($to);throw new RuntimeException('Não foi possível armazenar uma foto do ZIP.');}
                if($onCreated)$onCreated($dest); // antes da escrita: rollback remove também arquivo parcial.
                try{$copied=stream_copy_to_stream($from,$to,$item['size']);}finally{fclose($from);fclose($to);}
                if($copied!==$item['size'])throw new RuntimeException('Foto incompleta ao salvar ZIP.');
                @chmod($dest,0644);
                if(!media_register($db,$filename,$item['name'],'','',(int)(admin_user()['id']??0)?:null))throw new RuntimeException('Falha ao registrar foto do ZIP.');
                $insert->execute([$articleId,$filename,$item['name'],$mime,(int)filesize($dest),'','',200+$created]);
                if(function_exists('news_try_thumbnail'))news_try_thumbnail($dest,$filename,$mime);
                $created++;
            }finally{fclose($temp);}
        }
        return $created;
    }finally{$zip->close();}
}
