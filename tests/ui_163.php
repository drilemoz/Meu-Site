<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$css=file_get_contents($root.'/public/assets/css/style.css');
$module=file_get_contents($root.'/public/assets/css/modules/15-archive.css');
$layout=file_get_contents($root.'/app/admin_views/layout.php');
function check163(bool $ok,string $message):void{if(!$ok)throw new RuntimeException('UI 1.0.63: '.$message);echo "OK: $message\n";}
check163(version_compare(trim(file_get_contents($root.'/VERSION')),'1.0.63','>='),'versão 1.0.63');
check163(str_contains($module,'.admin-nav-drawer{width:min(270px,calc(100vw - 72px))}') && str_contains($css,'.admin-nav-drawer{width:min(270px,calc(100vw - 72px))}'),'drawer menor em telas largas');
check163(str_contains($module,'.admin-nav-drawer{width:min(248px,calc(100vw - 72px))}') && str_contains($css,'.admin-nav-drawer{width:min(248px,calc(100vw - 72px))}'),'drawer 248 px no celular com folga de 72 px');
check163(str_contains($module,'min-height:40px') && str_contains($css,'min-height:40px'),'links preservam áreas de toque');
check163(str_contains($layout,'<dialog id="admin-nav-dialog"') && str_contains($layout,'class="admin-menu"') && str_contains($layout,'<details class="admin-nav-group"') && str_contains($css,'overflow:auto;overscroll-behavior:contain'),'menu mantém diálogo rolável e grupos');
check163(str_contains($layout,'id="admin-nav-close"') && str_contains($layout,"admin_link('logout')") && str_contains($layout,'<?=csrf_field()?>'),'fechamento e logout com CSRF');
echo "UI 1.0.63: inspeção estática concluída. Teste visual móvel pendente.\n";
