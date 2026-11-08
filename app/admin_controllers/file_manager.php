<?php
declare(strict_types=1);
// Este arquivo é incluído somente após sessão autenticada e check_csrf() em admin.php.
require_ability('manage_settings',$me);
$folder=(string)($_POST['folder']??'');
$folder=igua_files_relative($folder);
$_GET['folder']=$folder;
$operation=(string)($_POST['operation']??'');
if($operation==='upload'){
    igua_files_upload($_FILES['file']??[],$folder);
    igua_security_event('admin_action','files_upload');
    flash('Arquivo enviado com sucesso.');
}elseif($operation==='mkdir'){
    igua_files_new_folder($folder,(string)($_POST['name']??''));
    igua_security_event('admin_action','files_mkdir');
    flash('Pasta criada.');
}elseif($operation==='save'){
    $scope=(string)($_POST['scope']??'');
    $file=(string)($_POST['file']??'');
    $path=$scope==='special'?igua_files_special($file):igua_files_resolve($file,false);
    if($path===null || ($scope!=='special' && ($path===null || igua_files_extension($path)!=='txt' && igua_files_extension($path)!=='csv')))
        throw new InvalidArgumentException('Este arquivo não pode ser editado.');
    igua_files_write_text($path,(string)($_POST['content']??''),is_string($_POST['sha256']??null)?$_POST['sha256']:null);
    igua_security_event('admin_action',$scope==='special'?'files_save_public':'files_save');
    flash('Alteração salva.');
}elseif($operation==='rename'){
    igua_files_rename((string)($_POST['file']??''),(string)($_POST['name']??''));
    igua_security_event('admin_action','files_rename');flash('Item renomeado.');
}elseif($operation==='delete'){
    igua_files_delete((string)($_POST['file']??''));
    igua_security_event('admin_action','files_delete');flash('Item excluído permanentemente.');
}else throw new InvalidArgumentException('Operação desconhecida.');
$_GET['folder']=$folder;
$action='files';
