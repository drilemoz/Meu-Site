<?php
        if ($action==='save_article') {
            require_ability('write',$me);
            $id=(int)($_POST['id']??0); $title=trim((string)($_POST['title']??''));
            $summary=trim((string)($_POST['summary']??''));$body=trim((string)($_POST['body']??''));
            $blocks=normalize_news_blocks((string)($_POST['blocks_json']??''));
            if($blocks&&ulen($body)<10)$body=blocks_plain_text($blocks);
            if($summary==='')$summary=news_seo_description($title??'', '',$body);
            $cat=(int)($_POST['category_id']??0);$status=in_array($_POST['status']??'',['draft','published'],true)?$_POST['status']:'draft';
            // Decisão final obrigatória do assistente, validada novamente no servidor.
            $publicationChoice=(string)($_POST['publication_choice']??'');
            if($publicationChoice!==''){
                if(!in_array($publicationChoice,['draft','now','scheduled'],true))throw new RuntimeException('Escolha de publicação inválida.');
                if($publicationChoice!=='draft'&&!role_allowed((string)$me['role'],'publish'))throw new RuntimeException('Sem permissão para publicar.');
                $status=$publicationChoice==='draft'?'draft':'published';
            }
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
            if($publicationChoice==='now')$publishAt=date('Y-m-d H:i:s');
            if($publicationChoice==='scheduled' && ($when===''||strtotime($publishAt)<=time()))throw new RuntimeException('Escolha data e horário futuros para agendar.');
            $libraryCover=trim((string)($_POST['library_cover']??''));
            if($libraryCover!==''){
                if(!empty($_FILES['image']['name']))throw new RuntimeException('Escolha a foto da biblioteca ou envie uma nova capa, não ambas.');
                $verified=news_library_resolve($libraryCover);
                if(!$verified)throw new RuntimeException('Foto da biblioteca indisponível ou inválida.');
                $validMedia=$db->prepare('SELECT id FROM media_library WHERE filename=? AND deleted_at IS NULL');
                $validMedia->execute([$libraryCover]);if(!$validMedia->fetchColumn())throw new RuntimeException('Imagem fora da biblioteca ativa.');
                $image=$verified['filename'];
            }else $image=!empty($_POST['remove_cover'])?null:($old['image']??null); // remover capa só sob escolha explícita
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
                    $image=image_upload('image',!empty($_POST['remove_cover'])?null:($old['image']??null),$onCreated);
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
                // Primeiro fotos diretas, depois o ZIP. Todas integram a transação.
                foreach(['gallery_files'=>'image','attachment_files'=>'attachment'] as $field=>$kind){
                    if(isset($_FILES[$field])) news_media_upload($db,$id,$_FILES[$field],$kind,
                        (array)($_POST[$kind==='image'?'new_image_caption':'new_attachment_caption']??[]),
                        (array)($_POST[$kind==='image'?'new_image_credit':'new_attachment_credit']??[]),$onCreated);
                }
                news_photo_zip_import($db,$id,$_FILES['photo_zip']??null,$onCreated);
                if(empty($image) && isset($_POST['auto_cover'])){
                    $first=$db->prepare("SELECT filename,caption,credit FROM article_media WHERE article_id=? AND kind='image' ORDER BY sort_order,id LIMIT 1");
                    $first->execute([$id]);$picked=$first->fetch();
                    if($picked){
                        $image=(string)$picked['filename'];
                        $autoCaption=$caption!==''?$caption:(string)$picked['caption'];
                        $autoCredit=$credit!==''?$credit:(string)$picked['credit'];
                        $db->prepare('UPDATE articles SET image=?,image_caption=?,image_credit=? WHERE id=?')->execute([$image,$autoCaption,$autoCredit,$id]);
                    }
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
