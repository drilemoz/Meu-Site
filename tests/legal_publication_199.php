<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/apps_legal.php';
require __DIR__.'/../app/admin_controllers/post_routes.php';
function legal199(bool $ok,string $message):void {if(!$ok)throw new RuntimeException('1.0.99: '.$message);}
$model=igua_uai_legal_examples();
foreach(['home','privacy','terms'] as $kind){
    legal199(!str_contains($model[$kind]['body'],'PREENCHER:'),'modelo atual sem pendência embutida: '.$kind);
    legal199(!str_contains($model[$kind]['body'],'Última atualização:'),'modelo sem data estática: '.$kind);
}
$legacy=[
'home'=>"2199372ce8b83f834f08c765df91bdb468c8f4de3f7605f14434eaad79bb5f22",
'privacy'=>"ab5efcede2d5428aa06d156aea4548c9bd389e563a4e10e2317b66d1c169fcd8",
];
legal199(!igua_app_uai_legacy_model_untouched('privacy',$model['privacy']['body']),'modelo novo não é confundido com antigo');
legal199(!igua_app_uai_legacy_model_untouched('terms','conteúdo personalizado'),'texto editado não será sobrescrito');
foreach(['','Texto curto','PREENCHER: direitos autorais'] as $bad){
    $thrown=false;try{igua_app_assert_legal_publishable(['draft_title'=>'Política','draft_body'=>$bad]);}catch(InvalidArgumentException){$thrown=true;}
    legal199($thrown,'página incompleta bloqueada');
}
igua_app_assert_legal_publishable(['draft_title'=>'Termos','draft_body'=>$model['terms']['body']]);
$view=file_get_contents(__DIR__.'/../app/admin_views/routes/legal_apps.php');
$controller=file_get_contents(__DIR__.'/../app/admin_controllers/apps_legal.php');
$public=file_get_contents(__DIR__.'/../app/public_views/routes/legal_app.php');
$css=file_get_contents(__DIR__.'/../public/assets/css/modules/17-legal-apps.css');
legal199(igua_admin_post_module('publish_legal_all')==='apps_legal','nova ação roteada e protegida');
legal199(str_contains($controller,"if(\$action==='publish_legal_all')") && str_contains($controller,'$db->beginTransaction()') && str_contains($controller,'$db->rollBack()'),'publicação de todas as páginas transacional');
legal199(str_contains($controller,'igua_app_assert_legal_publishable($pages[$slug])'),'validação completa antes das gravações');
legal199(str_contains($view,'legal_app_info') && str_contains($view,'legal-four-actions'),'informações e quatro controles');
legal199(str_contains($view,'legal-publication-actions') && str_contains($view,'legal-revision-status'),'status e ações alinhados');
legal199(str_contains($view,'Última atualização pública:') && str_contains($view,"$".'published[\'created_at\']'),'data automática da revisão');
legal199(str_contains($public,"$".'legalPage[\'published_updated_at\']'),'data pública usa revisão publicada');
legal199(str_contains($css,'height:fit-content!important') && str_contains($css,'max-height:min(82dvh,740px)'),'modal reduzido e rolável');
legal199(!preg_match('/PREENCHER:/u',$model['privacy']['body']),'privacidade editável e sem marcador');
echo "OK 1.0.99: publicação transacional, data automática, modelos e interface.\n";
