<?php
declare(strict_types=1);
// Ações administrativas privadas: executadas após autenticação e validação CSRF.
        if($action==='health_verify'){
            require_ability('backup',$me);
            $name=trim((string)($_POST['filename']??''));
            $checked=igua_health_verify_backup($name);
            flash('Backup validado (leitura somente): versão '.$checked['version'].', '.$checked['tables'].' tabelas, '.$checked['rows'].' registros. SHA-256: '.$checked['sha256'].'. Isso não substitui um teste de restauração.');
            redirect('/admin.php?action=health');
        }
        if($action==='analytics_settings'){
            require_ability('manage_settings',$me);
            $hasGa4=array_key_exists('ga4_measurement_id',$_POST);
            $measurement=$hasGa4&&is_string($_POST['ga4_measurement_id'])?strtoupper(trim($_POST['ga4_measurement_id'])):'';
            if($hasGa4&&$measurement!=='' && analytics_ga4_measurement_id($measurement)==='')throw new InvalidArgumentException('O ID do Google Analytics deve ter o formato G- seguido de letras e números.');
            if($hasGa4&&isset($_POST['ga4_enabled']) && $measurement==='')throw new InvalidArgumentException('Informe um ID de medição GA4 válido antes de ativar a integração.');
            $db->beginTransaction();
            try {
                $save=$db->prepare('INSERT INTO settings (name,value) VALUES (?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)');
                $preferences=['analytics_enabled'=>isset($_POST['enabled'])?'1':'0'];
                if($hasGa4){$preferences['ga4_enabled']=isset($_POST['ga4_enabled'])?'1':'0';$preferences['ga4_measurement_id']=$measurement;}
                foreach($preferences as $key=>$value)$save->execute([$key,$value]);
                $db->commit();
            }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
            igua_security_event('settings_changed','analytics');
            flash('Preferências de métricas salvas.');redirect('/admin.php?action=analytics#analytics-integrations');
        }
        if ($action==='save_settings') {
            require_ability('manage_settings',$me);
            igua_settings_save($db,$_POST);
            igua_security_event('settings_changed');
            flash('Configurações atualizadas.');redirect('/admin.php?action=settings');
        }
