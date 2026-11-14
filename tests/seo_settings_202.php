<?php
declare(strict_types=1);
$root=dirname(__DIR__);
function seo202(bool $ok,string $description):void {if(!$ok)throw new RuntimeException('SEO 1.0.102: '.$description);echo "OK: $description\n";}
function h($value):string{return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function igua_brand_name():string{return 'Iguatama Online';}
function igua_brand_description():string{return 'Acompanhe notícias locais e informações importantes para a comunidade de Iguatama.';}
function setting(string $name,string $default=''):string{return [
 'seo_title_template'=>'%title% | %site%',
 'seo_site_url'=>'https://iguatama.online',
 'seo_social_image'=>'https://iguatama.online', // Configuração legada inválida: não deve virar imagem válida na prévia.
 'seo_index'=>'1','seo_pretty_urls'=>'1','site_tagline'=>'Informação local',
 'city'=>'Iguatama - MG','contact_email'=>'contato@iguatama.online'
][$name]??$default;}
function csrf_field():string{return '<input name="csrf" type="hidden" value="teste-ficticio">';}
function admin_link(string $action):string{return '/admin.php?action='.$action;}
ob_start();require $root.'/app/admin_views/routes/settings.php';$html=(string)ob_get_clean();
seo202(str_contains($html,'id="site-settings-form"')&&str_contains($html,'admin.php?action=save_settings')&&str_contains($html,'name="csrf"'),'formulário original e CSRF preservados');
foreach(['site_name','site_tagline','city','contact_email','seo_title_template','seo_default_description','seo_site_url','seo_social_image','seo_pretty_urls','seo_index'] as $key){seo202(substr_count($html,'name="'.$key.'"')===1,'campo único preservado: '.$key);}
seo202(str_contains($html,'role="tablist"')&&str_contains($html,'seo-preview-google')&&str_contains($html,'seo-preview-social'),'prévias Google e social acessíveis');
seo202(str_contains($html,'seo-content-dialog')&&str_contains($html,'seo-advanced-dialog')&&str_contains($html,'seo-diagnostic-dialog'),'configurações e diagnóstico em diálogos nativos');
seo202(!str_contains($html,'class="seo-settings admin-settings-fold"'),'SEO não permanece escondido em details');
seo202(str_contains($html,'Indexação exige portal pronto')&&str_contains($html,'URLs amigáveis exigem roteamento funcional'),'avisos de risco preservados');
seo202(str_contains($html,'seo-social-preview-image')&&str_contains($html,'data-media-pick="social"'),'biblioteca existente preservada');
seo202(substr_count($html,'method="post"')===1,'nenhum novo fluxo de gravação sem confirmação');
$layout=file_get_contents($root.'/app/admin_views/layout.php');
seo202(str_contains($layout,"\$action==='settings'")&&str_contains($layout,'seo-settings.js'),'script somente na tela de configurações');
$media=file_get_contents($root.'/public/assets/js/media-library.js');
seo202(str_contains($media,"input.dispatchEvent(new Event('input',{bubbles:true}))"),'seleção de imagem atualiza prévia social');
$js=file_get_contents($root.'/public/assets/js/seo-settings.js');
seo202(str_contains($js,'indexação do portal')&&str_contains($js,'IguaAdminUI?.confirm'),'desativar indexação exige confirmação');
seo202(!str_contains($js,'fetch('),'diagnóstico local não acessa APIs remotas');
echo "SEO 1.0.102: regressões concluídas, hospedagem ainda não verificada.\n";
