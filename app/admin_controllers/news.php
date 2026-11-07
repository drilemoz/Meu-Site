<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
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
        if ($action==='save_article') {
            require_ability('write',$me);
            $id=(int)($_POST['id']??0); $title=trim((string)($_POST['title']??''));
            $summary=trim((string)($_POST['summary']??''));$body=trim((string)($_POST['body']??''));
            $blocks=normalize_news_blocks((string)($_POST['blocks_json']??''));
            if($blocks&&ulen($body)<10)$body=blocks_plain_text($blocks);
            if($summary==='')$summary=news_seo_description($title??'', '',$body);
            $cat=(int)($_POST['category_id']??0);$status=in_array($_POST['status']??'',['draft','published'],true)?$_POST['status']:'draft';
            if(!role_allowed($me['role'],'publish'))$status='draft';
            $profile=(int)($_POST['author_profile_id']??0);$sourceId=(int)($_POST['source_id']??0);
            if($profile){$st=$db->prepare('SELECT id FROM author_profiles WHERE id=?');$st->execute([$profile]);if(!$st->fetchColumn())throw new RuntimeException('Autor editorial inválido.');}
            if($sourceId){$st=$db->prepare('SELECT name,url FROM editorial_sources WHERE id=?');$st->execute([$sourceId]);$src=$st->fetch();if(!$src)throw new RuntimeException('Fonte inválida.');}
            if (ulen($title)<5 || ulen($title)>220 || ulen($summary)>480 || ulen($body)<10) throw new RuntimeException('Confira título (5–220), resumo (até 480) e texto (mínimo 10 caracteres).');
            $check=$db->prepare('SELECT id FROM categories WHERE id=?');$check->execute([$cat]);if (!$check->fetch()) throw new RuntimeException('Categoria inválida.');
            $old=null;if ($id) {$s=$db->prepare('SELECT * FROM articles WHERE id=?');$s->execute([$id]);$old=$s->fetch();if(!$old) throw new RuntimeException('Notícia inexistente.'); if(!can_edit_article($me,$old))throw new RuntimeException('Você só pode editar seus próprios rascunhos.');}
            $publishAt=(string)($old['published_at']??date('Y-m-d H:i:s'));
            $when=trim((string)($_POST['publish_at']??''));
            if($when!==''){
                $parsed=DateTimeImmutable::createFromFormat('!Y-m-d\TH:i',$when,new DateTimeZone('America/Sao_Paulo'));
                if(!$parsed||$parsed->format('Y-m-d\TH:i')!==$when||$parsed->format('Y')<'2000'||$parsed->format('Y')>'2100')throw new RuntimeException('Data ou horário de publicação inválido.');
                $publishAt=$parsed->format('Y-m-d H:i:s');
            }
            $libraryCover=trim((string)($_POST['library_cover']??''));
            if($libraryCover!==''){
                if(!empty($_FILES['image']['name']))throw new RuntimeException('Escolha a foto da biblioteca ou envie uma nova capa, não ambas.');
                $verified=news_library_resolve($libraryCover);
                if(!$verified)throw new RuntimeException('Foto da biblioteca indisponível ou inválida.');
                $validMedia=$db->prepare('SELECT id FROM media_library WHERE filename=? AND deleted_at IS NULL');
                $validMedia->execute([$libraryCover]);if(!$validMedia->fetchColumn())throw new RuntimeException('Imagem fora da biblioteca ativa.');
                $image=$verified['filename'];
            }else $image=$old['image']??null; // upload da capa dentro da transação
            $caption=trim((string)($_POST['image_caption']??''));
            $credit=trim((string)($_POST['image_credit']??''));
            $sourceName=trim((string)($_POST['source_name']??''));
            $sourceUrl=trim((string)($_POST['source_url']??''));
            if(!empty($src) && $sourceId){$sourceName=$src['name'];$sourceUrl=$src['url'];}
            $seoTitle=clean_seo_text((string)($_POST['seo_title']??''),220);
            $seoDesc=clean_seo_text((string)($_POST['seo_description']??''),320);
            if($seoDesc==='')$seoDesc=news_seo_description($title,$summary,$body);
            if (ulen($caption)>360 || ulen($credit)>180 || ulen($sourceName)>160 || ulen($sourceUrl)>600) throw new RuntimeException('Legenda, crédito ou fonte acima do limite.');
            if ($sourceUrl!=='' && (!filter_var($sourceUrl,FILTER_VALIDATE_URL) || !str_starts_with($sourceUrl,'https://'))) throw new RuntimeException('A fonte deve ser uma URL HTTPS válida.');
            $candidate=article_editor_slug($db,$title,(string)($_POST['url_slug']??''),$id,(string)($old['slug']??''));
            $args=[$cat,$title,$candidate,$summary,$body,$image,$caption,$credit,$sourceName,$sourceUrl,$sourceId?:null,$profile?:null,$seoTitle,$seoDesc,$status,(isset($_POST['featured'])&&role_allowed($me['role'],'publish'))?1:0,$publishAt];
            $createdFiles=[];
            $onCreated=static function(string $path)use(&$createdFiles): void {$createdFiles[]=$path;};
            $db->beginTransaction();
            $commitStarted=false;
            try{
                if($libraryCover===''){
                    $image=image_upload('image',$old['image']??null,$onCreated);
                    $args[5]=$image;
                }
                if ($id) {
                    news_snapshot_revision($db,$old,(int)$me['id']);
                    article_record_slug_change($db,$id,(string)$old['slug'],$candidate);
                    $stmt=$db->prepare('UPDATE articles SET category_id=?,title=?,slug=?,summary=?,body=?,image=?,image_caption=?,image_credit=?,source_name=?,source_url=?,source_id=?,author_profile_id=?,seo_title=?,seo_description=?,status=?,featured=?,published_at=?,is_demo=is_demo WHERE id=?');
                    $stmt->execute([...$args,$id]);
                } else {
                    $stmt=$db->prepare('INSERT INTO articles (category_id,title,slug,summary,body,image,image_caption,image_credit,source_name,source_url,source_id,author_profile_id,seo_title,seo_description,status,featured,published_at,author_id,is_demo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)');
                    $stmt->execute([...$args,$me['id']]);$id=(int)$db->lastInsertId();
                }
                save_article_extras($db,$id,(array)($_POST['extra_categories']??[]),$blocks);
                news_media_update($db,$id,$_POST);
                news_library_attach_gallery($db,$id,(array)($_POST['library_gallery']??[]));
                foreach(['gallery_files'=>'image','attachment_files'=>'attachment'] as $field=>$kind){
                    if(isset($_FILES[$field])) news_media_upload($db,$id,$_FILES[$field],$kind,
                        (array)($_POST[$kind==='image'?'new_image_caption':'new_attachment_caption']??[]),
                        (array)($_POST[$kind==='image'?'new_image_credit':'new_attachment_credit']??[]),$onCreated);
                }
                $db->prepare('DELETE FROM article_drafts WHERE user_id=? AND article_id IN (?,0)')->execute([(int)$me['id'],$id]);
                $commitStarted=true;
                if(!$db->commit())throw new RuntimeException('Falha ao confirmar a gravação da notícia.');
            }catch(Throwable $e){
                // Excluir uploads apenas após rollback confirmado. Uma falha de conexão
                // durante COMMIT deixa o resultado incerto: preservar arquivos é mais
                // seguro que apagar fotografias possivelmente referenciadas pelo banco.
                $rolledBack=false;
                if($db->inTransaction()){
                    try {$db->rollBack();$rolledBack=true;}
                    catch(Throwable $rollbackError){igua_health_log('NEWS_ROLLBACK_FAILED',$rollbackError);}
                }
                if($rolledBack)igua_discard_new_uploads($createdFiles);
                if($commitStarted && !$rolledBack)igua_health_log('NEWS_COMMIT_UNCERTAIN',$e);
                throw $e;
            }
            unset($_SESSION['news_import_preview']);
            flash('Notícia e conteúdo estruturado salvos.');redirect('/admin.php?action=articles');
        }
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
