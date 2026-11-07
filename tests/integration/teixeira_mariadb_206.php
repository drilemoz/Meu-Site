<?php
declare(strict_types=1);
/** Exclusivamente banco MariaDB isolado do CI. Nunca executar sobre a hospedagem. */
$name=(string)(getenv('IGUA_TEST_DB_NAME')?:'');
if(PHP_SAPI!=='cli'||getenv('IGUA_TEST_ALLOW_WRITE')!=='YES_ISOLATED_DB'||
   !preg_match('/^igua_ci_[a-z0-9_]{3,40}$/D',$name)||
   !in_array((string)getenv('IGUA_TEST_DB_HOST'),['127.0.0.1','localhost','::1'],true)||
   !extension_loaded('pdo_mysql')){
    fwrite(STDERR,"TEIXEIRA206 bloqueado: apenas MariaDB isolado do CI.\n");exit(2);
}
require_once __DIR__.'/../../app/bootstrap.php';
require_once __DIR__.'/../../app/bus.php';
require_once __DIR__.'/../../app/bus_teixeira_2026.php';
$db=connection(['host'=>getenv('IGUA_TEST_DB_HOST'),'port'=>(int)(getenv('IGUA_TEST_DB_PORT')?:3306),
    'database'=>$name,'username'=>getenv('IGUA_TEST_DB_USER'),'password'=>getenv('IGUA_TEST_DB_PASSWORD')]);
function bus206check(bool $ok,string $reason):void{if(!$ok)throw new RuntimeException('Teixeira MariaDB 206: '.$reason);}
// Nenhum registro do portal de produção participa deste teste; limpar apenas os IDs criados aqui.
$existing=$db->query("SELECT id FROM bus_routes WHERE source_type='manual' AND source_key LIKE 'teixeira-foto-20260914-%'")->fetchAll(PDO::FETCH_COLUMN);
bus206check(count($existing)===0,'base de integração sem importação anterior');
$created=[];
try{
    $result=bus_teixeira_2026_import($db);
    bus206check($result['created']===10&&$result['skipped']===0,'10 trajetos inseridos na primeira tentativa');
    $created=$db->query("SELECT id FROM bus_routes WHERE source_type='manual' AND source_key LIKE 'teixeira-foto-20260914-%'")->fetchAll(PDO::FETCH_COLUMN);
    bus206check(count($created)===10,'10 linhas persistidas');
    $ids=array_map('intval',$created);$marks=implode(',',array_fill(0,count($ids),'?'));
    $q=$db->prepare('SELECT COUNT(*) FROM bus_departures WHERE route_id IN ('.$marks.')');$q->execute($ids);
    bus206check((int)$q->fetchColumn()===31,'31 grupos de horários persistidos');
    $q=$db->prepare('SELECT COUNT(*) FROM bus_stops WHERE route_id IN ('.$marks.')');$q->execute($ids);
    bus206check((int)$q->fetchColumn()===20,'rodoviária de origem e destino nas 10 linhas');
    $again=bus_teixeira_2026_import($db);
    bus206check($again['created']===0&&$again['skipped']===10,'segunda tentativa não duplica e informa 10 preservadas');
    $q=$db->prepare('SELECT COUNT(*) FROM bus_departures WHERE route_id IN ('.$marks.')');$q->execute($ids);
    bus206check((int)$q->fetchColumn()===31,'reimportação não altera partidas');
}finally{
    if($created){
      $ids=array_map('intval',$created);$marks=implode(',',array_fill(0,count($ids),'?'));
      foreach(['bus_departures','bus_stops'] as $table){$q=$db->prepare('DELETE FROM '.$table.' WHERE route_id IN ('.$marks.')');$q->execute($ids);}
      $q=$db->prepare('DELETE FROM bus_routes WHERE id IN ('.$marks.')');$q->execute($ids);
    }
}
echo "Teixeira 206 MariaDB: 10 linhas, 31 grupos, 20 paradas e idempotência aprovados.\n";
