<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/requirements.php';
require __DIR__.'/../app/schema.php';
require __DIR__.'/../app/backup.php';
require __DIR__.'/../app/migration_package.php';
require __DIR__.'/../app/installer.php';
require __DIR__.'/../app/setup_chunked_backup.php';
require __DIR__.'/../app/content_maintenance.php';
if (installed()) redirect('/admin.php?action=login');
$runtime=igua_runtime_profile();
$autoAvailable=igua_auto_install_allowed($runtime);
$databaseDefault=$runtime['db_name'];
$hostDefault=$runtime['db_host'];
$requirements=igua_requirements();
$ready=igua_required_ready($requirements);
$errors=[];
$notices=[];
$step=max(1,min(3,(int)($_GET['step']??1)));
// O modo é escolhido antes das credenciais; nunca confundir com recuperação de config.
if(!request_post() && $step===2 && in_array($_GET['mode']??'', ['fresh','migrate'],true)){
    $_SESSION['igua_setup_install_mode']=(string)$_GET['mode'];
    unset($_SESSION['iguanews_setup_db']);
}
$setupMode=in_array($_SESSION['igua_setup_install_mode']??'', ['fresh','migrate'],true)?$_SESSION['igua_setup_install_mode']:'fresh';
$migrationFolder=IGUA_STORAGE.'/migration';
$hasEmbeddedMigration=is_link($migrationFolder)||is_dir($migrationFolder)&& (is_file($migrationFolder.'/manifest.json')||is_file($migrationFolder.'/backup.zip')||is_dir($migrationFolder.'/extras'));
$setupData=$_SESSION['iguanews_setup_db']??null;
if (!is_array($setupData) || (time()-(int)($setupData['verified_at']??0))>1200) {
    unset($_SESSION['iguanews_setup_db']);
    $setupData=null;
}
if (!$ready) $step=1;
if ($step===3 && !$setupData) $step=2;
if (request_post() && empty($_POST) && igua_post_exceeds_limit()) {
    $errors[]='O arquivo enviado ultrapassou post_max_size do PHP. O servidor descartou o formulário antes da validação. Reduza o backup ou aumente o limite de envio e tente novamente.';
    $step=$setupData ? 3 : 2;
} elseif (request_post()) {
    check_csrf();
    $action=(string)($_POST['action']??'');
    if ($action==='backup_chunk') {
        header('Content-Type: application/json; charset=UTF-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        try {
            if (!$ready || !$setupData) throw new DomainException('Verifique a conexão do banco antes de enviar o backup.');
            $name=$_POST['backup_name']??null;
            $size=$_POST['upload_total']??null;
            $offset=$_POST['upload_offset']??null;
            $token=$_POST['upload_token']??null;
            if (!is_string($name) || !is_string($size) || !ctype_digit($size)
                || !is_string($offset) || !ctype_digit($offset) || !is_string($token)) {
                throw new DomainException('Parâmetros do upload inválidos.');
            }
            $answer=igua_setup_receive_chunk($_FILES['chunk']??[], $name, (int)$size, (int)$offset, $token);
            echo json_encode($answer, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
        } catch (DomainException $e) {
            http_response_code(422);
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
        } catch (Throwable) {
            http_response_code(500);
            echo json_encode(['ok'=>false,'error'=>'Não foi possível armazenar o bloco com segurança. Confira espaço livre e permissões.'],JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
    if (!$ready) {
        $errors[]='Existem requisitos obrigatórios pendentes. Corrija-os antes de prosseguir.';
        $step=1;
    } elseif ($action==='test_db' || $action==='auto_db') {
        $step=2;
        // Resultado anterior só pode ser usado depois de um teste bem-sucedido.
        unset($_SESSION['iguanews_setup_db']);
        $setupData=null;
        $port=(int)($_POST['port']??3306);
        $database=trim((string)($_POST['database']??$databaseDefault));
        $host=trim((string)($_POST['host']??$hostDefault));
        if ($port<1 || $port>65535) $errors[]='Escolha uma porta válida (1 a 65535).';
        else {
            try {
                if ($action==='auto_db') {
                    if (!$autoAvailable) throw new InvalidArgumentException('A criação automática está disponível somente para Termux identificado pelo PHP e acesso local do próprio aparelho. Na hospedagem use banco existente.');
                    $adminHost=trim((string)($_POST['admin_host']??'localhost'));
                    $adminUser=trim((string)($_POST['admin_username']??'root'));
                    $adminPassword=(string)($_POST['admin_password_db']??'');
                    igua_sql_identifier($database);
                    $admin=igua_admin_connection($adminHost,$port,$adminUser,$adminPassword);
                    // A senha administrativa não é guardada em arquivo nem sessão.
                    $candidate=igua_provision_database($admin,$database,'127.0.0.1',$port,!empty($_POST['reuse_empty']));
                } else {
                    $username=trim((string)($_POST['username']??''));
                    $password=(string)($_POST['password']??'');
                    if (!$host || !$database || !$username) throw new InvalidArgumentException('Preencha servidor, banco e usuário.');
                    $candidate=['host'=>$host,'port'=>$port,'database'=>$database,'username'=>$username,'password'=>$password];
                }
                $db=connection($candidate);
                $inventory=igua_setup_database_inventory($db);
                $mode='fresh';
                if ($inventory['status']==='recoverable' && igua_setup_recovery_allowed($db)) {
                    $mode='recover';
                } elseif (!in_array($inventory['status'],['empty','partial_empty'],true)) {
                    throw new DomainException($inventory['detail']);
                }
                if($setupMode==='migrate' && $hasEmbeddedMigration && $inventory['status']!=='empty')
                    throw new DomainException('Migração exige um banco completamente vazio. Crie outro banco no novo provedor.');
                if (!igua_setup_config_write_probe()) {
                    throw new DomainException('O PHP conecta ao banco, mas não consegue criar e renomear arquivos privados em storage/. Ajuste as permissões da hospedagem antes de instalar.');
                }
                $_SESSION['iguanews_setup_db']=$candidate+['verified_at'=>time(),'mode'=>$mode];
                redirect('/setup.php?step=3');
            } catch (Throwable $e) {
                if ($action==='auto_db') {
                    $errors[]=($e instanceof InvalidArgumentException || ($e instanceof RuntimeException && !($e instanceof PDOException))) ? $e->getMessage() : 'Não foi possível preparar o banco automaticamente.';
                    $notices[]=[
                        'Confira se o MariaDB está iniciado, se o usuário administrativo conecta e possui CREATE DATABASE, CREATE USER e GRANT.',
                        $runtime['kind']==='termux' ? 'No Termux, experimente: mariadb -u root e confirme se o acesso local funciona.' : 'Na hospedagem, cadastre o banco e usuário no painel da conta.',
                        'Se o banco já existe, marque a opção de reaproveitar um banco vazio. Bancos com dados nunca são sobrescritos.',
                        'A criação automática requer acesso ao site pelo próprio celular (127.0.0.1) e MariaDB local. Hospedagens normalmente exigem criar banco e usuário pelo painel.'
                    ];
                } elseif ($e instanceof InvalidArgumentException || $e instanceof DomainException) {
                    $errors[]=$e->getMessage();
                } elseif ($e instanceof RuntimeException && str_contains($e->getMessage(),'banco contém')) {
                    $errors[]=$e->getMessage();
                } else {
                    $diagnosis=igua_db_diagnosis($e,$host,$port,$database);
                    $errors[]=$diagnosis[0];
                    $notices[]=array_slice($diagnosis,1);
                }
            }
        }
    } elseif ($action==='install_fresh' || $action==='install_restore' || $action==='install_migration' || $action==='recover_config' || $action==='reset_existing') {
        $step=3;
        if (!$setupData) { $errors[]='A conexão expirou. Faça o teste novamente.'; $step=2; }
        else {
            $mode=(string)($setupData['mode']??'fresh');
            $resetting=$action==='reset_existing';
            $recovery=$action==='recover_config' || $resetting;
            $pendingConfig=null;
            $migrationSchemaOwned=false;
            $migrationCleanupPending=false;
            $phase='validation';
            $lock=null;
            try {
                if($hasEmbeddedMigration && !$recovery && $action!=='install_migration')
                    throw new DomainException('Este pacote contém um backup portátil. Use Migrar meu portal e confirme o administrador antigo. Para instalação limpa, retire o pacote privado do servidor e comece com um diretório vazio.');
                if (($mode==='recover')!==$recovery) throw new DomainException('O modo de instalação mudou. Verifique novamente o banco na etapa 2.');
                if ($runtime['kind']==='hosting' && !$runtime['https']) throw new DomainException('Ative HTTPS antes de enviar senhas administrativas.');
                if (!igua_setup_config_write_probe()) throw new DomainException('Não foi possível criar e renomear arquivos privados em storage/. Verifique permissões e open_basedir.');
                if ($recovery) {
                    $email=trim((string)($_POST['recovery_email']??''));
                    $pass=(string)($_POST['recovery_password']??'');
                    if (($_SESSION['igua_recovery_errors']??0)>=5) throw new DomainException('Muitas tentativas de recuperação nesta sessão. Faça novo teste do banco mais tarde.');
                } elseif ($action==='install_migration') {
                    if($setupMode!=='migrate')throw new DomainException('Escolha Migrar portal no início do instalador.');
                    if(!class_exists('ZipArchive'))throw new DomainException('É necessário habilitar a extensão PHP zip na nova hospedagem.');
                    if(empty($_POST['confirm_migration']))throw new DomainException('Confirme a migração do backup do portal antigo.');
                    if((int)($_SESSION['igua_migration_auth_failures']??0)>=5)throw new DomainException('Muitas tentativas de autenticação. Encerre a sessão e verifique o backup antes de tentar novamente.');
                    $payload=igua_migration_payload();
                    if($payload===null)throw new DomainException('Pacote de migração não encontrado. Extraia o ZIP completo na raiz do novo projeto.');
                    $migrationEmail=trim((string)($_POST['migration_email']??''));
                    $migrationPassword=(string)($_POST['migration_password']??'');
                    if(!igua_migration_authorized($payload,$migrationEmail,$migrationPassword)){
                        $_SESSION['igua_migration_auth_failures']=(int)($_SESSION['igua_migration_auth_failures']??0)+1;
                        throw new DomainException('Não foi possível autenticar o administrador da instalação anterior.');
                    }
                    unset($_SESSION['igua_migration_auth_failures']);
                    $migrationCheck=igua_migration_preflight($payload); // ZIP, mídias e dados íntegros antes de criar tabelas.
                    igua_migration_capacity_check($payload,$migrationCheck);
                    $migrationSiteUrl=igua_migration_site_url((string)($_POST['migration_site_url']??''));
                } elseif ($action==='install_restore') {
                    $uploadToken=$_POST['upload_token']??'';
                    if (!is_string($uploadToken)) throw new DomainException('Identificador do backup inválido.');
                    $import=$uploadToken!=='' ? igua_setup_read_staged_backup($uploadToken) : igua_read_import($_FILES['backup']??[]);
                } else {
                    $siteName=igua_validate_brand_name((string)($_POST['site_name']??''));
                    $siteTagline=trim((string)($_POST['site_tagline']??''));
                    $siteDescription=trim((string)($_POST['seo_default_description']??''));
                    $siteCity=trim((string)($_POST['city']??''));
                    if (ulen($siteTagline)>200 || ulen($siteDescription)<15 || ulen($siteDescription)>320 || ulen($siteCity)>100) throw new InvalidArgumentException('Confira o nome (2 a 80 caracteres), a descrição (15 a 320 caracteres) e os demais dados do portal.');
                    $name=trim((string)($_POST['name']??''));
                    $email=trim((string)($_POST['email']??''));
                    $pass=(string)($_POST['portal_password']??'');
                    if (ulen($name)<2 || !filter_var($email,FILTER_VALIDATE_EMAIL) || strlen($pass)<10) {
                        throw new InvalidArgumentException('Informe nome, e-mail válido e senha com pelo menos 10 caracteres.');
                    }
                }
                $phase='lock';
                $lock=@fopen(IGUA_STORAGE.'/.setup.lock','c');
                if (!$lock || !flock($lock,LOCK_EX)) throw new RuntimeException('Não foi possível obter o bloqueio de instalação.');
                if (installed()) redirect('/admin.php?action=login');
                $phase='database';
                $db=connection($setupData);
                $inventory=igua_setup_database_inventory($db);
                if ($recovery) {
                    if ($inventory['status']!=='recoverable' || !igua_setup_recovery_allowed($db)) throw new DomainException('O banco não possui uma instalação completa e recuperável desta versão. Preserve o banco e utilize um banco novo para testes.');
                    if (!igua_setup_verify_recovery_admin($db,$email,$pass)) {
                        $_SESSION['igua_recovery_errors']=(int)($_SESSION['igua_recovery_errors']??0)+1;
                        throw new DomainException('Não foi possível confirmar o administrador existente. Confira e-mail e senha do painel.');
                    }
                    if ($resetting) {
                        if (!hash_equals('REINSTALAR',trim((string)($_POST['confirm_reset']??''))))
                            throw new DomainException('Digite REINSTALAR exatamente antes de limpar um banco existente.');
                        $phase='backup';
                        $backup=make_backup($db,'manual');
                        $phase='reset';
                        igua_content_reset_schema($db,IGUA_STORAGE,false);
                        // A estrutura fica vazia; as credenciais serão informadas novamente no passo 2.
                        unset($_SESSION['iguanews_setup_db'],$_SESSION['igua_recovery_errors']);
                        session_regenerate_id(true);
                        if (is_resource($lock)) { flock($lock,LOCK_UN); fclose($lock); $lock=null; }
                        redirect('/setup.php?step=2');
                    }
                    // Recuperação comum nunca modifica uma tabela.
                    $phase='config';
                    igua_store_config($setupData);
                } else {
                    if($action==='install_migration' && $inventory['status']!=='empty')throw new DomainException('Migração exige um banco novo completamente vazio.');
                    if (!in_array($inventory['status'],['empty','partial_empty'],true)) throw new DomainException($inventory['detail']);
                    // O arquivo com credenciais é preparado ANTES de criar tabelas, mas não ativado ainda.
                    $phase='config_stage';
                    $pendingConfig=igua_stage_config($setupData);
                    $phase='schema';
                    $migrationSchemaOwned=($action==='install_migration');
                    install_schema($db,!$migrationSchemaOwned); // Não registra revisão antes da restauração portátil.
                    if ($action==='install_restore' || $action==='install_migration') {
                        $phase='restore';
                        if($action==='install_migration'){
                            igua_migration_restore($db,$payload);
                            igua_migration_update_site_url($db,$migrationSiteUrl);
                            // Para banco antigo v12, confirma o schema atual ao concluir os dados.
                            igua_record_schema_revision($db);
                        }
                        else restore_backup($db,$import);
                    } else {
                        $phase='admin';
                        if ((int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn()!==0) throw new RuntimeException('O banco já possui usuários.');
                        $db->beginTransaction();
                        $q=$db->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
                        $q->execute([$name,$email,password_hash($pass,PASSWORD_DEFAULT)]);
                        $uid=(int)$db->lastInsertId();
                        $s=$db->prepare('INSERT INTO settings (name,value) VALUES (?,?)');
                        foreach (['site_name'=>$siteName,'site_tagline'=>$siteTagline,'seo_default_description'=>$siteDescription,'seo_title_template'=>'%title% | %site%','city'=>$siteCity,'contact_email'=>$email] as $k=>$v) $s->execute([$k,$v]);
                        // As editorias são estruturais: existem mesmo sem notícias fictícias.
                        seed_initial_categories($db);
                        if (!empty($_POST['demo'])) seed_demo($db,$uid);
                        $db->commit();
                    }
                    $phase='config';
                    igua_activate_config($pendingConfig);
                    $pendingConfig=null;
                    if($action==='install_migration'){
                        try{$migrationCleanupPending=!igua_migration_cleanup($payload);}
                        catch(Throwable){$migrationCleanupPending=true;}
                        if($migrationCleanupPending)error_log('[Meu Site setup] cleanup_pending=1');
                    }
                    $migrationSchemaOwned=false;
                }
                $phase='complete';
                igua_setup_clear_chunk();
                unset($_SESSION['iguanews_setup_db'],$_SESSION['igua_recovery_errors']);
                session_regenerate_id(true);
                if (is_resource($lock)) { flock($lock,LOCK_UN); fclose($lock); $lock=null; }
                redirect('/admin.php?action=login&'.($recovery?'recovered=1':(in_array($action,['install_restore','install_migration'],true)?'restored=1':'installed=1')).($migrationCleanupPending?'&cleanup_pending=1':''));
            } catch (Throwable $e) {
                if (isset($db) && $db instanceof PDO && $db->inTransaction()) $db->rollBack();
                if($migrationSchemaOwned && in_array($phase,['schema','restore'],true) && isset($db) && $db instanceof PDO){
                    try{if(igua_migration_rollback_empty_schema($db))$notices[]=['O esquema criado nesta tentativa foi revertido porque permaneceu vazio.'];}
                    catch(Throwable){error_log('[Meu Site setup] rollback_schema_failed=1');}
                }
                // Nunca registrar senha, SQL ou texto completo de exceções (podem conter segredos).
                error_log('[Meu Site setup] etapa='.$phase.'; tipo='.get_class($e).'; codigo='.(is_int($e->getCode())?$e->getCode():0));
                if ($e instanceof DomainException) $errors[]=$e->getMessage();
                elseif ($e instanceof InvalidArgumentException || (isset($import) && $e instanceof JsonException)) $errors[]='Os dados informados ou o backup são inválidos.';
                else {
                    $errors[]=match($phase) {
                        'config_stage','config' => 'O banco pode conter dados da instalação, mas não foi possível salvar a configuração privada. Confira storage/, open_basedir e permissões e depois use Recuperar instalação (não reinstale sobre os dados).',
                        'schema' => 'Falha ao preparar as tabelas. Confira permissões CREATE/ALTER/INDEX do MySQL e o log PHP. Não apague tabelas antes de verificar o inventário.',
                        'admin','restore' => 'Falha ao restaurar banco ou mídias. Confira logs, espaço em disco e permissões; NÃO tente instalar sobre um banco parcialmente preenchido. Use um banco novo e mantenha o ZIP original.',
                        default => 'Instalação não concluída. Consulte o log PHP pela etapa indicada, sem compartilhar senhas.'
                    };
                }
                $notices[]=['Etapa técnica: '.$phase.'. Consulte os logs PHP do Plesk para identificar a falha. Nenhuma tabela é excluída automaticamente.',
                    'Na nova verificação do banco, instalações completas sem config.php podem ser recuperadas mediante autenticação do administrador.',
                    'Para começar realmente do zero, use outro banco vazio; preserve primeiro o backup do banco anterior.'];
            } finally {
                if (is_string($pendingConfig) && is_file($pendingConfig)) @unlink($pendingConfig);
                if (is_resource($lock)) { @flock($lock,LOCK_UN); fclose($lock); }
            }
        }
    } else { $errors[]='Ação inválida.'; $step=1; }
}
$requiredCount=count(array_filter($requirements,static fn($r)=>$r['required']));
$passedCount=count(array_filter($requirements,static fn($r)=>$r['required']&&$r['ok']));
$optionalWarnings=count(array_filter($requirements,static fn($r)=>!$r['required']&&!$r['ok']));
$importLimit=igua_upload_limits_diagnosis((string)ini_get('upload_max_filesize'),(string)ini_get('post_max_size'));
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#f5f7fc"><title>Instalação | Meu Site</title><link rel="stylesheet" href="/assets/css/style.css"></head>
<body class="setup-body"><main class="setup-shell setup-wizard"><div class="setup-brand"><span class="brand-mark">◒</span><span><strong>Meu Site</strong><small>Assistente de instalação · versão <?=h(igua_brand_version())?></small></span></div>
<nav class="wizard-steps" aria-label="Etapas de instalação"><span class="<?= $step===1?'current':($step>1?'complete':'') ?>"><b>1</b> Requisitos</span><span class="<?= $step===2?'current':($step>2?'complete':'') ?>"><b>2</b> Banco</span><span class="<?= $step===3?'current':'' ?>"><b>3</b> Conteúdo</span></nav>
<?php if ($step===1): ?>
  <h1>Vamos verificar o ambiente.</h1><p class="muted">Antes de instalar, o sistema examina o PHP, as extensões, a raiz web e as permissões. Os itens obrigatórios precisam estar aprovados.</p>
  <div class="setup-diagnosis"><strong>Ambiente identificado: <?= h($runtime['label']) ?></strong><p class="muted">O perfil usa o PHP do servidor, não o nome do navegador. <?= $runtime['kind']==='termux' ? 'Modo local: MariaDB do aparelho e servidor limitado a 127.0.0.1.' : 'Use um banco MySQL/MariaDB autorizado e mantenha app/ e storage/ fora do alcance público.' ?></p></div>
  <div class="requirements-summary"><strong><?= $passedCount ?>/<?= $requiredCount ?> requisitos obrigatórios aprovados</strong><span><?= $optionalWarnings?'Há '.$optionalWarnings.' melhoria(s) opcional(is)':'Todos os recursos opcionais disponíveis' ?></span></div>
  <div class="requirements-list"><?php foreach ($requirements as $req): ?><section class="requirement-item <?= $req['ok']?'passed':($req['required']?'failed':'optional') ?>">
    <span class="requirement-icon" aria-hidden="true"><?= $req['ok']?'✓':($req['required']?'!':'i') ?></span><div><h2><?= h($req['title']) ?><?= !$req['required']?' <small>Opcional</small>':'' ?></h2><p><?= h($req['current']) ?></p>
    <?php if (!$req['ok']): ?><div class="requirement-fix"><strong>Como corrigir:</strong> <?= h($req['fix']) ?></div><?php endif; ?></div></section><?php endforeach; ?></div>
  <?php if (!$ready): ?><div class="alert error">Corrija os requisitos obrigatórios e verifique novamente.</div><?php else: ?><div class="alert setup-success">Ambiente aprovado! Escolha como preparar o banco de dados.</div><?php endif; ?>
  <div class="wizard-actions"><a class="btn outline" href="/setup.php?step=1">↻ Verificar novamente</a></div>
  <?php if($ready): ?><div class="setup-install-choice"><h2>Como deseja instalar?</h2>
   <a class="setup-choice-card" href="/setup.php?step=2&amp;mode=fresh"><strong>＋ Instalação limpa</strong><span>Instalar um portal novo. Continua com o procedimento atual.</span></a>
   <a class="setup-choice-card" href="/setup.php?step=2&amp;mode=migrate"><strong>⇧ Migrar meu portal</strong><span>Restaurar banco, notícias, contas, imagens e anexos de um pacote portátil.</span></a>
  </div><?php endif; ?>
<?php elseif ($step===2): ?>
  <h1><?= $setupMode==='migrate'?'Migração: novo banco de dados':'Prepare o banco de dados' ?>.</h1><p class="muted"><?= $autoAvailable ? 'Termux detectado: você pode criar uma base MariaDB local, ou usar credenciais existentes.' : 'Ambiente de hospedagem ou desenvolvimento: crie uma base vazia no painel/servidor MySQL e informe os dados de conexão. A criação privilegiada de usuários está desativada.' ?></p>
  <?php foreach ($errors as $err): ?><div class="alert error" role="alert"><?= h($err) ?></div><?php endforeach; ?>
  <?php foreach ($notices as $notice): ?><div class="setup-diagnosis"><strong>O que verificar</strong><ul><?php foreach($notice as $item): ?><li><?= h($item) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?>
  <div class="setup-route-list">
    <?php if ($autoAvailable): ?>
    <details class="setup-route" open><summary><span class="setup-route-symbol">✦</span><span><strong>Criar automaticamente</strong><small>Ideal para MariaDB local no Termux</small></span><span class="route-chevron">⌄</span></summary>
      <div class="route-detail"><p class="muted">Usa temporariamente um administrador MariaDB para criar <strong>o banco e um usuário com senha gerada</strong>. Se o banco já existir e estiver vazio, poderá ser reaproveitado sem apagar dados. Não guarda a senha administrativa. Disponível somente ao abrir o instalador em 127.0.0.1 no próprio aparelho.</p>
      <form class="setup-form" method="post"><?= csrf_field() ?><input type="hidden" name="action" value="auto_db"><div class="form-grid">
        <label>Nome do novo banco<input name="database" required maxlength="64" pattern="[A-Za-z][A-Za-z0-9_]*" value="<?= h($_POST['database']??$databaseDefault) ?>"></label>
        <label>Porta MariaDB<input name="port" type="number" min="1" max="65535" required value="<?= h($_POST['port']??3306) ?>"></label>
        <label>Host administrativo<input name="admin_host" required value="<?= h($_POST['admin_host']??'localhost') ?>" autocomplete="off"></label>
        <label>Usuário administrativo MariaDB<input name="admin_username" required value="<?= h($_POST['admin_username']??'root') ?>" autocomplete="off"></label>
        <label class="full">Senha administrativa do MariaDB<input name="admin_password_db" type="password" placeholder="Deixe vazia somente se seu MariaDB permitir" autocomplete="off"></label>
      </div><label class="checkbox-line"><input name="reuse_empty" type="checkbox" value="1" checked><span>Se o banco informado já existir, reaproveitar somente se estiver vazio, sem excluir dados.</span></label><div class="wizard-actions"><button class="btn primary" type="submit">Preparar banco e usuário →</button></div></form>
      <p class="muted tiny">Se o root usa autenticação por socket ou não tem permissão, utilize a conexão manual. O MariaDB precisa estar iniciado; o instalador não inicia serviços do Android.</p></div>
    </details>
    <?php endif; ?>
    <details class="setup-route" <?= !$autoAvailable ? 'open' : '' ?>><summary><span class="setup-route-symbol route-secondary">⇄</span><span><strong>Usar banco existente</strong><small><?= $autoAvailable ? 'Para banco já preparado no Termux' : 'Recomendado na hospedagem e cPanel' ?></small></span><span class="route-chevron">⌄</span></summary>
      <div class="route-detail"><p class="muted">Informe um banco vazio com usuário que tenha permissões para criar tabelas. Este modo apenas testa a conexão, sem alterar dados.</p>
      <form method="post" class="setup-form"><?= csrf_field() ?><input type="hidden" name="action" value="test_db"><div class="form-grid">
        <label>Servidor<input name="host" required value="<?= h($_POST['host']??$hostDefault) ?>" autocomplete="off"></label>
        <label>Porta<input name="port" type="number" min="1" max="65535" required value="<?= h($_POST['port']??3306) ?>"></label>
        <label>Nome do banco<input name="database" required value="<?= h($_POST['database']??$databaseDefault) ?>" maxlength="64"></label>
        <label>Usuário MySQL<input name="username" required value="<?= h($_POST['username']??($autoAvailable ? 'meu_site' : '')) ?>" autocomplete="off"></label>
        <label class="full">Senha MySQL<input name="password" type="password" autocomplete="off" placeholder="Senha do usuário do banco"></label>
      </div><div class="wizard-actions"><button class="btn primary" type="submit">Testar conexão →</button></div></form></div>
    </details>
  </div>
  <div class="wizard-actions"><a href="/setup.php?step=1" class="btn outline">← Requisitos</a></div>
<?php elseif (($setupData['mode']??'fresh')==='recover'): ?>
  <h1>Recuperar instalação anterior</h1>
  <div class="alert warning" role="status">O banco já possui tabelas e dados deste portal. A recuperação não criará nem duplicará tabelas, não apagará notícias e não redefinirá a senha do administrador. Ela somente recria a configuração privada ausente.</div>
  <?php foreach ($errors as $err): ?><div class="alert error" role="alert"><?= h($err) ?></div><?php endforeach; ?>
  <?php foreach ($notices as $notice): ?><div class="setup-diagnosis"><ul><?php foreach ($notice as $item): ?><li><?= h($item) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?>
  <p>Confirme a identidade com o <strong>mesmo e-mail e senha do painel</strong> definidos na tentativa anterior. A senha do banco permanece na sessão apenas durante este assistente.</p>
  <form method="post" class="setup-form"><?= csrf_field() ?><input type="hidden" name="action" value="recover_config">
    <div class="form-grid"><label>E-mail do administrador<input type="email" name="recovery_email" required autocomplete="username"></label>
    <label>Senha do administrador<input type="password" name="recovery_password" required autocomplete="current-password"></label></div>
    <div class="wizard-actions"><button class="btn primary" type="submit">Verificar e recuperar configuração →</button></div>
  </form>
  <details class="setup-route"><summary><strong>Reiniciar este banco para uma instalação Alpha</strong></summary><div class="route-detail">
    <p class="muted">Somente para testes: exige administrador anterior, salva um backup lógico e remove as 25 tabelas reconhecidas, sem tocar nos arquivos de mídia nem nas fontes de outros sistemas. A exclusão SQL não pode ser revertida automaticamente. O instalador será reiniciado para você escolher um portal limpo ou demonstrativo.</p>
    <form method="post" class="setup-form"><?= csrf_field() ?><input type="hidden" name="action" value="reset_existing">
      <label>E-mail administrativo anterior<input name="recovery_email" type="email" required autocomplete="username"></label>
      <label>Senha administrativa anterior<input name="recovery_password" type="password" required autocomplete="current-password"></label>
      <label>Digite REINSTALAR<input name="confirm_reset" required autocomplete="off" placeholder="REINSTALAR"></label>
      <button class="btn outline" type="submit">Fazer backup e reiniciar o banco →</button>
    </form>
  </div></details>
  <div class="wizard-actions"><a href="/setup.php?step=2" class="btn outline">← Verificar outro banco</a></div>
<?php else: ?>
  <h1><?= $setupMode==='migrate'?'Restaurar portal antigo':'Instalação limpa' ?></h1><p class="muted">O banco <strong><?= h($setupData['database']??'') ?></strong> está pronto. Instale o portal com um novo administrador ou importe uma instalação anterior.</p>
  <div class="alert setup-success">✓ Conexão testada. Seus dados existentes não serão sobrescritos.</div>
  <?php if ($runtime['kind']==='hosting' && !$runtime['https']): ?><div class="alert warning" role="alert">⚠ Esta requisição não foi identificada como HTTPS. Antes de enviar senhas administrativas, ative HTTPS no domínio e confira a configuração do proxy com o provedor. Cabeçalhos enviados pelo visitante não são aceitos como prova de TLS.</div><?php endif; ?>
  <?php foreach($errors as $err): ?><div class="alert error" role="alert"><?= h($err) ?></div><?php endforeach; ?>
  <?php foreach($notices as $notice): ?><div class="setup-diagnosis"><ul><?php foreach($notice as $item): ?><li><?= h($item) ?></li><?php endforeach; ?></ul></div><?php endforeach; ?>
  <?php if($setupMode==='migrate'): ?>
   <div class="setup-migration-card">
    <h2>📦 Backup de migração</h2>
    <?php if($hasEmbeddedMigration): ?>
     <p>O instalador encontrou o backup incluído no pacote portátil. Clique para validar arquivos e restaurar os dados no <strong>banco novo vazio</strong>.</p>
     <form method="post" class="setup-form"><?=csrf_field()?><input type="hidden" name="action" value="install_migration">
      <p class="muted">Para impedir que terceiros importem seu backup antes da instalação, confirme o e-mail e a senha de um administrador do portal antigo.</p>
      <div class="form-grid"><label>E-mail do administrador antigo<input name="migration_email" type="email" required autocomplete="username"></label>
      <label>Senha do administrador antigo<input name="migration_password" type="password" required autocomplete="current-password"></label></div>
      <label>Endereço do novo domínio (opcional, se mudou)<input name="migration_site_url" type="url" maxlength="240" placeholder="https://novodominio.com.br" autocomplete="url"><small>Se mantiver o domínio atual, deixe em branco. Após migrar, revise DNS, SEO e integrações.</small></label>
      <label class="checkbox-line"><input type="checkbox" name="confirm_migration" value="1" required> Confirmo que o banco novo está vazio e quero restaurar o portal antigo.</label>
      <div class="wizard-actions"><button class="btn primary" type="submit">Validar e migrar portal →</button></div>
     </form>
    <?php else: ?><div class="alert warning">Nenhum pacote de migração embutido foi encontrado em <code>storage/migration/</code>. Para transferir tudo, gere o ZIP portátil na instalação antiga e extraia-o inteiro na raiz do projeto novo. Você ainda pode importar somente o banco JSON/GZIP abaixo.</div><?php endif; ?>
   </div>
  <?php endif; ?>
  <div class="setup-route-list">
    <?php if($setupMode==='fresh'): ?><details class="setup-route" open><summary><span class="setup-route-symbol">＋</span><span><strong>Começar um portal novo</strong><small>Personalize o portal e configure seu acesso</small></span><span class="route-chevron">⌄</span></summary>
      <div class="route-detail"><form method="post" class="setup-form" data-setup-identity><?= csrf_field() ?><input type="hidden" name="action" value="install_fresh">
      <section class="ig-identity-editor" aria-label="Identidade do portal"><div class="ig-identity-heading"><span aria-hidden="true">✦</span><div><h2>Seu portal, sua identidade</h2><p>Escolha o nome que aparecerá nas páginas, no painel, no rodapé e nas prévias de compartilhamento.</p></div></div>
      <div class="form-grid"><label class="full">Nome do portal<input name="site_name" maxlength="80" minlength="2" required placeholder="Ex.: Tribuna ou Viajando por Aí" value="<?=h($_POST['site_name']??'Meu Site')?>"></label>
      <label class="full">Frase de apresentação<input name="site_tagline" maxlength="200" placeholder="Ex.: Histórias e notícias pelo caminho" value="<?=h($_POST['site_tagline']??'Informação que acompanha você.')?>"></label>
      <label class="full">Descrição do site para buscas<textarea name="seo_default_description" minlength="15" maxlength="320" rows="3" required placeholder="Explique sobre o que é seu portal, para quem ele existe e o que publica."><?=h($_POST['seo_default_description']??'Notícias, reportagens e informações sobre a nossa região.')?></textarea><small class="ig-input-counter" data-description-count></small></label>
      <label class="full">Cidade ou região (opcional)<input name="city" maxlength="100" placeholder="Ex.: Iguatama - MG" value="<?=h($_POST['city']??'Iguatama - MG')?>"></label></div>
      <div class="ig-identity-preview" aria-live="polite"><span>PRÉ-VISUALIZAÇÃO DO SITE</span><h3 data-site-preview>Seu portal</h3><p data-tagline-preview></p><small data-description-preview></small></div></section>
      <h2 class="ig-account-heading">Acesso administrativo</h2><div class="form-grid">
        <label>Seu nome<input name="name" minlength="2" required value="<?= h($_POST['name']??'') ?>" autocomplete="name"></label>
        <label>E-mail<input name="email" type="email" required value="<?= h($_POST['email']??'') ?>" autocomplete="email"></label>
        <label class="full">Senha do painel (mínimo 10 caracteres)<input name="portal_password" type="password" minlength="10" required autocomplete="new-password"></label>
      </div><label class="checkbox-line"><input type="checkbox" name="demo" value="1" <?= !empty($_POST['demo'])?'checked':'' ?>><span>Adicionar 100 notícias de <strong>demonstração</strong> para testar o layout (opcional; o portal é instalado vazio por padrão).</span></label>
      <div class="wizard-actions"><button class="btn primary" type="submit">Instalar novo portal →</button></div></form></div>
    </details>
    <?php endif; ?><details class="setup-route"><summary><span class="setup-route-symbol route-secondary">⇧</span><span><strong>Importar somente banco (legado)</strong><small>Alternativa JSON/GZIP sem fotos</small></span><span class="route-chevron">⌄</span></summary>
      <div class="route-detail"><p class="muted">Escolha um backup <strong>.json ou .json.gz</strong> gerado na área de Backups da instalação anterior. Notícias, categorias, configurações e contas serão restauradas. O login seguirá sendo o da instalação anterior.</p>
      <?php if (!$importLimit['ok']): ?><div class="alert warning" role="status">Limite de upload direto: <?= h($importLimit['detail']) ?> <?php if (igua_setup_chunk_enabled()): ?>O instalador enviará automaticamente o backup em partes menores, sem precisar mudar o PHP. Mantenha esta página aberta durante a transferência.<?php else: ?>Envio segmentado indisponível: verifique file_uploads, upload_max_filesize e post_max_size.<?php endif; ?></div><?php endif; ?>
      <form method="post" enctype="multipart/form-data" class="setup-form"><?= csrf_field() ?><input type="hidden" name="action" value="install_restore">
        <label>Backup compatível (máximo 15 MiB)<input type="file" name="backup" accept=".json,.gz" required data-backup-import data-server-limit="<?= (int)$importLimit['max_bytes'] ?>" data-chunk-size="<?= igua_setup_chunk_enabled() ? igua_setup_chunk_size((string)ini_get('upload_max_filesize'),(string)ini_get('post_max_size')) : 0 ?>"></label>
        <small class="muted" id="backup-upload-hint" role="status" aria-live="polite">Limite do backup: 15 MiB. O envio será adaptado automaticamente aos limites do PHP quando o navegador permitir.</small>
        <div class="alert warning">As fotos enviadas não estão no backup do banco. Copie <code>public/uploads/</code> separadamente para o novo servidor. Não aceitamos dumps SQL arbitrários.</div>
        <div class="wizard-actions"><button type="submit" class="btn primary">Validar e importar backup →</button></div>
      </form></div>
    </details>
  </div>
  <div class="wizard-actions"><a href="/setup.php?step=2" class="btn outline">← Banco de dados</a> <a href="/setup.php?step=1" class="btn outline">Alterar modo de instalação</a></div>
<?php endif; ?>
<p class="setup-trust"><span>🔒</span> Senhas nunca aparecem na URL. Configurações são salvas fora da pasta pública. Após concluir, este assistente fica bloqueado.</p>
<?php igua_developer_footer(); ?></main><script src="/assets/js/file-inputs.js" defer></script><script src="/assets/js/setup-identity.js" defer></script><script src="/assets/js/setup-backup-upload.js" defer></script></body></html>
