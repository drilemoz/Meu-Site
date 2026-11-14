<?php
declare(strict_types=1);
require_once __DIR__.'/setup_inventory.php';
/**
 * Prepara somente pastas internas conhecidas, sem alterar permissões de pastas
 * existentes, seguir symlinks ou usar diretórios externos ao projeto.
 * A criação automática usa 0700; hospedagens precisam dar posse ao usuário PHP.
 */
function igua_prepare_runtime_directories(string $root=IGUA_ROOT, string $public=IGUA_PUBLIC): array {
    $paths=[
        'storage'=>$root.'/storage',
        'backups'=>$root.'/storage/backups',
        'media'=>$root.'/storage/media',
        'logs'=>$root.'/storage/logs',
        'uploads'=>$public.'/uploads',
    ];
    $status=[];
    foreach ($paths as $key=>$path) {
        if (is_link($path) || ($key !== 'storage' && $key !== 'uploads' && is_link($root.'/storage')) || ($key === 'uploads' && is_link($public))) { $status[$key]=false; continue; }
        $parent=dirname($path);
        // Não criar em pastas pai ausentes, ilegíveis ou apontadas por symlink.
        if (!is_dir($path) && !is_link($parent) && is_dir($parent) && is_writable($parent)) {
            @mkdir($path,0700);
        }
        clearstatcache(true,$path);
        $status[$key]=is_dir($path) && !is_link($path) && is_writable($path);
    }
    // Não criar uploads com acesso a execução se faltou o arquivo de proteção.
    $uploads=$paths['uploads'];
    if (($status['uploads']??false) && !is_file($uploads.'/.htaccess')) {
        $guard="Options -Indexes\n<FilesMatch \"\\.(php|php\\d|phtml|phar|cgi|pl|sh|html|htm)$\">\n  Require all denied\n</FilesMatch>\n";
        $written=@file_put_contents($uploads.'/.htaccess',$guard,LOCK_EX);
        $status['uploads']=$written!==false;
    }
    return $status;
}


/** Testa criação e rename atômico em storage/, sem criar config.php nem armazenar senhas. */
function igua_setup_config_write_probe(string $directory=IGUA_STORAGE): bool {
    if (!is_dir($directory) || is_link($directory) || !is_writable($directory)) return false;
    $from=$directory.'/.setup-probe-'.bin2hex(random_bytes(8));
    $to=$from.'.moved';
    try {
        $handle=@fopen($from,'x');
        if ($handle===false) return false;
        $written=fwrite($handle,'ok');
        fclose($handle);
        if ($written!==2) return false;
        return @rename($from,$to) && file_get_contents($to)==='ok';
    } finally {
        if (is_file($from)) @unlink($from);
        if (is_file($to)) @unlink($to);
    }
}

