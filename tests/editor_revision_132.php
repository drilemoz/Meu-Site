<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/news_editor.php';

/** Simula a transação SQL sem publicar notícias e sem exigir um banco externo. */
final class RevisionStatement132 extends PDOStatement {
    public function __construct(private RevisionDatabase132 $db,private string $sql) {}
    public function execute(?array $params=null):bool {
        $this->db->queries[]=[$this->sql,$params??[]];
        if(str_starts_with($this->sql,'UPDATE articles SET ')) {
            $this->db->updated++;
            if(str_contains($this->sql,'is_demo=0'))$this->db->demo=0;
        }
        return true;
    }
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed {
        if(str_contains($this->sql,'FROM articles WHERE id='))return [
            'id'=>11,'title'=>'Exemplo fictício','summary'=>'Um exemplo','body'=>'Conteúdo de demonstração',
            'category_id'=>2,'seo_title'=>'Título original','seo_description'=>'Descrição antiga',
            'is_demo'=>$this->db->demo,
        ];
        return false;
    }
    public function fetchColumn(int $column=0):mixed {
        if(str_contains($this->sql,'FROM article_revisions WHERE id='))return json_encode([
            'title'=>'Exemplo fictício restaurado','summary'=>'Resumo de teste','body'=>'Texto simulado',
            'category_id'=>2,'seo_title'=>'Metadado','seo_description'=>'Descrição',
            'blocks'=>[],'categories'=>[],
        ],JSON_THROW_ON_ERROR);
        return false;
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array {return [];}
}
final class RevisionDatabase132 extends PDO {
    public array $queries=[];
    public int $demo=1;
    public int $updated=0;
    private bool $tx=false;
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false {return new RevisionStatement132($this,$query);}
    public function beginTransaction():bool {$this->tx=true;return true;}
    public function inTransaction():bool {return $this->tx;}
    public function commit():bool {$this->tx=false;return true;}
    public function rollBack():bool {$this->tx=false;return true;}
}
$db=new RevisionDatabase132();
news_restore_revision($db,11,7,1);
if($db->updated!==1 || $db->demo!==1 || $db->inTransaction())throw new RuntimeException('Restauração de revisão alterou is_demo ou deixou transação aberta.');
if(!array_filter($db->queries,static fn($pair)=>str_contains($pair[0],'INSERT INTO article_revisions')))throw new RuntimeException('Snapshot anterior não foi salvo.');
$updates=array_filter($db->queries,static fn($pair)=>str_starts_with($pair[0],'UPDATE articles SET '));
foreach($updates as [$query,$params])if(str_contains($query,'is_demo')||count($params)!==7)throw new RuntimeException('A restauração alterou a marca fictícia ou os parâmetros SQL.');
echo "OK: restauração mantém is_demo=1, salva histórico e confirma a transação.\n";
