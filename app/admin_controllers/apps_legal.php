<?php
declare(strict_types=1);
/** Ações de páginas legais. Incluído apenas pelo router POST autenticado e com CSRF. */
require_ability('manage_settings',$me);
if(($me['role']??'')!=='admin')throw new RuntimeException('Somente administradores podem gerenciar aplicativos e páginas legais.');
if($action==='save_legal_app'){
    $id=max(0,(int)($_POST['id']??0));
    $old=$id?igua_app_find($db,$id):null;
    if($id && !$old)throw new InvalidArgumentException('Aplicativo não encontrado.');
    $name=igua_app_clean((string)($_POST['name']??''),160,'o nome do aplicativo',true);
    $slug=trim((string)($_POST['slug']??''));
    igua_app_assert_slug($db,$slug,$id);
    $summary=igua_app_clean((string)($_POST['summary']??''),300,'a descrição curta');
    $description=igua_app_clean((string)($_POST['description']??''),24000,'a descrição completa');
    $developer=igua_app_clean((string)($_POST['developer_name']??''),180,'o nome do responsável');
    $email=igua_app_clean((string)($_POST['support_email']??''),190,'o e-mail de suporte');
    if($email!=='' && !filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('E-mail de suporte inválido.');
    $website=igua_app_external_url((string)($_POST['website_url']??''));
    if($old && $slug!==$old['slug'] && ($_POST['confirm_slug_change']??'')!=='1')throw new InvalidArgumentException('Para alterar a URL pública, confirme a mudança de identificador e o redirecionamento permanente.');
    $logo=$old['logo_filename']??'';
    if(!empty($_POST['remove_logo']))$logo='';
    // O backend valida MIME, tamanho, assinatura da imagem e nome aleatório.
    if(isset($_FILES['logo']) && (int)($_FILES['logo']['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_NO_FILE)
        $logo=image_upload('logo',$logo);
    $db->beginTransaction();
    try {
        if($id){
            if($slug!==$old['slug']){
                // Reutilizar um alias da própria aplicação é permitido, mas não o de outra.
                $db->prepare('DELETE FROM legal_app_slug_redirects WHERE old_slug=? AND app_id=?')->execute([$slug,$id]);
                $db->prepare('INSERT INTO legal_app_slug_redirects (old_slug,app_id) VALUES (?,?) ON DUPLICATE KEY UPDATE app_id=VALUES(app_id)')->execute([$old['slug'],$id]);
            }
            $db->prepare('UPDATE legal_apps SET name=?,slug=?,summary=?,description=?,logo_filename=?,developer_name=?,support_email=?,website_url=? WHERE id=?')
                ->execute([$name,$slug,$summary,$description,$logo,$developer,$email,$website,$id]);
        }else{
            $db->prepare('INSERT INTO legal_apps (name,slug,summary,description,logo_filename,developer_name,support_email,website_url) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$name,$slug,$summary,$description,$logo,$developer,$email,$website]);
            $id=(int)$db->lastInsertId();
            igua_app_seed_default_pages($db,$id,$name);
        }
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    flash('Aplicativo salvo. Suas páginas não foram publicadas automaticamente.');
    redirect('/admin.php?action=legal_app_edit&id='.$id);
}
if($action==='publish_legal_app'){
    $id=(int)($_POST['id']??0);$app=igua_app_find($db,$id);
    if(!$app)throw new InvalidArgumentException('Aplicativo não encontrado.');
    if(($_POST['confirm']??'')!=='1')throw new InvalidArgumentException('Confirme a alteração de publicação.');
    $status=(string)($_POST['status']??'');
    if($status==='published'){
        if(trim($app['developer_name'])==='' || !filter_var((string)$app['support_email'],FILTER_VALIDATE_EMAIL))
            throw new InvalidArgumentException('Informe responsável e e-mail de suporte válido antes de publicar.');
        $q=$db->prepare("SELECT COUNT(*) FROM legal_app_pages WHERE app_id=? AND slug IN ('inicio','privacidade') AND status='published' AND published_revision_id IS NOT NULL");
        $q->execute([$id]);if((int)$q->fetchColumn()!==2)throw new InvalidArgumentException('Publique primeiro a Apresentação e a Política de Privacidade revisadas.');
        $db->prepare("UPDATE legal_apps SET status='published',published_at=COALESCE(published_at,NOW()) WHERE id=?")->execute([$id]);
    }elseif($status==='draft'){
        $db->prepare("UPDATE legal_apps SET status='draft' WHERE id=?")->execute([$id]);
    }else throw new InvalidArgumentException('Estado inválido.');
    flash($status==='published'?'Aplicativo publicado.':'Aplicativo retirado do público. As versões foram preservadas.');
    redirect('/admin.php?action=legal_app_edit&id='.$id);
}
if($action==='delete_legal_app'){
    $id=(int)($_POST['id']??0);$app=igua_app_find($db,$id);
    if(!$app)throw new InvalidArgumentException('Aplicativo não encontrado.');
    if((string)($_POST['confirm_slug']??'')!==$app['slug'])throw new InvalidArgumentException('Digite o identificador exato para confirmar a exclusão permanente.');
    $db->prepare('DELETE FROM legal_apps WHERE id=?')->execute([$id]);
    flash('Aplicativo e seu histórico removidos permanentemente.','warning');redirect('/admin.php?action=legal_apps');
}
if($action==='create_legal_page'){
    $appId=(int)($_POST['app_id']??0);$app=igua_app_find($db,$appId);
    if(!$app)throw new InvalidArgumentException('Aplicativo não encontrado.');
    $slug=trim((string)($_POST['slug']??''));
    if(!igua_app_slug_valid($slug)||in_array($slug,['inicio','privacidade','termos'],true))throw new InvalidArgumentException('Identificador de página inválido ou reservado.');
    $title=igua_app_clean((string)($_POST['title']??''),180,'o título',true);
    $q=$db->prepare("INSERT INTO legal_app_pages (app_id,kind,slug,draft_title,draft_body) VALUES (?,'custom',?,?,?)");
    $q->execute([$appId,$slug,$title,'']);
    $id=(int)$db->lastInsertId();flash('Página criada como rascunho.');
    redirect('/admin.php?action=legal_page_edit&id='.$id);
}
if($action==='save_legal_page'){
    $id=(int)($_POST['id']??0);$page=igua_app_find_page($db,$id);
    if(!$page)throw new InvalidArgumentException('Página não encontrada.');
    $title=igua_app_clean((string)($_POST['title']??''),180,'o título',true);
    $body=igua_app_clean((string)($_POST['body']??''),65000,'o texto da página');
    $description=igua_app_clean((string)($_POST['seo_description']??''),240,'a descrição SEO');
    $db->prepare('UPDATE legal_app_pages SET draft_title=?,draft_body=?,draft_seo_description=? WHERE id=?')
        ->execute([$title,$body,$description,$id]);
    flash('Rascunho salvo. O conteúdo público não foi modificado.');
    redirect('/admin.php?action=legal_page_edit&id='.$id);
}
if($action==='publish_legal_page'){
    $id=(int)($_POST['id']??0);$page=igua_app_find_page($db,$id);
    if(!$page)throw new InvalidArgumentException('Página não encontrada.');
    if(($_POST['confirm']??'')!=='1')throw new InvalidArgumentException('Confirme a publicação.');
    if(trim($page['draft_title'])==='' || ulen(trim($page['draft_body']))<40)throw new InvalidArgumentException('Preencha título e texto antes de publicar.');
    if(preg_match('/\bPREENCHER\s*:/iu',$page['draft_body']))throw new InvalidArgumentException('Substitua todas as instruções PREENCHER antes de publicar.');
    $db->beginTransaction();
    try {
        $lock=$db->prepare('SELECT id FROM legal_app_pages WHERE id=? FOR UPDATE');$lock->execute([$id]);
        $page=igua_app_find_page($db,$id);
        if(!$page || trim((string)$page['draft_title'])==='' || ulen(trim((string)$page['draft_body']))<40 || preg_match('/\bPREENCHER\s*:/iu',$page['draft_body'])) throw new InvalidArgumentException('O rascunho precisa estar completo antes da publicação.');
        $last=$db->prepare('SELECT COALESCE(MAX(revision_number),0) FROM legal_app_revisions WHERE page_id=?');$last->execute([$id]);
        $num=(int)$last->fetchColumn()+1;
        $q=$db->prepare('INSERT INTO legal_app_revisions (page_id,revision_number,title,body,seo_description,created_by) VALUES (?,?,?,?,?,?)');
        $q->execute([$id,$num,$page['draft_title'],$page['draft_body'],$page['draft_seo_description'],(int)$me['id']]);
        $revision=(int)$db->lastInsertId();
        $db->prepare("UPDATE legal_app_pages SET published_revision_id=?,status='published' WHERE id=?")->execute([$revision,$id]);
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
    flash('Página publicada como revisão '.$num.'.');redirect('/admin.php?action=legal_page_edit&id='.$id);
}
if($action==='unpublish_legal_page'){
    $id=(int)($_POST['id']??0);$page=igua_app_find_page($db,$id);
    if(!$page)throw new InvalidArgumentException('Página não encontrada.');
    if(($_POST['confirm']??'')!=='1')throw new InvalidArgumentException('Confirme a despublicação.');
    $db->prepare("UPDATE legal_app_pages SET status='draft' WHERE id=?")->execute([$id]);
    flash('Página despublicada. O histórico continua disponível.');redirect('/admin.php?action=legal_page_edit&id='.$id);
}
if($action==='restore_legal_revision'){
    $id=(int)($_POST['id']??0);$revisionId=(int)($_POST['revision_id']??0);
    if(!igua_app_find_page($db,$id))throw new InvalidArgumentException('Página não encontrada.');
    if(($_POST['confirm']??'')!=='1')throw new InvalidArgumentException('Confirme a restauração da revisão.');
    $q=$db->prepare('SELECT title,body,seo_description FROM legal_app_revisions WHERE id=? AND page_id=? LIMIT 1');$q->execute([$revisionId,$id]);
    $revision=$q->fetch();if(!$revision)throw new InvalidArgumentException('Revisão não encontrada.');
    $db->prepare('UPDATE legal_app_pages SET draft_title=?,draft_body=?,draft_seo_description=? WHERE id=?')
        ->execute([$revision['title'],$revision['body'],$revision['seo_description'],$id]);
    flash('Revisão restaurada para o rascunho. A página pública continua inalterada.');
    redirect('/admin.php?action=legal_page_edit&id='.$id);
}
if($action==='delete_legal_page'){
    $id=(int)($_POST['id']??0);$page=igua_app_find_page($db,$id);
    if(!$page)throw new InvalidArgumentException('Página não encontrada.');
    if($page['kind']!=='custom')throw new InvalidArgumentException('Páginas principais não podem ser excluídas. Use despublicar.');
    if(($_POST['confirm']??'')!=='1')throw new InvalidArgumentException('Confirme a exclusão permanente.');
    $db->prepare('DELETE FROM legal_app_pages WHERE id=?')->execute([$id]);
    flash('Página adicional excluída, inclusive seu histórico.','warning');
    redirect('/admin.php?action=legal_app_edit&id='.(int)$page['app_id']);
}
