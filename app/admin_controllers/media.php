<?php
declare(strict_types=1);
// Rotas autenticadas da Biblioteca de Mídias. Mantém respostas JSON e downloads originais.
// API de mídias: não expõe localização física nem caminhos arbitrários; todas as ações autenticadas.
if(in_array($action,['media_list','media_upload','media_update','media_download','media_trash','media_restore'],true)){
    require_ability(in_array($action,['media_trash','media_restore'],true)?'manage_settings':'write',$me);
    if($action==='media_download'){
        $id=max(0,(int)($_GET['id']??0));$stmt=$db->prepare('SELECT filename,original_name FROM media_library WHERE id=? AND deleted_at IS NULL');$stmt->execute([$id]);$item=$stmt->fetch();
        $path=$item?media_path((string)$item['filename']):null;
        if(!$path){http_response_code(404);exit('Imagem não encontrada.');}
        header('Content-Type: application/octet-stream');header('X-Content-Type-Options: nosniff');header('Cache-Control: no-store');
        header('Content-Disposition: attachment; filename="'.basename(preg_replace('/[^a-zA-Z0-9_.-]+/','_', (string)$item['original_name'])?:'imagem.jpg').'"');
        header('Content-Length: '.filesize($path));readfile($path);exit;
    }
    if($action==='media_list'){
        if(request_post())media_admin_json(['ok'=>false,'error'=>'Método não permitido.'],405);
        try{media_admin_json(['ok'=>true,...media_browse($db,$_GET)]);}catch(Throwable $e){igua_health_log('MEDIA_LIST_FAILED',$e);media_admin_json(['ok'=>false,'error'=>'Não foi possível consultar a biblioteca.'],500);}
    }
    if(!request_post())media_admin_json(['ok'=>false,'error'=>'Use POST.'],405);
    check_csrf();
    try{
        if($action==='media_upload'){
            $file=$_FILES['media_file']??null;if(!$file||is_array($file['name']??null))throw new RuntimeException('Selecione uma imagem.');
            $item=media_upload($db,$file,(int)$me['id']);media_admin_json(['ok'=>true,'item'=>$item]);
        }
        $id=(int)($_POST['id']??0);$st=$db->prepare('SELECT * FROM media_library WHERE id=?');$st->execute([$id]);$item=$st->fetch();if(!$item)throw new RuntimeException('Imagem não encontrada.');
        if($action==='media_update'){
            $fields=[];foreach(['title'=>200,'caption'=>360,'credit'=>180,'alt_text'=>220] as $name=>$max){$value=trim((string)($_POST[$name]??''));if(ulen($value)>$max)throw new RuntimeException('Campo '.$name.' acima do limite.');$fields[]=$value;}
            $db->prepare('UPDATE media_library SET title=?,caption=?,credit=?,alt_text=? WHERE id=?')->execute([...$fields,$id]);
        }elseif($action==='media_trash'){
            if($item['deleted_at'])throw new RuntimeException('Imagem já está na lixeira.');
            if(media_link_count($db,(string)$item['filename'])>0)throw new RuntimeException('Imagem em uso por notícia, galeria ou publicidade. Remova o vínculo antes de enviar à lixeira.');
            $db->prepare('UPDATE media_library SET deleted_at=NOW() WHERE id=?')->execute([$id]);
        }elseif($action==='media_restore'){$db->prepare('UPDATE media_library SET deleted_at=NULL WHERE id=?')->execute([$id]);}
        media_admin_json(['ok'=>true]);
    }catch(RuntimeException $e){media_admin_json(['ok'=>false,'error'=>$e->getMessage()],400);}
    catch(Throwable $e){igua_health_log('MEDIA_OPERATION_FAILED',$e);media_admin_json(['ok'=>false,'error'=>'Falha interna. Consulte a Central de Saúde.'],500);}
}
