<?php
declare(strict_types=1);
require_once __DIR__ . '/support/entry_view_source.php';
require __DIR__.'/../app/bootstrap.php';
function assert_brand(bool $condition,string $label):void {if(!$condition)throw new RuntimeException('Falhou: '.$label);echo "OK: $label\n";}
assert_brand(igua_brand_name()==='Meu Site','fallback da identidade para novas instalações');
assert_brand(igua_brand_version()===trim((string)file_get_contents(__DIR__.'/../VERSION')),'versão única do arquivo VERSION');
assert_brand(IGUA_DEVELOPER_NAME==='Adriedson Lemos','crédito fixo');
assert_brand(IGUA_DEVELOPER_EMAIL==='drilemoz@gmail.com','e-mail fixo');
assert_brand(IGUA_DEVELOPER_PHONE_URI==='+5537998747642','telefone normalizado');
ob_start();igua_developer_footer();$html=ob_get_clean();
foreach(['Versão '.trim((string)file_get_contents(__DIR__.'/../VERSION')),'Desenvolvido por','mailto:drilemoz@gmail.com','tel:+5537998747642','igDeveloperDialog'] as $s)assert_brand(str_contains($html,$s),'rodapé tem '.$s);
foreach(['public/setup.php','public/admin.php','public/index.php'] as $file){$source=igua_refactor_page_source($file);assert_brand(str_contains($source,'igua_developer_footer()'),'rodapé incluído em '.$file);}
$setup=file_get_contents(__DIR__.'/../public/setup.php');
foreach(['name="site_name"','name="seo_default_description"','data-setup-identity','data-site-preview','csrf_field()'] as $s)assert_brand(str_contains($setup,$s),'formulário de instalação: '.$s);
$settings=file_get_contents(__DIR__.'/../app/admin_controllers/system.php');
$settingsService=file_get_contents(__DIR__.'/../app/settings_service.php');
assert_brand(str_contains($settings, 'igua_settings_save($db,$_POST)') && str_contains($settingsService,"'site_name'=>80"),'admin grava nome do portal via serviço transacional');
assert_brand(!str_contains($settings,'IGUA_DEVELOPER_EMAIL') && !str_contains($settings,'IGUA_DEVELOPER_PHONE') && !str_contains($settingsService,'IGUA_DEVELOPER_EMAIL'),'contatos não são editáveis via admin');
$editorial=file_get_contents(__DIR__.'/../app/editorial.php');
assert_brand(str_contains($editorial,'igua_brand_name()') && str_contains($editorial,'%site%'),'SEO usa identidade centralizada');
$public=igua_refactor_page_source('public/index.php');
assert_brand(str_contains($public,"'name'=>igua_brand_name()"),'publisher JSON-LD dinâmico');
assert_brand(str_contains($public,'content="<?= h(igua_brand_name()) ?>"'),'Open Graph dinâmico');

assert_brand(igua_validate_brand_name('  Viajando   por Aí  ')==='Viajando por Aí','nome com acento e normalização de espaços');
assert_brand(igua_validate_brand_name('Tribuna')==='Tribuna','nome alternativo Tribuna');
foreach(['<script>falha</script>','A',str_repeat('X',81),"Invalido\n<tag>"] as $bad){
    try{igua_validate_brand_name($bad);throw new RuntimeException('Aceitou nome inválido');}
    catch(InvalidArgumentException){}
}
assert_brand(h('Viajando <i>por</i> Aí')==='Viajando &lt;i&gt;por&lt;/i&gt; Aí','marca escapada corretamente');
