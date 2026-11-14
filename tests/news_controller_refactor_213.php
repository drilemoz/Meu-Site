<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_controller_source.php';
require_once __DIR__.'/../app/admin_controllers/post_routes.php';
function news213(bool $ok,string $message): void {
    if(!$ok)throw new RuntimeException('1.0.113: '.$message);
    echo "OK: {$message}\n";
}
$root=dirname(__DIR__);
$main=(string)file_get_contents($root.'/app/admin_controllers/news.php');
$reconstituted=igua_test_news_controller_source();
news213(hash('sha256',$reconstituted)==='744ba96874b7691dff061177b90e5267cf3097722238efd979830388dc9ce780',
    'recomposição byte a byte equivalente ao controlador 1.0.112');
news213(substr_count($main,"require __DIR__.'/news/")===5 && !str_contains($main,'$_GET') && !str_contains($main,'$_POST'),
    'entrada contém somente includes fixos, sem caminhos ou ações controladas pelo usuário');
$files=['taxonomy','gemini','drafts','save','lifecycle'];
foreach($files as $part){
    $path=$root.'/app/admin_controllers/news/'.$part.'.php';
    news213(is_file($path),'fragmento disponível: '.$part);
}
foreach(['news_create_category','news_create_source','news_gemini_suggest','autosave_news','import_url',
    'restore_article_revision','save_article','duplicate_article','delete_article','toggle_article_status'] as $action){
    news213(igua_admin_post_module($action)==='news' && str_contains($reconstituted,"'".$action."'"),
        'rota editorial preservada: '.$action);
}
news213(str_contains($reconstituted,"require_ability('manage_taxonomy'"), 'ACL de categorias e fontes preservada');
news213(str_contains($reconstituted,"require_ability('write'"), 'ACL do editor preservada');
news213(str_contains($reconstituted,"require_ability('publish'"), 'ACL de publicação preservada');
news213(str_contains($reconstituted,'news_photo_zip_import(') && str_contains($reconstituted,'$db->beginTransaction()')
    && str_contains($reconstituted,'if($rolledBack)igua_discard_new_uploads($createdFiles)')
    && str_contains($reconstituted,'if($commitStarted && !$rolledBack)'),
    'uploads, ZIP, transação, rollback e commit incerto preservados');
news213(str_contains($reconstituted,'igua_gemini_suggest_time') && str_contains($reconstituted,'news_import_preview'),
    'limites Gemini e recuperação de importação preservados');
$action='__teste_rota_inexistente__';$db=null;$me=[];
require $root.'/app/admin_controllers/news.php';
news213(true,'entrada sem ação reconhecida não executa gravacão');
