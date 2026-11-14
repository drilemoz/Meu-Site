<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function check210(bool $ok,string $label):void{if(!$ok)throw new RuntimeException('210 '.$label);echo 'OK '.$label.PHP_EOL;}
require_once $root.'/app/bootstrap.php';
require_once $root.'/app/analytics.php';
require_once $root.'/app/editorial.php';
require_once $root.'/app/settings_service.php';
check210(analytics_page('/noticia/homem-resiste-a-abordagem')==='/noticia/homem-resiste-a-abordagem','notícia amigável preservada');
check210(analytics_page('/categoria/cidade/')==='/categoria/cidade','categoria amigável preservada');
check210(analytics_page('/uaicortes/privacidade',['p'=>'app','app_slug'=>'uaicortes','app_page'=>'privacidade'])==='/uaicortes/privacidade','página legal validada preservada');
check210(analytics_page('/dados-secretos/abc')==='/index.php','rota não reconhecida não é registrada');
check210(analytics_page('/index.php?p=noticia&slug=cidade&token=privado')==='/index.php?p=noticia&slug=cidade','query privada removida');
check210(analytics_bot('Mozilla/5.0')==='','navegador normal não é considerado bot');
check210(analytics_bot('node-fetch/3.0')==='Cliente HTTP','cliente automatizado reconhecido');
check210(analytics_bot('Mozilla/5.0 HeadlessChrome/99')==='Headless','automação reconhecida');
check210(analytics_bot('')!=='','UA vazio permanece suspeito');
class Fake210PDO extends PDO { public function __construct(){} }
$post=['site_name'=>'Iguatama Online','contact_email'=>'contato@iguatama.online','seo_title_template'=>'%title% | %site%',
 'seo_page_description_onibus'=>'Saídas e horários intermunicipais em Iguatama.',
 'seo_page_description_contato'=>'Entre em contato com a redação de Iguatama.'];
$set=igua_settings_validate(new Fake210PDO(),$post);
check210($set['seo_page_description_onibus']===$post['seo_page_description_onibus'] && $set['seo_page_description_contato']===$post['seo_page_description_contato'],'descrições persistidas na configuração');
check210($set['seo_page_description_sobre']==='' && $set['seo_default_description']==='','descrições opcionais mantêm fallback');
$v=file_get_contents($root.'/app/admin_views/routes/settings.php');
check210(str_contains($v,'data-media-pick="favicon"') && str_contains($v,'data-media-pick="social"'),'biblioteca é origem das imagens');
check210(str_contains($v,'type="hidden" name="seo_social_image"') && str_contains($v,'type="hidden" name="seo_favicon_image"'),'caminhos de imagens ocultos');
check210(!str_contains($v,'input name="seo_favicon_image" type="text"') && !str_contains($v,'input name="seo_social_image" type="text"'),'campos técnicos removidos');
check210(str_contains($v,'seo_page_description_<?=h($route)?>'),'páginas fixas possuem descrição independente');
$public=file_get_contents($root.'/public/index.php');
check210(str_contains($public,"'seo_page_description_'.\$route") && str_contains($public,'excerpt($seoDesc,320)'),'metadescrição pública incorpora overrides sem truncamento a 260');
$admin=file_get_contents($root.'/app/analytics_admin.php');
check210(!str_contains($admin,'Visualizações humanas') && str_contains($admin,'não são necessariamente humanos'),'painel de métricas não afirma humanidade');
check210(str_contains(file_get_contents($root.'/app/analytics.php'),"$".'bot=\'Volume suspeito\''),'volume suspeito sinalizado');
echo 'SEO/analytics 1.0.110 aprovados'.PHP_EOL;
