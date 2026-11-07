<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function check209(bool $ok,string $m):void{if(!$ok)throw new RuntimeException('109: '.$m);}
function h($s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function igua_brand_name():string{return 'Iguatama Online';}
function igua_brand_description():string{return 'Notícias locais';}
function setting(string $name,string $default=''):string{return ['seo_home_title'=>'Iguatama Online','seo_title_template'=>'%title% | %site%','seo_favicon_image'=>'/uploads/icone.png'][$name]??$default;}
function csrf_field():string{return '<input type="hidden" name="csrf" value="teste">';}
function admin_link(string $route):string{return '/admin.php?action='.$route;}
ob_start();require $root.'/app/admin_views/routes/settings.php';$html=(string)ob_get_clean();
foreach(['seo_home_title','seo_favicon_image','seo_page_title_onibus','seo_page_title_sobre','seo_page_title_contato','seo_page_title_anuncie','seo_page_title_privacidade','seo_page_title_termos'] as $name) check209(substr_count($html,'name="'.$name.'"')===1,'campo '.$name);
check209(str_contains($html,'data-media-pick="favicon"')&&str_contains($html,'seo-pages-dialog'),'seleção e popup');
$bus=file_get_contents($root.'/app/bus_public.php');
check209(str_contains($bus,'<details class="bus-search-surface bus-search-collapsible"')&&str_contains($bus,'bus-search-toggle'),'busca expansível');
check209(str_contains($bus,'method="get" action="/index.php"')&&str_contains($bus,'name="origem"')&&str_contains($bus,'name="destino"'),'GET original preservado');
$public=file_get_contents($root.'/public/index.php');
foreach(['seo_home_title','seo_page_title_onibus','seo_page_title_termos'] as $key)check209(str_contains($public,"'".$key."'"),'rota SEO '.$key);
$layout=file_get_contents($root.'/app/public_views/layout.php');
check209(str_contains($layout,'rel="icon"')&&str_contains($layout,'seo_favicon_image'),'favicon no head público');
check209(is_file($root.'/public/assets/img/favicon.svg'),'fallback favicon');
check209(str_contains(file_get_contents($root.'/public/assets/js/media-library.js'),"mode==='favicon'"),'biblioteca aceita favicon');
check209(str_contains(file_get_contents($root.'/public/assets/css/modules/17-legal-apps.css'),'seo-portal-dialog[open]>.seo-portal-dialog-body'),'popup responsivo');
require_once $root.'/app/editorial.php';
function ulen(string $s):int{return function_exists('mb_strlen')?mb_strlen($s,'UTF-8'):strlen($s);}
function igua_validate_brand_name(string $s):string{return trim($s);}
require_once $root.'/app/settings_service.php';
class Fake109PDO extends PDO {public function __construct(){}}
$post=['site_name'=>'Iguatama Online','contact_email'=>'contato@iguatama.online','seo_site_url'=>'https://iguatama.online','seo_title_template'=>'%title% | %site%','seo_home_title'=>'Iguatama Online','seo_favicon_image'=>'https://iguatama.online/favicon.png'];
$v=igua_settings_validate(new Fake109PDO(),$post);
check209($v['seo_home_title']==='Iguatama Online'&&$v['seo_page_title_onibus']===''&&$v['seo_favicon_image']==='https://iguatama.online/favicon.png','settings valida campos sem schema novo');
$invalid=false;try{igua_settings_validate(new Fake109PDO(),array_replace($post,['seo_favicon_image'=>'https://iguatama.online/admin.php']));}catch(InvalidArgumentException){$invalid=true;}
check209($invalid,'arquivo PHP recusado para favicon');
check209(page_title('Horários de ônibus')==='Horários de ônibus | Iguatama Online','título antigo preservado');
echo "SEO e ônibus 1.0.109 aprovados.\n";
