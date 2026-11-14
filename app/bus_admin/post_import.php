<?php
declare(strict_types=1);
/** Módulo privado do painel de ônibus. */
function bus_admin_post_import(PDO $db,string $action): never {
    if($action==='bus_stage_import') {
        $source=(string)($_POST['source']??'');
        if(!in_array($source,['antt','der_mg'],true))throw new RuntimeException('Fonte inválida.');
        $filter=trim((string)($_POST['city_filter']??''));
        if(mb_bus_len($filter)>100)throw new RuntimeException('Filtro muito longo.');
        $url=$source==='antt'?IGUA_ANTT_CATALOG:IGUA_DER_CATALOG;
        $remote=isset($_POST['remote_antt']);$path='';
        if($remote){if($source!=='antt')throw new RuntimeException('Download remoto disponível apenas para o arquivo ANTT confirmado.');$path=bus_import_remote_antt();}
        else {
            $f=$_FILES['official_file']??null;
            if(!$f)throw new RuntimeException('Escolha um arquivo CSV/TXT oficial para importar.');
            $uploadError=(int)($f['error']??UPLOAD_ERR_NO_FILE);
            if(in_array($uploadError,[UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE],true))throw new RuntimeException('Arquivo maior que o limite de upload do PHP ('.ini_get('upload_max_filesize').'). No Termux, reinicie o servidor com php -d upload_max_filesize=20M -d post_max_size=22M -S 127.0.0.1:8000 -t public.');
            if($uploadError!==UPLOAD_ERR_OK || !is_uploaded_file((string)($f['tmp_name']??'')))throw new RuntimeException('Não foi possível receber o arquivo. Verifique o tamanho máximo do PHP e selecione o TXT original.');
            if(($f['size']??0)>15*1024*1024)throw new RuntimeException('Arquivo maior que 15 MB.');
            $path=$f['tmp_name'];
        }
        try {$rows=bus_import_csv_rows($path);$parsed=bus_import_build_candidates($source,$rows,$filter);if(!$parsed['groups'])throw new RuntimeException('O arquivo foi lido, mas nenhuma linha compatível foi encontrada. Confira a cidade do filtro, o nome das linhas, o formato de trajeto e os dias de circulação (cidades ignoradas: '.($parsed['skipped']['no_cities']??0).'; horários inválidos: '.($parsed['skipped']['invalid_time']??0).'; sem dias: '.($parsed['skipped']['no_days']??0).').');$res=bus_import_stage($db,$source,$parsed['groups'],$url);}
        finally {if($remote && is_file($path))@unlink($path);}
        flash('Pré-importação concluída: '.$res['created'].' novos, '.$res['updated'].' atualizados, '.$res['fixed'].' protegidos. '.count($parsed['groups']).' linhas identificadas; revise antes de publicar.');
        redirect('/admin.php?action=bus_import');
    }
    if($action==='bus_approve_import' || $action==='bus_ignore_import') {
        $id=(int)($_POST['id']??0);
        if($action==='bus_approve_import'){$message=bus_import_approve($db,$id);flash($message);}
        else {$stmt=$db->prepare("UPDATE bus_import_queue SET status='ignored' WHERE id=? AND status='pending'");$stmt->execute([$id]);flash('Importação ignorada.');}
        redirect('/admin.php?action=bus_import');
    }
    throw new RuntimeException('Ação inválida para ônibus.');
}
