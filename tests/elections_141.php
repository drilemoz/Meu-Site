<?php
declare(strict_types=1);
require_once __DIR__ . '/support/entry_view_source.php';
require_once __DIR__.'/../app/bootstrap.php';
require_once __DIR__.'/../app/editorial.php';
require_once __DIR__.'/../app/backup.php';
require_once __DIR__.'/../app/elections/service.php';
require_once __DIR__.'/../app/elections/schema.php';
require_once __DIR__.'/../app/admin_controllers/post_routes.php';
function e141(bool $yes,string $description):void {if(!$yes)throw new RuntimeException('Falha: '.$description);echo "OK: $description\n";}
function e141_reject(callable $cb,string $description):void {
    try {$cb();}catch(InvalidArgumentException $e){e141(true,$description);return;}
    throw new RuntimeException('Falha: '.$description);
}
class Election141Db extends PDO {
    public array $sql=[];
    public function __construct(){}
    public function exec(string $statement):int|false {$this->sql[]=$statement;return 0;}
}
$db=new Election141Db();meu_elections_schema($db);
e141(count($db->sql)===2 && str_contains($db->sql[0],'CREATE TABLE IF NOT EXISTS elections') && str_contains($db->sql[1],'UNIQUE KEY uq_election_number') && str_contains($db->sql[1],'FOREIGN KEY'),'tabelas InnoDB, FK e número único por eleição');
$e=['title'=>'Eleição municipal teste','election_year'=>'2024','election_scope'=>'municipal','office'=>'mayor','round_number'=>'1','state_code'=>'mg','municipality'=>'Iguatama','municipality_ibge'=>'3130309','election_date'=>'2024-10-06','source_url'=>'https://www.tse.jus.br/'];
$d=meu_election_validate($e);
e141($d['uf']==='MG' && $d['round']===1 && $d['city']==='Iguatama','município e UF normalizados');
e141(meu_election_validate([...$e,'title'=>'Viajando por Aí'])['title']==='Viajando por Aí','títulos com acentos');
foreach([
    [...$e,'election_year'=>'2026','election_date'=>'2024-10-06'],
    [...$e,'office'=>'president'],
    [...$e,'round_number'=>'3'],
    [...$e,'office'=>'councilor','round_number'=>'2'],
    [...$e,'state_code'=>'XX'],
    [...$e,'municipality_ibge'=>'not-ibge'],
    [...$e,'source_url'=>'http://example.com'],
    [...$e,'source_url'=>'https://user:pass@example.com'],
    [...$e,'municipality'=>''],
    [...$e,'title'=>str_repeat('x',181)]
] as $bad){e141_reject(fn()=>meu_election_validate($bad),'dados eleitorais inválidos recusados');}
$c=meu_candidate_validate(['full_name'=>'João da Silva','ballot_name'=>'João','candidate_number'=>'01234','party_abbr'=>'ABC','party_name'=>'Partido de exemplo']);
e141($c['number']==='01234' && $c['ballot']==='João','nome e número textual sem perder zeros');
e141_reject(fn()=>meu_candidate_validate(['full_name'=>'J','ballot_name'=>'João','candidate_number'=>'123']),'nome do candidato obrigatório');
e141_reject(fn()=>meu_candidate_validate(['full_name'=>'Maria','ballot_name'=>'Maria','candidate_number'=>'1 OR 1=1']),'número precisa ser numérico');
e141(!role_allowed('editor','manage_settings')&&!role_allowed('author','manage_settings')&&role_allowed('admin','manage_settings'),'administração restrita');
foreach(['save_election','save_election_candidate','archive_election','toggle_election_candidate','toggle_elections_module'] as $action)
    e141(igua_admin_post_module($action)==='elections','rota POST controlada: '.$action);
$admin=igua_refactor_page_source('public/admin.php');
e141(str_contains($admin,"'elections'=>'manage_settings'") && str_contains($admin,"$".'action===\'elections\'') && str_contains($admin,'meu_elections_admin_view'), 'rota e tela restritas no painel');
$view=file_get_contents(__DIR__.'/../app/elections/admin_view.php');
e141(str_contains($view,'csrf_field()')&&str_contains($view,'h($row[\'title\'])')&&str_contains($view,"setting('elections_enabled','0')"),'formulário CSRF, escape e módulo desligado por padrão');
// Simula v10, com as tabelas exatas do formato anterior, e v11 com o novo módulo.
$u=[['id'=>1,'name'=>'Editor','role'=>'admin','active'=>1,'password_hash'=>'hash']];
$old=array_fill_keys(BACKUP_TABLES_V10,[]);$old['users']=$u;
$v10=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>10,'tables'=>$old],JSON_THROW_ON_ERROR),'test.json');
e141(array_keys($v10['tables'])===BACKUP_TABLES && $v10['tables']['elections']===[] && $v10['tables']['election_candidates']===[],'backup v10 compatível, tabelas eleitorais vazias');
$new=array_fill_keys(BACKUP_TABLES,[]);$new['users']=$u;
$new['elections']=[['id'=>5,'title'=>'Eleição 2024','election_year'=>2024,'election_scope'=>'municipal']];
$new['election_candidates']=[['id'=>7,'election_id'=>5,'ballot_name'=>'Nome de cadastro']];
$v11=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>11,'tables'=>$new],JSON_THROW_ON_ERROR),'test.json');
e141(count($v11['tables']['elections'])===1 && count($v11['tables']['election_candidates'])===1,'backup v11 mantém pleitos e candidaturas');
e141(!in_array('admin_remember_tokens',BACKUP_TABLES,true),'tokens de login fora do backup');
e141(!str_contains($view,'SELECT * FROM election_results'),'nenhum voto declarado como resultado oficial');
echo "Eleições 1.0.41: testes locais aprovados; MariaDB real pendente.\n";
