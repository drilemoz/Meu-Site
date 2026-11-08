<?php
declare(strict_types=1);
/** Retrato pontual e somente leitura do PHP que atendeu à requisição. Sem rede, comandos shell ou credenciais. */
function igua_server_info_ini_bytes(string $value): ?int {
    $value=trim($value);
    if($value==='' || $value==='-1') return null;
    if(!preg_match('/^(\d+(?:\.\d+)?)\s*([KMG]?)B?$/iD',$value,$m))return null;
    $num=(float)$m[1];$power=[''=>0,'K'=>1,'M'=>2,'G'=>3][strtoupper($m[2])];
    $bytes=$num*(1024**$power);
    return is_finite($bytes) && $bytes<=PHP_INT_MAX ? (int)$bytes : null;
}
function igua_server_info_size(?int $bytes): string {
    if($bytes===null || $bytes<0)return 'Indisponível';
    if($bytes<1024)return $bytes.' B';
    foreach(['KiB','MiB','GiB','TiB'] as $unit){$bytesFloat=$bytes/1024; if($bytesFloat<1024 || $unit==='TiB')return number_format($bytesFloat,1,',','.').' '.$unit;$bytes=(int)$bytesFloat;}
    return 'Indisponível';
}
function igua_server_info_platform(string $software): string {
    foreach(['LiteSpeed'=>'LiteSpeed','nginx'=>'Nginx','Apache'=>'Apache','Microsoft-IIS'=>'IIS','Caddy'=>'Caddy'] as $needle=>$name){if(stripos($software,$needle)!==false)return $name;}
    return 'Não identificado';
}
function igua_server_info_snapshot(?PDO $db=null, ?string $diskRoot=null): array {
    $software=(string)($_SERVER['SERVER_SOFTWARE']??'');
    $diskRoot=$diskRoot??(defined('IGUA_ROOT')?IGUA_ROOT:__DIR__);
    $free=@disk_free_space($diskRoot);$total=@disk_total_space($diskRoot);
    $disk=($free!==false && $total!==false && $total>0 && $free>=0 && $free<=$total)
        ? ['free'=>(int)$free,'total'=>(int)$total,'used'=>(int)($total-$free),'percent'=>min(100,max(0,(int)round(100*($total-$free)/$total)))] : null;
    $dbResult=['status'=>'Indisponível','version'=>null];
    if($db instanceof PDO){
        try{
            $version=(string)$db->query('SELECT VERSION()')->fetchColumn();
            $version=preg_replace('/[^0-9A-Za-z._-]/','',substr($version,0,50));
            $dbResult=['status'=>'Conectado','version'=>$version!==''?$version:null];
        }catch(Throwable $e){$dbResult=['status'=>'Consulta indisponível','version'=>null];}
    }
    $extensions=[];
    foreach(['pdo_mysql'=>'MySQL (PDO)','zip'=>'ZIP','curl'=>'cURL','gd'=>'GD / imagens','fileinfo'=>'Fileinfo','mbstring'=>'Multibyte','openssl'=>'OpenSSL','intl'=>'Intl','opcache'=>'OPcache'] as $extension=>$label){
        $extensions[]=['label'=>$label,'enabled'=>extension_loaded($extension)];
    }
    $upload=(string)ini_get('upload_max_filesize');$post=(string)ini_get('post_max_size');
    $memory=(string)ini_get('memory_limit');$seconds=(string)ini_get('max_execution_time');
    $uploadBytes=igua_server_info_ini_bytes($upload);$postBytes=igua_server_info_ini_bytes($post);
    $maxUpload=($uploadBytes!==null && $postBytes!==null)?min($uploadBytes,$postBytes):null;
    return [
        'collected_at'=>date('d/m/Y H:i:s'),
        'php_version'=>PHP_VERSION,
        'php_sapi'=>PHP_SAPI,
        'server_family'=>igua_server_info_platform($software),
        'https'=>!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS'])!=='off',
        'timezone'=>date_default_timezone_get(),
        'disk'=>$disk,
        'memory_limit'=>$memory===''?'Indisponível':$memory,
        'request_peak'=>memory_get_peak_usage(true),
        'upload_limit'=>$upload===''?'Indisponível':$upload,
        'post_limit'=>$post===''?'Indisponível':$post,
        'max_upload_bytes'=>$maxUpload,
        'execution_limit'=>$seconds===''?'Indisponível':($seconds==='0'?'Sem limite definido no PHP':$seconds.' s'),
        'max_input_vars'=>(string)ini_get('max_input_vars'),
        'opcache'=>function_exists('opcache_get_status') && is_array(@opcache_get_status(false)),
        'db'=>$dbResult,
        'extensions'=>$extensions,
        'write_storage'=>defined('IGUA_STORAGE') && is_dir(IGUA_STORAGE) && is_writable(IGUA_STORAGE),
        'write_uploads'=>defined('IGUA_PUBLIC') && is_dir(IGUA_PUBLIC.'/uploads') && is_writable(IGUA_PUBLIC.'/uploads'),
    ];
}
