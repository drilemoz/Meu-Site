<?php
declare(strict_types=1);
/** Módulo privado do painel de ônibus. */
function bus_admin_post_departures(PDO $db,string $action): never {
    if($action==='save_bus_departure') {
        $id=(int)($_POST['route_id']??0);$time=trim((string)($_POST['start_time']??''));
        $departureId=(int)($_POST['departure_id']??0);
        $mask=bus_weekday_mask((array)($_POST['days']??[]));
        if(!bus_valid_time($time))throw new RuntimeException('Horário inválido. Use HH:MM.');
        $st=$db->prepare('SELECT id FROM bus_routes WHERE id=?');$st->execute([$id]);if(!$st->fetchColumn())throw new RuntimeException('Linha inexistente.');
        $st=$db->prepare('SELECT id FROM bus_departures WHERE route_id=? AND start_time=? AND days_mask=? AND id<>?');
        $st->execute([$id,$time,$mask,$departureId]);if($st->fetchColumn())throw new RuntimeException('Esse horário com os mesmos dias já foi cadastrado.');
        if($departureId){
            $st=$db->prepare('UPDATE bus_departures SET start_time=?,days_mask=? WHERE id=? AND route_id=?');
            $st->execute([$time,$mask,$departureId,$id]);
            if(!$st->rowCount())throw new RuntimeException('Horário não alterado; verifique se ele existe nessa linha.');
        }else $db->prepare('INSERT INTO bus_departures (route_id,start_time,days_mask) VALUES (?,?,?)')->execute([$id,$time,$mask]);
        flash($departureId?'Horário atualizado.':'Horário cadastrado.');redirect('/admin.php?action=bus_edit&id='.$id);
    }
    if($action==='toggle_bus_departure' || $action==='delete_bus_departure') {
        $id=(int)($_POST['id']??0);$st=$db->prepare('SELECT route_id FROM bus_departures WHERE id=?');$st->execute([$id]);$rid=(int)$st->fetchColumn();
        if(!$rid)throw new RuntimeException('Horário não encontrado.');
        if($action==='toggle_bus_departure')$db->prepare('UPDATE bus_departures SET active=1-active WHERE id=?')->execute([$id]);
        else $db->prepare('DELETE FROM bus_departures WHERE id=?')->execute([$id]);
        flash($action==='toggle_bus_departure'?'Horário atualizado.':'Horário excluído.');redirect('/admin.php?action=bus_edit&id='.$rid);
    }
    throw new RuntimeException('Ação inválida para ônibus.');
}
