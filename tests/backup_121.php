<?php
declare(strict_types=1);
/** Regressões sem MySQL para o pacote de mídias da v1.0.21. */
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/media_library.php';
require_once __DIR__.'/../app/backup.php';
function backup121_assert(bool $condition,string $case): void {
    if(!$condition)throw new RuntimeException('Falha: '.$case);
    echo "OK: $case\n";
}
backup121_assert(media_bundle_size_allowed(150*1024*1024),'ZIP antigo de 150 MiB aceito');
backup121_assert(media_bundle_size_allowed(340*1024*1024),'ZIP até 340 MiB aceito após correção');
backup121_assert(media_bundle_size_allowed(MEDIA_BUNDLE_MAX_ARCHIVE_BYTES),'limite máximo aceito');
backup121_assert(!media_bundle_size_allowed(MEDIA_BUNDLE_MAX_ARCHIVE_BYTES+1),'ZIP excessivo bloqueado');
backup121_assert(!media_bundle_size_allowed(0),'ZIP vazio bloqueado');
backup121_assert(MEDIA_BUNDLE_MAX_ARCHIVE_BYTES>MEDIA_BUNDLE_MAX_MEDIA_BYTES+45*1024*1024,'espaço para banco e manifesto');
backup121_assert(MEDIA_BUNDLE_MAX_FILES+2<=3005,'quantidade de entradas compatível');

// Quando ext-zip não estiver instalada, testar o mesmo stream por um objeto de teste.
if(!class_exists('ZipArchive')) {
    class ZipArchive {
        public function __construct(private array $entries) {}
        public function getStream(string $name) {
            if(!isset($this->entries[$name]))return false;
            $out=fopen('php://temp','w+b');fwrite($out,$this->entries[$name]);rewind($out);
            return $out;
        }
    }
}
$zipFile=null;
$input=str_repeat('Teste IguaNews 1.0.21: fotografia comprimida. ',64000);
$entry='assets/images/'.str_repeat('a',32).'.jpg';
if(extension_loaded('zip')) {
    $zipFile=tempnam(sys_get_temp_dir(),'igz-');
    $w=new ZipArchive();
    if($w->open($zipFile,ZipArchive::OVERWRITE)!==true)throw new RuntimeException('Não foi possível preparar ZIP de testes.');
    $w->addFromString($entry,$input);$w->close();
    $zip=new ZipArchive();$zip->open($zipFile);
} else $zip=new ZipArchive([$entry=>$input]);
$dir=sys_get_temp_dir().'/igua-backup121-'.bin2hex(random_bytes(4));
if(!mkdir($dir,0700,true))throw new RuntimeException('Sem diretório de teste.');
$target=$dir.'/'.str_repeat('a',32).'.jpg';
try {
    $sha=hash('sha256',$input);
    $temp=media_bundle_stage_entry($zip,$entry,$sha,$target,strlen($input));
    backup121_assert(is_string($temp)&&is_file($temp),'arquivo novo preparado em disco');
    backup121_assert(hash_file('sha256',$temp)===$sha,'stream SHA-256 íntegro');
    backup121_assert(!is_file($target),'arquivo definitivo não criado antes de validar');
    rename($temp,$target);
    backup121_assert(media_bundle_stage_entry($zip,$entry,$sha,$target,strlen($input))===null,'arquivo já existente igual é preservado');
    $failed=false;
    try{media_bundle_stage_entry($zip,$entry,str_repeat('0',64),$target,strlen($input));}
    catch(RuntimeException){$failed=true;}
    backup121_assert($failed,'arquivo existente divergente é rejeitado');
    unlink($target);
    $failed=false;
    try{media_bundle_stage_entry($zip,$entry,str_repeat('0',64),$target,strlen($input));}
    catch(RuntimeException){$failed=true;}
    backup121_assert($failed && glob($dir.'/.iguanews-restore-*')===[], 'hash incorreto não deixa mídia parcial');
    $failed=false;
    try{media_bundle_stage_entry($zip,$entry,$sha,$target,strlen($input)-1);}
    catch(RuntimeException){$failed=true;}
    backup121_assert($failed && glob($dir.'/.iguanews-restore-*')===[], 'tamanho falso é rejeitado com limpeza');
    mkdir($dir.'/safe');
    if(function_exists('symlink') && @symlink(sys_get_temp_dir(),$dir.'/safe/2026')){
        $failed=false;
        try{media_bundle_stage_entry($zip,$entry,$sha,$dir.'/safe/2026/'.str_repeat('a',32).'.jpg',strlen($input),$dir.'/safe');}
        catch(RuntimeException){$failed=true;}
        backup121_assert($failed,'recusa subpasta de destino por symlink externo');
        unlink($dir.'/safe/2026');
    }
    rmdir($dir.'/safe');
} finally {
    if(is_file($target))unlink($target);
    foreach(glob($dir.'/*')?:[] as $file)@unlink($file);
    foreach(glob($dir.'/.iguanews-restore-*')?:[] as $file)@unlink($file);
    rmdir($dir);
    if($zipFile!==null){$zip->close();unlink($zipFile);}
}
$testFile=IGUA_PUBLIC.'/uploads/'.str_repeat('d',32).'.png';
try{
    file_put_contents($testFile,'conteudo de teste');
    backup121_assert(media_bundle_safe_source('assets/images/'.basename($testFile),$testFile)===realpath($testFile),'exportação aceita arquivo interno');
    $blocked=false;
    try{media_bundle_safe_source('assets/images/'.basename($testFile),IGUA_ROOT.'/README.md');}
    catch(RuntimeException){$blocked=true;}
    backup121_assert($blocked,'exportação recusa arquivo fora de uploads');
}finally{@unlink($testFile);}
echo "Backup v1.0.21: testes de limite, stream e caminhos aprovados; MySQL e restauração real pendentes.\n";
