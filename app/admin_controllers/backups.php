<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
        if($action==='restore_bundle'){
            require_ability('backup',$me);
            if(!isset($_FILES['bundle'])||$_FILES['bundle']['error']!==UPLOAD_ERR_OK||!is_uploaded_file($_FILES['bundle']['tmp_name']))throw new RuntimeException('Selecione um pacote ZIP de migração.');
            $st=$db->prepare('SELECT password_hash FROM users WHERE id=?');$st->execute([$me['id']]);
            if(!password_verify((string)($_POST['confirm_password']??''),(string)$st->fetchColumn()))throw new RuntimeException('Senha administrativa incorreta.');
            if(empty($_POST['confirm_overwrite']))throw new RuntimeException('Confirme a substituição dos dados antes de restaurar.');
            restore_media_bundle($db,$_FILES['bundle']['tmp_name']);
            igua_security_event('backup_restored','bundle');
            $_SESSION=[];session_regenerate_id(true);
            redirect('/admin.php?action=login&restored=1');
        }
        if ($action==='create_backup') {require_ability('backup',$me);
            $filename=make_backup($db);rotate_backups();flash('Backup criado: '.$filename);redirect('/admin.php?action=backups');
        }
        if ($action==='restore_backup') {require_ability('backup',$me);
            if (empty($_FILES['backup']['tmp_name']) || $_FILES['backup']['error']!==UPLOAD_ERR_OK || $_FILES['backup']['size']>15*1024*1024) throw new RuntimeException('Selecione um arquivo de backup de até 15 MB.');
            $stmt=$db->prepare('SELECT password_hash FROM users WHERE id=?');$stmt->execute([$me['id']]);
            if(!password_verify((string)($_POST['confirm_password']??''),(string)$stmt->fetchColumn())) throw new RuntimeException('Senha administrativa incorreta.');
            $file=$_FILES['backup'];$bytes=file_get_contents($file['tmp_name']);
            if ($bytes===false) throw new RuntimeException('Não foi possível ler o backup.');
            $backup=parse_backup($bytes,(string)$file['name']);
            // Preserva o estado anterior fora da transação de restauração.
            make_backup($db,'manual');
            restore_backup($db,$backup);
            igua_security_event('backup_restored','logical');
            $_SESSION=[];session_regenerate_id(true);
            redirect('/admin.php?action=login&restored=1');
        }
