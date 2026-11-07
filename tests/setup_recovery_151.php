<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/installer.php';
require __DIR__.'/../app/requirements.php';
function verify151(bool $condition,string $name):void {if(!$condition) throw new RuntimeException('FALHOU: '.$name);echo "OK: $name\n";}
class Fake151Statement extends PDOStatement {
    public function __construct(private mixed $value=null,private array $rows=[]){}
    public function execute(?array $params=null):bool {return true;}
    public function fetchColumn(int $column=0):mixed {return $this->value;}
    public function fetchAll(int $mode=PDO::FETCH_DEFAULT,mixed ...$args):array {return $this->rows;}
}
class Fake151Database extends PDO {
    public array $tables=[];
    public array $counts=[];
    public int $revision=37;
    public string $adminEmail='admin@example.test';
    public string $adminHash='';
    public int $brandSettings=2;
    public array $queried=[];
    public function __construct(){$this->adminHash=password_hash('minha-senha-segura',PASSWORD_DEFAULT);}
    public function query(string $query, ?int $fetchMode=null, mixed ...$fetchModeArgs):PDOStatement|false {
        $this->queried[]=$query;
        if($query==='SHOW TABLES') return new Fake151Statement(rows:$this->tables);
        if(preg_match('/^SELECT COUNT\(\*\) FROM `([a-z_]+)`$/D',$query,$m)) return new Fake151Statement($this->counts[$m[1]]??0);
        throw new RuntimeException('Consulta inesperada: '.$query);
    }
    public function prepare(string $query,array $options=[]):PDOStatement|false {
        $this->queried[]=$query;
        if(str_contains($query,'SELECT value FROM settings')) return new Fake151Statement((string)$this->revision);
        if(str_contains($query,'SELECT COUNT(*) FROM settings')) return new Fake151Statement($this->brandSettings);
        if(str_contains($query,'SELECT password_hash FROM users')) return new Fake151Statement($this->adminHash);
        throw new RuntimeException('Prepare inesperado: '.$query);
    }
}
$expected=igua_setup_known_tables();
verify151(count($expected)===29 && count(array_unique($expected))===29,'lista fechada das 29 tabelas');
// Regressão: nomes da lista correspondem às tabelas criadas pelo esquema e módulos.
$contents='';foreach(glob(__DIR__.'/../app/**/*.php')?:[] as $f)$contents.=file_get_contents($f);foreach(glob(__DIR__.'/../app/*.php')?:[] as $f)$contents.=file_get_contents($f);
preg_match_all('/CREATE TABLE IF NOT EXISTS\s+`?([a-z_]+)`?/i',$contents,$matches);
$found=array_unique($matches[1]);sort($found);$sorted=$expected;sort($sorted);
verify151($found===$sorted,'inventário sincronizado às definições SQL reais');
$db=new Fake151Database();$a=igua_setup_database_inventory($db);
verify151($a['status']==='empty' && igua_database_empty($db)[0],'banco sem tabelas aceito');
$db->tables=$expected;$a=igua_setup_database_inventory($db);
verify151($a['status']==='partial_empty' && igua_database_empty($db)[0],'29 tabelas vazias podem ser retomadas sem duplicação');
$db->counts=['users'=>1,'settings'=>11];$a=igua_setup_database_inventory($db);
verify151($a['status']==='recoverable' && !igua_database_empty($db)[0],'instalação com dados não é tratada como vazia');
verify151(igua_setup_recovery_allowed($db),'instalação completa revisão 37 recuperável');
verify151(igua_setup_verify_recovery_admin($db,'admin@example.test','minha-senha-segura'),'senha administrativa correta validada');
verify151(!igua_setup_verify_recovery_admin($db,'admin@example.test','errada'),'senha administrativa incorreta rejeitada');
$db->revision=35;verify151(!igua_setup_recovery_allowed($db),'revisão antiga requer migração, sem recuperação cega');
$db->revision=37;$db->tables=['users','settings'];verify151(igua_setup_database_inventory($db)['status']==='occupied','esquema incompleto com dados bloqueado');
$db->tables=['users','malware'];verify151(igua_setup_database_inventory($db)['status']==='foreign','tabelas de terceiros bloqueadas');
$db->tables=$expected;$db->counts=['settings'=>4];verify151(igua_setup_database_inventory($db)['status']==='occupied','configuração sem administrador não é recuperada');
$folder=sys_get_temp_dir().'/meusite-151-'.bin2hex(random_bytes(6));mkdir($folder,0700);
try {
    verify151(igua_setup_config_write_probe($folder),'preflight criação e rename sem segredos');
    verify151(!igua_setup_config_write_probe($folder.'/ausente'),'preflight rejeita storage ausente');
    $credentials=['host'=>'localhost','port'=>3306,'database'=>'iguatama_online','username'=>'iguatama_online','password'=>"a'\\senha\nsegura"];
    $staged=igua_stage_config($credentials,$folder);
    verify151(is_file($staged) && !is_file($folder.'/config.php'),'config preparada sem ativar instalação');
    igua_activate_config($staged,$folder);
    verify151(!is_file($staged) && is_file($folder.'/config.php'),'ativação só após persistência dos dados');
    $loaded=require $folder.'/config.php'; verify151($loaded===$credentials,'credenciais serializadas sem truncar caracteres');
    $staged=igua_stage_config($credentials,$folder);
    try{igua_activate_config($staged,$folder);throw new RuntimeException('sobrescreveu config existente');}catch(RuntimeException $e){verify151(str_contains($e->getMessage(),'Já existe'),'não sobrescreve config existente');}
    @unlink($staged);
}finally{foreach(glob($folder.'/*')?:[] as $f)@unlink($f);rmdir($folder);}
echo "Testes de instalação, inventário, recuperação e gravação segura v1.0.51 aprovados (sem banco real).\n";
