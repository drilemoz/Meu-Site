<?php
declare(strict_types=1);
/** Regressão da UI do editor sem credenciais nem acesso a banco real. */
$root=getenv('IGUA_VIEW_TEST_ROOT')?:dirname(__DIR__);
require $root.'/app/bootstrap.php';
require $root.'/app/editorial.php';
require $root.'/app/news_admin_view.php';
require_once dirname(__DIR__).'/tests/support/news_editor_view_source.php';

class NewsViewStatement137 extends PDOStatement {
    private array $params=[];
    public function __construct(private string $sql) {}
    public function execute(?array $params=null): bool {$this->params=$params??[];return true;}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0): mixed {return false;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args): array {return [];}
    public function fetchColumn(int $column=0): mixed {return str_contains($this->sql,'alt_text')?'Foto de capa em teste':false;}
}
class NewsViewPDO137 extends PDO {
    public function __construct() {}
    public function prepare(string $query,array $options=[]):PDOStatement|false {return new NewsViewStatement137($query);}
}
function selected(string $a,string $b):string{return $a===$b?'selected':'';}
$_SESSION=['csrf'=>str_repeat('a',64)];
$db=new NewsViewPDO137();
$me=['id'=>1,'role'=>'admin'];
$row=['id'=>0,'title'=>'Título teste','summary'=>'Resumo com <script>inseguro</script>',
    'category_id'=>4,'status'=>'draft','published_at'=>'2026-10-31 09:00:00','author_profile_id'=>0,'source_id'=>0,
    'source_name'=>'','source_url'=>'','seo_title'=>'','seo_description'=>'','url_slug'=>'','slug'=>'',
    'featured'=>0,'image'=>'','image_caption'=>'','image_credit'=>'','body'=>''] ;
$cats=[['id'=>4,'name'=>'Cidades'],['id'=>9,'name'=>'Esportes']];
$profiles=[['id'=>2,'name'=>'Jornalista']];
$sources=[['id'=>3,'name'=>'Fonte de teste']];
function view137_assert(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
foreach([0,42] as $id){
    $row['id']=$id;
    if($id){$row['image']='demo-cidade.jpg';$row['status']='published';}
    ob_start();news_admin_editor($db,$row,$me,$profiles,$sources,$cats);$html=(string)ob_get_clean();
    view137_assert(substr_count($html,'data-tab="')===9,'9 tabs');
    view137_assert(substr_count($html,'data-step="')===9,'9 steps');
    view137_assert(substr_count($html,'name="csrf"')===2,'CSRF forms');
    view137_assert(str_contains($html,'&lt;script&gt;inseguro&lt;/script&gt;'),'escape XSS');
    view137_assert(!str_contains($html,'<script>inseguro</script>'),'não inserir script');
    view137_assert(str_contains($html,'name="id" value="'.$id.'"'),'id editor');
    view137_assert(str_contains($html,'name="library_cover"')&&str_contains($html,'name="attachment_files[]"'),'media fields');
    view137_assert(str_contains($html,'name="status"')&&str_contains($html,'name="publication_choice"'),'publish allowed');
    view137_assert(str_contains($html,'name="seo_title"')&&str_contains($html,'name="seo_description"'),'SEO preserved');
    view137_assert(str_contains($html,'name="extra_categories[]"'),'extra categories');
    view137_assert(str_contains($html,'id="news-draft-initial"'),'draft script');
    view137_assert(str_contains($html,'news-editor.js'),'editor asset');
    if($id){view137_assert(str_contains($html,'Histórico de revisões'),'revisions view');view137_assert(str_contains($html,'Índice Google') && str_contains($html,'page=article%3A42'),'atalho do editor apenas na notícia publicada');}
    else {view137_assert(!str_contains($html,'Histórico de revisões'),'no revisions new');view137_assert(!str_contains($html,'Índice Google'),'sem atalho em rascunho novo');}
    view137_assert(str_contains($html,'news-steps-toggle')&&str_contains($html,'news-seo-dialog'),'wizard compacto + diálogo SEO');
    view137_assert(str_contains($html,'news-upload-actions')&&str_contains($html,'news-taxonomy-main'),'upload e classificação integrados');
    view137_assert(!str_contains($html,'📖 Como preencher'),'botão de guia removido');
    echo 'render-'.($id===0?'new':'existing').': estrutura validada ('.strlen($html).' bytes)' .PHP_EOL;
}
if (is_file($root.'/app/news_admin_view/header.php')){
    $view=igua_news_editor_view_source();
    $parts=array_map('trim',explode("\n",file_get_contents($root.'/tools/news_editor_view.sha256')));
    $hash=explode(' ', $parts[0])[0];
    view137_assert(hash('sha256',$view)===$hash,'templates da versão atual conforme fingerprint');
    view137_assert(substr_count($view,'data-tab="')===9,'9 abas nos templates');
    view137_assert(substr_count($view,'data-step="')===9,'9 etapas nos templates');
    $facade=file_get_contents($root.'/app/news_admin_view.php');
    foreach(['prepare.php','header.php','details.php','blocks.php','media.php','footer.php','revisions.php','scripts.php'] as $name){
        view137_assert(str_contains($facade,"/news_admin_view/{$name}"),'partial '.$name);
    }
}
echo "Testes estruturais/dinâmicos do editor 1.0.37 aprovados.\n";
