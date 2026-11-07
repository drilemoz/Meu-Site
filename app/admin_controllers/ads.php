<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
        if (in_array($action,['ads_prepare_ads_txt','ads_save_settings','ads_save_slot','ads_save_campaign','ads_toggle_campaign','ads_delete_campaign','ads_delete_demo'],true)) {require_ability('manage_settings',$me);ad_admin_post($db,$action);}
        if ($action==='load_ad_examples') {
            require_ability('manage_settings',$me);
            $password=(string)($_POST['confirm_password']??'');
            $s=$db->prepare('SELECT password_hash FROM users WHERE id=?');$s->execute([(int)$me['id']]);
            if(!password_verify($password,(string)$s->fetchColumn()))throw new RuntimeException('Senha inválida.');
            // Confere reservas antes de gerar backup e de modificar dados.
            ad_assert_demo_seed_safe($db);
            $backup=make_backup($db,'manual');
            $result=ad_seed_demo_campaigns($db);
            flash('Backup preventivo criado: '.$backup.'. '.$result['inserted'].' campanhas de demonstração cadastradas e '.$result['enabled_slots'].' posições configuradas com anúncios locais.');
            redirect('/admin.php?action=ads');
        }
