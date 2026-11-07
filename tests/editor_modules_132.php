<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_editor_view_source.php';
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/news_editor.php';

function modules132(bool $result,string $description):void {
    if(!$result)throw new RuntimeException('Falha: '.$description);
    echo "OK: {$description}\n";
}
$root=dirname(__DIR__);
$modules=['schema','blocks','format','import','library','media','revisions'];
$facade=(string)file_get_contents($root.'/app/news_editor.php');
foreach($modules as $module){
    modules132(is_file($root.'/app/news_editor/'.$module.'.php'),'Módulo '.$module.' existe');
    modules132(str_contains($facade,"require_once __DIR__.'/news_editor/{$module}.php';"),'Fachada carrega '.$module.' com require_once');
}
$contracts=[
 'migrate_news_schema'=>1,'normalize_news_blocks'=>1,'blocks_plain_text'=>1,
 'save_article_extras'=>4,'article_extra_categories'=>2,'article_blocks'=>2,
 'article_media'=>2,'news_seo_description'=>3,'news_safe_url'=>1,
 'news_public_ipv4'=>1,'news_meta_from_html'=>2,'news_import_url'=>1,
 'news_library_images'=>1,'news_library_resolve'=>1,'news_library_attach_gallery'=>3,
 'news_inline_format'=>1,'news_text_format'=>1,'news_media_upload'=>4,
 'news_media_update'=>3,'news_render_blocks'=>1,'news_try_thumbnail'=>3,
 'news_media_thumbnail'=>1,'news_snapshot_revision'=>3,'news_restore_revision'=>4,
];
foreach($contracts as $name=>$minimumParams){
    $reflection=new ReflectionFunction($name);
    modules132($reflection->getNumberOfParameters()>=$minimumParams && str_contains($reflection->getFileName(),'/app/news_editor/'),
        'API estável no módulo: '.$name);
}
modules132(substr_count((string)igua_news_editor_view_source(),'data-tab="')===9,'Editor continua com três etapas');
modules132(news_seo_description('Teste','Resumo confirmado','Texto')==='Resumo confirmado','SEO preservado');
modules132(count(normalize_news_blocks('[{"kind":"paragraph","content":"Conteúdo"}]'))===1,'Validação dos blocos preservada');
echo "Refatoração editorial v1.0.32: contratos verificados.\n";
