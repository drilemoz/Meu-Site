<?php
declare(strict_types=1);
/** Teste offline do template de leitura, sem banco ou chamadas HTTP. */
$root=dirname(__DIR__);
class FakeArticleStatement extends PDOStatement {
    private array $params=[];
    public function __construct(private string $sql, private bool $related) {}
    public function execute(?array $params=null): bool { $this->params=$params??[]; return true; }
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,...$args): array {
        if (str_contains($this->sql,'FROM articles a LEFT JOIN categories')) {
            if (!$this->related)return [];
            return [
                ['id'=>2,'slug'=>'noticia-relacionada','title'=>'Notícia relacionada','image'=>'foto.jpg','published_at'=>'2026-11-09 14:00:00','is_demo'=>0],
                ['id'=>3,'slug'=>'outro-tema','title'=>'Texto <script>alert(1)</script>','image'=>'foto2.jpg','published_at'=>'2026-11-08 14:00:00','is_demo'=>1],
            ];
        }
        return [];
    }
}
class FakeArticlePDO extends PDO {
    public array $queries=[];
    public function __construct(public bool $related=true) {}
    public function prepare(string $query,array $options=[]):PDOStatement|false {
        $this->queries[]=$query;
        return new FakeArticleStatement($query,$this->related);
    }
}
function h(mixed $x):string{return htmlspecialchars((string)$x,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function published_filter():string{return "a.status = 'published' AND a.published_at <= NOW()";}
function url(array $options):string{return '/index.php?'.http_build_query($options);}
function news_link(array $a):string{return url(['p'=>'noticia','slug'=>$a['slug']]);}
function asset_image(mixed $s):string{return '/uploads/'.(string)$s;}
function readable_date(string $s):string{return date('d/m/Y',strtotime($s));}
function igua_brand_name():string{return 'IguatamaOnline';}
function article_blocks(PDO $db,int $id):array{return [['kind'=>'paragraph','heading'=>'','content'=>str_repeat('notícia texto ',92),'collapsed'=>0]];}
function blocks_plain_text(array $blocks):string{return implode(' ',array_column($blocks,'content'));}
function news_render_blocks(array $blocks):void{echo '<p>'.h($blocks[0]['content']).'</p>';}
function ad_display_slot(PDO $db,string $slot):void{}
function ad_article_body(PDO $db,string $body):void{echo '<p>'.h($body).'</p>';}
function article_media(PDO $db,int $id):array{return [];}
function igua_health_log(string $code,?Throwable $error=null):void{}
require $root.'/app/news_queries.php';
require $root.'/app/news_editor/links.php';

function render_article(bool $related,int $category=8,string $author='Redação',int $views=10,string $links='',string $sourceUrl=''):array {
    $db=new FakeArticlePDO($related);
    $article=['id'=>1,'slug'=>'principal','title'=>'Título da notícia','summary'=>'Resumo curto',
        'category_slug'=>'cidade','category'=>'Cidade','category_id'=>$category,'author'=>'Conta interna <Privada>','source_name'=>$author,
        'published_at'=>'2026-11-10 10:00:00','views'=>$views,'is_demo'=>0,'image'=>'capa.jpg',
        'body'=>'Texto original','source_url'=>$sourceUrl,'reference_links'=>$links];
    ob_start();require dirname(__DIR__).'/app/public_views/routes/article.php';
    return [ob_get_clean(),$db->queries];
}
[$html,$queries]=render_article(true);
assert(str_contains($html,'Leia também'));
assert(str_contains($html,'1 min de leitura'));
assert(substr_count($html,'class="article-related-item"')===2);
assert(str_contains($html,'Notícia relacionada'));
assert(!str_contains($html,'<script>alert(1)</script>'));
assert(str_contains($html,'&lt;script&gt;alert(1)&lt;/script&gt;'));
assert(str_contains($html,'· Exemplo'));
assert(str_contains($html,'id="texto-da-noticia"'));
assert(strpos($html,'data-share-trigger')<strpos($html,'data-share-copy'));
assert(strpos($html,'data-share-copy')<strpos($html,'article-reading-time'));
[$byline,]=render_article(false,8,' Autora <Teste> ',0);
assert(str_contains($byline,'Fonte: <strong>Autora &lt;Teste&gt;</strong>'));
assert(str_contains($byline,'>1 visualização</span>'));
assert(!str_contains($byline,'Conta interna'));
assert(str_contains($byline,'Fonte de referência:')); 
[$fallback,]=render_article(false,8,'  ');
assert(str_contains($fallback,'<strong>Redação IguatamaOnline</strong>'));
assert(count(array_filter($queries,fn($q)=>str_contains($q,'a.status = \'published\'') && str_contains($q,'a.id<>?')))==1);
[$empty,$unused]=render_article(false);
assert(!str_contains($empty,'class="article-related"'));
[$noCategory,$unused]=render_article(true,0);
assert(!str_contains($noCategory,'class="article-related"'));
[$linked,]=render_article(false,8,'Fonte',1,"https://example.org/original\nhttps://example.org/documento?a=1&b=2\nhttps://example.org/documento?a=1&b=2\njavascript:alert(1)",'https://example.org/original');
if(substr_count($linked,'href="https://example.org/original"')!==1 || substr_count($linked,'href="https://example.org/documento?a=1&amp;b=2" target="_blank" rel="noopener noreferrer nofollow"')!==1 || str_contains($linked,'javascript:alert(1)') || !str_contains($linked,'Links de referência'))throw new RuntimeException('Links públicos inválidos ou repetidos');
if(str_contains($empty,'article-reference-links-title'))throw new RuntimeException('Bloco vazio de links');
if(in_array('--render',$argv,true)){echo $html;exit;}
echo "OK: matéria, metadados, contagem estimada, sugestões filtradas, escape, categoria vazia\n";
