<?php
    $id=(int)$row['id'];$existing=$id?article_blocks($db,$id):[];
    $extra=$id?article_extra_categories($db,$id):[];
    $media=$id?article_media($db,$id):[];
    if(!empty($row['image'])) {try {$altStmt=$db->prepare('SELECT alt_text FROM media_library WHERE filename=? AND deleted_at IS NULL LIMIT 1');$altStmt->execute([(string)$row['image']]);$row['cover_alt']=(string)($altStmt->fetchColumn()?:'');}catch(PDOException){$row['cover_alt']='';}}
    $draft=$db->prepare('SELECT payload,updated_at FROM article_drafts WHERE user_id=? AND article_id=? LIMIT 1');
    $draft->execute([$me['id'],$id]);$savedDraft=$draft->fetch();
    $prefill=($id===0&&isset($_SESSION['news_import_preview'])&&is_array($_SESSION['news_import_preview']))?$_SESSION['news_import_preview']:[];
    foreach(['title','summary','source_name','source_url'] as $field)if(empty($row[$field])&&!empty($prefill[$field]))$row[$field]=$prefill[$field];
    if(empty($row['seo_description'])&&!empty($prefill['summary']))$row['seo_description']=news_seo_description((string)$row['title'],(string)$row['summary'],'');
