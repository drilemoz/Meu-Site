<?php
declare(strict_types=1);
require_once __DIR__.'/setup_inventory.php';
require_once __DIR__.'/db_transport.php';
/** Provisionamento controlado para instalações novas. NUNCA altera banco ou usuário preexistente. */
function igua_sql_identifier(string $name): string {
    if (!preg_match('/\A[a-z][a-z0-9_]{0,63}\z/iD', $name)) {
        throw new InvalidArgumentException('Use um nome de banco com até 64 letras, números ou sublinhados, começando por letra.');
    }
    return '`'.$name.'`';
}
function igua_local_browser(): bool {
    return in_array((string)($_SERVER['REMOTE_ADDR']??''),['127.0.0.1','::1'],true);
}
function igua_auto_local_host(string $host): bool {
    return in_array(strtolower($host),['127.0.0.1','localhost','::1'],true);
}
/** Teste de permissões essenciais, exclusivamente em banco vazio e sem alterar tabelas do portal. */
function igua_setup_probe_schema_permissions(PDO $db): void {
    $name='igua_probe_'.bin2hex(random_bytes(5));
    $id=igua_sql_identifier($name);
    $created=false;$done=false;
    try {
        $db->exec('CREATE TABLE '.$id.' (id INT NOT NULL PRIMARY KEY) ENGINE=InnoDB');
        $created=true;
        $db->exec('INSERT INTO '.$id.' (id) VALUES (1)');
        $db->exec('UPDATE '.$id.' SET id=2 WHERE id=1');
        if((int)$db->query('SELECT COUNT(*) FROM '.$id)->fetchColumn()!==1)throw new RuntimeException('Consulta de verificação não retornou o esperado.');
        $db->exec('ALTER TABLE '.$id.' ADD COLUMN teste INT DEFAULT NULL');
        $db->exec('CREATE INDEX igua_probe_idx ON '.$id.' (teste)');
        $db->exec('DELETE FROM '.$id.' WHERE id=2');
        $done=true;
    }catch(Throwable $e){
        throw new DomainException('A conexão funciona, mas faltam privilégios para criar ou modificar tabelas. Confira CREATE, SELECT, INSERT, UPDATE, DELETE, ALTER, INDEX e DROP no novo banco.',0,$e);
    }finally{
        if($created){
            try{$db->exec('DROP TABLE '.$id);}catch(Throwable $e){
                throw new DomainException('Não foi possível limpar a tabela temporária de diagnóstico '.$name.'. Corrija a permissão DROP antes de instalar.',0,$e);
            }
        }
    }
    if(!$done)throw new RuntimeException('Verificação de permissões não concluída.');
}
/** Admin sem dbname: criação de bases só com uma conta que já tenha privilégios. */
function igua_admin_connection(string $host, int $port, string $username, string $password): PDO {
    if (!igua_auto_local_host($host) || $port<1 || $port>65535 || $username==='') {
        throw new InvalidArgumentException('A criação automática é limitada ao MariaDB local. Para hospedagem remota, use conexão manual.');
    }
    $dsn='mysql:host='.$host.';port='.$port.';charset=utf8mb4';
    return new PDO($dsn,$username,$password,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_TIMEOUT=>6
    ]);
}
/**
 * Conexão de instalação SEM dbname. Não confundir com conexões do portal:
 * ela existe somente durante este POST e nunca deve ser salva na sessão/config.
 * Hospedagens remotas só funcionam se o próprio provedor permitir CREATE DATABASE.
 */
