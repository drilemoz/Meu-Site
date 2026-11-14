<?php
declare(strict_types=1);
/** Regressão da exportação em fluxo e limpeza dos arquivos efêmeros da 1.0.43. */
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/backup.php';
function t143(bool $condition,string $message):void{if(!$condition)throw new RuntimeException('Falha: '.$message);echo "OK: $message\n";}
function t143_exception(callable $callback,string $message):void{
    try{$callback();}catch(RuntimeException $e){t143(true,$message);return;}
    throw new RuntimeException('Falha: '.$message);
}
class Backup143Stmt extends PDOStatement{
    private int $index=0;
    public function __construct(private array $rows,private bool $fail=false){}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed{
        if($this->fail)throw new RuntimeException('Falha simulada no banco');
        return $this->rows[$this->index++]??false;
    }
    public function closeCursor():bool{return true;}
    public function execute(?array $params=null):bool{return true;}
}
class Backup143DB extends PDO{
    public bool $committed=false,$rolledBack=false,$active=false;
    public array $queried=[];
    public function __construct(private array $rows=[],private ?string $failTable=null){}
    public function exec(string $statement):int|false{return 0;}
    public function beginTransaction():bool{$this->active=true;return true;}
    public function inTransaction():bool{return $this->active;}
    public function commit():bool{$this->committed=true;$this->active=false;return true;}
    public function rollBack():bool{$this->rolledBack=true;$this->active=false;return true;}
    public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false{
        preg_match('/FROM `([^`]+)`/',$query,$match);
        $table=$match[1]??'';$this->queried[]=$table;
        return new Backup143Stmt($this->rows[$table]??[], $this->failTable===$table);
    }
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new Backup143Stmt([]);}
}
$folder=IGUA_STORAGE.'/backups';
if(!is_dir($folder))mkdir($folder,0700,true);
$sample=new Backup143DB(['users'=>[['id'=>'1','role'=>'admin','active'=>'1'],['id'=>'2','role'=>'author','active'=>'1']],'articles'=>[['title'=>'São Paulo 😀','body'=>'Linha A\nLinha B'],['title'=>'Teste de aspas " e barra \\']]]);
$name=make_backup($sample);
try{
    $path=backup_path($name);t143(is_string($path)&&is_file($path),'arquivo final criado e nome compatível');
    $raw=(string)file_get_contents($path);
    $parsed=parse_backup($raw,$name);
    if(str_ends_with($name,'.gz'))$raw=(string)gzdecode($raw);
    t143($parsed['version']===12 && $parsed['tables']['users'][1]['role']==='author','backup v12 preserva linhas e ordem');
    t143($parsed['tables']['articles'][0]['title']==='São Paulo 😀','UTF-8 preservado na exportação');
    t143($raw===json_encode($parsed,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE),'JSON transmitido equivalente ao formato anterior');
    t143($sample->committed && !$sample->rolledBack,'commit após leitura completa');
    t143($sample->queried===BACKUP_TABLES,'somente tabelas aprovadas e na ordem original');
}finally{@unlink($folder.'/'.$name);}
$before=glob($folder.'/.backup-*')?:[];
$broken=new Backup143DB(['users'=>[['id'=>1]]],'articles');
t143_exception(static fn()=>make_backup($broken),'erro durante leitura interrompe exportação');
t143($broken->rolledBack && !$broken->committed,'rollback executado quando leitura falha');
t143((glob($folder.'/.backup-*')?:[])===$before,'nenhum temporário de backup após falha');
$old=$folder.'/.backup-ABC123';$fresh=$folder.'/.bundle-ABC123';$persistent=$folder.'/manual-20200101-010101-123abc.json';
file_put_contents($old,'abandonado');touch($old,time()-26*3600);
file_put_contents($fresh,'em uso');
file_put_contents($persistent,'arquivo manual antigo');touch($persistent,time()-26*3600);
try{
    t143(cleanup_backup_temporaries()===1 && !file_exists($old),'limpeza remove apenas temporário antigo');
    t143(is_file($fresh) && is_file($persistent),'temporário recente e backup manual permanecem');
}finally{@unlink($old);@unlink($fresh);@unlink($persistent);}
$downloads=file_get_contents(__DIR__.'/../app/admin_controllers/downloads.php');
t143(str_contains($downloads,'register_shutdown_function')&&str_contains($downloads,'@unlink($path);'),'pacote de download removido no final e no encerramento da requisição');
echo "Backup 1.0.43: testes locais aprovados; ext-zip e MariaDB reais pendentes.\n";
