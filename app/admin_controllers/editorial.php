<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
        if ($action==='save_category') {
            require_ability('manage_taxonomy',$me);
            $name=trim((string)($_POST['name']??''));if(ulen($name)<2 || ulen($name)>75) throw new RuntimeException('Nome de categoria inválido.');
            $base=slug($name);$c=$base;$i=2;
            while (true) {$t=$db->prepare('SELECT id FROM categories WHERE slug=?');$t->execute([$c]);if(!$t->fetch())break;$c=$base.'-'.$i++;}
            $stmt=$db->prepare('INSERT INTO categories (name,slug,sort_order) VALUES (?,?,100)');$stmt->execute([$name,$c]);flash('Categoria criada.');redirect('/admin.php?action=categories');
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