/** Diagnóstico do ambiente que executa o PHP WEB, não do Termux inteiro. */
function igua_requirements(): array {
    $prepared=igua_prepare_runtime_directories();
    $profile=igua_runtime_profile();
    $isTermux=$profile['kind']==='termux';
    $rows = [];
    $add = static function (string $id, string $title, bool $ok, bool $required, string $current, string $fix) use (&$rows): void {
        $rows[] = compact('id', 'title', 'ok', 'required', 'current', 'fix');
    };
    $add('runtime','Ambiente detectado',true,false,$profile['label'].' · '.strtoupper($profile['sapi']),
        'O perfil é detectado pelo PHP executado, não pelo navegador. O tipo de banco deve ser validado na próxima etapa.');
    $webroot=igua_webroot_diagnosis();
    $add('webroot','Raiz pública protegida', $webroot['ok'],true,$webroot['detail'],
        $isTermux
          ? 'No Termux execute na pasta do projeto: php -S 127.0.0.1:8000 -t public public/router.php. Evite -t . e /sdcard.'
          : 'Na hospedagem, aponte o domínio para public/ ou copie somente os arquivos de public/ para public_html/, mantendo app/ e storage/ como irmãos privados. Não coloque o ZIP inteiro em public_html.');
    $publicFiles=igua_public_files_diagnosis(IGUA_PUBLIC);
    $add('public_files','Arquivos públicos completos',$publicFiles['ok'],true,$publicFiles['detail'],
        'Republique integralmente a pasta public/, incluindo assets/. Em hospedagem public_html/ deve conter os arquivos públicos, mas nunca storage/ e app/.');
    if (PHP_SAPI==='cli-server') {
        $serverAddress=(string)($_SERVER['SERVER_ADDR']??'');
        $localOnly=in_array($serverAddress,['127.0.0.1','::1'],true);
        $add('local_listener','Servidor PHP local restrito ao aparelho',$localOnly,true,
            $localOnly?'Conexões recebidas exclusivamente em loopback.':'Servidor PHP embutido não identificado como exclusivamente local.',
            'Não use -S 0.0.0.0 nem exponha php -S à internet. No Termux execute sh bin/start-termux.sh; na hospedagem use Apache/Nginx com PHP-FPM.');
    }
    if (!$isTermux && $profile['kind']==='hosting') {
        $add('https','HTTPS para hospedagem pública',$profile['https'],false,
            $profile['https']?'Conexão HTTPS detectada':'Acesso atual sem HTTPS confirmado',
            'Ative o certificado TLS no painel de hospedagem e force HTTPS antes de publicar ou usar sessões persistentes. Não confie em cabeçalhos X-Forwarded-* sem configurar um proxy confiável.');
    }
    $add('php', 'PHP 8.1 ou superior', PHP_VERSION_ID >= 80100, true, 'Detectado: '.PHP_VERSION,
        'Atualize o pacote PHP no Termux com: pkg update && pkg upgrade. Na hospedagem, selecione PHP 8.1 ou superior.');
    foreach ([
        ['pdo', 'PDO', 'Instale ou habilite o PDO na instalação de PHP. No Termux, confira: php -m'],
        ['pdo_mysql', 'Driver PDO MySQL', 'O PHP precisa da extensão pdo_mysql. No Termux: php -m | grep -i pdo_mysql. Na hospedagem, habilite a extensão PDO MySQL.'],
        ['fileinfo', 'Extensão Fileinfo', 'Ative fileinfo no PHP: ela valida os tipos das fotos enviadas.'],
        ['json', 'Extensão JSON', 'Ative json no PHP, necessária para o backup e a restauração.'],
    ] as [$id, $title, $fix]) {
        $add($id, $title, extension_loaded($id), true,
            extension_loaded($id) ? 'Disponível' : 'Não disponível no PHP que abriu esta página', $fix);
    }
    $active = session_status() === PHP_SESSION_ACTIVE;
    $add('session', 'Sessões PHP ativas', $active, true, $active ? 'Disponíveis' : 'Indisponíveis',
        'Habilite sessions e cookies no navegador. Confira o session.save_path e as permissões no PHP.');
    foreach ([
        ['storage', 'Pasta storage/', IGUA_STORAGE, 'Credenciais e arquivos internos.'],
        ['backups', 'Pasta storage/backups/', IGUA_STORAGE.'/backups', 'Arquivos de backup.'],
        ['uploads', 'Pasta de uploads pública/', IGUA_PUBLIC.'/uploads', 'Imagens enviadas pelo painel.'],
    ] as [$id, $title, $path, $purpose]) {
        $ok = (bool)($prepared[$id]??false);
        $add($id, $title.' gravável', $ok, true, $ok ? 'Gravação permitida' : (is_dir($path) ? 'Sem permissão de escrita' : 'Pasta inexistente'),
            'A pasta é necessária para '.$purpose.' O instalador tenta criá-la sem modificar pastas existentes. '.($isTermux ? 'No Termux, use a pasta privada $HOME e execute mkdir -p storage/backups storage/media storage/logs public/uploads; chmod 700 storage/backups storage/media storage/logs. Evite /sdcard.' : 'Na hospedagem, ajuste a propriedade e permissões para o usuário do PHP; não use chmod 777. Mantenha storage/ fora da pasta pública.'));
    }
    $configProbe=($prepared['storage']??false) && igua_setup_config_write_probe();
    $add('config_write', 'Configuração privada pode ser ativada', $configProbe, true,
        $configProbe ? 'Criação e renomeação de arquivo em storage/ aprovadas' : 'Falha ao criar ou renomear arquivo em storage/',
        'O PHP precisa criar e renomear arquivos na pasta storage/, inclusive config.php. Verifique proprietário e open_basedir no painel da hospedagem. Não use chmod 777.');
    $gzip = extension_loaded('zlib') && function_exists('gzencode') && function_exists('gzdecode');
    $add('zlib', 'Compactação GZIP (opcional)', $gzip, false,
        $gzip ? 'Backup JSON.GZ disponível' : 'Backup JSON sem compactação',
        'Habilite zlib para gerar arquivos de backup menores; não impede a instalação.');
    require_once __DIR__.'/setup_chunked_backup.php';
    $upload=igua_upload_limits_diagnosis((string)ini_get('upload_max_filesize'),(string)ini_get('post_max_size'));
    $chunked=igua_setup_chunk_enabled() && (bool)($prepared['storage']??false);
    $uploadDetail=$upload['detail'].(!$upload['ok'] && $chunked
        ? ' O instalador pode enviar automaticamente em blocos de '.(int)(igua_setup_chunk_size((string)ini_get('upload_max_filesize'),(string)ini_get('post_max_size'))/1024).' KiB; não é necessário alterar o limite do PHP.'
        : '');
    $add('migration_upload', 'Importação de backups até 15 MiB', $upload['ok'] || $chunked, false,
        $uploadDetail,
        $chunked ? 'O navegador divide o backup em partes, que são reunidas na pasta privada antes da validação. JavaScript e uploads pequenos precisam estar habilitados; o PHP não altera limites impostos pelo provedor.' :
            'Para importar 15 MiB sem envio em partes, ajuste upload_max_filesize para 20M e post_max_size para 24M no PHP HTTP, ou use sh bin/start-termux.sh no Termux.');
    $utf = function_exists('mb_strlen') || function_exists('iconv_strlen');
    $add('unicode', 'Funções de texto UTF-8 (opcional)', $utf, false,
        $utf ? 'Disponíveis' : 'Ausentes',
        'Habilite mbstring ou iconv para maior precisão em textos com acentos.');
    return $rows;
}
function igua_required_ready(array $rows): bool {
    foreach ($rows as $item) if ($item['required'] && !$item['ok']) return false;
    return true;
}
/** Limite lógico do arquivo de migração; não é o limite de configuração do servidor. */
const IGUA_BACKUP_IMPORT_MAX_BYTES = 15 * 1048576;
/** Limites do PHP distinguem o arquivo do overhead multipart do POST. */
function igua_php_size(string $value): int {
    $value=trim($value);
    if ($value==='-1') return PHP_INT_MAX;
    if (!preg_match('/\A(\d+(?:\.\d+)?)([KMG]?)\z/iD',$value,$m)) return 0;
    $factor=[''=>1,'K'=>1024,'M'=>1048576,'G'=>1073741824][strtoupper($m[2])];
    return (int)min(PHP_INT_MAX, (float)$m[1]*$factor);
}
/** Tamanho conservador do payload (1 MiB reservado para campos e envelope multipart). */
function igua_backup_upload_capacity(string $uploadIni, string $postIni, int $max=IGUA_BACKUP_IMPORT_MAX_BYTES): int {
    $upload=igua_php_size($uploadIni);
    $post=igua_php_size($postIni);
    if ($upload<=0 || $post<=0) return 0;
    $postCapacity=$post===PHP_INT_MAX ? PHP_INT_MAX : max(0,$post-1048576);
    return min($max,$upload,$postCapacity);
}
function igua_upload_limits_diagnosis(string $uploadIni, string $postIni, int $required=IGUA_BACKUP_IMPORT_MAX_BYTES): array {
    $capacity=igua_backup_upload_capacity($uploadIni,$postIni,$required);
    $ok=$capacity >= $required;
    $limited=$capacity>0 ? number_format($capacity/1048576,2,',','.') . ' MiB' : 'indisponível';
    return ['ok'=>$ok, 'max_bytes'=>$capacity, 'detail'=>'Por arquivo: '.$uploadIni.'; corpo POST: '.$postIni.'. '.
        ($ok?'Aceita backups de até 15 MiB, com margem para formulário.':'Limite conservador estimado por envio: '.$limited.'. Um backup de 15 MiB pode ser recusado antes de chegar ao instalador.')];
}
/** Permite avisar corretamente sobre POST descartado pelo PHP, sem executar a ação. */
function igua_post_exceeds_limit(?array $server=null, ?string $postIni=null): bool {
    $server ??= $_SERVER;
    $postIni ??= (string)ini_get('post_max_size');
    $limit=igua_php_size($postIni);
    $length=$server['CONTENT_LENGTH']??null;
    return $limit>0 && $limit<PHP_INT_MAX && is_scalar($length)
        && ctype_digit((string)$length) && (float)$length>$limit;
}

