<?php
declare(strict_types=1);

const IGUA_STORY_PHOTO_MAX = 512 * 1024;
const IGUA_STORY_QUEUE_MAX = 8 * 1024 * 1024;
function igua_story_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS story_submissions (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL, contact VARCHAR(190) NOT NULL,
        title VARCHAR(220) NOT NULL, message TEXT NOT NULL,
        photos MEDIUMTEXT NOT NULL, rate_key CHAR(64) NOT NULL,
        status ENUM('new','accepted','rejected','archived') NOT NULL DEFAULT 'new',
        article_id INT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_story_status (status,id), INDEX idx_story_rate (rate_key,created_at),
        CONSTRAINT fk_story_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function igua_story_validate(array $input): array {
    if (($input['website']??'')!=='' || ($input['consent']??'')!=='1') throw new DomainException('Confirme o consentimento e tente novamente.');
    $out=[];
    foreach (['name'=>[2,120],'contact'=>[5,190],'title'=>[5,220],'message'=>[20,5000]] as $key=>[$min,$max]) {
        $value=$input[$key]??'';
        if(!is_string($value) || !preg_match('//u',$value))throw new DomainException('Preencha os campos corretamente.');
        $value=trim($value);
        if(ulen($value)<$min || ulen($value)>$max || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',$value))throw new DomainException('Confira os campos e seus limites de tamanho.');
        $out[$key]=$value;
    }
    if (!filter_var($out['contact'],FILTER_VALIDATE_EMAIL) && !(preg_match('/^\+?[0-9() .-]+$/D',$out['contact']) && preg_match('/^[0-9]{8,15}$/D',preg_replace('/\D/','',$out['contact']))))throw new DomainException('Informe um e-mail ou telefone válido.');
    return $out;
}
function igua_story_photo(string $bytes): array {
    if($bytes==='' || strlen($bytes)>IGUA_STORY_PHOTO_MAX)throw new DomainException('Cada foto deve ter até 512 KiB.');
    $info=@getimagesizefromstring($bytes);
    $allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $mime=$info['mime']??'';
    if(!isset($allowed[$mime]) || $info[0]<1 || $info[1]<1 || $info[0]>4096 || $info[1]>4096 || (new finfo(FILEINFO_MIME_TYPE))->buffer($bytes)!==$mime)throw new DomainException('Envie fotos JPG, PNG ou WebP de até 4096 × 4096 pixels.');
    return ['type'=>$mime,'data'=>base64_encode($bytes)];
}
function igua_story_uploads(array $file): array {
    if(!$file)return [];
    if(!is_array($file['error']??null) || count($file['error'])>2)throw new DomainException('Envie no máximo duas fotos.');
    $out=[];
    foreach($file['error'] as $i=>$error){
        if($error===UPLOAD_ERR_NO_FILE)continue;
        $tmp=$file['tmp_name'][$i]??'';
        if($error!==UPLOAD_ERR_OK || !is_string($tmp) || !is_uploaded_file($tmp))throw new DomainException('Não foi possível receber a foto. Verifique o tamanho e tente novamente.');
        if(filesize($tmp)>IGUA_STORY_PHOTO_MAX)throw new DomainException('Cada foto deve ter até 512 KiB.');
        $bytes=file_get_contents($tmp,false,null,0,IGUA_STORY_PHOTO_MAX+1);
        if($bytes===false)throw new DomainException('Não foi possível ler a foto.');
        $out[]=igua_story_photo($bytes);
    }
    return $out;
}
function igua_story_receive(PDO $db,array $input,array $files): int {
    $data=igua_story_validate($input);
    $photos=json_encode(igua_story_uploads($files),JSON_THROW_ON_ERROR);
    // Chave pseudônima diária; não guarda IP completo. Limite por sessão e origem.
    $key=hash_hmac('sha256',date('Y-m-d').'|'.($_SERVER['REMOTE_ADDR']??''),(string)(config()['password']??''));
    if(time()-(int)($_SESSION['story_last']??0)<60)throw new DomainException('Aguarde um minuto antes de enviar outra notícia.');
    // Serializa admissões, inclusive entre sessões, para respeitar o limite de armazenamento.
    $lock='igua_story_'.substr(hash('sha256',(string)$db->query('SELECT DATABASE()')->fetchColumn()),0,40);
    $stmt=$db->prepare('SELECT GET_LOCK(?,3)');$stmt->execute([$lock]);
    if((int)$stmt->fetchColumn()!==1)throw new DomainException('Caixa de entrada ocupada. Tente novamente.');
    try{
        $q=$db->prepare('SELECT COUNT(*) FROM story_submissions WHERE rate_key=? AND created_at >= CURRENT_DATE()');$q->execute([$key]);
        if((int)$q->fetchColumn()>=3)throw new DomainException('Limite diário de envios atingido. Tente amanhã.');
        $stats=$db->query('SELECT COUNT(*) AS n, COALESCE(SUM(OCTET_LENGTH(photos)+OCTET_LENGTH(message)+OCTET_LENGTH(title)+OCTET_LENGTH(name)+OCTET_LENGTH(contact)),0) AS bytes FROM story_submissions')->fetch();
        if((int)$stats['n']>=500 || (int)$stats['bytes']+strlen($photos)+array_sum(array_map('strlen',$data))>IGUA_STORY_QUEUE_MAX)throw new DomainException('A caixa de entrada está temporariamente cheia. Tente novamente mais tarde.');
        $q=$db->prepare('INSERT INTO story_submissions (name,contact,title,message,photos,rate_key) VALUES (?,?,?,?,?,?)');
        $q->execute([$data['name'],$data['contact'],$data['title'],$data['message'],$photos,$key]);
        $id=(int)$db->lastInsertId();$_SESSION['story_last']=time();return $id;
    }finally{$q=$db->prepare('SELECT RELEASE_LOCK(?)');$q->execute([$lock]);}
}
function igua_story_draft(PDO $db,int $id,int $user): int {
    $db->beginTransaction();
    try{
        $q=$db->prepare('SELECT title,message,status,article_id FROM story_submissions WHERE id=? FOR UPDATE');$q->execute([$id]);$row=$q->fetch();
        if(!$row)throw new DomainException('Sugestão não encontrada.');
        if($row['article_id']){$db->commit();return (int)$row['article_id'];}
        if(in_array($row['status'],['rejected','archived'],true))throw new DomainException('Reabra a sugestão antes de criar um rascunho.');
        $title=trim(strip_tags($row['title']));$body=trim(strip_tags($row['message']));
        if(ulen($title)<5 || ulen($body)<20)throw new DomainException('O conteúdo não tem texto suficiente para criar um rascunho.');
        $slug=article_editor_slug($db,$title,'');
        $q=$db->prepare("INSERT INTO articles (title,slug,summary,body,status,published_at,author_id) VALUES (?,?,?,?,'draft',NOW(),?)");
        // Contato e fotos não entram no acervo público; revisão manual obrigatória.
        $q->execute([$title,$slug,'',$body,$user]);
        $article=(int)$db->lastInsertId();
        $q=$db->prepare("UPDATE story_submissions SET article_id=?,status='accepted' WHERE id=?");$q->execute([$article,$id]);
        $db->commit();return $article;
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
