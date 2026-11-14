<?php
declare(strict_types=1);
// Este arquivo é incluído somente após sessão autenticada e check_csrf() em admin.php.
require_ability('manage_settings',$me);
$folder=(string)($_POST['folder']??'');
$folder=igua_files_project_relative($folder);
$_GET['folder']=$folder;
$operation=(string)($_POST['operation']??'');
$managedFolder=in_array($operation,['upload','mkdir'],true)?igua_files_project_managed_relative($folder):'';
if($operation==='upload'){
    igua_files_upload($_FILES['file']??[],$managedFolder);
    igua_security_event('admin_action','files_upload');
    flash('Arquivo enviado com sucesso.');
}elseif($operation==='mkdir'){
    igua_files_new_folder($managedFolder,(string)($_POST['name']??''));
    igua_security_event('admin_action','files_mkdir');
    flash('Pasta criada.');
}elseif($operation==='save'){
    $scope=(string)($_POST['scope']??'');
    $file=(string)($_POST['file']??'');
    $path=$scope==='special'?igua_files_special($file):igua_files_resolve(igua_files_project_managed_relative($file),false);
    if($path===null || ($scope!=='special' && ($path===null || igua_files_extension($path)!=='txt' && igua_files_extension($path)!=='csv')))
        throw new InvalidArgumentException('Este arquivo não pode ser editado.');
    igua_files_write_text($path,(string)($_POST['content']??''),is_string($_POST['sha256']??null)?$_POST['sha256']:null);
    igua_security_event('admin_action',$scope==='special'?'files_save_public':'files_save');
    flash('Alteração salva.');
}elseif($operation==='rename'){
    igua_files_rename(igua_files_project_managed_relative((string)($_POST['file']??'')),(string)($_POST['name']??''));
    igua_security_event('admin_action','files_rename');flash('Item renomeado.');
}elseif($operation==='export_zip'){
    igua_security_event('admin_action','files_export_zip');
    igua_fm_export_zip($_POST['selected']??[]);
    exit;
}elseif($operation==='copy' || $operation==='cut'){
    igua_fm_clipboard($_SESSION,$operation,$_POST['selected']??[]);
    igua_security_event('admin_action','files_clipboard');
    flash($operation==='copy'?'Arquivos prontos para copiar. Abra a pasta de destino e toque em Colar.':'Arquivos prontos para mover. Abra a pasta de destino e toque em Colar.');
}elseif($operation==='paste'){
    $n=igua_fm_paste($_SESSION,$folder);
    igua_security_event('admin_action','files_paste');flash($n.' item(ns) colado(s).');
}elseif($operation==='clipboard_clear'){
    unset($_SESSION['igua_files_clipboard']);flash('Área de transferência limpa.');
}elseif($operation==='zip'){
    $name=igua_fm_zip_selection($_POST['selected']??[],$folder,(string)($_POST['name']??''));
    igua_security_event('admin_action','files_zip');flash('Arquivo '.$name.' criado.');
}elseif($operation==='unzip'){
    $name=igua_fm_zip_extract((string)($_POST['file']??''));
    igua_security_event('admin_action','files_unzip');flash('ZIP extraído na pasta '.$name.'.');
}elseif($operation==='bulk_delete'){
    if(($_POST['confirm']??'')!=='EXCLUIR')throw new InvalidArgumentException('Digite EXCLUIR para confirmar a remoção.');
    $n=igua_fm_delete_selection($_POST['selected']??[]);
    igua_security_event('admin_action','files_bulk_delete');flash($n.' item(ns) excluído(s) permanentemente.');
}elseif($operation==='chmod'){
    igua_fm_chmod((string)($_POST['file']??''),(string)($_POST['mode']??''));
    igua_security_event('admin_action','files_chmod');flash('Permissões atualizadas.');
}elseif($operation==='delete'){
    igua_files_delete(igua_files_project_managed_relative((string)($_POST['file']??'')));
    igua_security_event('admin_action','files_delete');flash('Item excluído permanentemente.');
}else throw new InvalidArgumentException('Operação desconhecida.');
$_GET['folder']=$folder;
$action='files';
