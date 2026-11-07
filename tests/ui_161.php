<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/media_admin.php';
function check161(bool $ok,string $message): void {if(!$ok)throw new RuntimeException('UI 1.0.61: '.$message);echo "OK: $message\n";}
$root=dirname(__DIR__);
ob_start(); media_admin_modal(true); $inline=(string)ob_get_clean();
ob_start(); media_admin_modal(); $overlay=(string)ob_get_clean();
check161(str_contains($inline,'class="media-modal media-inline"') && str_contains($inline,'<section') && !str_contains($inline,'aria-modal="true"') && !str_contains($inline,'media-close'), 'biblioteca inline acessível e sem janela modal');
check161(str_contains($overlay,'role="dialog" aria-modal="true"') && str_contains($overlay,'media-close') && str_contains($overlay,' hidden '), 'seletor permanece modal fechado por padrão');
check161(substr_count($inline,'data-media-upload')>=1 && str_contains($inline,'data-media-search') && str_contains($inline,'data-media-trash') && str_contains($inline,'media-details-form'),'biblioteca reutiliza upload, busca, lixeira e detalhes');
$pages=file_get_contents($root.'/app/admin_views/pages.php');
$layout=file_get_contents($root.'/app/admin_views/layout.php');
$public=file_get_contents($root.'/app/public_views/layout.php');
$mediajs=file_get_contents($root.'/public/assets/js/media-library.js');
$css=file_get_contents($root.'/public/assets/css/style.css');
check161(str_contains($pages,'media_admin_modal(true)') && !str_contains($pages,"dataset.mediaPage='true'") && str_contains($layout,"if(\$action!=='media')media_admin_modal()"),'rota mantém a página do painel, com uma única instância da biblioteca');
check161(!str_contains($public,'<strong>Projetos</strong>') && str_contains($public,'<strong>Explorar</strong>') && str_contains($public,'<strong>Sobre</strong>')===false && str_contains($public,'p=sobre'),'rodapé sem Projetos, links institucionais preservados');
check161(str_contains($public,'igua_brand_version()') && str_contains($css,'.site-footer .ig-footer-credit') && str_contains($css,'.admin-nav-drawer{width:min(270px,calc(100vw - 72px))}'),'cache do CSS e ajustes móveis presentes');
check161(str_contains($mediajs,'if(embedded){mode=\'standalone\'') && str_contains($mediajs,"mode==='standalone'?'details':'select'") && str_contains($css,'.media-modal.media-inline{position:static'),'JS abre biblioteca embutida e seleção segue modal');
check161(version_compare(trim(file_get_contents($root.'/VERSION')),'1.0.61','>='),'versão do portal atualizada');
echo "Validação estática e renderização PHP aprovadas. Testes em dispositivo e MariaDB pendentes.\n";
