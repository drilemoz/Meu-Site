<?php
declare(strict_types=1);
require_once __DIR__.'/../app/home_news.php';
function published_filter():string{return "a.status = 'published' AND a.published_at <= NOW()";}
function home156(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);echo "OK: $message\n";}
class Home156Statement extends PDOStatement {
    private array $rows=[];
    public function __construct(private Home156PDO $db,private string $sql){}
    public function execute(?array $params=null):bool{$this->rows=$this->db->select($this->sql,$params??[]);return true;}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed{return $this->rows[0]??false;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array{return $this->rows;}
}
class Home156PDO extends PDO {
    public array $articles=[];public array $calls=[];
    public function __construct(){}
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new Home156Statement($this,$query);}
    public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false{$stmt=$this->prepare($query);$stmt->execute();return $stmt;}
    public function select(string $sql,array $params):array{
        $this->calls[]=[$sql,$params];
        if(!str_contains($sql,published_filter()))throw new RuntimeException('Filtro de publicação ausente');
        $rows=array_values(array_filter($this->articles,static fn($a)=>$a['published']));
        if(str_contains($sql,'a.featured=1'))$rows=array_values(array_filter($rows,static fn($a)=>$a['featured']));
        if(str_contains($sql,'EXISTS(SELECT 1 FROM article_categories')){
            $cat=array_shift($params);$secondary=array_shift($params);
            if($cat!==$secondary)throw new RuntimeException('Categoria secundária inconsistente');
            $rows=array_values(array_filter($rows,static fn($a)=>in_array($cat,$a['cats'],true)));
        }
        if(preg_match('/NOT IN \(([^)]+)\).*LIMIT/s',$sql,$m)){
            if(substr_count($m[1],'?')!==count($params))throw new RuntimeException('Parâmetros de exclusão incorretos');
            $rows=array_values(array_filter($rows,static fn($a)=>!in_array($a['id'],$params,true)));
        }elseif($params)throw new RuntimeException('Exclusões ignoradas');
        usort($rows,static fn($a,$b)=>str_contains($sql,'ORDER BY a.views')?([$b['views'],$b['id']]<=>[$a['views'],$a['id']]):($b['id']<=>$a['id']));
        preg_match('/LIMIT (\d+)$/',$sql,$limit);
        return array_slice($rows,0,(int)$limit[1]);
    }
}
$cats=[['id'=>1,'slug'=>'cidade','name'=>'Cidade'],['id'=>2,'slug'=>'eventos','name'=>'Eventos'],['id'=>3,'slug'=>'politica','name'=>'Política'],['id'=>4,'slug'=>'regiao','name'=>'Região'],['id'=>5,'slug'=>'economia','name'=>'Economia']];
$defaults=igua_home_preferences($cats);
home156($defaults===['ids'=>[1,2,3],'region'=>4],'configuração padrão e categoria regional explícita');
home156(igua_home_preferences($cats,'[]','none')===['ids'=>[],'region'=>0],'desativar todas persiste sem restaurar padrões');
home156(igua_home_preferences($cats,'[1,5,99]','99')===['ids'=>[1,5],'region'=>0],'ignora categorias excluídas');
$values=igua_home_validate_preferences($cats,['home_category_ids'=>['2','5'],'home_region_category'=>'4']);
home156($values===['home_category_ids'=>'[2,5]','home_region_category'=>'4'],'validação e serialização por ID');
foreach([['home_category_ids'=>['99']],['home_category_ids'=>'1'],['home_region_category'=>['4']],['home_region_category'=>'99']] as $bad){$rejected=false;try{igua_home_validate_preferences($cats,$bad);}catch(InvalidArgumentException){$rejected=true;}home156($rejected,'configuração inválida rejeitada');}
$db=new Home156PDO();
for($id=1;$id<=24;$id++)$db->articles[]=['id'=>$id,'published'=>$id<23,'featured'=>in_array($id,[21,22,24],true),'cats'=>match($id){22,21,20,19,18,17,16,15=>[1,2],14,13,12,11=>[4,2],10,9,8=>[2,5],7,6,5=>[3],default=>[5]},'views'=>$id,'published_at'=>date('Y-m-d H:i:s',time()-($id===1?86400*60:86400*(24-$id)))];
$home=igua_home_news($db,$cats,['ids'=>[1,2,3,4,5],'region'=>4]);
$ids=static fn($rows)=>array_column($rows,'id');
home156($home['featured']['id']===22,'destaque publicado mais recente vence; futuro/rascunho excluído');
home156($ids($home['recent'])===[19,18,17,16],'últimas recebem notícias restantes após reservar manchetes');
home156(array_column($home['sections'],'title')===['Cidade','Acontece na região','Eventos','Política','Economia'],'ordem editorial e categoria regional sem segunda seção');
home156($ids($home['sections'][0]['rows'])===[21]&&$ids($home['sections'][1]['rows'])===[14,13,12]&&$ids($home['sections'][2]['rows'])===[20],'manchetes reservadas sem consumir categoria seguinte; complementos limitados');
$all=[$home['featured']['id'],...$ids($home['recent']),...$ids($home['popular'])];foreach($home['sections'] as $sec)array_push($all,...$ids($sec['rows']));
home156(count($all)===count(array_unique($all)),'nenhum ID repetido na home completa');
$disabled=igua_home_news($db,$cats,['ids'=>[],'region'=>0]);
home156($disabled['sections']===[]&&count($disabled['popular'])===5,'editorias desativadas não excluem notícias do ranking');
// Regressão do print: a última da Cidade não deve ir para Últimas enquanto outubro vira manchete.
$db->articles=[
 ['id'=>10,'published'=>true,'featured'=>false,'cats'=>[1],'views'=>10,'published_at'=>date('Y-m-d H:i:s')],
 ['id'=>9,'published'=>true,'featured'=>false,'cats'=>[4],'views'=>9,'published_at'=>date('Y-m-d H:i:s')],
 ['id'=>8,'published'=>true,'featured'=>false,'cats'=>[5],'views'=>8,'published_at'=>date('Y-m-d H:i:s')],
 ['id'=>1,'published'=>true,'featured'=>false,'cats'=>[1],'views'=>1,'published_at'=>date('Y-m-d H:i:s',strtotime('-45 days'))],
];
$regression=igua_home_news($db,$cats,$defaults);
home156($ids($regression['sections'][0]['rows'])===[10]&&$ids($regression['sections'][1]['rows'])===[9],'Cidade e região herdam notícia mais recente sem complemento antigo');
$db->articles[0]['featured']=true;
$regression=igua_home_news($db,$cats,$defaults);
home156(!in_array('Cidade',array_column($regression['sections'],'title'),true),'se manchete já é destaque, não preencher Cidade com notícia de 45 dias');
$db->articles=array_slice($db->articles,1,2);
// Acervo reduzido fora das editorias habilitadas.
foreach($db->articles as &$a)$a['cats']=[5];unset($a);
$short=igua_home_news($db,$cats,$defaults);
home156($short['featured']===false&&count($short['recent'])===2&&$short['sections']===[]&&$short['popular']===[],'acervo pequeno: não repete e não cria destaque automático');
$db->articles=[];$empty=igua_home_news($db,$cats,$defaults);
home156($empty===['featured'=>false,'recent'=>[],'sections'=>[],'popular'=>[]],'acervo vazio sem módulos de notícias');
