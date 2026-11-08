<?php
declare(strict_types=1);
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/files.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/modules/18-file-manager.css');
$checks=0;
function ok224(bool $result,string $message): void {global $checks;$checks++;if(!$result)throw new RuntimeException('Falhou: '.$message);}
foreach([
    'files-status-card',
    'Explorador do projeto',
    'Pasta com edição liberada',
    'files-bulk-buttons-grid',
    'files-operations-head',
    'Ir para uploads',
    'Novo arquivo ou pasta',
] as $needle){
    ok224(str_contains($view,$needle), 'view contém '.$needle);
}
foreach([
    '.files-status-card',
    '.files-bulk-buttons-grid',
    '.files-operations-head',
    '.files-jump-link',
    '1.0.124: refinamento visual do gerenciador',
] as $needle){
    ok224(str_contains($css,$needle), 'css contém '.$needle);
}
echo "OK file_manager_224: {$checks} verificações.\n";
