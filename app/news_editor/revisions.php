<?php
declare(strict_types=1);
/** IguaNews: serviço editorial, módulo revisions. Requer bootstrap.php no ponto de entrada. */

function news_snapshot_revision(PDO $db,array $article,int $userId): void {
    $articleId=(int)$article['id'];
    $snapshot=['title'=>$article['title'],'summary'=>$article['summary'],'body'=>$article['body'],
      'category_id'=>$article['category_id'],'seo_title'=>$article['seo_title'],'seo_description'=>$article['seo_description'],
      'blocks'=>article_blocks($db,$articleId),'categories'=>article_extra_categories($db,$articleId)];
    $json=json_encode($snapshot,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);
    $db->prepare('INSERT INTO article_revisions(article_id,user_id,snapshot_json) VALUES (?,?,?)')->execute([$articleId,$userId,$json]);
    // Limite de 20 versões anteriores por matéria.
    $del=$db->prepare('DELETE FROM article_revisions WHERE article_id=? AND id NOT IN (SELECT id FROM (SELECT id FROM article_revisions WHERE article_id=? ORDER BY id DESC LIMIT 20) AS recent)');
    $del->execute([$articleId,$articleId]);
}

function news_restore_revision(PDO $db,int $articleId,int $revisionId,int $userId): void {
    $s=$db->prepare('SELECT * FROM articles WHERE id=? LIMIT 1');$s->execute([$articleId]);$article=$s->fetch();
    if(!$article)throw new RuntimeException('Matéria não encontrada.');
    $r=$db->prepare('SELECT snapshot_json FROM article_revisions WHERE id=? AND article_id=? LIMIT 1');$r->execute([$revisionId,$articleId]);$json=$r->fetchColumn();
    if($json===false)throw new RuntimeException('Revisão não encontrada.');
    // is_demo não faz parte do snapshot: nunca remover a marcação fictícia na restauração.
    $data=json_decode($json,true,32,JSON_THROW_ON_ERROR);
    $blocks=normalize_news_blocks(json_encode($data['blocks']??[],JSON_THROW_ON_ERROR));
    $categories=(array)($data['categories']??[]);
    $db->beginTransaction();
    try{
        news_snapshot_revision($db,$article,$userId);
        $db->prepare('UPDATE articles SET title=?,summary=?,body=?,category_id=?,seo_title=?,seo_description=? WHERE id=?')->execute([
            (string)$data['title'],(string)$data['summary'],(string)$data['body'],
            (int)$data['category_id']?:null,(string)$data['seo_title'],(string)$data['seo_description'],$articleId]);
        save_article_extras($db,$articleId,$categories,$blocks);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}

