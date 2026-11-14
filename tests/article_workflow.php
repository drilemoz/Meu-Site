<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_controller_source.php';
require_once __DIR__ . '/support/entry_view_source.php';
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_editor.php';
require __DIR__.'/../app/article_workflow.php';
function assert_workflow(bool $yes,string $label):void {if(!$yes)throw new RuntimeException('Falhou: '.$label);echo "OK: {$label}\n";}
assert_workflow(news_admin_status('draft')==='draft'&&news_admin_status('scheduled')==='scheduled'&&news_admin_status('roubo')==='all','filtros permitidos');
[$sql,$args]=news_admin_conditions(['id'=>7,'role'=>'author'],'draft','Iguatama');
assert_workflow(str_contains($sql,'a.author_id=?') && str_contains($sql,"a.status='draft'")&&count($args)===3&&$args[0]===7&&$args[1]==='%Iguatama%','autor vê somente conteúdo próprio e pesquisa parametrizada');
[$sql,$args]=news_admin_conditions(['id'=>9,'role'=>'admin'],'scheduled','');
assert_workflow(str_contains($sql,'a.published_at>NOW()')&&$args===[],'agendadas separadas das publicadas');
[$sql,$args]=news_admin_conditions(['id'=>9,'role'=>'admin'],'published','');
assert_workflow(str_contains($sql,'a.published_at<=NOW()'),'publicadas excluem agendadas');
// Banco falso para testar colisão de slug sem dependência MySQL/SQLite.
class WorkflowResult extends PDOStatement {
    private bool $found=false;
    public function execute(?array $params=null):bool {$this->found=in_array($params[0]??'', ['portal-local','portal-local-2'],true);return true;}
    public function fetchColumn(int $column=0):mixed{return $this->found?1:false;}
}
class WorkflowDB extends PDO { public function __construct() {} public function prepare(string $query,array $options=[]):PDOStatement|false{return new WorkflowResult();}}
$db=new WorkflowDB();
assert_workflow(article_stable_slug($db,'Título alterado',42,'endereco-original')==='endereco-original','edição preserva URL pública');
assert_workflow(article_stable_slug($db,'Portal local')==='portal-local-3','slug novo evita colisões');
assert_workflow(article_stable_slug($db,'Outra notícia',23,'')==='outra-noticia','cópia precisa de URL própria');
assert_workflow(article_stable_slug($db,'Notícia renovada',1,'slug-antigo')==='slug-antigo','SEO não quebra URL ao trocar manchete');
$js=file_get_contents(__DIR__.'/../public/assets/js/news-editor.js');
assert_workflow(str_contains($js,'lastJson=value;status.textContent=')&&str_contains($js,'finally{saving=false;}'),'salvamento automático confirma resposta e tenta de novo');
$admin=igua_refactor_page_source('public/admin.php');
$newsController=igua_test_news_controller_source();
assert_workflow(str_contains($newsController,"$".'action===\'duplicate_article\'') && str_contains($newsController,'can_edit_article($me,$original)'),'duplicação protegida por autorização');
assert_workflow(str_contains($newsController,"$".'action===\'toggle_article_status\'') && str_contains($newsController,'article_toggle_publication($db,$id,$me)'),'atalho de publicação rápida disponível');
assert_workflow(str_contains($admin,'news_admin_conditions(')&&str_contains($admin,'PDO::PARAM_INT'),'paginação e parâmetros tipados');
echo "Fluxo editorial 1.0.13: testes isolados aprovados.\n";
