<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
        if ($action==='save_user') {
            require_ability('manage_users',$me);
            $id=(int)($_POST['id']??0);$name=trim((string)($_POST['name']??''));$email=trim((string)($_POST['email']??''));
            $role=(string)($_POST['role']??'author');$pass=(string)($_POST['password']??'');
            if(ulen($name)<2||ulen($name)>100||!filter_var($email,FILTER_VALIDATE_EMAIL)||!in_array($role,['admin','editor','author'],true))throw new RuntimeException('Confira os dados do usuário.');
            $dup=$db->prepare('SELECT id FROM users WHERE email=? AND id<>?');$dup->execute([$email,$id]);if($dup->fetchColumn())throw new RuntimeException('Este e-mail já está cadastrado.');
            if($id){
                $previous=$db->prepare('SELECT role,active FROM users WHERE id=?');$previous->execute([$id]);$old=$previous->fetch();if(!$old)throw new RuntimeException('Usuário inexistente.');
                if(($old['role']==='admin'&&$role!=='admin') && count_active_admins($db)<=1)throw new RuntimeException('Deve existir ao menos um administrador ativo.');
                if($id===(int)$me['id'] && $role!=='admin')throw new RuntimeException('Você não pode retirar sua própria permissão administrativa.');
                if($pass!=='' && strlen($pass)<10)throw new RuntimeException('A senha deve ter pelo menos 10 caracteres.');
                if($pass!=='') {
                    $db->prepare('UPDATE users SET name=?,email=?,role=?,password_hash=? WHERE id=?')->execute([$name,$email,$role,password_hash($pass,PASSWORD_DEFAULT),$id]);
                    igua_remember_revoke_user($db,$id);
                }
                else $db->prepare('UPDATE users SET name=?,email=?,role=? WHERE id=?')->execute([$name,$email,$role,$id]);
            } else {
                if(strlen($pass)<10)throw new RuntimeException('Informe uma senha com pelo menos 10 caracteres.');
                $db->prepare('INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1)')->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT),$role]);
            }
            igua_security_event('user_changed');
            flash('Usuário salvo.');redirect('/admin.php?action=users');
        }
        if ($action==='toggle_user') {
            require_ability('manage_users',$me);$id=(int)($_POST['id']??0);
            if($id===(int)$me['id'])throw new RuntimeException('Você não pode desativar sua própria conta.');
            $q=$db->prepare('SELECT role,active FROM users WHERE id=?');$q->execute([$id]);$u=$q->fetch();if(!$u)throw new RuntimeException('Usuário não encontrado.');
            if($u['role']==='admin' && (int)$u['active']===1 && count_active_admins($db)<=1)throw new RuntimeException('É obrigatório manter um administrador ativo.');
            $db->prepare('UPDATE users SET active=? WHERE id=?')->execute([(int)!$u['active'],$id]);
            igua_remember_revoke_user($db,$id);
            igua_security_event('user_access_changed');
            flash('Acesso do usuário atualizado.');redirect('/admin.php?action=users');
        }
