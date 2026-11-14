<?php
declare(strict_types=1);
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/files.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/modules/18-file-manager.css');
$checks=0;
function ok225(bool $result,string $message):void{global $checks;$checks++;if(!$result)throw new RuntimeException('Falhou: '.$message);}
foreach(['files-select-toggle','files-selection-panel','files-filter-count','files-filter-empty','files-row-extra','files-quick-bar','data-files-unavailable'] as $word){
 ok225(str_contains($view,$word), 'interface inclui '.$word);
}
ok225(str_contains($css,'.files-manager.files-enhanced:not(.files-selecting) .files-select'), 'checkbox oculto apenas com JS');
ok225(str_contains($css,'.files-select-toggle{display:none}'), 'botão inerte oculto sem JS');
ok225(str_contains($css,'.files-manager.files-selecting .files-quick-bar'), 'barra seleção sticky');
ok225(str_contains($view,'files-bulk-delete'), 'exclusão em lote preservada');
ok225(str_contains($view,'files-create-menu'), 'criação de arquivos preservada');
echo "OK file_manager_225: {$checks} verificações.\n";
