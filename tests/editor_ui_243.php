<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/editorial.php';
require __DIR__.'/../app/news_admin_view.php';
function check243(bool $ok,string $description): void {if(!$ok)throw new RuntimeException('editor 143: '.$description);}
class MockStatement243 extends PDOStatement {
    public function __construct(private string $statement) {}
    public function execute(?array $params=null): bool {return true;}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0): mixed {return false;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args): array {return [];}
    public function fetchColumn(int $column=0): mixed {return false;}
}
class MockPDO243 extends PDO {
    public function __construct() {}
    public function prepare(string $query,array $options=[]):PDOStatement|false {return new MockStatement243($query);}
}
function selected(string $a,string $b):string {return $a===$b?'selected':'';}
$_SESSION=['csrf'=>str_repeat('b',64)];
$db=new MockPDO243();
$row=['id'=>0,'title'=>'Título local','summary'=>'Resumo verificado para teste editorial','category_id'=>4,'status'=>'draft',
 'published_at'=>'','author_profile_id'=>0,'source_id'=>0,'source_name'=>'','source_url'=>'',
 'seo_title'=>'','seo_description'=>'','url_slug'=>'','slug'=>'','featured'=>0,'image'=>'',
 'image_caption'=>'','image_credit'=>'','body'=>'Texto com informação de teste.'];
function render243(PDO $db,array $row,string $role):string {
  ob_start(); news_admin_editor($db,$row,['id'=>1,'role'=>$role],[],[],[['id'=>4,'name'=>'Cidades']]); return (string)ob_get_clean();
}
$admin=render243($db,$row,'admin');
check243(str_contains($admin,'<option value="now" selected>Publicar agora</option>'),'nova notícia de publicador inicia em Publicar agora');
check243(substr_count($admin,'id="news-submit"')===1,'um único botão de finalização');
check243(strpos($admin,'id="news-publication-choice"')<strpos($admin,'id="news-submit"') && strpos($admin,'id="news-submit"')<strpos($admin,'id="news-review-title"'),'seletor e botão lado a lado antes da revisão');
check243(str_contains($admin,'<option value="scheduled" >Agendar</option>') && str_contains($admin,'<option value="draft" >Rascunho</option>'),'opções preservadas');
check243(str_contains($admin,'>Gemini IA</span>'),'atalhos Gemini identificados');
check243(!str_contains($admin,'class="news-gemini-icon igua-gemini-mark"'),'botão Gemini sem preenchimento transparente');
check243(substr_count($admin,'data-media-pick="choose"')===1,'biblioteca única');
$author=render243($db,$row,'author');
check243(str_contains($author,'<option value="draft" selected>Rascunho</option>'),'redator sem publish salva rascunho');
check243(!str_contains($author,'value="now"') && !str_contains($author,'value="scheduled"'),'redator não recebe opções de publicação');
$row['id']=15;$row['status']='published';$row['published_at']=date('Y-m-d H:i:s',time()+86400);
$scheduled=render243($db,$row,'admin');
check243(str_contains($scheduled,'<option value="scheduled" selected>Agendar</option>'),'agendamento preexistente preservado');
$row['published_at']=date('Y-m-d H:i:s',time()-86400);
$published=render243($db,$row,'admin');
check243(str_contains($published,'<option value="now" selected>Publicar agora</option>'),'publicada existente permanece publicar agora');
echo "OK editor_ui_243.php: padrão, permissão, agendamento e HTML reais.\n";
