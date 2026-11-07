<?php
declare(strict_types=1);
/** Executar após restore_mariadb_134.php, somente em banco novo e exclusivo igua_ci_*. */
$name=(string)(getenv('IGUA_TEST_DB_NAME')?:'');
if(PHP_SAPI!=='cli' || getenv('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB' ||
   !preg_match('/^igua_ci_[a-z0-9_]{3,40}$/D',$name) ||
   !in_array((string)getenv('IGUA_TEST_DB_HOST'),['localhost','127.0.0.1','::1'],true) ||
   !extension_loaded('pdo_mysql')){
    fwrite(STDERR,"Teste eleitoral bloqueado: exige MariaDB isolado e confirmação explícita.\n");exit(2);
}
require_once __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/schema.php';
require_once __DIR__.'/../../app/schema_migrations.php';
require_once __DIR__.'/../../app/backup.php';
require_once __DIR__.'/../../app/elections/service.php';
require_once __DIR__.'/../../app/elections/repository.php';
$db=connection(['host'=>getenv('IGUA_TEST_DB_HOST'),'port'=>getenv('IGUA_TEST_DB_PORT')?:3306,
  'database'=>$name,'username'=>getenv('IGUA_TEST_DB_USER'),'password'=>getenv('IGUA_TEST_DB_PASSWORD')]);
function e141_mysql(bool $condition,string $description):void {if(!$condition)throw new RuntimeException('Falhou MariaDB eleitoral: '.$description);echo "OK MariaDB: $description\n";}
e141_mysql(igua_schema_revision($db)===IGUA_SCHEMA_REVISION,'esquema atual registrado');
foreach(['elections','election_candidates'] as $table){$db->query('SELECT * FROM `'.$table.'` LIMIT 0')->closeCursor();}
$base=['title'=>'Pleito CI — somente teste','election_year'=>'2024','election_scope'=>'municipal','office'=>'mayor',
 'round_number'=>'1','state_code'=>'MG','municipality'=>'Iguatama','municipality_ibge'=>'','election_date'=>'2024-10-06','source_url'=>'https://www.tse.jus.br/'];
$id=0;$backupPath=null;
try {
    $id=meu_election_save($db,$base,0);
    e141_mysql((int)(meu_election_find($db,$id)['election_year']??0)===2024,'pleito criado com PDO preparado');
    $cid=meu_candidate_save($db,$id,['full_name'=>'Candidato de teste CI','ballot_name'=>'Teste CI','candidate_number'=>'12345','party_abbr'=>'TEST','party_name'=>'Partido fictício'],0);
    e141_mysql(count(meu_candidates_list($db,$id))===1,'candidatura inserida e relacionada');
    $duplicate=false;
    try{meu_candidate_save($db,$id,['full_name'=>'Outro teste','ballot_name'=>'Outro','candidate_number'=>'12345','party_abbr'=>'X','party_name'=>''],0);}
    catch(PDOException $e){$duplicate=$e->getCode()==='23000';}
    e141_mysql($duplicate,'número duplicado barrado pelo índice real');
    meu_candidate_save($db,$id,['full_name'=>'Candidato de teste CI','ballot_name'=>'Teste alterado','candidate_number'=>'12345','party_abbr'=>'TEST','party_name'=>'Partido fictício'],$cid);
    e141_mysql(meu_candidates_list($db,$id)[0]['ballot_name']==='Teste alterado','edição preserva FK');
    $backup=make_backup($db);$backupPath=backup_path($backup);
    $original=parse_backup((string)file_get_contents($backupPath),$backup);
    e141_mysql($original['version']===12 && count($original['tables']['elections'])>=1 && count($original['tables']['election_candidates'])>=1,'backup v12 inclui ambas as tabelas');
    $db->prepare('DELETE FROM election_candidates WHERE id=?')->execute([$cid]);
    restore_backup($db,$original);
    e141_mysql(count(meu_candidates_list($db,$id))===1,'restauração recupera candidatura e eleição');
} finally {
    if($id>0)$db->prepare('DELETE FROM elections WHERE id=?')->execute([$id]);
    if(is_string($backupPath) && is_file($backupPath))@unlink($backupPath);
}
echo "Integração eleitoral concluída em banco isolado.\n";
