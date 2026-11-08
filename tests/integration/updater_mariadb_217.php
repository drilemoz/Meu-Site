<?php
declare(strict_types=1);
/**
 * Ensaio real de ZIP v2 + backup MariaDB + instalacao + rollback, EXCLUSIVAMENTE
 * num projeto ficticio em diretório efêmero e banco de integração previamente criado.
 * Execute APÓS restore_mariadb_134.php no workflow, nunca na hospedagem publicada.
 * O teste nao executa PHP do pacote nem restaura dados no banco.
 */
function fail217(string $message): never {fwrite(STDERR,"Integração 217 bloqueada: $message\n");exit(2);}
function check217(bool $ok,string $message): void {
    if(!$ok)throw new RuntimeException('FALHA integração 217: '.$message);
    echo "OK MariaDB + ZIP: $message\n";
}
if(PHP_SAPI!=='cli'||(string)getenv('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB')
    fail217('exige opt-in explícito YES_ISOLATED_DB em CLI.');
$name=(string)getenv('IGUA_TEST_DB_NAME');
$host=(string)getenv('IGUA_TEST_DB_HOST');
$port=(int)getenv('IGUA_TEST_DB_PORT');
$user=(string)getenv('IGUA_TEST_DB_USER');
$pass=(string)(getenv('IGUA_TEST_DB_PASSWORD')?:'');
if(!preg_match('/^igua_ci_[a-z0-9_]{3,40}$/D',$name) ||
   !in_array($host,['127.0.0.1','localhost','::1'],true) ||
   $port<1||$port>65535||$user==='')
    fail217('banco igua_ci_* e conexão exclusivamente local obrigatórios.');
if(!extension_loaded('pdo_mysql')||!class_exists('ZipArchive'))
    fail217('requer as extensões reais pdo_mysql e ZipArchive.');

// Definir IGUA_ROOT em sandbox ANTES de carregar os módulos, sem bootstrap.php.
$root=sys_get_temp_dir().'/igua-ci-updater217-'.bin2hex(random_bytes(12));
if(!mkdir($root,0700,true))fail217('não foi possível criar sandbox.');
define('IGUA_ROOT',$root);
define('IGUA_STORAGE',$root.'/storage');
define('IGUA_PUBLIC',$root.'/public');
require_once __DIR__.'/../../app/schema_migrations.php';
require_once __DIR__.'/../../app/backup.php';
require_once __DIR__.'/../../app/updater.php';

function cleanup217(string $path): void {
    if(!is_dir($path)||is_link($path))return;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
    foreach($it as $file){
        if($file->isDir()&&!$file->isLink())@rmdir($file->getPathname());
        else @unlink($file->getPathname());
    }
    @rmdir($path);
}
function fixture217(string $file,array $new): void {
    $files=[];
    foreach($new as $path=>$content){
        $files[]=['path'=>$path,'sha256'=>hash('sha256',$content),'size'=>strlen($content)];
    }
    $manifest=['format'=>IGUA_UPDATE_FORMAT,'version'=>'1.0.117','min_php'=>'8.1.0',
        'schema_revision'=>IGUA_SCHEMA_REVISION,'files'=>$files];
    $zip=new ZipArchive();
    if($zip->open($file,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)
        throw new RuntimeException('Não foi possível criar ZIP de teste.');
    try{
        if(!$zip->addFromString('release.json',json_encode($manifest,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)))
            throw new RuntimeException('Manifesto de teste não criado.');
        foreach($new as $path=>$content){
            if(!$zip->addFromString($path,$content))throw new RuntimeException('Falha na entrada ZIP: '.$path);
        }
    }finally{$zip->close();}
}
try {
    foreach(['app','public','storage','storage/backups'] as $path){
        if(!mkdir($root.'/'.$path,0700,true))throw new RuntimeException('Falha no diretório do sandbox.');
    }
    $dsn='mysql:host='.$host.';port='.$port.';dbname='.$name.';charset=utf8mb4';
    $db=new PDO($dsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>6]);
    check217(igua_schema_revision($db)===IGUA_SCHEMA_REVISION,'banco CI já instalado, schema 37');
    $admin=(int)$db->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1")->fetchColumn();
    check217($admin>=1,'banco CI já preparado com administrador ativo');
    $articlesBefore=(int)$db->query('SELECT COUNT(*) FROM articles')->fetchColumn();
    $logsBefore=(int)$db->query('SELECT COUNT(*) FROM backup_logs')->fetchColumn();
    $old=['VERSION'=>"1.0.116\n",'app/updater.php'=>"<?php // sandbox updater antigo\n",
        'app/module.php'=>"<?php // modulo anterior\n",'public/admin.php'=>"<?php // admin anterior\n"];
    $new=['VERSION'=>"1.0.117\n",'app/updater.php'=>"<?php // sandbox updater novo\n",
        'app/module.php'=>"<?php // modulo novo\n",'public/admin.php'=>"<?php // admin novo\n",
        'public/new.js'=>"// novo no sandbox\n"];
    foreach($old as $path=>$body){
        if(file_put_contents($root.'/'.$path,$body)!==strlen($body))throw new RuntimeException('Falha na fixture original.');
    }
    $zipPath=$root.'/update-test.zip';fixture217($zipPath,$new);
    $stageTrace=[];
    $result=igua_updater_install_zip($zipPath,$db,static function(string $stage,string $message)use(&$stageTrace):void{$stageTrace[]=$stage;});
    check217(($result['version']??'')==='1.0.117','instalação pelo ZIP real concluída');
    check217($stageTrace===['validating','preflight','backup','applying','complete'],'etapas do instalador registradas na ordem');
    foreach($new as $path=>$body)check217(file_get_contents($root.'/'.$path)===$body,'conteúdo instalado: '.$path);
    check217(!is_file(igua_updater_maintenance()),'manutenção retirada após instalação');
    $last=igua_updater_last_job();
    check217(($last['journal']['status']??null)==='complete','journal registra instalação completa');
    $backup=backup_path((string)($result['backup']??''));
    check217(is_string($backup)&&is_file($backup),'backup lógico real registrado na pasta efêmera');
    check217((int)$db->query('SELECT COUNT(*) FROM backup_logs')->fetchColumn()===$logsBefore+1,
        'atualização registra um backup SQL e não executa migração');
    check217((int)$db->query('SELECT COUNT(*) FROM articles')->fetchColumn()===$articlesBefore,
        'instalação não modifica notícias no banco');
    $raw=file_get_contents($backup);
    $parsed=parse_backup((string)$raw,basename($backup));
    check217(count($parsed['tables'])===count(BACKUP_TABLES),'backup da instalação contém todas as tabelas v12');
    $integrity=igua_updater_installed_integrity($last['journal']);
    check217(($integrity['status']??'')==='ok'&&($integrity['verified']??0)===count($new),'integridade de todos os arquivos instalados');
    igua_updater_manual_rollback();
    foreach($old as $path=>$body)check217(file_get_contents($root.'/'.$path)===$body,'rollback restaurou: '.$path);
    check217(!file_exists($root.'/public/new.js'),'rollback eliminou apenas o novo arquivo');
    check217(!is_file(igua_updater_maintenance()) &&
        (igua_updater_last_job()['journal']['status']??'')==='rolled_back','rollback encerra manutenção e journal');
    check217(is_file($backup),'backup de banco continua preservado após rollback do código');

    // Pacote adulterado: hashes do manifesto devem impedir aplicação dos arquivos.
    $badZip=$root.'/update-tampered.zip';
    if(!copy($zipPath,$badZip))throw new RuntimeException('Falha ao duplicar ZIP de testes.');
    $zip=new ZipArchive();
    if($zip->open($badZip)!==true)throw new RuntimeException('ZIP copiado não abre.');
    try{if(!$zip->addFromString('app/module.php',"<?php // adulterado\n"))throw new RuntimeException('Falha ao adulterar ZIP.');}
    finally{$zip->close();}
    $rejected=false;
    try{igua_updater_install_zip($badZip,$db);}
    catch(RuntimeException $e){$rejected=str_contains($e->getMessage(),'SHA-256');}
    check217($rejected,'ZIP adulterado recusado pelo instalador via SHA-256 real');
    check217((int)$db->query('SELECT COUNT(*) FROM backup_logs')->fetchColumn()===$logsBefore+1,
        'ZIP adulterado falha antes de registrar outro backup');
    check217(!is_file(igua_updater_maintenance()),'ZIP adulterado não deixa manutenção ativa');
    foreach($old as $path=>$body)check217(file_get_contents($root.'/'.$path)===$body,'adulteração não tocou no código: '.$path);
    echo "INTEGRAÇÃO 217: ciclo completo ZIP + MariaDB + rollback aprovado em sandbox.\n";
} finally {cleanup217($root);}
