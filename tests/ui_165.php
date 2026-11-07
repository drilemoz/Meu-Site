<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
function ui165(bool $ok,string $message):void {if(!$ok)throw new RuntimeException('UI 1.0.65: '.$message); echo 'OK: '.$message."\n";}
$root=dirname(__DIR__);
$read=static fn(string $file)=>(string)file_get_contents($root.'/'.$file);
ui165(version_compare(trim($read('VERSION')),'1.0.65','>='),'versão sequencial');
ui165(str_contains($read('app/bootstrap.php'), "media_path(\$name)!==null"), 'imagem pública requer arquivo existente para URL de upload');
ui165(str_contains($read('app/media_library.php'), "['missing']=media_path"), 'biblioteca distingue arquivos fisicamente ausentes');
ui165(str_contains($read('public/assets/js/main.js'), "addEventListener('error'"), 'fallback do navegador monitora imagens dinâmicas');
ui165(str_contains($read('public/assets/js/media-library.js'), 'data-media-source='), 'thumbnail com fallback para original');
ui165(str_contains($read('public/assets/js/media-library.js'), 'item.missing){notify('), 'arquivo ausente não pode ser escolhido como capa ou galeria');
ui165(str_contains($read('app/admin_views/routes/backups.php'), 'backup-responsive-table'), 'tabela móvel de backups');
ui165(str_contains($read('app/admin_views/routes/sources.php'), 'source-link-help'), 'URLs de fontes sob ajuda contextual');
ui165(str_contains($read('app/admin_views/security.php'), 'security-metric-help'), 'explicação dos indicadores sob interrogação');
ui165(str_contains($read('public/assets/css/modules/15-archive.css'), 'position:sticky;top:-20px'), 'fechamento do modal permanece na área rolável');
ui165(str_contains($read('app/public_views/layout.php'), "\$route==='buscar'?'active'"), 'navegação inferior marca notícias');
ui165(str_contains($read('public/index.php'), 'if (!$list) return;'), 'oculta apenas seções vazias da página inicial');
