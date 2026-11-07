<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
function check_upload(bool $test,string $reason): void {if(!$test)throw new RuntimeException($reason);echo "OK: $reason\n";}
check_upload(igua_ini_bytes('2M')===2*1024*1024,'Converte 2M corretamente');
check_upload(igua_ini_bytes('8M')===8*1024*1024,'Converte 8M corretamente');
check_upload(igua_ini_bytes('512K')===512*1024,'Converte 512K corretamente');
check_upload(igua_ini_bytes('0')===0,'Limite sem valor 0');
check_upload(str_contains(igua_upload_error_message(UPLOAD_ERR_INI_SIZE),'upload_max_filesize'),'Mensagens PHP identificam limite do servidor');
check_upload(str_contains(igua_upload_error_message(UPLOAD_ERR_PARTIAL),'interrompido'),'Mensagens distinguem upload parcial');
check_upload(str_contains(igua_upload_error_message(UPLOAD_ERR_CANT_WRITE),'armazenamento'),'Mensagens distinguem erro de escrita');
check_upload(image_upload('__arquivo_ausente__','foto-anterior.jpg')==='foto-anterior.jpg','Mantém capa se nenhum arquivo enviado');
$_FILES['teste']=['name'=>'rejeitada.png','error'=>UPLOAD_ERR_INI_SIZE,'size'=>0,'tmp_name'=>''];
try {image_upload('teste');throw new RuntimeException('Aceitou rejeição PHP');}
catch(RuntimeException $e){check_upload(str_contains($e->getMessage(),'recusada pelo PHP'),'Diferencia erro PHP de imagem maior que 4 MB');}
$_FILES['teste']=['name'=>'grande.png','error'=>UPLOAD_ERR_OK,'size'=>4*1024*1024+1,'tmp_name'=>''];
try {image_upload('teste');throw new RuntimeException('Aceitou capa de mais de 4 MB');}
catch(RuntimeException $e){check_upload(str_contains($e->getMessage(),'acima de 4 MB'),'Mantém limite da capa');}
unset($_FILES['teste']);
