<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
function n191(bool $condition,string $description): void {if(!$condition)throw new RuntimeException('Falhou: '.$description);echo "OK: $description\n";}
$allowed=news_attachment_extensions();
foreach(['pdf','txt','csv','json','rtf','docx','xlsx','pptx','odt','ods','odp','jpg','jpeg','png','webp','mp3','wav','mp4','zip'] as $ext){
    n191(isset($allowed[$ext])&&news_attachment_storage_name_valid(str_repeat('f',32).'.'.$ext),'anexo '.$ext.' incluído no contrato');
}
foreach(['php','phtml','html','htm','svg','js','exe','sh','bat','htaccess'] as $ext){
    n191(!news_attachment_storage_name_valid(str_repeat('f',32).'.'.$ext),'formato executável/ativo '.$ext.' rejeitado');
}
n191(!news_attachment_storage_name_valid('../'.str_repeat('f',32).'.pdf'),'caminho de anexo proibido');
n191(!news_attachment_storage_name_valid(str_repeat('f',33).'.pdf'),'nome de anexo adulterado recusado');
$temp=tempnam(sys_get_temp_dir(),'n191');file_put_contents($temp,'%PDF-1.4'.str_repeat('a',120));
n191(news_attachment_extension('documento.pdf','application/pdf',$temp)==='pdf','PDF com assinatura válida');
n191(news_attachment_extension('documento.php','application/pdf',$temp)===null,'extensão proibida mesmo com MIME PDF');
n191(news_attachment_extension('documento.pdf','text/html',$temp)===null,'MIME divergente recusado');
file_put_contents($temp,'não é um PDF');n191(news_attachment_extension('documento.pdf','application/pdf',$temp)===null,'PDF sem cabeçalho recusado');unlink($temp);
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/news.php');
n191(str_contains($controller,"'news_create_category'")&&str_contains($controller,"require_ability('manage_taxonomy'"),'criação de categoria e fonte exige ACL de taxonomia');
n191(str_contains($controller,'!empty($_POST[\'remove_cover\'])'),'remoção de capa validada no salvamento');
$backup=file_get_contents(__DIR__.'/../app/backup.php');
n191(str_contains($backup,'news_attachment_storage_name_valid'),'backup/restauração atualizados para novos anexos');
