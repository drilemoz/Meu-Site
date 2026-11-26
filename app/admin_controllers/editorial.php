<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
        require_once __DIR__.'/../portal_options.php';
        if($action==='portal_local_preset'){
            require_ability('manage_taxonomy',$me);igua_portal_local_preset($db);flash('Modelo local aplicado. Notícias e endereços antigos preservados.');redirect('/admin.php?action=categories');
        }
        if($action==='portal_category_selection'){
            require_ability('manage_taxonomy',$me);
            $known=array_map('intval',$db->query('SELECT id FROM categories')->fetchAll(PDO::FETCH_COLUMN));$raw=$_POST['active_categories']??[];
            if(!is_array($raw)||!array_is_list($raw)||!$raw)throw new RuntimeException('Selecione ao menos uma categoria.');
            $ids=[];foreach($raw as $id){if(!is_string($id)||!ctype_digit($id)||!in_array((int)$id,$known,true))throw new RuntimeException('Categoria inválida.');$ids[]=(int)$id;}
            igua_portal_setting_write($db,'portal_active_categories',json_encode(array_values(array_unique($ids)),JSON_THROW_ON_ERROR));flash('Categorias ativas salvas.');redirect('/admin.php?action=categories');
        }
        if($action==='portal_category_move'){
            require_ability('manage_taxonomy',$me);$from=filter_var($_POST['from']??null,FILTER_VALIDATE_INT);$to=filter_var($_POST['to']??null,FILTER_VALIDATE_INT);
            $known=array_map('intval',$db->query('SELECT id FROM categories')->fetchAll(PDO::FETCH_COLUMN));
            if($from===$to||!in_array($from,$known,true)||!in_array($to,$known,true))throw new RuntimeException('Escolha duas categorias diferentes.');
            $db->beginTransaction();try{
                // Preserva a associação antiga para URLs de arquivo e buscas históricas.
                $db->prepare('INSERT IGNORE INTO article_categories(article_id,category_id) SELECT id,category_id FROM articles WHERE category_id=?')->execute([$from]);
                $s=$db->prepare('UPDATE articles SET category_id=? WHERE category_id=?');$s->execute([$to,$from]);$n=$s->rowCount();
                $db->commit();
            }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
            flash($n.' matérias reclassificadas. Categoria histórica preservada.');redirect('/admin.php?action=categories');
        }
        if ($action==='save_category') {
            require_ability('manage_taxonomy',$me);
            $name=trim((string)($_POST['name']??''));if(ulen($name)<2 || ulen($name)>75) throw new RuntimeException('Nome de categoria inválido.');
            $base=slug($name);$c=$base;$i=2;
            while (true) {$t=$db->prepare('SELECT id FROM categories WHERE slug=?');$t->execute([$c]);if(!$t->fetch())break;$c=$base.'-'.$i++;}
            
            $id=(int)($_POST['id']??0);$order=filter_var($_POST['sort_order']??100,FILTER_VALIDATE_INT,['options'=>['min_range'=>0,'max_range'=>9999]]);if($order===false)throw new RuntimeException('Ordem inválida.');
            if($id){$db->prepare('UPDATE categories SET name=?,sort_order=? WHERE id=?')->execute([$name,$order,$id]);flash('Categoria atualizada.');redirect('/admin.php?action=categories');}
            $stmt=$db->prepare('INSERT INTO categories (name,slug,sort_order) VALUES (?,?,?)');$stmt->execute([$name,$c,$order]);igua_portal_add_active($db,(int)$db->lastInsertId());flash('Categoria criada.');redirect('/admin.php?action=categories');
        }
        if ($action==='delete_category') {
            require_ability('manage_taxonomy',$me);
            $id=(int)($_POST['id']??0);
            $db->prepare('DELETE FROM categories WHERE id=?')->execute([$id]);flash('Categoria removida. As notícias continuam salvas.');redirect('/admin.php?action=categories');
        }
        if ($action==='load_editor_examples') {
            require_ability('manage_taxonomy',$me);
            $password=(string)($_POST['confirm_password']??'');
            $s=$db->prepare('SELECT password_hash FROM users WHERE id=?');$s->execute([(int)$me['id']]);
            if(!password_verify($password,(string)$s->fetchColumn()))throw new RuntimeException('Senha inválida.');
            $backup=make_backup($db,'manual');
            $count=igua_seed_editor_examples($db,(int)$me['id']);
            flash('Backup preventivo criado: '.$backup.'. '.$count.' novos exemplos fictícios inseridos; os existentes foram preservados.');
            redirect('/admin.php?action=articles');
        }
        if ($action==='load_samples') {
            require_ability('manage_taxonomy',$me);
            $password=(string)($_POST['confirm_password']??'');
            $s=$db->prepare('SELECT password_hash FROM users WHERE id=?');$s->execute([$me['id']]);
            if (!password_verify($password,(string)$s->fetchColumn())) throw new RuntimeException('Senha da conta inválida.');
            $filename=make_backup($db,'manual');
            $result=seed_demo($db,(int)$me['id'],true);
            flash('Backup criado (' .$filename. '). '.$result['inserted'].' matérias de referência carregadas. Notícias próprias preservadas.');
            redirect('/admin.php?action=articles');
        }
        if ($action==='save_author') {
            require_ability('manage_taxonomy',$me);
            $name=trim((string)($_POST['name']??''));$bio=trim((string)($_POST['bio']??''));$id=(int)($_POST['id']??0);
            if(ulen($name)<2||ulen($name)>120||ulen($bio)>500)throw new RuntimeException('Confira nome e biografia do autor.');
            if($id)$db->prepare('UPDATE author_profiles SET name=?,bio=? WHERE id=?')->execute([$name,$bio,$id]);
            else $db->prepare('INSERT INTO author_profiles (name,bio) VALUES (?,?)')->execute([$name,$bio]);
            flash('Autor salvo.');redirect('/admin.php?action=authors');
        }
        if ($action==='delete_author') {
            require_ability('manage_taxonomy',$me);$id=(int)($_POST['id']??0);
            $db->prepare('UPDATE articles SET author_profile_id=NULL WHERE author_profile_id=?')->execute([$id]);
            $db->prepare('DELETE FROM author_profiles WHERE id=?')->execute([$id]);flash('Autor removido. Matérias preservadas.');redirect('/admin.php?action=authors');
        }
        if ($action==='save_source') {
            require_ability('manage_taxonomy',$me);
            $name=trim((string)($_POST['name']??''));$url=trim((string)($_POST['url']??''));$id=(int)($_POST['id']??0);
            if(ulen($name)<2||ulen($name)>160||ulen($url)>600)throw new RuntimeException('Confira o nome e URL da fonte.');
            if($url!=='' && (!filter_var($url,FILTER_VALIDATE_URL)||!str_starts_with($url,'https://')))throw new RuntimeException('Use URL HTTPS para a fonte.');
            if($id)$db->prepare('UPDATE editorial_sources SET name=?,url=? WHERE id=?')->execute([$name,$url,$id]);
            else $db->prepare('INSERT INTO editorial_sources (name,url) VALUES (?,?)')->execute([$name,$url]);
            flash('Fonte salva.');redirect('/admin.php?action=sources');
        }
        if ($action==='delete_source') {
            require_ability('manage_taxonomy',$me);$id=(int)($_POST['id']??0);
            $db->prepare('UPDATE articles SET source_id=NULL WHERE source_id=?')->execute([$id]);
            $db->prepare('DELETE FROM editorial_sources WHERE id=?')->execute([$id]);flash('Fonte removida. Referências das notícias preservadas.');redirect('/admin.php?action=sources');
        }
