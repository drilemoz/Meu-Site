<?php
declare(strict_types=1);
/** Limites de recuperação e segurança de caminhos, sem tocar em banco de produção. */
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/backup.php';
require_once __DIR__.'/../app/updater.php';
require_once __DIR__.'/../app/schema_migrations.php';
function recovery214_assert(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('Falha: '.$message);
    echo 'OK: '.$message."\n";
}
function recovery214_reject(callable $run,string $message):void {
    try{$run();}catch(Throwable $e){recovery214_assert(true,$message);return;}
    throw new RuntimeException('Falha: entrada inesperadamente aceita: '.$message);
}
$tables=array_fill_keys(BACKUP_TABLES,[]);
$tables['users']=[['id'=>1,'role'=>'admin','active'=>1]];
$backup=['format'=>'iguanews-backup','version'=>12,'tables'=>$tables];
$encode=static fn(array $data):string=>json_encode($data,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
recovery214_assert(parse_backup($encode($backup),'teste.json')['tables']['users'][0]['role']==='admin','backup v12 de administrador válido');
if(function_exists('gzencode')){
    recovery214_assert(parse_backup(gzencode($encode($backup)),'teste.json.gz')['version']===12,'backup GZIP válido');
    recovery214_reject(static fn()=>@parse_backup('GZIP inválido','teste.json.gz'),'GZIP corrompido recusado');
}
$mutated=$backup;unset($mutated['tables']['settings']);
recovery214_reject(static fn()=>parse_backup($encode($mutated),'teste.json'),'tabela obrigatória ausente');
$mutated=$backup;$mutated['tables']['inventada']=[];
recovery214_reject(static fn()=>parse_backup($encode($mutated),'teste.json'),'tabela desconhecida recusada');
$mutated=$backup;$mutated['tables']['users'][0]['active']=0;
recovery214_reject(static fn()=>parse_backup($encode($mutated),'teste.json'),'recusa backup sem administrador ativo');
$mutated=$backup;$mutated['tables']['users']=[['id'=>1,'role'=>'admin','active'=>1], 'linha_invalida'];
recovery214_reject(static fn()=>parse_backup($encode($mutated),'teste.json'),'recusa linha que não seja objeto');
$mutated=$backup;$mutated['tables']['users']=[['id'=>1,'role'=>'admin','active'=>1],['id'=>2,'role'=>'editor','active'=>1]];
recovery214_assert(count(parse_backup($encode($mutated),'teste.json')['tables']['users'])===2,'dados válidos preservados');

$folder=IGUA_STORAGE.'/backups';
if(!is_dir($folder) && !mkdir($folder,0700,true) && !is_dir($folder))throw new RuntimeException('Sem área privada de teste.');
$nonce=bin2hex(random_bytes(3));
$name='manual-'.date('Ymd-His').'-'.$nonce.'.json';
$path=$folder.'/'.$name;
$outside=tempnam(sys_get_temp_dir(),'igua214-out-');
if($outside===false)throw new RuntimeException('Falha ao preparar arquivo externo para teste.');
try {
    file_put_contents($outside,'arquivo externo protegido');
    recovery214_assert(backup_path('../'.$name)===null,'impede navegação por caminho no download');
    if(function_exists('symlink') && @symlink($outside,$path)) {
        recovery214_assert(backup_path($name)===null,'download de backup não segue link simbólico');
        recovery214_assert(!in_array($name,array_column(list_backups(),'name'),true),'lista de backups omite link simbólico');
        recovery214_assert(file_get_contents($outside)==='arquivo externo protegido','arquivo externo permanece intocado');
        @unlink($path);
    } else {
        echo "INFO: symlink indisponível; teste de link ignorado.\n";
    }
    if(file_put_contents($path,$encode($backup))===false)throw new RuntimeException('Falha ao preparar backup local.');
    recovery214_assert(backup_path($name)===$path,'arquivo real de backup continua disponível');
    recovery214_assert(in_array($name,array_column(list_backups(),'name'),true),'listagem mantém backup legítimo');
} finally {
    if(is_link($path)||is_file($path))@unlink($path);
    @unlink($outside);
}
$files=[
 ['path'=>'VERSION','size'=>8,'sha256'=>str_repeat('a',64)],
 ['path'=>'app/updater.php','size'=>12,'sha256'=>str_repeat('b',64)],
 ['path'=>'public/admin.php','size'=>12,'sha256'=>str_repeat('c',64)],
];
$manifest=['format'=>IGUA_UPDATE_FORMAT,'version'=>'1.0.999','schema_revision'=>IGUA_SCHEMA_REVISION,'min_php'=>'8.1.0','files'=>$files];
recovery214_assert(igua_updater_check_manifest($encode($manifest))['signature_status']==='unsigned','manifesto v2 íntegro é reconhecido como sem assinatura de origem');
$bad=$manifest;$bad['files'][]=$files[1];
recovery214_reject(static fn()=>igua_updater_check_manifest($encode($bad)),'manifesto de atualização com caminho duplicado');
$bad=$manifest;$bad['files'][1]['path']='public/uploads/2026/11/segredo.jpg';
recovery214_reject(static fn()=>igua_updater_check_manifest($encode($bad)),'atualização não escreve uploads');
$bad=$manifest;$bad['files'][1]['path']='public/ads.txt';
recovery214_reject(static fn()=>igua_updater_check_manifest($encode($bad)),'atualização preserva ads.txt');
$bad=$manifest;$bad['files'][1]['size']=12*1024*1024+1;
recovery214_reject(static fn()=>igua_updater_check_manifest($encode($bad)),'limite por arquivo da atualização');
$bad=$manifest;$bad['min_php']='99.0.0';
recovery214_reject(static fn()=>igua_updater_check_manifest($encode($bad)),'requisitos PHP incompatíveis recusados');
$bad=$manifest;$bad['format']=IGUA_UPDATE_LEGACY_FORMAT;
recovery214_reject(static fn()=>igua_updater_check_manifest($encode($bad)),'pacote legado não aceita ausência de assinatura');
recovery214_assert(!igua_updater_safe_path('app/../../storage/config.php'),'atualizador recusa traversal');
recovery214_assert(!igua_updater_safe_path('public/uploads/arquivo.jpg'),'atualizador recusa upload persistente');
echo "Regressão de recuperação 1.0.114: limites e caminhos protegidos, sem escrita em MariaDB.\n";
