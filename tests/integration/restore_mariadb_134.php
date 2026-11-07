<?php
declare(strict_types=1);
/**
 * Teste destrutivo APENAS em banco de integração novo e vazio, com opt-in explícito.
 * Uso: IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB IGUA_TEST_DB_NAME=igua_ci_134 ... php tests/integration/restore_mariadb_134.php
 * Nunca rodar em produção. Não cria/apaga bancos ou usuários, mas restaura conteúdo no banco indicado.
 */
require_once __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/backup.php';
require_once __DIR__.'/../../app/schema.php';
require_once __DIR__.'/../../app/schema_migrations.php';

function integration134_assert(bool $condition,string $message): void {
    if(!$condition)throw new RuntimeException('INTEGRAÇÃO FALHOU: '.$message);
    echo 'OK MariaDB: '.$message."\n";
}
function integration134_env(string $key): string {return trim((string)(getenv($key)?:''));}
if(PHP_SAPI!=='cli'||integration134_env('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB'){
    fwrite(STDERR,"Teste bloqueado. Informe IGUA_TEST_ALLOW_WRITE=YES_ISOLATED_DB em um banco NOVO de integração.\n");
    exit(2);
}
$name=integration134_env('IGUA_TEST_DB_NAME');
if(!preg_match('/^igua_ci_[a-z0-9_]{3,40}$/D',$name)){
    fwrite(STDERR,"Banco não autorizado. Exigido nome igua_ci_* reservado para integração.\n");
    exit(2);
}
if(!extension_loaded('pdo_mysql')||!class_exists('ZipArchive')){
    fwrite(STDERR,"Requer pdo_mysql e ext-zip reais. Não é uma simulação.\n");
    exit(2);
}
$host=integration134_env('IGUA_TEST_DB_HOST')?:'127.0.0.1';
$port=(int)(integration134_env('IGUA_TEST_DB_PORT')?:'3306');
$user=integration134_env('IGUA_TEST_DB_USER');
$pass=(string)(getenv('IGUA_TEST_DB_PASSWORD')?:'');
if($user===''||!preg_match('/^(127\.0\.0\.1|localhost|::1)$/D',$host)||$port<1||$port>65535){
    fwrite(STDERR,"A conexão de teste deve ser local e ter usuário explícito.\n");
    exit(2);
}
// Exigir banco já criado e VAZIO: não é seguro rodar em uma instalação em uso.
$db=connection(['host'=>$host,'port'=>$port,'database'=>$name,'username'=>$user,'password'=>$pass]);
$stmt=$db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=?');
$stmt->execute([$name]);
integration134_assert((int)$stmt->fetchColumn()===0,'banco de integração inicialmente vazio');

$createdMedia=[];$createdBackups=[];
try{
    install_schema($db);
    integration134_assert(igua_schema_revision($db)===IGUA_SCHEMA_REVISION,'schema instalado e revisão registrada');
    integration134_assert(igua_migrate_schema($db)===false,'segunda migração não altera esquema');
    foreach(BACKUP_TABLES as $table){
        $db->query('SELECT * FROM `'.$table.'` LIMIT 0')->closeCursor();
    }
    integration134_assert(true,'todas as tabelas do backup v12 existem');
    $db->prepare('INSERT INTO users (name,email,password_hash,role,active) VALUES (?,?,?,?,1)')
        ->execute(['Administrador teste','integracao134@example.invalid',password_hash('senha-temporaria-134',PASSWORD_DEFAULT),'admin']);
    $userId=(int)$db->lastInsertId();
    $db->prepare('INSERT INTO categories (name,slug,sort_order) VALUES (?,?,?)')->execute(['Integração','integracao-teste',5]);
    $categoryId=(int)$db->lastInsertId();
    $fileName=date('Y').'/'.date('m').'/'.bin2hex(random_bytes(16)).'.png';
    $mediaPath=IGUA_PUBLIC.'/uploads/'.$fileName;
    if(!is_dir(dirname($mediaPath))&&!mkdir(dirname($mediaPath),0755,true)&&!is_dir(dirname($mediaPath)))throw new RuntimeException('Sem pasta temporária de mídia.');
    if(file_exists($mediaPath)||is_link($mediaPath))throw new RuntimeException('Destino do teste já existente: interrompido sem sobrescrever.');
    // Imagem gerada localmente para o teste; nenhuma mídia do usuário é utilizada.
    $png=base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9WlmuSAAAAAASUVORK5CYII=',true);
    if(!is_string($png)||file_put_contents($mediaPath,$png)!==strlen($png))throw new RuntimeException('Falha ao criar imagem de teste.');
    $createdMedia[]=$mediaPath;
    $imageHash=hash_file('sha256',$mediaPath);
    $db->prepare('INSERT INTO articles (category_id,author_id,title,slug,summary,body,image,status,is_demo,published_at) VALUES (?,?,?,?,?,?,?,?,?,NOW())')
        ->execute([$categoryId,$userId,'Notícia teste de recuperação','noticia-teste-integracao-134','Resumo da integração','Corpo do teste de backup e restauração.',$fileName,'published',1]);
    $articleId=(int)$db->lastInsertId();
    $db->prepare('INSERT INTO media_library (filename,original_name,title,mime,bytes,width,height,sha256,folder_year,folder_month) VALUES (?,?,?,?,?,?,?,?,?,?)')
        ->execute([$fileName,'imagem-teste.png','Imagem fictícia','image/png',strlen($png),1,1,$imageHash,(int)date('Y'),(int)date('n')]);

    $backup=make_backup($db);
    $backupPath=backup_path($backup);
    integration134_assert(is_string($backupPath)&&is_file($backupPath),'backup lógico gravado no disco');
    $createdBackups[]=$backupPath;
    $original=parse_backup((string)file_get_contents($backupPath),$backup);
    integration134_assert((int)$original['tables']['articles'][0]['is_demo']===1,'backup preserva marca de demonstração');
    $db->prepare('UPDATE articles SET title=? WHERE id=?')->execute(['Título modificado temporariamente',$articleId]);
    restore_backup($db,$original);
    $title=$db->query('SELECT title FROM articles WHERE id='.$articleId)->fetchColumn();
    integration134_assert($title==='Notícia teste de recuperação','round-trip do banco restaura título e artigo');

    // Uma FK inválida deve provocar rollback completo após os DELETE transacionais.
    $bad=$original;
    $bad['tables']['articles'][0]['category_id']=4294967294;
    $rejected=false;
    try{restore_backup($db,$bad);}catch(PDOException){$rejected=true;}
    integration134_assert($rejected,'restrição de chave estrangeira recusa restauração inválida');
    integration134_assert($db->query('SELECT title FROM articles WHERE id='.$articleId)->fetchColumn()==='Notícia teste de recuperação','falha mantém estado anterior (rollback InnoDB)');
    integration134_assert(!$db->inTransaction(),'nenhuma transação é deixada aberta');

    $bundle=make_media_bundle($db);$createdBackups[]=$bundle;
    integration134_assert(is_file($bundle),'ZIP completo com mídia gerado via ZipArchive real');
    $zip=new ZipArchive();if($zip->open($bundle)!==true)throw new RuntimeException('Pacote gerado não abre.');
    $manifest=json_decode((string)$zip->getFromName('manifest.json'),true,50,JSON_THROW_ON_ERROR);
    integration134_assert(($manifest['db_sha256']??'')===hash('sha256',(string)$zip->getFromName($manifest['db'])),'SHA-256 do banco corresponde ao manifesto');
    integration134_assert(isset($manifest['files']['assets/images/'.$fileName]),'mídia referenciada aparece no manifesto');
    $zip->close();

    // Rejeição antes de tocar no banco ou restaurar mídia.
    $brokenBundle=tempnam(IGUA_STORAGE.'/backups','igua134-broken-');
    if($brokenBundle===false||!copy($bundle,$brokenBundle))throw new RuntimeException('Não foi possível copiar ZIP para adulteração.');
    $createdBackups[]=$brokenBundle;
    $tamper=new ZipArchive();if($tamper->open($brokenBundle)!==true)throw new RuntimeException('ZIP temporário inválido.');
    if(!$tamper->addFromString($manifest['db'],'banco adulterado'))throw new RuntimeException('Falha na adulteração do teste.');
    $tamper->close();
    $db->prepare('UPDATE articles SET title=? WHERE id=?')->execute(['Estado protegido contra ZIP adulterado',$articleId]);
    $caught=false;
    try{restore_media_bundle($db,$brokenBundle);}catch(RuntimeException){$caught=true;}
    integration134_assert($caught,'ZIP adulterado é rejeitado');
    integration134_assert($db->query('SELECT title FROM articles WHERE id='.$articleId)->fetchColumn()==='Estado protegido contra ZIP adulterado','ZIP adulterado não modifica o banco');

    // Agora realmente recupera foto apagada e banco alterado.
    if(!unlink($mediaPath))throw new RuntimeException('Não foi possível simular exclusão da mídia.');
    // ZIP com manifesto atualizado e banco estruturalmente válido, porém FK inválida:
    // a foto preparada deve ser retirada quando a transação do MySQL falhar.
    $brokenFkBundle=tempnam(IGUA_STORAGE.'/backups','igua134-fk-');
    if($brokenFkBundle===false||!copy($bundle,$brokenFkBundle))throw new RuntimeException('Falha ao preparar ZIP com FK inválida.');
    $createdBackups[]=$brokenFkBundle;
    $badZip=new ZipArchive();if($badZip->open($brokenFkBundle)!==true)throw new RuntimeException('ZIP de FK inválido.');
    $rawDb=(string)$badZip->getFromName($manifest['db']);
    $plainDb=str_ends_with($manifest['db'],'.gz')?gzdecode($rawDb):$rawDb;
    if(!is_string($plainDb))throw new RuntimeException('GZIP do banco de teste inválido.');
    $badData=json_decode($plainDb,true,256,JSON_THROW_ON_ERROR);
    $badData['tables']['articles'][0]['category_id']=4294967294;
    $badJson=json_encode($badData,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
    $badRaw=str_ends_with($manifest['db'],'.gz')?gzencode($badJson,6):$badJson;
    if(!is_string($badRaw))throw new RuntimeException('Falha ao criar banco inválido.');
    $badManifest=$manifest;$badManifest['db_sha256']=hash('sha256',$badRaw);
    if(!$badZip->addFromString($manifest['db'],$badRaw)||!$badZip->addFromString('manifest.json',json_encode($badManifest,JSON_THROW_ON_ERROR)))throw new RuntimeException('Falha ao preparar manifesto de teste.');
    $badZip->close();
    $rejectedFk=false;
    try{restore_media_bundle($db,$brokenFkBundle);}catch(PDOException){$rejectedFk=true;}
    integration134_assert($rejectedFk,'ZIP com FK inválida é recusado após staging');
    integration134_assert(!is_file($mediaPath),'falha de banco remove mídia recém-promovida');
    integration134_assert($db->query('SELECT title FROM articles WHERE id='.$articleId)->fetchColumn()==='Estado protegido contra ZIP adulterado','falha de banco mantém os dados anteriores');
    restore_media_bundle($db,$bundle);
    integration134_assert(is_file($mediaPath)&&hash_file('sha256',$mediaPath)===$imageHash,'foto restaurada com bytes e SHA-256 idênticos');
    integration134_assert($db->query('SELECT title FROM articles WHERE id='.$articleId)->fetchColumn()==='Notícia teste de recuperação','ZIP recupera o conteúdo do banco');
    integration134_assert((int)$db->query('SELECT is_demo FROM articles WHERE id='.$articleId)->fetchColumn()===1,'ZIP preserva marca de demonstração');
    integration134_assert(!$db->inTransaction(),'restituição ZIP encerra a transação');
    echo "INTEGRAÇÃO 1.0.34: MariaDB real + ZipArchive real aprovados em banco isolado.\n";
} finally {
    foreach($createdBackups as $file)if(is_file($file))@unlink($file);
    foreach($createdMedia as $file)if(is_file($file))@unlink($file);
    // O banco de integração não é apagado automaticamente, para investigação de falhas.
}
