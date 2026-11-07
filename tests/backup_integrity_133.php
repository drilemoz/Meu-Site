<?php
declare(strict_types=1);
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/backup.php';
function backup133(bool $ok,string $name): void {if(!$ok)throw new RuntimeException('Falha: '.$name);echo "OK: $name\n";}

// A extensão ZIP pode não estar instalada no ambiente de testes.
if(!class_exists('ZipArchive')){
    class ZipArchive {
        public int $numFiles=0;
        public function __construct(private array $names=[]){$this->numFiles=count($names);}
        public function statIndex(int $i):array|false{return isset($this->names[$i])?['name'=>$this->names[$i]]:false;}
    }
}
function backup133_archive(array $names): ZipArchive {
    if(!extension_loaded('zip'))return new ZipArchive($names);
    $path=tempnam(sys_get_temp_dir(),'igua133-');
    $zip=new ZipArchive();$zip->open($path,ZipArchive::OVERWRITE);
    foreach($names as $name)$zip->addFromString($name,'teste');
    $zip->close();$reopen=new ZipArchive();$reopen->open($path);@unlink($path);
    return $reopen;
}
/**
 * Um ZIP real com entradas duplicadas. addFromString() com o comportamento
 * padrão sobrescreve nomes existentes e NÃO exercita a verificação desejada.
 * O fixture usa entradas duplicadas reais no diretório central do ZIP.
 */
function backup133_duplicate_archive(array $expectedNames): ZipArchive {
    if (!extension_loaded('zip')) {
        // Ambiente local sem extensão: teste de contrato por stub.
        return new ZipArchive($expectedNames);
    }
    $fixture = __DIR__.'/fixtures/backup133_duplicate_names.zip';
    if (!is_file($fixture)) throw new RuntimeException('Fixture ZIP com duplicatas não encontrado.');
    $zip = new ZipArchive();
    if ($zip->open($fixture) !== true) throw new RuntimeException('Fixture ZIP não pôde ser aberto.');
    $names = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $stat = $zip->statIndex($i);
        $names[] = $stat['name'] ?? null;
    }
    if ($names !== $expectedNames) {
        throw new RuntimeException('Fixture deve conter entradas realmente duplicadas (4 itens).');
    }
    return $zip;
}
function backup133_rejected(callable $run,string $message): void {
    try {$run();throw new LogicException('Falha: '.$message.' foi aceito');}
    catch(RuntimeException) {echo "OK: $message\n";}
}
$entry='assets/images/'.str_repeat('a',32).'.png';
$dbEntry='db/manual-20261030-123000-abcdef.json';
$db='{"format":"teste"}';
$manifest=['format'=>'iguanews-media-bundle','version'=>2,'db'=>$dbEntry,'db_sha256'=>hash('sha256',$db),'files'=>[$entry=>str_repeat('a',64)]];
media_bundle_verify_manifest(backup133_archive(['manifest.json',$dbEntry,$entry]),$manifest,$dbEntry,$db);
backup133(true,'ZIP com entradas exatas e hash do banco íntegro');
$legacy=$manifest;unset($legacy['db_sha256']);
media_bundle_verify_manifest(backup133_archive(['manifest.json',$dbEntry,$entry]),$legacy,$dbEntry,$db);
backup133(true,'ZIP antigo sem hash adicional continua aceito');
backup133_rejected(fn()=>media_bundle_verify_manifest(backup133_archive(['manifest.json',$dbEntry,$entry]),$manifest,$dbEntry,'banco alterado'),'hash do banco alterado recusado');
backup133_rejected(fn()=>media_bundle_verify_manifest(backup133_archive(['manifest.json',$dbEntry,$entry]),array_replace($manifest,['db_sha256'=>'incorreto']),$dbEntry,$db),'hash inválido recusado');
backup133_rejected(fn()=>media_bundle_verify_manifest(backup133_archive(['manifest.json',$dbEntry,$entry,'backup-oculto']),$manifest,$dbEntry,$db),'entrada extra recusada');
backup133_rejected(fn()=>media_bundle_verify_manifest(backup133_archive(['manifest.json',$dbEntry]),$manifest,$dbEntry,$db),'mídia ausente recusada');
backup133_rejected(fn()=>media_bundle_verify_manifest(backup133_duplicate_archive(['manifest.json',$dbEntry,$entry,$entry]),$manifest,$dbEntry,$db),'nome duplicado recusado');

class Backup133Stmt extends PDOStatement {
    private int $idx=0;
    public function __construct(private array $rows) {}
    public function fetch(int $mode=PDO::FETCH_DEFAULT,int $cursorOrientation=PDO::FETCH_ORI_NEXT,int $cursorOffset=0):mixed{return $this->rows[$this->idx++]??false;}
    public function closeCursor():bool{return true;}
    public function execute(?array $params=null):bool{return true;}
}
class Backup133DB extends PDO {
    public bool $started=false;
    public bool $rolledBack=false;
    public bool $committed=false;
    public function __construct(public array $rows=[]) {}
    public function exec(string $statement):int|false{return 0;}
    public function beginTransaction():bool{$this->started=true;return true;}
    public function inTransaction():bool{return $this->started;}
    public function commit():bool{$this->started=false;$this->committed=true;return true;}
    public function rollBack():bool{$this->started=false;$this->rolledBack=true;return true;}
    public function query(string $query,?int $fetchMode=null,mixed ...$fetchModeArgs):PDOStatement|false{
        preg_match('/FROM `([^`]+)`/',$query,$m);
        return new Backup133Stmt($this->rows[$m[1]??'']??[]);
    }
    public function prepare(string $query,array $options=[]):PDOStatement|false{return new Backup133Stmt([]);}
}
$sample=new Backup133DB(['users'=>[['id'=>1,'role'=>'admin','active'=>1]]]);
$name=make_backup($sample);
try{
    $path=backup_path($name);
    backup133(is_string($path)&&is_file($path),'backup pequeno finalizado atomicamente');
    $data=parse_backup((string)file_get_contents($path),$name);
    backup133($data['version']===11&&$sample->committed,'exportação mantém v11 e transação consistente');
}finally{@unlink(IGUA_STORAGE.'/backups/'.$name);}
$large=new Backup133DB(['users'=>[['data'=>str_repeat('x',BACKUP_MAX_JSON_BYTES+4)]]]);
backup133_rejected(fn()=>make_backup($large),'exportação acima de 40 MiB recusada');
backup133($large->rolledBack && !$large->committed,'exportação excessiva desfaz transação');
echo "Backup 1.0.33: simulações e arquivos locais aprovados, MariaDB/ZIP real pendentes.\n";
