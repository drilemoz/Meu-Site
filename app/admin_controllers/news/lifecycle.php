<?php
        if ($action==='duplicate_article') {
            require_ability('write',$me);
            $sourceId=(int)($_POST['id']??0);
            $sel=$db->prepare('SELECT id,author_id,status FROM articles WHERE id=? LIMIT 1');
            $sel->execute([$sourceId]);$original=$sel->fetch();
            if(!$original||!can_edit_article($me,$original))throw new RuntimeException('Você não pode duplicar esta notícia.');
            $copyId=article_duplicate_draft($db,$sourceId,(int)$me['id']);
            flash('Cópia criada como rascunho. Revise título, dados e imagens antes de publicar.');
            redirect('/admin.php?action=edit&id='.$copyId);
        }
        if ($action==='delete_article') {
            require_ability('delete',$me);
            $id=(int)($_POST['id']??0);
            $db->prepare('DELETE FROM articles WHERE id=?')->execute([$id]);flash('Notícia excluída.');redirect('/admin.php?action=articles');
        }
        if ($action==='toggle_article_status') {
            require_ability('publish',$me);
            $id=(int)($_POST['id']??0);
            $result=article_toggle_publication($db,$id,$me);
            flash('Situação alterada para '.$result['label'].'.');
            $page=max(1,(int)($_POST['page']??1));
            $statusFilter=news_admin_status((string)($_POST['status_filter']??'all'));
            $q=usub(trim((string)($_POST['q_filter']??'')),0,100);
            redirect('/admin.php?'.http_build_query(['action'=>'articles','page'=>$page,'status'=>$statusFilter,'q'=>$q]));
        }
