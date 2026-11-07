<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/news_archive.php';
require_once __DIR__.'/../app/news_queries.php';
require_once __DIR__.'/../app/article_workflow.php';
function perf145(bool $condition, string $description): void {
    if (!$condition) throw new RuntimeException('1.0.45: '.$description);
    echo 'OK: '.$description."\n";
}
class NewsPerf145Statement extends PDOStatement {
    public array $bound=[];
    public array $executeArgs=[];
    public function __construct(public string $sql, private NewsPerf145Database $db) {}
    public function execute(?array $params=null): bool {
        $this->executeArgs=$params??[];
        return true;
    }
    public function bindValue(int|string $param, mixed $value, int $type=PDO::PARAM_STR): bool {
        $this->bound[(int)$param]=[$value,$type];
        return true;
    }
    public function fetch(int $mode=PDO::FETCH_DEFAULT, int $cursorOrientation=PDO::FETCH_ORI_NEXT, int $cursorOffset=0): mixed {
        return $this->db->oneRow;
    }
    public function fetchColumn(int $column=0): mixed {
        if (str_contains($this->sql,'COUNT(*)')) return $this->db->total;
        return $this->db->oneValue;
    }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT, mixed ...$args): array {
        return $this->db->rows;
    }
}
class NewsPerf145Database extends PDO {
    /** @var list<NewsPerf145Statement> */
    public array $statements=[];
    public mixed $oneValue=false;
    public mixed $oneRow=false;
    public array $rows=[];
    public int $total=0;
    public function __construct() {}
    public function prepare(string $query, array $options=[]): PDOStatement|false {
        $stmt=new NewsPerf145Statement($query,$this);
        $this->statements[]=$stmt;
        return $stmt;
    }
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs): PDOStatement|false {
        $stmt=new NewsPerf145Statement($query,$this);
        $this->statements[]=$stmt;
        return $stmt;
    }
}
$db=new NewsPerf145Database();
$db->rows=[['id'=>6,'slug'=>'teste','title'=>'Teste']];
$feed=fetch_news($db,500,3,9);
$statement=$db->statements[0];
perf145(count($feed)===1,'consulta do feed retorna os itens fornecidos pelo driver');
perf145(!preg_match('/SELECT\s+a\.\*/i',$statement->sql) && !str_contains($statement->sql,'a.body') && !str_contains($statement->sql,'JOIN users'), 'feed não carrega MEDIUMTEXT nem JOIN desnecessário com usuários');
perf145(str_contains($statement->sql,'a.summary') && str_contains($statement->sql,'a.image') && str_contains($statement->sql,'a.is_demo') && str_contains($statement->sql,'c.color AS category_color'),'campos exigidos pelos cartões mantidos');
perf145(str_contains($statement->sql,'EXISTS(SELECT 1 FROM article_categories') && str_contains($statement->sql,'a.id<>?') && $statement->executeArgs===[3,3,9],'categoria principal/secundária e exclusão preservadas com parâmetros');
perf145(str_contains($statement->sql,'ORDER BY a.published_at DESC,a.id DESC LIMIT 30'),'limite máximo e desempate estável');
$db->statements=[];
$db->oneRow=['id'=>15,'title'=>'Destaque','slug'=>'teste'];
$featured=fetch_featured_news($db);
perf145($featured['id']===15 && str_contains($db->statements[0]->sql,'a.featured=1 ORDER BY a.published_at DESC,a.id DESC'), 'destaque exige marcação explícita e ordem previsível');
perf145(!str_contains($db->statements[0]->sql,'a.*') && !str_contains($db->statements[0]->sql,'a.body'), 'destaque não transfere conteúdo integral');
$db->statements=[];
$popular=fetch_popular_news($db);
perf145(str_contains($db->statements[0]->sql,'ORDER BY a.views DESC,a.published_at DESC,a.id DESC LIMIT 5') && !str_contains($db->statements[0]->sql,'a.body'), 'mais lidas mantêm ranking, desempate e campos mínimos');
$db->statements=[];
$db->oneValue='1';
perf145(has_published_demo_news($db) && str_contains($db->statements[0]->sql,'SELECT 1') && str_contains($db->statements[0]->sql,'LIMIT 1'), 'aviso de demonstração usa existência, não COUNT completo');
$db->oneValue=false;
perf145(!has_published_demo_news($db),'ausência de demonstrações identificada corretamente');
$db->statements=[];
$db->total=0;
$empty=archive_listing($db,500,'sem resultados',null,7,12);
perf145($empty['rows']===[] && $empty['page']===1 && $empty['pages']===1 && $empty['per_page']===12,'paginação vazia retorna metadados corretos');
perf145(count($db->statements)===1 && str_contains($db->statements[0]->sql,'COUNT(*)'),'sem resultados elimina consulta de cartões');
$db->statements=[];
$db->total=25;
$archive=archive_listing($db,9999,'',null,0,12);
perf145($archive['page']===3 && $archive['pages']===3 && count($db->statements)===2,'páginas excessivas limitadas ao último conjunto não vazio');
$binds=$db->statements[1]->bound;
perf145($binds[1]===[12,PDO::PARAM_INT] && $binds[2]===[24,PDO::PARAM_INT], 'LIMIT e OFFSET usam inteiros parametrizados');
perf145(news_admin_page_number(['2'])===1 && news_admin_page_number('2xyz')===1 && news_admin_page_number('-1')===1 && news_admin_page_number('10000')===10000 && news_admin_page_number('99999')===10000,'página do painel rejeita valores inválidos');
perf145(news_admin_search_term(['invalido'])==='' && news_admin_search_term(str_repeat('x',105))===str_repeat('x',100),'busca administrativa aceita só texto e limita comprimento');
perf145(str_contains(file_get_contents(__DIR__.'/../public/index.php'),'SELECT a.*,c.name AS category'), 'detalhe da notícia preserva campos completos para renderização');
echo "Consultas e paginação 1.0.45: testes simulados aprovados; EXPLAIN e MariaDB reais pendentes.\n";
