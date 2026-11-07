<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/integrations.php';
function t170(bool $condition,string $reason): void {if(!$condition)throw new RuntimeException('Verificador 170: '.$reason); echo 'OK: '.$reason.PHP_EOL;}
foreach([null,'','home',[],['article:1'],'https://127.0.0.1/','article:0','article:-2','article:9999999999999999999','category:1?x=2','../../../etc/passwd',str_repeat('a',60)] as $untrusted){
    t170(igua_page_inspect_selector($untrusted)==='home','entradas malformadas não definem URLs arbitrárias');
}
t170(igua_page_inspect_selector('article:17')==='article:17' && igua_page_inspect_selector('category:2')==='category:2','seletores internos são aceitos');
$article=['kind'=>'article','title'=>'Cidade ganha nova biblioteca municipal','data'=>['id'=>17,'title'=>'Cidade ganha nova biblioteca municipal','summary'=>'A nova biblioteca recebeu equipamentos e espaços de leitura para estudantes, professores e toda a comunidade da cidade.','body'=>str_repeat('Reportagem original para demonstrar a avaliação local. ',8),'slug'=>'biblioteca-municipal','seo_title'=>'','seo_description'=>'','image'=>'','image_caption'=>'','cover_alt'=>'','is_demo'=>0,'status'=>'published','published_at'=>'2026-11-01 12:00:00','has_blocks'=>0]];
$cfg=['seo_site_url'=>'https://site.exemplo.com','seo_index'=>'1','seo_pretty_urls'=>'0','has_demo'=>false];
$root=sys_get_temp_dir().'/inspect-170-'.bin2hex(random_bytes(4));mkdir($root);file_put_contents($root.'/index.php','<?php');file_put_contents($root.'/sitemap.php','<?php');
t170(igua_page_inspect_relative(['kind'=>'home'],false)==='/index.php','rota canônica da inicial');
t170(igua_page_inspect_relative($article,false)==='/index.php?p=noticia&slug=biblioteca-municipal','rota clássica da notícia');
t170(igua_page_inspect_relative($article,true)==='/noticia/biblioteca-municipal','rota amigável da notícia');
t170(igua_page_inspect_relative(['kind'=>'category','data'=>['slug'=>'eventos']],true)==='/categoria/eventos','rota de categoria');
t170(igua_page_inspect_relative(['kind'=>'article','data'=>['slug'=>'../../x']],true)==='','slug inválido não produz URL');
$report=igua_page_inspect_report($article,$cfg,$root);
t170(str_contains($report['canonical'],'https://site.exemplo.com/index.php?p=noticia&slug=biblioteca-municipal'),'URL canônica deriva apenas de configuração e slug');
t170($report['counts']['unknown']===1,'Google não consultado é explicitamente não testado');
t170($report['counts']['attention']>=1,'ausência de imagem gera alerta, não inventa arquivo');
t170(array_sum($report['counts'])===count($report['checks']),'indicadores correspondem às verificações');
$demo=$article;$demo['data']['is_demo']=1;
$check=igua_page_inspect_report($demo,$cfg,$root);
t170((bool)array_filter($check['checks'],static fn($x)=>$x['label']==='Permissão de indexação'&&$x['status']==='attention'),'demo não deve anunciar indexação permitida');
$home=igua_page_inspect_report(['kind'=>'home','title'=>'Página inicial'],array_replace($cfg,['has_demo'=>true]),$root);
t170((bool)array_filter($home['checks'],static fn($x)=>$x['label']==='Permissão de indexação'&&$x['status']==='attention'),'home em acervo com demo não simula indexação');
$missing=igua_page_inspect_report(['kind'=>'missing'],$cfg,$root);
t170($missing['counts']['error']===1 && $missing['canonical']==='','página ausente não produz URL pública');
$bad=igua_page_inspect_report($article,array_replace($cfg,['seo_site_url'=>'http://site.exemplo.com']),$root);
t170($bad['canonical']==='' && $bad['counts']['attention']>=1,'HTTP não é aceito como base canônica');
$files=(string)file_get_contents(__DIR__.'/../app/page_inspector.php');
t170(!str_contains($files,'curl_exec(') && !str_contains($files,'file_get_contents(') && !str_contains($files,'fsockopen('),'verificador não envia requisições a destinos de rede');
$view=(string)file_get_contents(__DIR__.'/../app/admin_views/routes/integrations.php');
t170(str_contains($view,'data-int-target="paginas"')&&str_contains($view,'id="int-page-choice"')&&str_contains($view,'<?=h($check['),'formulário mobile e saídas escapadas');
$admin=(string)file_get_contents(__DIR__.'/../public/admin.php');
t170(str_contains($admin,"'integrations'=>'manage_settings'") && str_contains($admin,'require_admin();'),'auditoria restrita ao administrador');
unlink($root.'/index.php');unlink($root.'/sitemap.php');rmdir($root);
echo 'Página 1.0.70: apenas diagnóstico local; Google e hospedagem externa não testados.'.PHP_EOL;
