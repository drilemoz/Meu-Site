<?php
declare(strict_types=1);
/** Módulo privado do painel de ônibus. */
function bus_admin_post_routes(PDO $db,string $action): never {
    if($action==='bus_toggle_fixed'){
        $id=(int)($_POST['id']??0);$mode=($_POST['mode']??'fixed')==='auto'?'auto':'fixed';
        $db->prepare("UPDATE bus_routes SET sync_mode=? WHERE id=? AND source_type<>'manual'")->execute([$mode,$id]);
        flash($mode==='fixed'?'Linha fixa: protegida contra importações.':'Linha automática: atualizações passam pela revisão.');
        redirect('/admin.php?action=bus');
    }
    if($action==='save_bus_wizard') {
        $saved=bus_wizard_save($db,$_POST);
        flash('Horário e percurso salvos com sucesso.');
        redirect('/admin.php?action=bus_edit&id='.$saved);
    }
    if($action==='save_bus_route') {
        $id=(int)($_POST['id']??0);
        $name=trim((string)($_POST['name']??''));$company=trim((string)($_POST['company']??''));
        $notes=trim((string)($_POST['notes']??''));$active=isset($_POST['active'])?1:0;
        $syncMode=($_POST['sync_mode']??'fixed')==='auto'?'auto':'fixed';
        if(mb_bus_len($name)<4||mb_bus_len($name)>160||mb_bus_len($company)>160||mb_bus_len($notes)>500)throw new RuntimeException('Confira o nome, a empresa e a observação da linha.');
        $stops=bus_clean_stops((array)($_POST['stop_city']??[]),(array)($_POST['stop_label']??[]),(array)($_POST['stop_offset']??[]));
        if($id){$st=$db->prepare('SELECT id FROM bus_routes WHERE id=?');$st->execute([$id]);if(!$st->fetchColumn())throw new RuntimeException('Linha não encontrada.');}
        $db->beginTransaction();
        try {
            if($id){
                $db->prepare('UPDATE bus_routes SET name=?,company=?,notes=?,active=?,sync_mode=? WHERE id=?')->execute([$name,$company,$notes,$active,$syncMode,$id]);
                $db->prepare('DELETE FROM bus_stops WHERE route_id=?')->execute([$id]);
            }else{
                $db->prepare('INSERT INTO bus_routes (name,company,notes,active,sync_mode) VALUES (?,?,?,?,?)')->execute([$name,$company,$notes,$active,'fixed']);
                $id=(int)$db->lastInsertId();
            }
            $st=$db->prepare('INSERT INTO bus_stops (route_id,city,location_label,minutes_from_start,stop_order) VALUES (?,?,?,?,?)');
            foreach($stops as $stop)$st->execute([$id,$stop['city'],$stop['location_label'],$stop['minutes_from_start'],$stop['stop_order']]);
            $db->commit();
        }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
        flash('Linha e paradas salvas. Agora cadastre os horários de saída.');
        redirect('/admin.php?action=bus_edit&id='.$id);
    }
    if($action==='delete_bus_route') {
        $id=(int)($_POST['id']??0);
        $db->prepare('DELETE FROM bus_routes WHERE id=?')->execute([$id]);
        flash('Linha, paradas e horários excluídos.');redirect('/admin.php?action=bus');
    }
    throw new RuntimeException('Ação inválida para ônibus.');
}
