<?php
declare(strict_types=1);
function check208(bool $condition,string $msg):void {if(!$condition)throw new RuntimeException('Sitemap 208: '.$msg);}
$view=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/sitemap.php');
$brand=(string)file_get_contents(__DIR__.'/../app/branding.php');
foreach(['sitemap-check-dialog','sitemap-indexnow-dialog','sitemap-bing-dialog'] as $id){
 check208(str_contains($view,'id="'.$id.'"'),'pop-up '.$id);
 check208(str_contains($view,'data-sitemap-open="'.$id.'"'),'botão abre '.$id);
}
foreach(['indexnow_setup','indexnow_submit','bing_save','bing_test','bing_remove'] as $action){
 check208(str_contains($view,'action='.$action),'ação preservada: '.$action);
}
check208(str_contains($view,"$"."perPage=12"),'paginação reduzida');
check208(str_contains($view,'data-sitemap-probe')&&str_contains($view,'data-sitemap-copy'),'ações URL preservadas');
check208(str_contains($view,'csrf_field()'),'CSRF preservado');
check208(str_contains($brand,"const IGUA_DEVELOPER_WEBSITE = 'https://adriedsonlemos.vercel.app';"),'link HTTPS oficial');
check208(str_contains($brand,'rel="noopener noreferrer"'),'proteção da aba externa');
echo "Sitemap 208: layout, diálogos, rotas, CSRF e contato verificados.\n";
