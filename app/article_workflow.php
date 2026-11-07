<?php
declare(strict_types=1);
/** Endereços de notícias já publicadas não mudam quando a manchete é editada. */
function article_stable_slug(PDO $db,string $title,int $id=0,string $oldSlug=''): string {
    if ($id>0 && $oldSlug!=='' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$oldSlug)) return $oldSlug;
    $base=slug($title);$candidate=$base;
    for($i=2;$i<=10001;$i++) {
        $stmt=$db->prepare('SELECT 1 FROM articles WHERE slug=? AND id<>? LIMIT 1');
        $stmt->execute([$candidate,$id]);
        if(!$stmt->fetchColumn())return $candidate;
        $candidate=$base.'-'.$i;
    }
    throw new RuntimeException('Não foi possível gerar endereço único para a notícia.');
}
/** Indexa endereços antigos sem sobrescrever a matéria e sem mover URLs de terceiros. */
function migrate_article_slug_schema(PDO $db): void {
    $db->exec("CREATE TABLE IF NOT EXISTS article_slug_redirects (
      slug VARCHAR(240) NOT NULL PRIMARY KEY, article_id INT UNSIGNED NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_slug_redirect_article (article_id),
      CONSTRAINT fk_slug_redirect_article FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
/** Slugs devem ser exclusivos tanto nos endereços ativos quanto nos históricos. */
function article_slug_owner(PDO $db,string $candidate): int {
    $active=$db->prepare('SELECT id FROM articles WHERE slug=? LIMIT 1');
    $active->execute([$candidate]);$owner=$active->fetchColumn();
    if ($owner!==false) return (int)$owner;
    $old=$db->prepare('SELECT article_id FROM article_slug_redirects WHERE slug=? LIMIT 1');
    $old->execute([$candidate]);$owner=$old->fetchColumn();
    return $owner===false?0:(int)$owner;
}
/** Vazio = manter endereço existente ou gerar automaticamente; manual = erro em caso de colisão. */
function article_editor_slug(PDO $db,string $title,string $requested,int $id=0,string $oldSlug=''): string {
    $requested=trim($requested);
    // URLs legadas podem ter até 240 caracteres. Nunca truncá-las por engano ao salvar.
    if($id>0 && $oldSlug!=='' && $requested===$oldSlug)return $oldSlug;
    if($requested!=='') {
        if(ulen($requested)>120)throw new RuntimeException('Endereço SEO muito longo (máximo 120 caracteres).');
        $ascii=iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$requested);
        if($ascii===false || !preg_match('/[a-z0-9]/i',$ascii))throw new RuntimeException('Digite um nome de URL que contenha letras ou números.');
        $candidate=substr(slug($requested),0,120);$candidate=trim($candidate,'-');
        $owner=article_slug_owner($db,$candidate);
        if($owner!==0 && $owner!==$id)throw new RuntimeException('Este endereço já pertence a outra notícia ou a um link antigo. Escolha outro nome.');
        return $candidate;
    }
    if($id>0 && $oldSlug!=='' && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D',$oldSlug))return $oldSlug;
    $base=trim(substr(slug($title),0,115),'-');
    if($base==='')$base='noticia';
    for($i=1;$i<10000;$i++){
        $candidate=$i===1?$base:$base.'-'.$i;
        if(article_slug_owner($db,$candidate)===0)return $candidate;
    }
    throw new RuntimeException('Não foi possível encontrar endereço livre para a notícia.');
}
/** Chamar dentro da transação que grava o artigo, ANTES do UPDATE do slug. */
function article_record_slug_change(PDO $db,int $id,string $oldSlug,string $newSlug): void {
    if($id<=0 || $oldSlug===$newSlug)return;
    // Reutilizar endereço antigo da PRÓPRIA notícia é permitido; nenhum outro artigo pode tomá-lo.
    $del=$db->prepare('DELETE FROM article_slug_redirects WHERE slug=? AND article_id=?');
    $del->execute([$newSlug,$id]);
    $add=$db->prepare('INSERT INTO article_slug_redirects (slug,article_id) VALUES (?,?)');
    $add->execute([$oldSlug,$id]);
}
/** Página administrativa, validada sem coerções surpreendentes de arrays ou texto misto. */
function news_admin_page_number(mixed $raw): int {
    if (!is_scalar($raw) || !preg_match('/^[1-9][0-9]{0,4}$/D', (string)$raw)) return 1;
    return min(10000, (int)$raw);
}
function news_admin_search_term(mixed $raw): string {
    return is_string($raw) ? usub(trim($raw),0,100) : '';
}
function news_admin_status(string $value):string {
    return in_array($value,['all','draft','published','scheduled'],true)?$value:'all';
}
/** Retorna apenas condições parametrizadas; nunca insere entrada livre no SQL. */
function news_admin_conditions(array $user,string $status,string $search):array {
    $parts=['1=1'];$args=[];
    if(($user['role']??'')==='author') {$parts[]='a.author_id=?';$args[]=(int)$user['id'];}
    switch(news_admin_status($status)) {
        case 'draft': $parts[]="a.status='draft'";break;
        case 'published': $parts[]="a.status='published' AND a.published_at<=NOW()";break;
        case 'scheduled': $parts[]="a.status='published' AND a.published_at>NOW()";break;
    }
    if($search!=='') {$parts[]="(a.title LIKE ? ESCAPE '!' OR a.summary LIKE ? ESCAPE '!')";
        $pattern='%'.strtr($search,['!'=>'!!','%'=>'!%','_'=>'!_']).'%';
        $args[]=$pattern;$args[]=$pattern;}
    return [implode(' AND ',$parts),$args];
}
/** Cópia não pública: mantém categoria e blocos, mas não clona anexos nem galeria. */

function article_toggle_publication(PDO $db,int $id,array $user): array {
    $stmt=$db->prepare('SELECT id,author_id,status,published_at FROM articles WHERE id=? LIMIT 1');
    $stmt->execute([$id]);$article=$stmt->fetch();
    if(!$article)throw new RuntimeException('Notícia não encontrada.');
    if(!role_allowed((string)($user['role']??''),'publish') || !can_edit_article($user,$article))throw new RuntimeException('Você não pode alterar a publicação desta notícia.');
    $now=date('Y-m-d H:i:s');
    if((string)$article['status']==='published'){
        $db->prepare("UPDATE articles SET status='draft' WHERE id=?")->execute([$id]);
        return ['status'=>'draft','label'=>'Rascunho'];
    }
    $db->prepare("UPDATE articles SET status='published', published_at=? WHERE id=?")->execute([$now,$id]);
    return ['status'=>'published','label'=>'Publicada'];
}

function article_duplicate_draft(PDO $db,int $sourceId,int $userId):int {
    $sel=$db->prepare('SELECT * FROM articles WHERE id=? LIMIT 1');$sel->execute([$sourceId]);
    $article=$sel->fetch();if(!$article)throw new RuntimeException('Notícia não encontrada.');
    $db->beginTransaction();
    try {
        $title=usub('Cópia: '.$article['title'],0,220);
        $slug=article_editor_slug($db,$title,'');
        $sql='INSERT INTO articles (category_id,title,slug,summary,body,image,image_caption,image_credit,source_name,source_url,source_id,author_profile_id,seo_title,seo_description,status,featured,published_at,author_id,is_demo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $db->prepare($sql)->execute([
            $article['category_id'],$title,$slug,$article['summary'],$article['body'],$article['image'],$article['image_caption'],$article['image_credit'],
            $article['source_name'],$article['source_url'],$article['source_id'],$article['author_profile_id'],
            '',$article['seo_description'],'draft',0,date('Y-m-d H:i:s'),$userId,(int)$article['is_demo']
        ]);
        $newId=(int)$db->lastInsertId();
        $cats=article_extra_categories($db,$sourceId);
        $blocks=article_blocks($db,$sourceId);
        save_article_extras($db,$newId,$cats,$blocks);
        $db->commit();return $newId;
    }catch(Throwable $e) {if($db->inTransaction())$db->rollBack();throw $e;}
}
