<?php
declare(strict_types=1);
/** Diagnósticos somente para administradores. Nunca incluir credenciais, IP, URL, corpo de requisição ou mensagem de exceção em logs. */
function igua_health_log(string $code, ?Throwable $error=null): void {
    if (!preg_match('/^[A-Z][A-Z0-9_]{1,39}$/D', $code)) return;
    $folder=IGUA_STORAGE.'/logs';
    if (!is_dir($folder) && !@mkdir($folder,0700,true) && !is_dir($folder)) return;
    $lock=@fopen($folder.'/.lock','c');
    if ($lock===false) return;
    try {
        if (!@flock($lock,LOCK_EX)) return;
        $path=$folder.'/health.log';
        if (is_file($path) && (int)@filesize($path)>512*1024) {
            @rename($folder.'/health.1.log',$folder.'/health.2.log');
            @rename($path,$folder.'/health.1.log');
        }
        $file=$error?basename($error->getFile()):'-';
        $line=$error?(int)$error->getLine():0;
        $kind=$error?basename(str_replace('\\','/',get_class($error))):'-';
        $entry=date('c').' | '.$code.' | '.$kind.' | '.preg_replace('/[^a-zA-Z0-9._-]/','',$file).' | '.$line."\n";
        @file_put_contents($path,$entry,FILE_APPEND|LOCK_EX);
        @chmod($path,0600);
    } finally { @flock($lock,LOCK_UN); @fclose($lock); }
}
function igua_health_init(): void {
    if (PHP_SAPI==='cli') return;
    // Apenas categoria, nome-base e linha. Nunca capturar mensagens com credenciais ou dados.
    set_error_handler(static function(int $severity, string $message, string $file, int $line): bool {
        static $reported=0;
        if(!(error_reporting() & $severity) || $reported>=4)return false;
        if(!in_array($severity,[E_WARNING,E_USER_WARNING,E_RECOVERABLE_ERROR],true))return false;
        $reported++;
        igua_health_log('PHP_WARNING',new ErrorException('Erro técnico',0,$severity,$file,$line));
        return false; // Mantém o handler padrão; não mascara nem converte warnings em sucesso.
    });
    register_shutdown_function(static function(): void {
        $last=error_get_last();
        if(!is_array($last) || !in_array((int)$last['type'],[E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR],true)) return;
        // Sanitização: sem texto da falha ou caminho completo, que podem conter segredos.
        igua_health_log('PHP_FATAL',new ErrorException('Erro interno',0,(int)$last['type'],(string)$last['file'],(int)$last['line']));
    });
}
function igua_health_checks(PDO $db): array {
    $rows=[];
    $add=static function(string $name,string $status,string $detail,string $suggest='') use (&$rows): void {
        $rows[]=['name'=>$name,'status'=>$status,'detail'=>$detail,'suggest'=>$suggest];
    };
    $runtime=igua_runtime_profile();
    $add('Ambiente PHP','info',$runtime['label'].' · '.strtoupper($runtime['sapi']).'.',
        'Termux usa MariaDB local; na hospedagem use credenciais e permissões do provedor.');
    $webroot=igua_webroot_diagnosis();
    $add('Raiz pública',$webroot['ok']?'ok':'error',$webroot['detail'],
        $webroot['ok']?'':'Use public/ como DocumentRoot ou public_html/ com app/ e storage/ como irmãos privados. Nunca publique pastas internas.');
    $publicFiles=igua_public_files_diagnosis(IGUA_PUBLIC);
    $add('Arquivos públicos',$publicFiles['ok']?'ok':'error',$publicFiles['detail'],
        $publicFiles['ok']?'':'Republique os arquivos de public/ sem misturar o diretório privado com a raiz web.');
    if($runtime['kind']==='hosting') {
        $add('HTTPS',$runtime['https']?'ok':'warning',
            $runtime['https']?'Acesso HTTPS detectado.':'HTTPS não confirmado nesta requisição.',
            'Ative TLS no painel e configure o domínio público HTTPS nas opções de SEO.');
    }
    try {
        $db->query('SELECT 1')->fetchColumn();
        $version=(string)$db->query('SELECT VERSION()')->fetchColumn();
        $add('MySQL / MariaDB','ok','Conexão operacional ('.preg_replace('/[^0-9.a-zA-Z_-]/','',substr($version,0,45)).').');
    } catch(Throwable $e) {$add('MySQL / MariaDB','error','Não foi possível executar a consulta de teste.','Confira o serviço, credenciais e permissões SQL.');igua_health_log('DB_CHECK_FAILED',$e);}
    try {
        // A revisão não substitui a checagem física das tabelas essenciais.
        $required=BACKUP_TABLES;
        $names=$db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        $missing=array_values(array_diff($required,$names));
        $add('Estrutura do banco',$missing?'warning':'ok',$missing?'Tabelas ausentes: '.implode(', ',$missing):'Tabelas fundamentais encontradas.',$missing?'Entre no painel com uma conta autorizada para executar as migrações.':'');
    } catch(Throwable $e) {$add('Estrutura do banco','error','Não foi possível listar tabelas.','Verifique permissões do usuário SQL.');igua_health_log('SCHEMA_CHECK_FAILED',$e);}
    try {
        require_once __DIR__.'/schema_migrations.php';
        $revision=igua_schema_revision($db);
        $outdated=$revision!==IGUA_SCHEMA_REVISION;
        $add('Revisão de esquema',$outdated?'warning':'ok',
            $outdated?'Registro atual: '.$revision.'; código exige '.IGUA_SCHEMA_REVISION.'.':'Migrações registradas na revisão '.IGUA_SCHEMA_REVISION.'.',
            $outdated?'Conclua o login administrativo e confira permissões CREATE/ALTER TABLE.':'A revisão registrada não substitui a checagem das tabelas.');
    }catch(Throwable $e){$add('Revisão de esquema','warning','Não foi possível consultar o registro de migrações.','Confira permissões SQL e a tabela settings.');}
    try {
        $q=$db->query("SHOW TABLES LIKE 'admin_remember_tokens'");
        $ready=(bool)$q->fetchColumn();
        $add('Sessões persistentes',$ready?'ok':'warning',
            $ready?'Tabela de tokens revogáveis encontrada.':'Tabela admin_remember_tokens ausente.',
            $ready?'':'Execute as migrações administrativas com permissão CREATE TABLE (a tabela de sessões foi introduzida na revisão 34).');
    } catch(Throwable $e) {
        $add('Sessões persistentes','warning','Não foi possível verificar a tabela de autenticação.','Confira as permissões do banco.');
    }
    $requiredExtensions=['pdo_mysql','fileinfo','json'];
    $missing=array_values(array_filter($requiredExtensions,static fn(string $ex)=>!extension_loaded($ex)));
    $add('Extensões PHP',$missing?'error':'ok',$missing?'Ausentes: '.implode(', ',$missing):'PHP '.PHP_VERSION.' com extensões essenciais ativas.',$missing?'Habilite estas extensões no PHP que atende o site.':'');
    foreach (['storage'=>IGUA_STORAGE,'backups'=>IGUA_STORAGE.'/backups','uploads'=>IGUA_PUBLIC.'/uploads','anexos'=>IGUA_STORAGE.'/media'] as $name=>$path) {
        $ok=is_dir($path) && is_writable($path);
        $add('Gravação em '.$name,$ok?'ok':'warning',$ok?'Pasta pronta para escrita.':'Diretório inexistente ou sem permissão.', 'Confira as permissões e não deixe storage acessível publicamente.');
    }
    $free=@disk_free_space(IGUA_STORAGE);
    if($free===false) $add('Espaço livre','info','Não foi possível consultar o espaço disponível.');
    else {
        $gb=number_format((float)$free/1073741824,2,',','.');
        $add('Espaço livre',$free<150*1048576?'warning':'ok',$gb.' GiB disponíveis na partição do armazenamento.',$free<150*1048576?'Libere espaço antes de gerar pacotes grandes.':'');
    }
    $backups=list_backups();
    if(!$backups) $add('Backup do banco','warning','Nenhum backup local encontrado.','Crie um backup em Configurações → Backups e guarde uma cópia externa.');
    else {
        $last=$backups[0];
        $age=time()-(int)(@filemtime(IGUA_STORAGE.'/backups/'.$last['name']) ?: 0);
        $add('Backup do banco',$age>48*3600?'warning':'ok', 'Mais recente: '.date('d/m/Y H:i',time()-$age).($age>48*3600?' (há mais de 48 horas).':'.'), 'A presença do arquivo NÃO confirma integridade nem agendamento automático. Execute a verificação manual e mantenha cópia externa.');
    }
    $zip=class_exists('ZipArchive');
    $add('Pacote com fotos e anexos',$zip?'ok':'warning',$zip?'ZipArchive disponível.':'Extensão zip indisponível.','Para migrar imagens e anexos junto ao banco, habilite php-zip.');
    $host=(string)getenv('IGUANEWS_SMTP_HOST');
    $smtp=$host!=='' && (string)getenv('IGUANEWS_SMTP_USER')!=='' && (string)getenv('IGUANEWS_SMTP_PASSWORD')!=='' && filter_var((string)getenv('IGUANEWS_SMTP_FROM'),FILTER_VALIDATE_EMAIL) && is_file(IGUA_ROOT.'/vendor/autoload.php');
    $add('Formulário / SMTP',$smtp?'info':'warning',$smtp?'Configuração encontrada, mas entrega não testada.':'Sem todas as variáveis SMTP ou dependências do Composer.','Configure SMTP na hospedagem e envie um teste real. Nenhuma senha é mostrada aqui.');
    $geo=glob(IGUA_STORAGE.'/geoip/*.mmdb') ?: [];
    $add('Geolocalização aproximada',$geo?'info':'warning',$geo?'Arquivo GeoIP encontrado (validade não verificada).':'Sem base GeoIP local. Métricas geográficas podem ficar desconhecidas.');
    return $rows;
}
/** O conteúdo de um backup só é lido mediante clique, sem restauração nem alteração de tabelas. */
function igua_health_verify_backup(string $filename): array {
    $path=backup_path($filename);
    if(!$path) throw new RuntimeException('Arquivo de backup inexistente ou com nome inválido.');
    $size=(int)filesize($path);
    if($size>40*1048576) throw new RuntimeException('Arquivo muito grande para a verificação online. Confira em ambiente isolado.');
    $raw=file_get_contents($path);
    if($raw===false) throw new RuntimeException('Não foi possível ler o backup.');
    $data=parse_backup($raw,$filename);
    $total=0;foreach($data['tables'] as $records)$total+=count($records);
    return ['version'=>(int)$data['version'],'tables'=>count($data['tables']),'rows'=>$total,'sha256'=>hash_file('sha256',$path)];
}
function igua_health_log_lines(int $limit=35): array {
    $files=[IGUA_STORAGE.'/logs/health.2.log',IGUA_STORAGE.'/logs/health.1.log',IGUA_STORAGE.'/logs/health.log'];
    $rows=[];
    foreach($files as $path){
        if(!is_file($path))continue;
        $handle=@fopen($path,'rb');if(!$handle)continue;
        try{
            while(($line=fgets($handle))!==false){
                if(strlen($line)>500)continue;
                if(preg_match('/^\d{4}-\d\d-\d\dT[^|]+ \| [A-Z_0-9]+ \| [a-zA-Z0-9_-]+ \| [a-zA-Z0-9._-]+ \| \d+\s*$/D',trim($line)))$rows[]=trim($line);
                if(count($rows)>6000)$rows=array_slice($rows,-$limit);
            }
        }finally{fclose($handle);}
    }
    return array_reverse(array_slice($rows,-$limit));
}
