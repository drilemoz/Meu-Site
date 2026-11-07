<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/auth_throttle.php';
require_once __DIR__.'/../app/settings_service.php';
function r142(bool $yes,string $description): void {if(!$yes)throw new RuntimeException('Falhou: '.$description);echo 'OK: '.$description."\n";}
function r142_failure(callable $fn): void {try{$fn();}catch(Throwable){return;}throw new RuntimeException('Falha esperada não ocorreu.');}

// O mesmo diretório representa sessões PHP independentes do mesmo IP.
$dir=sys_get_temp_dir().'/igua_142_'.bin2hex(random_bytes(8));
if(!mkdir($dir,0700))throw new RuntimeException('Não foi possível criar ambiente de teste.');
try {
    $called=0;
    for($i=0;$i<5;$i++){
        $r=igua_login_with_limit('198.51.100.10',static function()use(&$called){$called++;return null;},1000,$dir);
        r142(!$r['limited']&&$r['user']===null,'tentativa inválida contabilizada '.($i+1));
    }
    $_SESSION=[]; // trocar a sessão não libera a origem
    $r=igua_login_with_limit('198.51.100.10',static function()use(&$called){$called++;return ['id'=>1];},1001,$dir);
    r142($r['limited']&&$called===5,'bloqueio compartilhado e autenticação não executada na sexta tentativa');
    $r=igua_login_with_limit('198.51.100.10',static function()use(&$called){$called++;return ['id'=>999];},1898,$dir);
    r142($r['limited']&&$called===5,'bloqueio continua ativo após expirar janela da primeira falha');
    $r=igua_login_with_limit('198.51.100.11',static fn()=>['id'=>2],1001,$dir);
    r142(!$r['limited']&&($r['user']['id']??null)===2,'IP distinto não compartilha estado');
    r142(!str_contains(implode('',glob($dir.'/*.json')?:[]),'198.51.100.10'),'nomes de arquivo não incluem IP bruto');
    $r=igua_login_with_limit('198.51.100.10',static fn()=>['id'=>3],1900,$dir);
    r142(!$r['limited']&&($r['user']['id']??null)===3,'expiração do bloqueio e liberação');
    $r=igua_login_with_limit('198.51.100.10',static fn()=>null,1901,$dir);
    r142(!$r['limited'],'sucesso limpa contador de falhas');
    r142_failure(static fn()=>igua_login_with_limit('198.51.100.22',static function(){throw new RuntimeException('DB indisponível');},1901,$dir));
    $r=igua_login_with_limit('198.51.100.22',static fn()=>['id'=>4],1901,$dir);
    r142(!$r['limited'],'falha técnica não vira erro de senha');
} finally {
    foreach(glob($dir.'/*')?:[] as $path)@unlink($path);
    @rmdir($dir);
}

// Fake PDO para simular transação e falha SQL sem alterar produção.
class Settings142PDO extends PDO {
    public array $rows=[];
    public bool $active=false;
    public bool $shouldFail=false;
    public int $commits=0,$rollbacks=0;
    public array $original=[];
    public function __construct(){}
    public function beginTransaction(): bool {$this->active=true;$this->original=$this->rows;return true;}
    public function commit(): bool {$this->commits++;$this->active=false;return true;}
    public function rollBack(): bool {$this->rollbacks++;$this->rows=$this->original;$this->active=false;return true;}
    public function inTransaction():bool{return $this->active;}
    public function prepare(string $query,array $options=[]):PDOStatement|false {return new Settings142Stmt($this,$query);}
}
class Settings142Stmt extends PDOStatement {
    public function __construct(private Settings142PDO $db,private string $query){}
    public function execute(?array $params=null):bool{
        if(str_starts_with($this->query,'INSERT INTO settings')){
            if($this->db->shouldFail && $params[0]==='city')throw new PDOException('Falha simulada de gravação');
            $this->db->rows[$params[0]]=$params[1];
        }
        return true;
    }
    public function fetchColumn(int $column=0):mixed{return false;}
}
$db=new Settings142PDO();
$valid=['site_name'=>'Portal teste','site_tagline'=>'Notícias','city'=>'Iguatama','contact_email'=>'contato@example.org','seo_title_template'=>'%title% | %site%'];
r142_failure(static fn()=>igua_settings_save($db,[...$valid,'contact_email'=>'inválido']));
r142(!$db->rows && $db->commits===0,'validação de todos os campos precede escritas');
$db->shouldFail=true;
r142_failure(static fn()=>igua_settings_save($db,$valid));
r142(!$db->rows && $db->rollbacks===1,'falha após primeira gravação restaura estado anterior');
$db->shouldFail=false;
igua_settings_save($db,$valid);
r142($db->commits===1 && $db->rows['site_name']==='Portal teste' && $db->rows['seo_index']==='0','configurações gravadas como unidade');
r142_failure(static fn()=>igua_settings_validate($db,[...$valid,'city'=>['malformed']]));
r142(true,'campos em forma de array são rejeitados');

// Remover exclusivamente uploads novos, nunca arquivos de terceiros, mesmo se injetados por acidente.
$folder=IGUA_STORAGE.'/media';
if(!is_dir($folder))mkdir($folder,0700,true);
$created=$folder.'/'.bin2hex(random_bytes(16)).'.pdf';
file_put_contents($created,'%PDF-test data');
$other=$folder.'/relatorio-'.bin2hex(random_bytes(8)).'.txt';file_put_contents($other,'preservar');
igua_discard_new_uploads([$created,$other,IGUA_STORAGE.'/config.php']);
r142(!is_file($created) && is_file($other),'limpeza de anexos novos sem exclusão de nomes não gerados');
unlink($other);
$news=file_get_contents(__DIR__.'/../app/admin_controllers/news.php');
r142(str_contains($news,'if($rolledBack)igua_discard_new_uploads($createdFiles)')&&str_contains($news,'image_upload(\'image\',$old[\'image\']??null,$onCreated)'),'editor vincula capa e galeria ao rollback');
echo "Refatoração 1.0.42: testes locais completos, integração MariaDB e upload HTTP reais ainda pendentes.\n";
