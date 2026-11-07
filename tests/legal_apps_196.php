<?php
declare(strict_types=1);
/** Regressões offline do módulo de aplicativos e páginas legais. */
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/apps_legal.php';
require_once __DIR__.'/../app/backup.php';
require_once __DIR__.'/../app/schema_migrations.php';
require_once __DIR__.'/../app/admin_controllers/post_routes.php';
function legal196(bool $ok,string $description):void{if(!$ok)throw new RuntimeException('Módulo legal: '.$description);}
legal196(IGUA_SCHEMA_REVISION===37,'schema 37 exigido');
legal196(igua_app_slug_valid('uaicortes') && igua_app_slug_valid('meu-aplicativo-2'),'identificadores de múltiplos aplicativos');
foreach(['','u','UPPER','meu_app','site/evil','../admin','app--dois','app-',' app','á'] as $bad)
    legal196(!igua_app_slug_valid($bad),'identificador recusado: '.$bad);
foreach(['admin','noticia','categoria','robots','api','assets','privacidade','onibus','app','index'] as $reserved)
    legal196(igua_app_slug_reserved($reserved),'rota existente reservada: '.$reserved);
legal196(igua_app_page_url('uaicortes')==='/uaicortes','URL de apresentação permanente');
legal196(igua_app_page_url('uaicortes','privacidade')==='/uaicortes/privacidade','URL de privacidade permanente');
legal196(igua_app_page_url('uaicortes','termos')==='/uaicortes/termos','URL de termos permanente');
legal196(igua_app_page_slug('home')==='inicio' && igua_app_page_slug('privacy')==='privacidade','slugs de página independentes do nome');
$escaped=igua_app_render_markdown('## Cabeçalho' . "\n\n" . '<img src=x onerror=alert(1)> **negrito** [externo](https://example.org)' . "\n\n" . '- tópico');
legal196(str_contains($escaped,'<h3>Cabeçalho</h3>') && str_contains($escaped,'<ul><li>tópico</li></ul>'),'títulos e listas formatados');
legal196(str_contains($escaped,'&lt;img') && !str_contains($escaped,'<img') && !str_contains($escaped,'onerror='.'alert(1)>'), 'HTML de usuário escapado');
legal196(str_contains($escaped,'<strong>negrito</strong>') && str_contains($escaped,'href="https://example.org"'),'negrito e link seguro');
foreach(['[x](javascript:alert(1))','[x](data:text/html,boom)','[x](//external.com)','[x](/%2f%2fexample.com)','<script>window.evil=1</script>'] as $malicious){
    $html=igua_app_render_markdown($malicious);
    legal196(!str_contains($html,'href="javascript:') && !str_contains($html,'href="data:') && !str_contains($html,'href="//') && !str_contains($html,'<script'),'Markdown malicioso não cria link perigoso ou script');
}
legal196(str_contains(igua_app_legal_template('privacy','Teste'),'PREENCHER:') && str_contains(igua_app_legal_template('terms','Teste'),'PREENCHER:'),'documentos legais são somente rascunhos-guia');
legal196(igua_app_external_url('https://github.com/example/rep')==='https://github.com/example/rep','URL externa HTTPS');
foreach(['http://example.org','javascript:alert(1)','https://user:secret@example.com','ftp://example.org'] as $bad){
    $rejected=false;try{igua_app_external_url($bad);}catch(InvalidArgumentException){$rejected=true;}
    legal196($rejected,'URL externa perigosa rejeitada');
}
legal196(in_array('legal_apps',BACKUP_TABLES,true)&&in_array('legal_app_revisions',BACKUP_TABLES,true),'backup contempla aplicativos e revisões');
legal196(count(BACKUP_TABLES)===count(BACKUP_TABLES_V11)+4,'backup adiciona somente quatro tabelas');
foreach(['save_legal_app','publish_legal_app','save_legal_page','publish_legal_page','restore_legal_revision','unpublish_legal_page'] as $action)
    legal196(igua_admin_post_module($action)==='apps_legal','rota POST explícita '.$action);
$admin=file_get_contents(__DIR__.'/../public/admin.php');
legal196(is_string($admin) && str_contains($admin,'check_csrf()') && str_contains($admin,'legal_apps') && str_contains($admin,"!=='admin'"),'admin, sessão e CSRF mantidos');
$public=file_get_contents(__DIR__.'/../public/index.php');
legal196(is_string($public) && str_contains($public,"'sobre','app'"),'rota pública app não é convertida em 404');
$apache=file_get_contents(__DIR__.'/../public/.htaccess');$router=file_get_contents(__DIR__.'/../public/router.php');
legal196(str_contains($apache,'app_slug') && str_contains($router,'app_slug'),'rotas amigáveis presentes no Apache e servidor PHP');
$view=file_get_contents(__DIR__.'/../app/public_views/routes/legal_app.php');
legal196(str_contains($view,'igua_app_render_markdown') && !str_contains($view,'data-legal-body-script'),'páginas server-rendered sem dependência JS');
echo "1.0.96: slugs, rotas, autorização, Markdown/HTML seguro, SEO e backup offline aprovados.\n";
