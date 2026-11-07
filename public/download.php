<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
if(!installed()){http_response_code(404);exit;}
try{
 $db=db();$st=$db->prepare("SELECT m.* FROM article_media m JOIN articles a ON a.id=m.article_id WHERE m.id=? AND m.kind='attachment' AND a.status='published' AND a.published_at<=NOW() LIMIT 1");
 $st->execute([(int)($_GET['id']??0)]);$m=$st->fetch();
 if(!$m){http_response_code(404);exit('Anexo não disponível.');}
 $file=IGUA_STORAGE.'/media/'.$m['filename'];if(!is_file($file)){http_response_code(404);exit('Arquivo não encontrado.');}
 header('Content-Type: '.$m['mime']);header('X-Content-Type-Options: nosniff');header("Content-Security-Policy: default-src 'none'; sandbox");
 header('Content-Disposition: attachment; filename="arquivo.'.pathinfo($m['filename'],PATHINFO_EXTENSION).'"');
 header('Content-Length: '.filesize($file));header('Cache-Control: public, max-age=3600');readfile($file);
}catch(Throwable){http_response_code(503);exit('Download indisponível.');}
