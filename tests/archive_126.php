<?php
declare(strict_types=1);
require_once __DIR__.'/support/news_editor_view_source.php';
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/news_archive.php';
function a126(bool $value,string $message):void{if(!$value)throw new RuntimeException('FALHOU: '.$message);echo "OK: {$message}\n";}
class ArchiveTestStatement extends PDOStatement {
    public array $bound=[];
    public function __construct(private ArchiveTestDatabase $owner, public string $sql) {}
    public function bindValue(int|string $param,mixed $value,int $type=PDO::PARAM_STR):bool {
        $this->bound[(int)$param]=[$value,$type];return true;
    }
    public function execute(?array $params=null):bool {
        $this->owner->executed[]=$this;
        return true;
    }
    public function fetchColumn(int $column=0):mixed{return $this->owner->total;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT, mixed ...$args):array {
        $values=array_values($this->bound);
        $offset=(int)$values[count($values)-1][0];
        $limit=(int)$values[count($values)-2][0];
        $count=max(0,min($limit,$this->owner->total-$offset));
        if ($count===0) return [];
        return array_map(static fn($index)=>['id'=>$offset+$index+1,'slug'=>'teste-'.($offset+$index+1)],range(0,$count-1));
    }
}
class ArchiveTestDatabase extends PDO {
    public array $executed=[];
    public function __construct(public int $total) {}
    public function prepare(string $query,array $options=[]):PDOStatement|false {return new ArchiveTestStatement($this,$query);}
}
$cats=[['id'=>2,'slug'=>'cidade'],['id'=>3,'slug'=>'eventos']];
a126(archive_search_term(['invalido'])==='' && archive_search_term(str_repeat('x',110))===str_repeat('x',100),'termos inválidos e limite de 100 caracteres');
a126(archive_page_number('2')===2 && archive_page_number(['2'])===1 && archive_page_number('-20')===1 && archive_page_number('9999999999')===1,'página limitada e parâmetros malformados bloqueados');
a126(archive_period_days('90')===90 && archive_period_days('365')===0 && archive_period_days(['7'])===0,'períodos limitados a 7, 30 e 90 dias');
a126(archive_category_id($cats,'cidade')===2 && archive_category_id($cats,'../../admin')===null,'editoria resolvida apenas da lista autorizada');
a126(archive_escape_like('50%_!')==='50!%!_!!','coringas LIKE e escape escapados para busca literal');
$db=new ArchiveTestDatabase(29);
$r=archive_listing($db,2,'MG_50%',2,30,12);
a126($r['total']===29 && $r['pages']===3 && $r['page']===2 && count($r['rows'])===12,'paginação de 29 notícias: segunda página tem 12');
a126(str_contains($db->executed[0]->sql,'SELECT COUNT(*)') && str_contains($db->executed[1]->sql,'LIMIT ? OFFSET ?'),'contagem e página usam consultas separadas com LIMIT/OFFSET bind');
a126(str_contains($db->executed[1]->sql,'EXISTS(SELECT 1 FROM article_categories') && str_contains($db->executed[1]->sql,"LIKE ? ESCAPE '!'") && str_contains($db->executed[1]->sql,'INTERVAL 30 DAY'),'pesquisa contempla categorias secundárias, texto literal e filtro de data');
a126(!str_contains($db->executed[1]->sql,'MG_50%') && !str_contains($db->executed[1]->sql,'2=2'),'busca não concatena conteúdo do visitante no SQL');
$all=array_values($db->executed[1]->bound);
a126($all[0]===[2,PDO::PARAM_INT] && $all[1]===[2,PDO::PARAM_INT] && $all[2]===['%MG!_50!%%',PDO::PARAM_STR] && $all[3]===$all[2],'parâmetros de categoria e texto com tipos corretos');
a126($all[4]===[12,PDO::PARAM_INT] && $all[5]===[12,PDO::PARAM_INT],'limit/offset tipados como inteiros');
$db=new ArchiveTestDatabase(13);$last=archive_listing($db,100000,'',null,0,12);
a126($last['page']===2 && $last['pages']===2 && count($last['rows'])===1,'página excessiva ajustada à última página existente');
$db=new ArchiveTestDatabase(0);$empty=archive_listing($db,99);
a126($empty['page']===1 && $empty['pages']===1 && $empty['rows']===[],'zero notícias não gera paginação vazia');
a126(archive_pages_window(50,100)===[1,null,49,50,51,null,100],'janela de botões com reticências em arquivos longos');
$link=archive_link('buscar',3,'arcos & cidade','eventos',7);
a126(str_contains($link,'q=arcos+%26+cidade') && str_contains($link,'cat=eventos') && str_contains($link,'period=7') && str_contains($link,'page=3'),'links preservam busca, editoria, período e página');
a126(archive_link('categoria',2,'','cidade',90)==='/index.php?p=categoria&slug=cidade&period=90&page=2','editoria mantém querystring quando URL amigável desativada');
ob_start();archive_navigation(['page'=>2,'pages'=>3],'buscar','<script>','cidade',7);$html=(string)ob_get_clean();
a126(str_contains($html,'aria-current="page"') && str_contains($html,'rel="prev"') && str_contains($html,'rel="next"'),'navegação acessível com anterior e próxima');
a126(!str_contains($html,'<script>') && str_contains($html,'cat=cidade'),'URL escapada no HTML e categoria preservada');
$index=(string)file_get_contents(__DIR__.'/../public/index.php');
a126(str_contains($index,'archive_listing(') && !str_contains($index,'ORDER BY a.published_at DESC LIMIT 30'),'portal usa listagens paginadas e remove limite fixo de 30');
a126(str_contains($index,'archive_link(\'categoria\',archive_page_number('),'301 do modo amigável preserva os filtros e a página');
a126(str_contains((string)file_get_contents(__DIR__.'/../public/.htaccess'),'[L,QSA]'),'Apache conserva ?period= e ?page= em URLs amigáveis');
a126(substr_count((string)igua_news_editor_view_source(),'data-tab="')===3,'fluxo editorial mantém apenas três etapas');
echo "Arquivo 1.0.26: verificações locais concluídas, integração MariaDB pendente.\n";
