<?php
declare(strict_types=1);
require __DIR__.'/../app/bootstrap.php';
require __DIR__.'/../app/backup.php';
function ok(bool $value,string $name): void { if(!$value) throw new RuntimeException('Falhou: '.$name);echo "OK: {$name}\n"; }
ok(slug('Notícias de Iguatama - MG')==='noticias-de-iguatama-mg','slug com acentos');
ok(strpos(asset_image('demo-cidade.jpg'),'/assets/img/cidade.jpg')===0,'imagem de demonstração');
ok(asset_image('../config.php')==='/assets/img/cidade.jpg','imagem com caminho inválido bloqueada');
ok(ulen('Notícias')===8,'contagem de caracteres UTF-8');
ok(readable_date(date('Y-m-d H:i:s',time()-7200))==='há 2 horas','data relativa');
$tables=[];foreach(BACKUP_TABLES_V1 as $key) $tables[$key]=[];
$tables['users']=[['id'=>1,'name'=>'Admin','email'=>'a@example.org','password_hash'=>'#hash']];
$raw=json_encode(['format'=>'iguanews-backup','version'=>1,'created_at'=>date(DATE_ATOM),'tables'=>$tables],JSON_THROW_ON_ERROR);
$a=parse_backup($raw,'example.json');
ok(count($a['tables']['users'])===1 && array_keys($a['tables'])===BACKUP_TABLES,'leitura e conversão de backup v1');
if (function_exists('gzencode')) { $a=parse_backup(gzencode($raw),'example.json.gz');ok(count($a['tables']['users'])===1,'leitura GZIP'); }
try {parse_backup('{"unknown":true}','invalid.json'); throw new RuntimeException('Aceitou formato inválido');}catch(RuntimeException $e){ok($e->getMessage()==='Formato ou versão de backup inválida.','rejeita formato inválido');}
$v2=[];foreach(BACKUP_TABLES_V2 as $table)$v2[$table]=[];
$v2['users']=[['id'=>1,'name'=>'Admin','email'=>'a@example.org','password_hash'=>'#hash','role'=>'admin','active'=>1]];
$v2['author_profiles']=[['id'=>1,'name'=>'Redação IguaNews','bio'=>'Autores locais']];
$v2['editorial_sources']=[['id'=>1,'name'=>'Agência Brasil','url'=>'https://agenciabrasil.ebc.com.br/']];
$parsed=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>2,'created_at'=>date(DATE_ATOM),'tables'=>$v2],JSON_THROW_ON_ERROR),'version2.json');
ok(count($parsed['tables']['author_profiles'])===1 && count($parsed['tables']['editorial_sources'])===1,'backup v2 com autores e fontes');
ok(count($parsed['tables']['bus_routes'])===0,'backup v2 compatível com linhas de ônibus v3');
$v3=array_replace(array_fill_keys(BACKUP_TABLES_V3,[]),$v2);
$v3['bus_routes']=[['id'=>1,'name'=>'Linha de teste','company'=>'Viação fictícia','notes'=>'','active'=>1]];
$v3['bus_stops']=[['id'=>1,'route_id'=>1,'city'=>'Iguatama','location_label'=>'Rodoviária','minutes_from_start'=>0,'stop_order'=>1],['id'=>2,'route_id'=>1,'city'=>'Arcos','location_label'=>'Centro','minutes_from_start'=>55,'stop_order'=>2]];
$v3['bus_departures']=[['id'=>1,'route_id'=>1,'start_time'=>'07:00:00','days_mask'=>127,'active'=>1]];
$three=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>3,'tables'=>$v3],JSON_THROW_ON_ERROR),'v3.json');
ok(count($three['tables']['bus_stops'])===2 && count($three['tables']['bus_departures'])===1,'backup v3 inclui linhas, paradas e horários');
$v4=array_replace(array_fill_keys(BACKUP_TABLES_V5,[]),$v3);
$v4['bus_import_queue']=[['id'=>1,'source'=>'der_mg','external_key'=>'code','payload'=>'{}','source_url'=>'https://www.consultas.der.mg.gov.br','status'=>'pending']];
$four=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>4,'tables'=>$v4],JSON_THROW_ON_ERROR),'v4.json');
ok(count($four['tables']['bus_import_queue'])===1 && count($three['tables']['bus_import_queue'])===0,'backup v4 inclui importações e lê v3');
$v5=$v4;
$v5['bus_routes'][0]['fare_amount']='18.50';
$five=parse_backup(json_encode(['format'=>'iguanews-backup','version'=>5,'tables'=>$v5],JSON_THROW_ON_ERROR),'v5.json');
ok(($five['tables']['bus_routes'][0]['fare_amount']??'')==='18.50','backup v5 inclui tarifa e aceita v4');
$bad=$v2;$bad['users'][0]['active']=0;
try {parse_backup(json_encode(['format'=>'iguanews-backup','version'=>2,'tables'=>$bad]),'bad.json');throw new RuntimeException('Aceitou backup sem administrador ativo');} catch(RuntimeException $e){ok(str_contains($e->getMessage(),'administrador ativo'),'impede restauração sem administrador ativo');}
require __DIR__.'/../app/editorial.php';
ok(role_allowed('admin','manage_bus') && role_allowed('editor','manage_bus') && !role_allowed('author','manage_bus') && role_allowed('admin','manage_users') && !role_allowed('editor','manage_users') && !role_allowed('author','publish'),'matriz de permissões');
ok(can_edit_article(['id'=>2,'role'=>'author'],['author_id'=>2,'status'=>'draft']),'redator edita rascunho próprio');
ok(!can_edit_article(['id'=>2,'role'=>'author'],['author_id'=>3,'status'=>'draft']),'redator não edita notícia alheia');
ok(!can_edit_article(['id'=>2,'role'=>'author'],['author_id'=>2,'status'=>'published']),'redator não altera matéria publicada');
ok(safe_seo_url('https://iguanews.example/')==='https://iguanews.example','normaliza domínio SEO');
foreach(['http://site.com','javascript:alert(1)','https://usuario:senha@site.com','https://site.com/path#frag'] as $invalid){try{safe_seo_url($invalid);throw new RuntimeException('Aceitou URL insegura');}catch(InvalidArgumentException) {}}
ok(true,'valida URLs canônicas HTTPS');
echo "Testes isolados aprovados. Não substituem teste de MySQL.\n";