/** Mensagens específicas sem mostrar consultas, senhas nem stack traces. */
function igua_db_diagnosis(Throwable $error, string $host, int $port, string $database): array {
    $code = $error instanceof PDOException ? (int)($error->errorInfo[1] ?? 0) : 0;
    if ($code === 0 && preg_match('/\[(?:HY000|[0-9A-Z]{5})\] \[(\d+)\]/', $error->getMessage(), $m)) $code=(int)$m[1];
    if (!$code && preg_match('/\b(1045|1044|1049|2002|2003|2005)\b/', $error->getMessage(), $m)) $code=(int)$m[1];
    if (in_array($code, [2002,2003], true) || stripos($error->getMessage(), 'Connection refused') !== false) {
        return ['Servidor do banco não está respondendo',
            'O PHP não conseguiu conectar ao servidor em '.$host.':'.$port.'. Verifique se o MariaDB/MySQL está iniciado e aceitando TCP nessa porta.',
            igua_runtime_profile()['kind']==='termux' ? 'No Termux, em outra sessão: mariadbd-safe --bind-address=127.0.0.1 --port='.$port.' &' : 'Na hospedagem, confirme no painel se o banco está ativo e se o host/porta são os fornecidos pelo provedor.',
            igua_runtime_profile()['kind']==='termux' ? 'Depois confirme: mariadb-admin --protocol=tcp -h 127.0.0.1 -P '.$port.' ping' : 'Em bancos remotos, solicite liberação de conexão ao provedor se necessário.'];
    }
    if ($code === 1045) return ['Usuário ou senha rejeitados',
        'O servidor respondeu, mas recusou as credenciais MySQL. Confira usuário e senha. O login do MariaDB não é o mesmo login do administrador do portal.',
        igua_runtime_profile()['kind']==='termux' ? 'Teste no Termux: mariadb --protocol=tcp -h 127.0.0.1 -P '.$port.' -u SEU_USUARIO -p' : 'Na hospedagem, confira as credenciais e os privilégios do usuário no painel MySQL.'];
    if ($code === 1044) return ['Usuário sem acesso ao banco',
        'O usuário autenticou, mas não tem permissão para acessar a base informada. Configure GRANT no MariaDB/MySQL.'];
    if ($code === 1049) return ['Banco de dados inexistente',
        'O servidor está funcionando, mas o banco "'.$database.'" ainda não existe. Crie-o antes de instalar o portal.',
        igua_runtime_profile()['kind']==='termux' ? 'No console MariaDB (com usuário autorizado): CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;' : 'Crie um banco vazio e associe o usuário pelo painel da hospedagem.'];
    if ($code === 2005) return ['Servidor não encontrado',
        'Não foi possível resolver o endereço do servidor. Verifique se o host informado está correto. No Termux, normalmente é 127.0.0.1.'];
    if (!($error instanceof PDOException) && str_contains($error->getMessage(), 'Configuração do banco inválida'))
        return ['Configuração do banco inválida', 'Use um host, uma porta válida e um nome de banco sem espaços ou caracteres especiais.'];
    return ['Conexão não validada', 'A conexão não foi concluída. Confira servidor, porta, permissões e credenciais no MySQL/MariaDB. Consulte os registros do servidor se continuar.'];
}
/** Compatibilidade da API antiga: somente bancos sem dados podem receber nova instalação. */
function igua_database_empty(PDO $db): array {
    $result=igua_setup_database_inventory($db);
    return [in_array($result['status'],['empty','partial_empty'],true),$result['detail']];
}
