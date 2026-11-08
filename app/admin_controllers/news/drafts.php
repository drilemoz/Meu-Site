<?php
        if($action==='autosave_news'){
            require_ability('write',$me);
            $articleId=(int)($_POST['id']??0);$payload=(string)($_POST['payload']??'');
            if(strlen($payload)>230000)throw new RuntimeException('Rascunho muito grande.');
            $decoded=json_decode($payload,true,30,JSON_THROW_ON_ERROR);
            if(!is_array($decoded)||!isset($decoded['fields'],$decoded['blocks'])||!is_array($decoded['fields']))throw new RuntimeException('Rascunho inválido.');
            if($articleId){$probe=$db->prepare('SELECT id,author_id,status FROM articles WHERE id=?');$probe->execute([$articleId]);$existing=$probe->fetch();if(!$existing||!can_edit_article($me,$existing))throw new RuntimeException('Sem permissão de edição.');}
            $db->prepare('INSERT INTO article_drafts(user_id,article_id,payload) VALUES (?,?,?) ON DUPLICATE KEY UPDATE payload=VALUES(payload),updated_at=NOW()')->execute([(int)$me['id'],$articleId,$payload]);
            header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo '{"saved":true}';exit;
        }
        if($action==='import_url'){
            require_ability('write',$me);
            $importUrl=trim((string)($_POST['source_url']??''));
            $dup=$db->prepare('SELECT id FROM articles WHERE source_url=? LIMIT 1');$dup->execute([$importUrl]);
            if($dup->fetchColumn())throw new RuntimeException('Esta URL já está vinculada a uma notícia. Verifique as duplicações antes de importar.');
            $preview=news_import_url($importUrl);
            $db->prepare('INSERT INTO article_import_logs(user_id,source_url,result_title) VALUES (?,?,?)')->execute([$me['id'],$importUrl,$preview['title']]);
            $_SESSION['news_import_preview']=$preview;
            flash('Metadados recuperados; complete e confira a notícia antes de publicar.');redirect('/admin.php?action=new');
        }
        if($action==='restore_article_revision'){
            require_ability('write',$me);
            $articleId=(int)($_POST['id']??0);$revisionId=(int)($_POST['revision_id']??0);
            $sel=$db->prepare('SELECT id,author_id,status FROM articles WHERE id=?');$sel->execute([$articleId]);$candidate=$sel->fetch();
            if(!$candidate||!can_edit_article($me,$candidate))throw new RuntimeException('Você não pode restaurar esta matéria.');
            news_restore_revision($db,$articleId,$revisionId,(int)$me['id']);
            flash('Texto e categorias da revisão recuperados. Publicação, anexos e fotos mantidos.');
            redirect('/admin.php?action=edit&id='.$articleId);
        }