function igua_schema_creation_connection(string $host, int $port, string $username, string $password, bool $tls=false): PDO {
    // Rejeita delimitadores DSN, sockets arbitrários e opções injetadas pelo formulário.
    if (strlen($host)<1 || strlen($host)>253 || !preg_match('/\A[a-z0-9][a-z0-9._-]*\z/iD',$host)
        || $port<1 || $port>65535 || trim($username)==='' || strlen($username)>128 || preg_match('/[\r\n\x00]/',$username)) {
        throw new InvalidArgumentException('Informe um servidor MySQL válido, porta e usuário autorizado.');
    }
    $dsn='mysql:host='.$host.';port='.$port.';charset=utf8mb4';
    return new PDO($dsn,$username,$password,[
        PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_TIMEOUT=>6,
    ]+igua_mysql_tls_options($tls));
}
/**
 * Usa uma conta MySQL já autorizada. Cria SOMENTE um banco novo: não executa
 * IF NOT EXISTS, DROP, CREATE USER ou GRANT. Falha se o nome já estiver ocupado.
 * A mesma conta continua sendo usada pelo portal; o usuário deve entender
 * que ela pode manter privilégios amplos, dependendo do provedor.
 */
function igua_create_database_with_login(PDO $admin, string $database): void {
    $admin->exec('CREATE DATABASE '.igua_sql_identifier($database).' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
}
/**
 * Criação dedicada no MySQL LOCAL de VPS: não permite usuários remotos ou
 * host com wildcard, sem reutilizar banco existente. Privilégios SQL locais
 * ficam nas credenciais fornecidas pelo proprietário, nunca salvas.
 */
function igua_provision_hosting_local(PDO $admin, string $database, string $host, int $port, ?callable $progress=null): array {
    if (!in_array($host,['localhost','127.0.0.1'],true)) {
        throw new InvalidArgumentException('O provisionamento de usuário exclusivo exige banco MySQL no mesmo servidor (localhost ou 127.0.0.1). Para banco remoto, utilize Criar banco com usuário existente.');
    }
    return igua_provision_database($admin,$database,$host,$port,false,$progress);
}

/**
 * Banco exclusivo, usuário aleatório exclusivo e senha forte gerada localmente.
 * Não grava nem devolve a senha administrativa; concede permissões SOMENTE no novo banco.
 * DDL não é transacional: se o provisionamento falhar, dados criados não são removidos automaticamente.
 */
function igua_provision_database(PDO $admin, string $database, string $appHost='127.0.0.1', int $port=3306, bool $reuseEmpty=false, ?callable $progress=null): array {
    $quotedDb=igua_sql_identifier($database);
    if (!in_array($appHost,['127.0.0.1','localhost'],true) || $port<1 || $port>65535) {
        throw new InvalidArgumentException('O usuário automático precisa conectar ao banco local.');
    }
    // A base existente só é reutilizada se o usuário explicitamente permitir e estiver VAZIA.
    // Nunca executamos DROP DATABASE, TRUNCATE ou DELETE nessa etapa.
    try {
        $admin->exec('CREATE DATABASE '.$quotedDb.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        if($progress!==null)$progress('database_created',$database);
    }
    catch (PDOException $e) {
        $errno=(int)($e->errorInfo[1]??0);
        if ($errno!==1007 || !$reuseEmpty) throw $e;
        $tables=$admin->query('SHOW TABLES FROM '.$quotedDb)->fetchAll(PDO::FETCH_COLUMN);
        $known=igua_setup_known_tables();
        if (array_diff($tables,$known)) throw new RuntimeException('O banco já possui tabelas desconhecidas. Não alteramos bases usadas por outros sistemas.');
        if($progress!==null)$progress('database_reused',$database);
        foreach ($tables as $table) {
            $rows=(int)$admin->query('SELECT COUNT(*) FROM '.$quotedDb.'.`'.$table.'`')->fetchColumn();
            if ($rows!==0) throw new RuntimeException('O banco existente possui dados e não será modificado.');
        }
    }
    $username='iguanews_'.bin2hex(random_bytes(4));
    $password=bin2hex(random_bytes(24));
    // MariaDB no Termux pode resolver TCP 127.0.0.1 para localhost; provisiona ambos.
    // Os hosts são constantes, não há wildcard de rede (%) ou privilégios globais.
    foreach (['localhost','127.0.0.1'] as $allowedHost) {
        $account=$admin->quote($username).'@'.$admin->quote($allowedHost);
        $admin->exec('CREATE USER '.$account.' IDENTIFIED BY '.$admin->quote($password));
        if($progress!==null)$progress('user_created',$username.'@'.$allowedHost);
        $admin->exec('GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON '.$quotedDb.'.* TO '.$account);
        if($progress!==null)$progress('grant_applied',$username.'@'.$allowedHost);
    }
    return ['host'=>$appHost,'port'=>$port,'database'=>$database,'username'=>$username,'password'=>$password];
}
/** Confirma que o backup de migração foi criado pelo IguaNews, não é dump SQL arbitrário. */
function igua_read_import(array $file): array {
    $name=(string)($file['name']??'');
    $size=(int)($file['size']??0);
    $error=(int)($file['error']??UPLOAD_ERR_NO_FILE);
    if (in_array($error,[UPLOAD_ERR_INI_SIZE,UPLOAD_ERR_FORM_SIZE],true)) {
        throw new DomainException('O servidor recusou o backup por exceder upload_max_filesize ou o limite do formulário. Confira os limites na etapa Requisitos e reinicie o PHP após alterá-los.');
    }
    if ($error===UPLOAD_ERR_PARTIAL) throw new DomainException('O envio do backup foi interrompido. Confira a conexão e tente novamente.');
    if ($error===UPLOAD_ERR_NO_FILE) throw new DomainException('Selecione o arquivo de backup para importar.');
    if ($error!==UPLOAD_ERR_OK) throw new DomainException('O PHP não conseguiu receber o backup (erro de upload '.$error.'). Confira permissões do diretório temporário.');
    if (!preg_match('/\.json(?:\.gz)?\z/iD',$name) || $size<1 || $size>IGUA_BACKUP_IMPORT_MAX_BYTES) {
        throw new DomainException('Selecione um backup .json ou .json.gz com tamanho entre 1 byte e 15 MiB.');
    }
    $tmp=(string)($file['tmp_name']??'');
    if (!is_uploaded_file($tmp)) throw new RuntimeException('Arquivo enviado inválido.');
    $content=file_get_contents($tmp);
    if ($content===false) throw new RuntimeException('Não foi possível ler o backup enviado.');
    try { return parse_backup($content,$name); }
    catch (Throwable $e) { throw new InvalidArgumentException('O arquivo não é um backup compatível válido e compatível.',0,$e); }
}
/** Prepara configuração privada ANTES de iniciar criação de tabelas/usuários. */
function igua_stage_config(array $source, string $directory=IGUA_STORAGE): string {
    if (!is_dir($directory) || is_link($directory)) throw new RuntimeException('Diretório privado indisponível.');
    $c=array_intersect_key($source,array_flip(['host','port','database','username','password','tls']));
    $tmp=$directory.'/config.'.bin2hex(random_bytes(12)).'.tmp';
    $php="<?php\n// Configuração privada gerada pelo instalador Meu Site.\nreturn ".var_export($c,true).";\n";
    $file=@fopen($tmp,'x');
    if ($file===false) throw new RuntimeException('Não foi possível criar arquivo temporário privado.');
    try {
        $ok=fwrite($file,$php)===strlen($php) && fflush($file);
    } finally { fclose($file); }
    if (!$ok) { @unlink($tmp); throw new RuntimeException('Não foi possível escrever configuração privada completa.'); }
    @chmod($tmp,0600);
    return $tmp;
}
/** Só publica o arquivo no final, após finalizar o banco. Não sobrescreve instalação existente. */
function igua_activate_config(string $staged, string $directory=IGUA_STORAGE): void {
    if (is_file($directory.'/config.php')) throw new RuntimeException('Já existe uma configuração privada.');
    if (!@rename($staged,$directory.'/config.php')) throw new RuntimeException('Falha ao ativar a configuração privada.');
}
function igua_store_config(array $source): void {
    $staged=igua_stage_config($source);
    try { igua_activate_config($staged); }
    finally { if (is_file($staged)) @unlink($staged); }
}
